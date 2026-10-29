<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // lan = the server prints over TCP 9100; bluetooth = the phone / PDA prints (the browser hands the label to the printer app)
        Schema::table('printers', fn (Blueprint $table) => $table->string('connection', 12)->default('lan'));
    }

    public function down(): void
    {
        Schema::table('printers', fn (Blueprint $table) => $table->dropColumn('connection'));
    }
};
