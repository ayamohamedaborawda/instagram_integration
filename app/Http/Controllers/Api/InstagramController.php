<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class InstagramController extends Controller
{
    private $verifyToken;
    private $accessToken;

    public function __construct()
    {
        $this->verifyToken = env('INSTAGRAM_VERIFY_TOKEN');
        $this->accessToken = env('INSTAGRAM_PAGE_ACCESS_TOKEN');
    }

    /**
     * Verify Webhook (GET request)
     */
    public function verify(Request $request)
    {
        Log::info('--- VERIFY WEBHOOK ---');
        Log::info($request->all());

        $mode = $request->input('hub_mode');
        $token = $request->input('hub_verify_token');
        $challenge = $request->input('hub_challenge');

        if ($mode === 'subscribe' && $token === $this->verifyToken) {
            Log::info('Webhook Verified Successfully!');
            return response($challenge, 200);
        }

        Log::warning('Webhook Verify Failed.', [
            'mode' => $mode,
            'token_received' => $token,
            'token_expected' => $this->verifyToken,
        ]);

        return response('Forbidden', 403);
    }


    /**
     * Handle incoming Webhook events (POST request)
     */
public function handle(Request $request)
{
    $data = $request->all();
    Log::info('Instagram Webhook Received:', $data);

    if (isset($data['entry'])) {
        foreach ($data['entry'] as $entry) {
            if (isset($entry['messaging'])) {
                foreach ($entry['messaging'] as $event) {
                    $senderId = $event['sender']['id'] ?? null;
                    $messageText = $event['message']['text'] ?? null;

                    if ($senderId && $messageText) {
                        Log::info("Message from {$senderId}: {$messageText}");

                        $this->sendReply($senderId, "Thanks for your message: {$messageText}");
                    }
                }
            }

            if (isset($entry['changes'])) {
                foreach ($entry['changes'] as $change) {
                    $field = $change['field'] ?? 'unknown';
                    $value = $change['value'] ?? [];

                    Log::info("Change field: {$field}", $value);

                    if (isset($value['text'])) {
                        $user = $value['from']['username'] ?? 'unknown';
                        $text = $value['text'];

                        Log::info("New message/comment from {$user}: {$text}");
                    }
                }
            }
        }
    }

    return response('EVENT_RECEIVED', 200);
}

/**
 * Send automatic reply to Instagram user
 */
private function sendReply($recipientId, $message)
{
    $accessToken = $this->accessToken;
    Log::info($accessToken);
    // $url = "https://graph.facebook.com/v17.0/{$recipientId}/messages?access_token={$accessToken}";
   $url = "https://graph.facebook.com/v24.0/me/messages?access_token={$accessToken}";

    $payload = [
        'recipient' => ['id' => $recipientId],
        'message' => ['text' => $message],
    ];

    try {
        $response = Http::post($url, $payload);
        Log::info('Reply Response:', $response->json());
    } catch (\Exception $e) {
        Log::error('Failed to send reply:', ['error' => $e->getMessage()]);
    }
}


    public function callback(Request $request)
    {
        $code = $request->query('code');
        $error = $request->query('error');

        if ($error) {
            Log::error('Instagram login error', ['error' => $error]);
            return response()->json(['error' => $error]);
        }

        Log::info('Instagram login code: ' . $code);
        return response()->json(['message' => 'Login successful', 'code' => $code]);
    }
}
