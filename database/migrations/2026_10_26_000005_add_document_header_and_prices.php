<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('items', function (Blueprint $table) {
            $table->string('article')->nullable()->index()->after('code'); // "artikul"
        });

        Schema::table('documents', function (Blueprint $table) {
            $table->date('doc_date')->nullable();
            $table->string('counterparty')->nullable();
            $table->string('warehouse')->nullable();
            $table->string('warehouse_to')->nullable();
            $table->string('reason')->nullable();
        });

        Schema::table('document_lines', function (Blueprint $table) {
            $table->decimal('price', 14, 2)->default(0)->after('qty');
        });
    }

    public function down(): void
    {
        Schema::table('document_lines', fn (Blueprint $t) => $t->dropColumn('price'));
        Schema::table('documents', fn (Blueprint $t) => $t->dropColumn(['doc_date', 'counterparty', 'warehouse', 'warehouse_to', 'reason']));
        Schema::table('items', fn (Blueprint $t) => $t->dropColumn('article'));
    }
};
