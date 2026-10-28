<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // One line per item is no longer enough: the same item with another production date is another line.
        Schema::table('document_lines', function (Blueprint $table) {
            $table->dropUnique(['document_id', 'item_id']);
        });
    }

    public function down(): void
    {
        Schema::table('document_lines', function (Blueprint $table) {
            $table->unique(['document_id', 'item_id']);
        });
    }
};
