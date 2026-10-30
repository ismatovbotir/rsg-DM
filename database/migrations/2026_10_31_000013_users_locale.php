<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // interface language of the user: uz (default) | ru
        Schema::table('users', fn (Blueprint $table) => $table->string('locale', 5)->default('uz'));
    }

    public function down(): void
    {
        Schema::table('users', fn (Blueprint $table) => $table->dropColumn('locale'));
    }
};
