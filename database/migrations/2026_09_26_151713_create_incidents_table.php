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
        Schema::create('incidents', function (Blueprint $table) {
            $table->ulid('id')->primary();
            $table->char('fingerprint', 64);
            $table->string('environment', 32);
            $table->string('application', 100);
            $table->string('source_commit', 64)->nullable();
            $table->string('route_name')->nullable();
            $table->string('method', 10);
            $table->string('path', 2048);
            $table->string('exception_class');
            $table->text('normalized_message');
            $table->unsignedInteger('occurrence_count')->default(1);
            $table->unsignedSmallInteger('threshold');
            $table->unsignedInteger('window_seconds');
            $table->timestamp('window_started_at');
            $table->timestamp('first_seen_at');
            $table->timestamp('last_seen_at');
            $table->timestamp('triggered_at')->nullable();
            $table->string('status', 32)->default('accumulating');
            $table->timestamp('resolved_at')->nullable();
            $table->timestamps();

            $table->index(['application', 'environment', 'fingerprint', 'status'], 'incidents_scope_status_index');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('incidents');
    }
};
