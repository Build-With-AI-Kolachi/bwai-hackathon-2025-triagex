<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Inertia\Inertia;
use App\Services\GeminiService;
use App\Models\KnowledgeBaseArticle;
use App\Models\Team; // Assuming you need teams for routing insights
use Illuminate\Support\Facades\Log;
use App\Jobs\ProcessIncomingWhatsAppMessage;
use App\Models\Message;
use App\Models\Classification;
use App\Models\TeamMember;
use App\Models\User;

class GeminiTestController extends Controller
{
    protected $geminiService;

    public function __construct(GeminiService $geminiService)
    {
        $this->geminiService = $geminiService;
    }

    public function index()
    {
        $categories = ['API issue', 'transaction delay', 'product flows', 'onboarding', 'billing', 'account management', 'general inquiry', 'technical support', 'feature request', 'bug report', 'other'];
        $priorities = ['low', 'medium', 'high', 'critical'];
        $teams = Team::all()->mapWithKeys(fn($team) => [$team->id => $team->name]); // For displaying team name

        return Inertia::render('GeminiTest', [
            'categories' => $categories,
            'priorities' => $priorities,
            'teams' => $teams,
        ]);
    }

    public function processMessage(Request $request)
    {
        $request->validate([
            'message_body' => 'required|string|max:2000',
        ]);

        $messageBody = $request->input('message_body');
        $tone = $request->input('tone', 'business-friendly');

        try {
            // ProcessIncomingWhatsAppMessage::dispatch($waMessage, $request->all());

            $message = Message::create([
                'whatsapp_message_id' => 'test',
                'from_number' => 'test',
                'message_body' => $messageBody,
                'message_type' => 'text',
                'raw_webhook_data' => [],
                'status' => 'pending_triage',
            ]);

            // 1. Classification and Prioritization
            $classificationResult = $this->geminiService->classifyAndPrioritize($messageBody);

            $category = $classificationResult['category'] ?? 'unknown';
            $priority = $classificationResult['priority'] ?? 'medium';
            $confidence = $classificationResult['confidence_score'] ?? null;
            $reasoning = $classificationResult['reasoning'] ?? null;


            // Update message status
            $message->update(['status' => 'triaged']);

            // Simplified team determination for test UI
            $assignedTeam = $this->determineAssignedTeam($category, $priority);

            Classification::create([
                'message_id' => $message->id,
                'category' => $category,
                'confidence_score' => $confidence,
                'priority' => $priority,
                'assigned_team_id' => $assignedTeam->id ?? null,
                'status' => 'auto_classified',
                'gemini_reasoning' => $reasoning,
            ]);
            // 2. Knowledge Base Retrieval (simplified for testing)
            // In a real scenario, this would be more sophisticated (e.g., vector search)
            $relevantArticles = KnowledgeBaseArticle::where('is_active', true)
                                                    ->where(function ($query) use ($category, $messageBody) {
                                                        $query->where('category', $category);
                                                        // Simple keyword search in articles based on message body for demonstration
                                                        $keywords = explode(' ', strtolower($messageBody));
                                                        foreach ($keywords as $keyword) {
                                                            if (strlen($keyword) > 3) {
                                                                $query->orWhere('content', 'like', "%{$keyword}%");
                                                                $query->orWhereJsonContains('keywords', $keyword);
                                                            }
                                                        }
                                                    })
                                                    ->limit(3)
                                                    ->get()
                                                    ->toArray();
            // Fallback if initial category match fails
             if (empty($relevantArticles) && $category !== 'unknown') {
                 $relevantArticles = KnowledgeBaseArticle::where('is_active', true)
                                                        ->where(function ($query) use ($messageBody) {
                                                            $keywords = explode(' ', strtolower($messageBody));
                                                            foreach ($keywords as $keyword) {
                                                                if (strlen($keyword) > 3) {
                                                                    $query->orWhere('content', 'like', "%{$keyword}%");
                                                                    $query->orWhereJsonContains('keywords', $keyword);
                                                                }
                                                            }
                                                        })
                                                        ->limit(3)
                                                        ->get()
                                                        ->toArray();
             }


            // 3. Reply Suggestion
            $replySuggestions = $this->geminiService->suggestReplies($messageBody, $relevantArticles, $tone);

            return response()->json([
                'classification' => $classificationResult,
                'assigned_team' => $assignedTeam ? $assignedTeam->name : 'N/A',
                'relevant_articles' => $relevantArticles,
                'reply_suggestions' => $replySuggestions,
            ]);

        } catch (\Exception $e) {
            Log::error("Gemini Test UI Error: " . $e->getMessage(), ['trace' => $e->getTraceAsString()]);
            return response()->json(['error' => 'An error occurred while processing your request. Please check logs.'], 500);
        }
    }

    /**
     * Determine the assigned team based on category and priority for testing.
     * This is a duplicate of the logic in ProcessIncomingWhatsAppMessage for self-containment.
     */
    protected function determineAssignedTeam(string $category, string $priority): ?Team
    {
        $teamMapping = [
            'API issue' => 'Tech',
            'transaction delay' => 'Ops',
            'onboarding' => 'Ops',
            'product flows' => 'Product',
            'billing' => 'Finance',
            'account management' => 'Sales',
            'technical support' => 'Tech',
            'bug report' => 'Tech',
            'feature request' => 'Product',
            'general inquiry' => 'Ops',
            'other' => 'Ops',
            'unknown' => 'Ops',
        ];

        $teamName = $teamMapping[$category] ?? $teamMapping['unknown'];

        if ($priority === 'critical' || $priority === 'high') {
             if (in_array($category, ['API issue', 'bug report', 'technical support'])) {
                 $teamName = 'Tech Lead'; // Or specific team within Tech
             }
        }

        return Team::where('name', $teamName)->first();
    }
}