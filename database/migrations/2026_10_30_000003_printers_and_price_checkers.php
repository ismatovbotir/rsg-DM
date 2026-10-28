<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // Network (ethernet / LAN) label printers, raw TCP on port 9100.
        Schema::create('printers', function (Blueprint $table) {
            $table->id();
            $table->string('name', 100);
            $table->string('host', 100); // IP or LAN host name
            $table->unsignedSmallInteger('port')->default(9100);
            $table->unsignedSmallInteger('label_width_mm')->default(58);
            $table->unsignedSmallInteger('label_height_mm')->default(40);
            $table->boolean('is_price_tag')->default(false); // the one printer that prints price tags
            $table->boolean('is_active')->default(true);
            $table->timestamps();
        });

        Schema::table('devices', function (Blueprint $table) {
            $table->unsignedBigInteger('printer_id')->nullable(); // printer assigned to this PDA
        });

        // Customer-facing price checker terminals: /pricechecker, activated by scanning a QR code made by the server.
        Schema::create('price_checkers', function (Blueprint $table) {
            $table->id();
            $table->string('name', 100);
            $table->unsignedBigInteger('price_type_id')->nullable(); // price list it shows; empty = base price
            $table->string('activation_code_hash', 64)->nullable()->unique(); // one-time, cleared when used
            $table->string('token_hash', 64)->nullable()->unique(); // set once activated
            $table->timestamp('activated_at')->nullable();
            $table->timestamp('last_seen_at')->nullable();
            $table->boolean('is_active')->default(true);
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('price_checkers');
        Schema::table('devices', fn (Blueprint $table) => $table->dropColumn('printer_id'));
        Schema::dropIfExists('printers');
    }
};
