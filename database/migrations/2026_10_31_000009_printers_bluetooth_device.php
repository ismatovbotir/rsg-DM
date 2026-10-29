<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('printers', function (Blueprint $table) {
            $table->string('bt_name')->nullable();       // paired Bluetooth printer name
            $table->string('bt_address')->nullable();    // its MAC (Android wrapper) or device id (Web Bluetooth)
            $table->unsignedBigInteger('owner_device_id')->nullable()->index(); // set when a PDA saved its own printer on the Printer page
        });
    }

    public function down(): void
    {
        Schema::table('printers', fn (Blueprint $table) => $table->dropColumn(['bt_name', 'bt_address', 'owner_device_id']));
    }
};
