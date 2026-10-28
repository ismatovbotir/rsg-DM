<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('units', function (Blueprint $table) {
            $table->id();
            $table->string('uuid', 36)->unique();
            $table->string('code', 50)->nullable();
            $table->string('name', 50);
            $table->decimal('qty', 15, 3)->default(1); // base units in one of these
            $table->decimal('weight', 15, 3)->default(0);
            $table->decimal('width', 15, 3)->default(0);
            $table->decimal('length', 15, 3)->default(0);
            $table->decimal('height', 15, 3)->default(0);
            $table->boolean('is_stackable')->default(false);
            $table->unsignedSmallInteger('shape')->default(1);
            $table->timestamps();
        });

        Schema::table('items', function (Blueprint $table) {
            $table->string('uuid', 36)->nullable()->unique();
            $table->unsignedBigInteger('unit_main_id')->nullable();
            $table->unsignedBigInteger('unit_order_id')->nullable();
            $table->unsignedBigInteger('unit_report_id')->nullable();
        });

        Schema::table('item_barcodes', function (Blueprint $table) {
            $table->boolean('is_active')->default(true);
        });

        Schema::table('counterparties', function (Blueprint $table) {
            $table->string('uuid', 36)->nullable()->unique();
            $table->string('type', 20)->default('contragent'); // contragent | employee
            $table->string('full_name')->nullable();
            $table->string('phone')->nullable();
            $table->string('address', 500)->nullable();
            $table->boolean('is_vat_payer')->default(false);
        });

        Schema::table('warehouses', function (Blueprint $table) {
            $table->unsignedBigInteger('price_type_id')->nullable(); // price list of the shop, as sent by Adines
        });

        Schema::table('item_stocks', function (Blueprint $table) {
            $table->decimal('cost', 15, 4)->nullable();
            $table->date('stock_date')->nullable();
        });
    }

    public function down(): void
    {
        Schema::table('item_stocks', fn (Blueprint $t) => $t->dropColumn(['cost', 'stock_date']));
        Schema::table('warehouses', fn (Blueprint $t) => $t->dropColumn('price_type_id'));
        Schema::table('counterparties', function (Blueprint $t) {
            $t->dropUnique(['uuid']);
            $t->dropColumn(['uuid', 'type', 'full_name', 'phone', 'address', 'is_vat_payer']);
        });
        Schema::table('item_barcodes', fn (Blueprint $t) => $t->dropColumn('is_active'));
        Schema::table('items', function (Blueprint $t) {
            $t->dropUnique(['uuid']);
            $t->dropColumn(['uuid', 'unit_main_id', 'unit_order_id', 'unit_report_id']);
        });
        Schema::dropIfExists('units');
    }
};
