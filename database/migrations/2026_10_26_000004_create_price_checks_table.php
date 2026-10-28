<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('price_checks', function (Blueprint $table) {
            $table->id();
            $table->foreignId('item_id')->constrained()->cascadeOnDelete();
            $table->foreignId('user_id')->constrained();
            $table->decimal('price', 14, 2);           // system price at the moment of the check
            $table->string('action')->index();         // confirmed | print
            $table->timestamp('printed_at')->nullable(); // set when the tag was printed (action = print)
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('price_checks');
    }
};
