<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('warehouses', function (Blueprint $table) {
            $table->id();
            $table->string('code')->nullable()->unique(); // 1C code
            $table->string('name');
            $table->boolean('is_active')->default(true);
            $table->timestamps();
        });

        Schema::create('counterparties', function (Blueprint $table) {
            $table->id();
            $table->string('name')->index();
            $table->string('stir', 20)->nullable()->unique(); // STIR (tax id)
            $table->string('onec_ref')->nullable()->index();
            $table->boolean('is_active')->default(true);
            $table->timestamps();
        });

        Schema::table('documents', function (Blueprint $table) {
            $table->foreignId('counterparty_id')->nullable()->constrained();
            $table->foreignId('warehouse_id')->nullable()->constrained();
            $table->foreignId('warehouse_to_id')->nullable()->constrained('warehouses');
        });

        // Free-text header values are replaced by the master-data references above.
        Schema::table('documents', function (Blueprint $table) {
            $table->dropColumn(['counterparty', 'warehouse', 'warehouse_to']);
        });
    }

    public function down(): void
    {
        Schema::table('documents', function (Blueprint $table) {
            $table->string('counterparty')->nullable();
            $table->string('warehouse')->nullable();
            $table->string('warehouse_to')->nullable();
        });

        Schema::table('documents', function (Blueprint $table) {
            $table->dropConstrainedForeignId('counterparty_id');
            $table->dropConstrainedForeignId('warehouse_id');
            $table->dropConstrainedForeignId('warehouse_to_id');
        });

        Schema::dropIfExists('counterparties');
        Schema::dropIfExists('warehouses');
    }
};
