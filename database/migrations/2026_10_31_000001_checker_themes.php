<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // Reusable look of the customer screen (App\Support\CheckerDesign); a price checker picks one.
        Schema::create('checker_themes', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->json('design')->nullable();
            $table->boolean('is_default')->default(false);
            $table->timestamps();
        });

        Schema::table('price_checkers', function (Blueprint $table) {
            $table->foreignId('theme_id')->nullable()->constrained('checker_themes')->nullOnDelete();
        });

        // A product picture shown on the checker screen.
        Schema::table('items', function (Blueprint $table) {
            $table->string('image')->nullable();
        });

        // Designs saved per checker before become themes.
        foreach (DB::table('price_checkers')->whereNotNull('design')->get() as $checker) {
            $themeId = DB::table('checker_themes')->insertGetId([
                'name' => $checker->name.' dizayni', 'design' => $checker->design, 'is_default' => false, 'created_at' => now(), 'updated_at' => now(),
            ]);
            DB::table('price_checkers')->where('id', $checker->id)->update(['theme_id' => $themeId]);
        }

        Schema::table('price_checkers', fn (Blueprint $table) => $table->dropColumn('design'));
    }

    public function down(): void
    {
        Schema::table('price_checkers', function (Blueprint $table) {
            $table->json('design')->nullable();
            $table->dropConstrainedForeignId('theme_id');
        });
        Schema::table('items', fn (Blueprint $table) => $table->dropColumn('image'));
        Schema::dropIfExists('checker_themes');
    }
};
