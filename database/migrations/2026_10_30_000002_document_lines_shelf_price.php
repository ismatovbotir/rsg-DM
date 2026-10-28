<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('document_lines', function (Blueprint $table) {
            $table->decimal('shelf_price', 15, 2)->nullable(); // price check: the price seen on the shelf tag
        });
    }

    public function down(): void
    {
        Schema::table('document_lines', fn (Blueprint $table) => $table->dropColumn('shelf_price'));
    }
};
