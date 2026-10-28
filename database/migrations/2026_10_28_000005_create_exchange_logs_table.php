<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // One row per inbound 1C call (POST /api/1c/{entity}), including rejected ones.
        Schema::create('exchange_logs', function (Blueprint $table) {
            $table->id();
            $table->string('entity', 30)->index();
            $table->string('ip', 45)->nullable();
            $table->unsignedSmallInteger('http_status');
            $table->unsignedInteger('rows')->default(0);
            $table->unsignedInteger('created')->default(0);
            $table->unsignedInteger('updated')->default(0);
            $table->unsignedInteger('errors_count')->default(0);
            $table->json('details')->nullable(); // first 50 row errors
            $table->unsignedInteger('duration_ms')->default(0);
            $table->timestamp('created_at')->useCurrent()->index();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('exchange_logs');
    }
};
