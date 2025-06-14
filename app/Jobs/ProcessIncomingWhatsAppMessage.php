<?php

namespace App\Jobs;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;

class ProcessIncomingWhatsAppMessage implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    protected $waMessage;
    protected $rawWebhookData;

    /**
     * Create a new job instance.
     */
    public function __construct(array $waMessage, array $rawWebhookData)
    {
        $this->waMessage = $waMessage;
        $this->rawWebhookData = $rawWebhookData;
    }

    /**
     * Execute the job.
     */
    public function handle(GeminiService $geminiService): void
    {
        try {
            $messageBody = $this->waMessage['text']['body'];
            $fromNumber = $this->waMessage['from'];
            $whatsappMessageId = $this->waMessage['id'];

            // Prevent duplicate processing if webhook retries
            $existingMessage = Message::where('whatsapp_message_id', $whatsappMessageId)->first();
            if ($existingMessage) {
                Log::info("Message ID {$whatsappMessageId} already processed. Skipping.");
                return;
            }

            // Save the incoming message
            $message = Message::create([
                'whatsapp_message_id' => $whatsappMessageId,
                'from_number' => $fromNumber,
                'message_body' => $messageBody,
                'message_type' => $this->waMessage['type'],
                'raw_webhook_data' => $this->rawWebhookData,
                'status' => 'pending_triage',
            ]);

            Log::info("New message saved: {$message->id}");

            // Classify and prioritize using Gemini
            $classificationResult = $geminiService->classifyAndPrioritize($messageBody);

            $category = $classificationResult['category'] ?? 'unknown';
            $priority = $classificationResult['priority'] ?? 'medium';
            $confidence = $classificationResult['confidence_score'] ?? null;
            $reasoning = $classificationResult['reasoning'] ?? null;

            // Determine the assigned team based on category and priority
            $assignedTeam = $this->determineAssignedTeam($category, $priority);

            // Save classification
            Classification::create([
                'message_id' => $message->id,
                'category' => $category,
                'confidence_score' => $confidence,
                'priority' => $priority,
                'assigned_team_id' => $assignedTeam->id ?? null,
                'status' => 'auto_classified',
                'gemini_reasoning' => $reasoning,
            ]);

            // Update message status
            $message->update(['status' => 'triaged']);

            // TODO: Notify the assigned team (e.g., Slack, Email)
            if ($assignedTeam) {
                Log::info("Message {$message->id} assigned to team: {$assignedTeam->name}");
                // Example: dispatch notification job
                // NotifyTeamJob::dispatch($assignedTeam, $message);
            }

            Log::info("Message {$message->id} successfully classified and processed.");

        } catch (Throwable $e) {
            Log::error("Error processing WhatsApp message: {$e->getMessage()}", [
                'waMessage' => $this->waMessage,
                'error_trace' => $e->getTraceAsString(),
            ]);
        }
    }

    /**
     * Determine the assigned team based on category and priority.
     */
    protected function determineAssignedTeam(string $category, string $priority): ?Team
    {
        // Simple mapping logic for demonstration.
        // In a real app, this could be more sophisticated (e.g., config file, DB table).
        $teamMapping = [
            'API issue' => 'Tech',
            'transaction delay' => 'Ops',
            'onboarding' => 'Ops',
            'product flows' => 'Product',
            'billing' => 'Finance',
            'account management' => 'Sales',
            // Default or fallback
            'unknown' => 'Ops',
        ];

        $teamName = $teamMapping[$category] ?? $teamMapping['unknown'];

        // High priority might always go to Tech lead for certain categories
        if ($priority === 'critical' || $priority === 'high') {
             if (in_array($category, ['API issue', 'product flows'])) {
                 $teamName = 'Tech Lead'; // Or specific team within Tech
             }
        }

        return Team::where('name', $teamName)->first();
    }
}
