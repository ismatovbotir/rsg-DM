<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('items', function (Blueprint $table) {
            $table->id();
            $table->string('code')->unique();
            $table->string('barcode')->nullable()->index();
            $table->string('name');
            $table->string('unit', 20)->default('dona');
            $table->decimal('price', 14, 2)->default(0);
            $table->string('onec_ref')->nullable()->index(); // 1C reference (GUID)
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('items');
    }
};
