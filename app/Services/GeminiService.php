<?php

namespace App\Services;

use Google\Cloud\AIPlatform\V1\Gapic\PredictionServiceClient;
use Google\Cloud\AIPlatform\V1\Types\PredictRequest;
use Google\Cloud\AIPlatform\V1\Types\Value;
use Google\Cloud\AIPlatform\V1\Types\Prediction\Prediction;
use Google\Cloud\AIPlatform\V1\Types\Endpoint;
use Google\Cloud\AIPlatform\V1\Types\PredictResponse;
use Google\Client; // Required for authentication if not using API key directly via header
use GuzzleHttp\Client as GuzzleClient;
use Illuminate\Support\Facades\Log;

class GeminiService
{
    protected $geminiClient;
    protected $modelName = 'gemini-2.0-flash'; // or other specific Gemini model
    protected $apiKey;

    public function __construct()
    {
        $this->apiKey = env('GEMINI_API_KEY');
        if (empty($this->apiKey)) {
            throw new \Exception("GEMINI_API_KEY is not set in .env file.");
        }

        // Using GuzzleHttp Client directly for simpler API Key based access
        // For more complex Google Cloud authentication (e.g., service accounts),
        // you would use the google/cloud-ai-generativelanguage library more extensively
        // and handle authentication with Google\Client.
        $this->geminiClient = new GuzzleClient([
            'base_uri' => 'https://generativelanguage.googleapis.com/v1beta/',
            'headers' => [
                'Content-Type' => 'application/json',
                'x-goog-api-key' => $this->apiKey,
            ],
            'verify' => false, // For local development, disable SSL verification if needed. Re-enable in production.
        ]);
    }

    /**
     * Send a prompt to the Gemini API.
     */
    protected function generateContent(string $prompt): string
    {
        try {
            $response = $this->geminiClient->post("models/{$this->modelName}:generateContent", [
                'json' => [
                    'contents' => [
                        [
                            'parts' => [
                                ['text' => $prompt]
                            ]
                        ]
                    ]
                ]
            ]);

            $body = json_decode($response->getBody()->getContents(), true);

            if (isset($body['candidates'][0]['content']['parts'][0]['text'])) {
                return $body['candidates'][0]['content']['parts'][0]['text'];
            }

            Log::error('Gemini API response missing content:', $body);
            return 'Error: Could not generate content.';

        } catch (\GuzzleHttp\Exception\RequestException $e) {
            Log::error('Gemini API Request Error: ' . $e->getMessage(), [
                'request' => $e->getRequest() ? $e->getRequest()->getUri() : 'N/A',
                'response' => $e->hasResponse() ? $e->getResponse()->getBody()->getContents() : 'N/A',
            ]);
            return 'Error: Gemini API request failed.';
        } catch (\Exception $e) {
            Log::error('Gemini Service Error: ' . $e->getMessage());
            return 'Error: An unexpected error occurred.';
        }
    }

    /**
     * Classify and prioritize an incoming message using Gemini.
     */
    public function classifyAndPrioritize(string $messageBody): array
    {
        $categories = "API issue, transaction delay, product flows, onboarding, billing, account management, general inquiry, technical support, feature request, bug report, other";
        $priorities = "low, medium, high, critical";

        $prompt = "Classify the following WhatsApp message into one of these categories: {$categories}. " .
                  "Also, assign a priority: {$priorities}. " .
                  "Provide a confidence score (0.0-1.0) for the classification. " .
                  "Explain your reasoning concisely. " .
                  "Format your response as a JSON object with 'category', 'priority', 'confidence_score', and 'reasoning' keys. " .
                  "If you are unsure, default to 'general inquiry' and 'medium' priority.\n\n" .
                  "Message: \"{$messageBody}\"";

        $geminiResponse = $this->generateContent($prompt);

        // Attempt to parse the JSON response
        $parsedResponse = json_decode($geminiResponse, true);

        if (json_last_error() === JSON_ERROR_NONE && is_array($parsedResponse)) {
            // Validate expected keys
            $category = $parsedResponse['category'] ?? 'unknown';
            $priority = $parsedResponse['priority'] ?? 'medium';
            $confidence = $parsedResponse['confidence_score'] ?? null;
            $reasoning = $parsedResponse['reasoning'] ?? 'No specific reasoning provided.';

            return [
                'category' => $category,
                'priority' => $priority,
                'confidence_score' => is_numeric($confidence) ? (float)$confidence : null,
                'reasoning' => $reasoning,
            ];
        }

        Log::warning('Gemini did not return a valid JSON for classification, attempting fallback parsing.', ['response' => $geminiResponse]);

        // Fallback parsing if JSON is malformed (less reliable)
        preg_match('/"category":\s*"(.*?)"/', $geminiResponse, $categoryMatch);
        preg_match('/"priority":\s*"(.*?)"/', $geminiResponse, $priorityMatch);
        preg_match('/"confidence_score":\s*([\d.]+)/', $geminiResponse, $confidenceMatch);
        preg_match('/"reasoning":\s*"(.*?)"/', $geminiResponse, $reasoningMatch);


        return [
            'category' => $categoryMatch[1] ?? 'unknown',
            'priority' => $priorityMatch[1] ?? 'medium',
            'confidence_score' => isset($confidenceMatch[1]) ? (float)$confidenceMatch[1] : null,
            'reasoning' => $reasoningMatch[1] ?? 'Could not parse reasoning from Gemini response.',
        ];
    }

    /**
     * Suggest replies based on message and knowledge base.
     */
    public function suggestReplies(string $messageBody, array $knowledgeBaseArticles, string $tone = 'business-friendly'): array
    {
        $context = "";
        if (!empty($knowledgeBaseArticles)) {
            $context .= "Based on the following internal knowledge base articles:\n\n";
            foreach ($knowledgeBaseArticles as $article) {
                $context .= "Title: {$article['title']}\nContent: {$article['content']}\n\n";
            }
            $context .= "End of knowledge base articles.\n\n";
        }

        $toneInstruction = "";
        if ($tone === 'technical') {
            $toneInstruction = "Generate a draft reply in a technical tone, including any relevant API details, error codes, or step-by-step troubleshooting where applicable.";
        } else { // business-friendly or default
            $toneInstruction = "Generate a draft reply in a clear, concise, and business-friendly tone, focusing on the solution and next steps from a client perspective.";
        }

        $prompt = "You are a customer support AI assistant for Neem. " .
                  "The client message is: \"{$messageBody}\". " .
                  $context .
                  $toneInstruction .
                  " Ensure the reply directly addresses the client's query. Provide a greeting and a closing. " .
                  "Also, suggest a few keywords (comma-separated) that could be used to tag this conversation for future reference or documentation improvement. " .
                  "Format your response as a JSON object with 'reply_draft' and 'suggested_tags' keys.";

        $geminiResponse = $this->generateContent($prompt);

        $parsedResponse = json_decode($geminiResponse, true);

        if (json_last_error() === JSON_ERROR_NONE && isset($parsedResponse['reply_draft'], $parsedResponse['suggested_tags'])) {
            return [
                'reply_draft' => $parsedResponse['reply_draft'],
                'suggested_tags' => explode(',', $parsedResponse['suggested_tags']), // Assuming comma-separated string
            ];
        }

        Log::warning('Gemini did not return a valid JSON for reply suggestion, attempting fallback parsing.', ['response' => $geminiResponse]);

        // Fallback for reply suggestion
        preg_match('/"reply_draft":\s*"(.*?)"/', $geminiResponse, $replyMatch);
        preg_match('/"suggested_tags":\s*"(.*?)"/', $geminiResponse, $tagsMatch);

        return [
            'reply_draft' => $replyMatch[1] ?? 'Could not generate a reply. Please draft manually.',
            'suggested_tags' => isset($tagsMatch[1]) ? explode(',', $tagsMatch[1]) : [],
        ];
    }
}