<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('price_types', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('code', 50)->nullable()->unique(); // 1C
            $table->boolean('is_active')->default(true);
            $table->timestamps();
        });

        // Item price per price type. Items without a row fall back to items.price.
        Schema::create('item_prices', function (Blueprint $table) {
            $table->id();
            $table->foreignId('item_id')->constrained()->cascadeOnDelete();
            $table->foreignId('price_type_id')->constrained()->cascadeOnDelete();
            $table->decimal('price', 14, 2);
            $table->unique(['item_id', 'price_type_id']);
        });

        Schema::table('documents', function (Blueprint $table) {
            $table->foreignId('price_type_id')->nullable()->constrained('price_types');
        });

        // Price check line: the operator's verdict for the scanned item.
        Schema::table('document_lines', function (Blueprint $table) {
            $table->string('check_action', 20)->nullable(); // confirmed | print
        });

        Schema::table('price_checks', function (Blueprint $table) {
            $table->foreignId('document_id')->nullable()->constrained()->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::table('price_checks', fn (Blueprint $t) => $t->dropConstrainedForeignId('document_id'));
        Schema::table('document_lines', fn (Blueprint $t) => $t->dropColumn('check_action'));
        Schema::table('documents', fn (Blueprint $t) => $t->dropConstrainedForeignId('price_type_id'));
        Schema::dropIfExists('item_prices');
        Schema::dropIfExists('price_types');
    }
};
