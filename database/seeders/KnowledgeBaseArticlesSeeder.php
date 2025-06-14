<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use App\Models\KnowledgeBaseArticle;

class KnowledgeBaseArticlesSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        KnowledgeBaseArticle::create([
            'title' => 'API Authentication Guide',
            'content' => "Our API uses OAuth 2.0 for authentication. To get started, you need to obtain a client ID and client secret from your developer dashboard. Exchange these credentials for an access token by making a POST request to `/oauth/token` with `grant_type=client_credentials`. Tokens expire after 1 hour. Refresh tokens are not supported for client credentials flow. Ensure your API key is passed in the `X-API-KEY` header for all subsequent requests. Common error codes include 401 (Unauthorized) and 403 (Forbidden).",
            'keywords' => ['API', 'authentication', 'OAuth', 'token', 'client ID', 'client secret', 'error 401', 'error 403'],
            'category' => 'API',
            'is_active' => true,
        ]);

        KnowledgeBaseArticle::create([
            'title' => 'Understanding Transaction Delays',
            'content' => "Transaction delays can occur due to various reasons, including network congestion, bank processing times, or system maintenance. Most transactions are processed within 5-10 minutes. If a transaction is delayed by more than 30 minutes, please provide the transaction ID, sender details, and recipient details for investigation. We recommend checking our status page at https://status.neem.com for any ongoing system-wide issues.",
            'keywords' => ['transaction', 'delay', 'processing', 'status', 'issue', 'slow'],
            'category' => 'Transactions',
            'is_active' => true,
        ]);

        KnowledgeBaseArticle::create([
            'title' => 'Onboarding Process for New Partners',
            'content' => "Welcome to Neem! Our onboarding process typically takes 3-5 business days. It involves account setup, KYC verification, API key generation, and initial integration support. You will receive a welcome email with your dedicated account manager's contact details. Please ensure all required documents are submitted via the partner portal to avoid delays.",
            'keywords' => ['onboarding', 'new partner', 'setup', 'KYC', 'integration'],
            'category' => 'Onboarding',
            'is_active' => true,
        ]);

        KnowledgeBaseArticle::create([
            'title' => 'Product Feature: Real-time Notifications',
            'content' => "Neem offers real-time notifications for various events (e.g., successful transactions, failed payments, account updates). You can configure webhook endpoints in your developer dashboard to receive these notifications. Ensure your endpoint is publicly accessible and configured to handle POST requests with JSON payloads.",
            'keywords' => ['notifications', 'webhooks', 'real-time', 'product', 'feature'],
            'category' => 'Product Flows',
            'is_active' => true,
        ]);

        KnowledgeBaseArticle::create([
            'title' => 'Troubleshooting Common API Errors',
            'content' => "Experiencing issues with our API?
        \n**400 Bad Request:** Check your request payload for missing or incorrect parameters. Refer to the API documentation for required fields.
        \n**404 Not Found:** Ensure the endpoint URL is correct and the resource ID (if any) exists.
        \n**500 Internal Server Error:** This is usually a server-side issue. Please report with request ID.
        \nFor detailed error codes, visit our API docs: https://docs.neem.com/api-errors.",
            'keywords' => ['API', 'errors', 'troubleshooting', '400', '404', '500', 'bug', 'issue'],
            'category' => 'API',
            'is_active' => true,
        ]);
    }
}
