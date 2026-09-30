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
        Schema::create('incident_occurrences', function (Blueprint $table) {
            $table->ulid('id')->primary();
            $table->foreignUlid('incident_id')->constrained()->cascadeOnDelete();
            $table->string('request_id', 64);
            $table->longText('stack_trace');
            $table->json('sanitized_context');
            $table->timestamp('occurred_at');
            $table->timestamps();

            $table->index(['incident_id', 'occurred_at']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('incident_occurrences');
    }
};
