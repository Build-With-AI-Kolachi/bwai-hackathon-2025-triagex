<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Services\GeminiService;
use App\Models\Message;
use App\Models\KnowledgeBaseArticle;
use App\Models\Team;
use App\Models\Classification;
use Illuminate\Support\Facades\Log;
use Inertia\Inertia;   
use Illuminate\Support\Facades\DB;

class DashboardController extends Controller
{
    protected $geminiService;

    public function __construct(GeminiService $geminiService)
    {
        $this->geminiService = $geminiService;
    }

    public function index(Request $request)
    {
        $messages = Message::with('classification.assignedTeam')
                            ->orderBy('created_at', 'desc')
                            ->paginate(10); // Paginate for better performance

        // Calculate statistics
        $totalMessages = Message::count();
        $messagesByStatus = Message::select('status', DB::raw('count(*) as count'))
                                    ->groupBy('status')
                                    ->pluck('count', 'status')
                                    ->toArray();

        $messagesByCategory = Classification::select('category', DB::raw('count(*) as count'))
                                            ->groupBy('category')
                                            ->pluck('count', 'category')
                                            ->toArray();

        $messagesByPriority = Classification::select('priority', DB::raw('count(*) as count'))
                                            ->groupBy('priority')
                                            ->pluck('count', 'priority')
                                            ->toArray();

        // Prepare data for Chart.js (Categories)
        $chartCategoriesLabels = array_keys($messagesByCategory);
        $chartCategoriesData = array_values($messagesByCategory);

        // Prepare data for Chart.js (Priorities)
        $chartPrioritiesLabels = array_keys($messagesByPriority);
        $chartPrioritiesData = array_values($messagesByPriority);


        return Inertia::render('Dashboard', [
            'messages' => $messages,
            'stats' => [
                'totalMessages' => $totalMessages,
                'messagesByStatus' => $messagesByStatus,
                'messagesByCategory' => $messagesByCategory,
                'messagesByPriority' => $messagesByPriority,
            ],
            'chartData' => [
                'categories' => [
                    'labels' => $chartCategoriesLabels,
                    'data' => $chartCategoriesData,
                ],
                'priorities' => [
                    'labels' => $chartPrioritiesLabels,
                    'data' => $chartPrioritiesData,
                ],
            ],
        ]);
    }

    public function showMessage(Message $message)
    {
        $message->load('classification.assignedTeam');
        $teams = Team::all(); // For manual assignment
        $categories = ['API issue', 'transaction delay', 'product flows', 'onboarding', 'billing', 'account management', 'general inquiry', 'technical support', 'feature request', 'bug report', 'other'];
        $priorities = ['low', 'medium', 'high', 'critical'];

        return Inertia::render('MessageDetails', [
            'message' => $message,
            'teams' => $teams,
            'categories' => $categories,
            'priorities' => $priorities,
        ]);
    }

    public function suggestReply(Request $request, Message $message)
    {
        $request->validate([
            'tone' => 'required|in:technical,business-friendly',
        ]);

        // Retrieve relevant knowledge base articles
        // This is a simplified example. In a real scenario, you'd use a vector search
        // or more sophisticated keyword matching based on message classification/keywords.
        $relevantArticles = KnowledgeBaseArticle::where('is_active', true)
                                                ->when($message->classification, function ($query, $classification) {
                                                    $query->orWhere('category', $classification->category)
                                                          ->orWhereJsonContains('keywords', strtolower($classification->category));
                                                    // Further refine by keywords from the message body (simple example)
                                                    $messageKeywords = explode(' ', strtolower($classification->message->message_body));
                                                    foreach ($messageKeywords as $keyword) {
                                                        if (strlen($keyword) > 3) { // Avoid very short common words
                                                            $query->orWhereJsonContains('keywords', $keyword);
                                                        }
                                                    }
                                                })
                                                ->limit(3) // Limit to top 3 relevant articles
                                                ->get()
                                                ->toArray();

        try {
            $suggestions = $this->geminiService->suggestReplies(
                $message->message_body,
                $relevantArticles,
                $request->input('tone')
            );
            return response()->json($suggestions);
        } catch (\Exception $e) {
            Log::error("Error suggesting reply for message {$message->id}: " . $e->getMessage());
            return response()->json(['error' => 'Failed to suggest reply.'], 500);
        }
    }

    public function updateMessageStatus(Request $request, Message $message)
    {
        $request->validate([
            'status' => 'required|in:pending_triage,triaged,replied,closed',
        ]);

        $message->update(['status' => $request->status]);

        return response()->json(['message' => 'Message status updated.']);
    }

    public function reclassifyMessage(Request $request, Message $message)
    {
        $request->validate([
            'category' => 'required|string',
            'priority' => 'required|string',
            'assigned_team_id' => 'nullable|exists:teams,id',
            'reasoning' => 'nullable|string',
        ]);

        if ($message->classification) {
            $message->classification->update([
                'category' => $request->category,
                'priority' => $request->priority,
                'assigned_team_id' => $request->assigned_team_id,
                'status' => 'human_reviewed', // Mark as human reviewed
                'gemini_reasoning' => $request->reasoning ?? $message->classification->gemini_reasoning,
            ]);
        } else {
            // Create a new classification if none existed
            Classification::create([
                'message_id' => $message->id,
                'category' => $request->category,
                'priority' => $request->priority,
                'assigned_team_id' => $request->assigned_team_id,
                'status' => 'human_reviewed',
                'gemini_reasoning' => $request->reasoning,
            ]);
        }

        return response()->json(['message' => 'Message reclassified successfully.']);
    }

    // You might add an endpoint here to send a reply via WhatsApp API (outbound)
    // This would involve another call to WhatsApp Business API.
    public function sendReply(Request $request, Message $message)
    {
        $request->validate([
            'reply_content' => 'required|string',
        ]);

        // TODO: Implement actual sending via WhatsApp Business API
        // This usually involves sending a POST request to a WhatsApp API endpoint
        // with the recipient number and message content.
        Log::info("Attempting to send reply to {$message->from_number}: {$request->reply_content}");

        // Example (conceptual, requires WhatsApp API client setup):
        // $whatsappApiClient->sendMessage($message->from_number, $request->reply_content);

        $message->update(['status' => 'replied']); // Update message status

        return response()->json(['message' => 'Reply sent successfully (simulated).']);
    }
}
