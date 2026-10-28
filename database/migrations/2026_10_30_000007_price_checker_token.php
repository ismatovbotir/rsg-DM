<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // The terminal token is kept encrypted so the admin can show it as a QR code again; token_hash is the lookup key.
        // The one-time activation code is gone: scanning the QR code never "uses it up", regenerating the token replaces it.
        Schema::table('price_checkers', function (Blueprint $table) {
            $table->text('token')->nullable();
        });

        Schema::table('price_checkers', function (Blueprint $table) {
            $table->dropUnique(['activation_code_hash']);
        });

        Schema::table('price_checkers', function (Blueprint $table) {
            $table->dropColumn('activation_code_hash');
        });
    }

    public function down(): void
    {
        Schema::table('price_checkers', function (Blueprint $table) {
            $table->dropColumn('token');
            $table->string('activation_code_hash', 64)->nullable()->unique();
        });
    }
};
