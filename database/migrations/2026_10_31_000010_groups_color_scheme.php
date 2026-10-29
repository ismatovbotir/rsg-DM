<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // key of a ready colour set (CheckerDesign::PALETTES) shown on the price checker for the items of this group; null = the theme's own colours
        Schema::table('groups', fn (Blueprint $table) => $table->string('color_scheme', 40)->nullable());
    }

    public function down(): void
    {
        Schema::table('groups', fn (Blueprint $table) => $table->dropColumn('color_scheme'));
    }
};
