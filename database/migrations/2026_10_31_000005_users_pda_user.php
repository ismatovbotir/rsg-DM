<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // "PDA user": signing in always leads to the PDA view, even when the role also opens the admin panel.
        Schema::table('users', fn (Blueprint $table) => $table->boolean('pda_user')->default(false));
    }

    public function down(): void
    {
        Schema::table('users', fn (Blueprint $table) => $table->dropColumn('pda_user'));
    }
};
