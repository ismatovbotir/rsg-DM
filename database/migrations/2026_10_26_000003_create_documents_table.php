<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('documents', function (Blueprint $table) {
            $table->id();
            $table->string('number')->nullable()->unique();
            $table->string('type')->index();                     // menu key: receiving, stocktaking, ...
            $table->string('status')->default('draft')->index(); // draft | completed
            $table->string('onec_status')->default('pending')->index(); // pending | synced | error
            $table->foreignId('user_id')->constrained();
            $table->string('comment')->nullable();
            $table->timestamp('completed_at')->nullable();
            $table->timestamp('synced_at')->nullable();
            $table->text('onec_error')->nullable();
            $table->timestamps();
        });

        Schema::create('document_lines', function (Blueprint $table) {
            $table->id();
            $table->foreignId('document_id')->constrained()->cascadeOnDelete();
            $table->foreignId('item_id')->constrained();
            $table->decimal('qty', 14, 3)->default(0);
            $table->timestamps();

            $table->unique(['document_id', 'item_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('document_lines');
        Schema::dropIfExists('documents');
    }
};
