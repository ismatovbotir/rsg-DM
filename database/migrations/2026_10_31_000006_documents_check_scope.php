<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // What a price check covers: the whole shop, one product group (with its subgroups) or free scanning.
        Schema::table('documents', function (Blueprint $table) {
            $table->string('scope', 10)->nullable();
            $table->unsignedBigInteger('group_id')->nullable();
        });
    }

    public function down(): void
    {
        Schema::table('documents', fn (Blueprint $table) => $table->dropColumn(['scope', 'group_id']));
    }
};
