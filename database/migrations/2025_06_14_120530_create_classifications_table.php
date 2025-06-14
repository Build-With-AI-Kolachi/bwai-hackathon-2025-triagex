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
        Schema::create('classifications', function (Blueprint $table) {
            $table->id();
            $table->foreignId('message_id')->constrained()->onDelete('cascade');
            $table->string('category'); // e.g., API issue, transaction delay, product flows, onboarding
            $table->float('confidence_score')->nullable(); // Confidence from Gemini
            $table->string('priority')->nullable(); // e.g., low, medium, high, critical
            $table->foreignId('assigned_team_id')->nullable()->constrained('teams');
            $table->string('status')->default('auto_classified'); // auto_classified, human_reviewed, reclassified
            $table->text('gemini_reasoning')->nullable(); // Optional: Store why Gemini classified it this way
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('classifications');
    }
};
