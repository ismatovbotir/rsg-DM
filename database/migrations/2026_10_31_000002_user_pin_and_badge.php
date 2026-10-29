<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // PDA sign-in without typing a password: pick yourself and enter a 4-digit PIN, or scan the personal badge QR code.
        Schema::table('users', function (Blueprint $table) {
            $table->string('pin')->nullable();                 // hashed 4-digit PIN
            $table->text('badge_token')->nullable();           // encrypted, so the badge can be printed again
            $table->string('badge_hash', 64)->nullable()->unique(); // sha256 of the token: lookup when the badge is scanned
            $table->unsignedTinyInteger('pin_failed')->default(0);
            $table->timestamp('pin_locked_until')->nullable();
        });
    }

    public function down(): void
    {
        Schema::table('users', fn (Blueprint $table) => $table->dropColumn(['pin', 'badge_token', 'badge_hash', 'pin_failed', 'pin_locked_until']));
    }
};
