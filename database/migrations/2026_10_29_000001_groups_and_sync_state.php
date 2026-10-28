<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // Groups form a tree: a first-level group has no parent.
        // The row id IS the 1C id (inserted as sent), so there is no separate 1C id column.
        Schema::create('groups', function (Blueprint $table) {
            $table->id();
            $table->foreignId('parent_id')->nullable()->constrained('groups')->nullOnDelete();
            $table->string('name')->index();
            $table->boolean('is_active')->default(true);
            $table->timestamps();
        });

        if (! Schema::hasColumn('items', 'group_id')) {
            Schema::table('items', function (Blueprint $table) {
                $table->foreignId('group_id')->nullable()->constrained('groups')->nullOnDelete();
            });
        }

        // Incremental pull cursor per entity: the largest 1C id already received.
        Schema::create('sync_states', function (Blueprint $table) {
            $table->string('entity', 30)->primary();
            $table->unsignedBigInteger('last_id')->default(0);
            $table->timestamp('synced_at')->nullable();
            $table->text('last_error')->nullable();
        });

        Schema::table('exchange_logs', function (Blueprint $table) {
            $table->string('direction', 4)->default('push'); // push = 1C called us, pull = we called 1C
        });
    }

    public function down(): void
    {
        Schema::table('exchange_logs', fn (Blueprint $t) => $t->dropColumn('direction'));
        Schema::dropIfExists('sync_states');
        Schema::table('items', function (Blueprint $table) {
            $table->dropConstrainedForeignId('group_id');
        });
        Schema::dropIfExists('groups');
    }
};
