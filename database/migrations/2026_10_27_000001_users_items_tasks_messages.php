<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->boolean('is_active')->default(true);
            $table->timestamp('last_login_at')->nullable();
            $table->json('menu_access')->nullable(); // allowed menu keys; null = every menu item
        });

        // A physical PDA. Identified by a secret cookie (only its hash is stored); configuration only, never auth.
        Schema::create('devices', function (Blueprint $table) {
            $table->id();
            $table->uuid('uuid')->unique();
            $table->string('token_hash', 64)->unique();
            $table->string('name')->nullable();
            $table->string('keyboard_mode')->default('auto'); // auto | on | off (off = hidden, called with the keyboard button)
            $table->timestamp('last_seen_at')->nullable();
            $table->foreignId('last_user_id')->nullable()->constrained('users')->nullOnDelete();
            $table->string('user_agent')->nullable();
            $table->timestamps();
        });

        Schema::table('items', function (Blueprint $table) {
            $table->boolean('is_weighed')->default(false);
            $table->decimal('vat_rate', 5, 2)->default(0);
            $table->boolean('is_active')->default(true);
        });

        // Extra barcodes (e.g. a case barcode): scanning one books qty_in_pack base units.
        Schema::create('item_barcodes', function (Blueprint $table) {
            $table->id();
            $table->foreignId('item_id')->constrained()->cascadeOnDelete();
            $table->string('barcode')->unique();
            $table->decimal('qty_in_pack', 14, 3)->default(1);
            $table->timestamps();
        });

        Schema::table('document_lines', function (Blueprint $table) {
            $table->string('batch_no')->nullable();
            $table->date('expiry_date')->nullable();
        });

        Schema::create('tasks', function (Blueprint $table) {
            $table->id();
            $table->string('title');
            $table->text('description')->nullable();
            $table->foreignId('assignee_id')->constrained('users');
            $table->foreignId('created_by')->constrained('users');
            $table->string('priority')->default('normal'); // low | normal | high
            $table->timestamp('due_at')->nullable();
            $table->string('status')->default('new')->index(); // new | accepted | in_progress | done | declined | cancelled
            $table->string('doc_type')->nullable();            // operation to start from the task
            $table->timestamp('accepted_at')->nullable();
            $table->timestamp('completed_at')->nullable();
            $table->string('cancel_reason')->nullable();       // reason for declined / cancelled
            $table->timestamps();
        });

        Schema::create('task_documents', function (Blueprint $table) {
            $table->foreignId('task_id')->constrained()->cascadeOnDelete();
            $table->foreignId('document_id')->constrained()->cascadeOnDelete();
            $table->primary(['task_id', 'document_id']);
        });

        Schema::create('messages', function (Blueprint $table) {
            $table->id();
            $table->foreignId('sender_id')->constrained('users');
            $table->string('title');
            $table->text('body');
            $table->string('priority')->default('normal'); // normal | urgent
            $table->timestamps();
        });

        Schema::create('message_recipients', function (Blueprint $table) {
            $table->id();
            $table->foreignId('message_id')->constrained()->cascadeOnDelete();
            $table->foreignId('user_id')->constrained();
            $table->timestamp('read_at')->nullable();
            $table->timestamps();

            $table->unique(['message_id', 'user_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('message_recipients');
        Schema::dropIfExists('messages');
        Schema::dropIfExists('task_documents');
        Schema::dropIfExists('tasks');
        Schema::table('document_lines', fn (Blueprint $t) => $t->dropColumn(['batch_no', 'expiry_date']));
        Schema::dropIfExists('item_barcodes');
        Schema::table('items', fn (Blueprint $t) => $t->dropColumn(['is_weighed', 'vat_rate', 'is_active']));
        Schema::dropIfExists('devices');
        Schema::table('users', fn (Blueprint $t) => $t->dropColumn(['is_active', 'last_login_at', 'menu_access']));
    }
};
