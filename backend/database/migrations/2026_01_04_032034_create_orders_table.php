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
        Schema::create('orders', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->uuid('encounter_id');
            $table->foreignId('ticket_id')->constrained('tickets')->cascadeOnDelete();
            $table->enum('type', ['lab', 'radiology', 'pharmacy']);
            $table->enum('status', ['pending', 'processing', 'completed', 'cancelled'])->default('pending');
            $table->json('items'); // Array of requested tests/meds
            $table->text('instructions')->nullable();
            $table->json('results')->nullable(); // Structured results
            $table->string('report_file')->nullable(); // Future file upload path
            $table->foreignId('ordered_by')->constrained('users');
            $table->foreignId('processed_by')->nullable()->constrained('users');
            $table->timestamp('completed_at')->nullable();
            $table->timestamps();

            // Indexes
            $table->index(['encounter_id', 'type']);
            $table->index('status');
            $table->index('ticket_id');

            // Foreign key for encounter
            $table->foreign('encounter_id')->references('id')->on('encounters')->cascadeOnDelete();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('orders');
    }
};
