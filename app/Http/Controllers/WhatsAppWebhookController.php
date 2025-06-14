<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;

class WhatsAppWebhookController extends Controller
{
    protected $geminiService;

    public function __construct(GeminiService $geminiService)
    {
        $this->geminiService = $geminiService;
    }

    // For WhatsApp webhook verification
    public function verify(Request $request)
    {
        $verifyToken = env('WHATSAPP_VERIFY_TOKEN');
        $mode = $request->query('hub_mode');
        $token = $request->query('hub_verify_token');
        $challenge = $request->query('hub_challenge');

        if ($mode && $token) {
            if ($mode === 'subscribe' && $token === $verifyToken) {
                Log::info('WhatsApp Webhook verified!');
                return response($challenge, 200);
            } else {
                return response('Forbidden', 403);
            }
        }
        return response('Bad Request', 400);
    }

    // For handling incoming messages
    public function handle(Request $request)
    {
        Log::info('Incoming WhatsApp Webhook:', $request->all());

        // Process the webhook payload
        $entries = $request->input('entry');

        if (empty($entries)) {
            return response()->json(['message' => 'No entries found'], 200);
        }

        foreach ($entries as $entry) {
            foreach ($entry['changes'] as $change) {
                if ($change['field'] === 'messages') {
                    foreach ($change['value']['messages'] as $waMessage) {
                        // Ensure we only process text messages for now or handle other types
                        if ($waMessage['type'] === 'text') {
                            // Dispatch a job to process the message asynchronously
                            ProcessIncomingWhatsAppMessage::dispatch($waMessage, $request->all());
                        }
                        // TODO: Handle other message types (image, document, etc.)
                    }
                }
            }
        }

        return response()->json(['message' => 'Webhook received'], 200);
    }
}
