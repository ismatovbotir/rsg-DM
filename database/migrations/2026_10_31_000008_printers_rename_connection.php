<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // "connection" clashes with Eloquent's own $connection property, so the column is called kind
        Schema::table('printers', fn (Blueprint $table) => $table->renameColumn('connection', 'kind'));
    }

    public function down(): void
    {
        Schema::table('printers', fn (Blueprint $table) => $table->renameColumn('kind', 'connection'));
    }
};
