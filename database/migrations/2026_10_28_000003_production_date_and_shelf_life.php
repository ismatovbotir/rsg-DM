<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // 1C sends the shelf life in days; expiry = production date + shelf life.
        Schema::table('items', function (Blueprint $table) {
            $table->unsignedInteger('shelf_life_days')->nullable();
        });

        // A line is unique per (document, item, production date); the date may be empty, so uniqueness is enforced in code.
        Schema::table('document_lines', function (Blueprint $table) {
            $table->date('production_date')->nullable();
            $table->index(['document_id', 'item_id', 'production_date'], 'lines_item_date');
        });
    }

    public function down(): void
    {
        Schema::table('document_lines', function (Blueprint $table) {
            $table->dropIndex('lines_item_date');
            $table->dropColumn('production_date');
        });
        Schema::table('items', fn (Blueprint $table) => $table->dropColumn('shelf_life_days'));
    }
};
