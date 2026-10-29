<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // permission keys of the role that are switched off for this one user (a user can only lose access, never gain it beyond the role)
        Schema::table('users', fn (Blueprint $table) => $table->json('denied_permissions')->nullable());
    }

    public function down(): void
    {
        Schema::table('users', fn (Blueprint $table) => $table->dropColumn('denied_permissions'));
    }
};
