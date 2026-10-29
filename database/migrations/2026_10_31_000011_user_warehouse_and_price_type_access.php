<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // warehouses / price lists a user may see and work with (besides their own shop); no rows = no extra access
        Schema::create('user_warehouse', function (Blueprint $table) {
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->foreignId('warehouse_id')->constrained()->cascadeOnDelete();
            $table->primary(['user_id', 'warehouse_id']);
        });

        Schema::create('user_price_type', function (Blueprint $table) {
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->foreignId('price_type_id')->constrained()->cascadeOnDelete();
            $table->primary(['user_id', 'price_type_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('user_price_type');
        Schema::dropIfExists('user_warehouse');
    }
};
