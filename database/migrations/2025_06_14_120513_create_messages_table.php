<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('messages', function (Blueprint $table) {
            $table->id();
            $table->string('whatsapp_message_id')->unique(); // Unique ID from WhatsApp
            $table->string('from_number'); // Sender's WhatsApp number
            $table->text('message_body');
            $table->string('message_type')->default('text'); // e.g., text, image, document
            $table->string('status')->default('pending_triage'); // pending_triage, triaged, replied, closed
            $table->json('raw_webhook_data')->nullable(); // Store original webhook data for debugging
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('messages');
    }
};
