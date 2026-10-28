<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // Price tag designs made in the admin label designer; elements is a JSON list (see LabelTemplate::sanitize).
        Schema::create('label_templates', function (Blueprint $table) {
            $table->id();
            $table->string('name', 100);
            $table->decimal('width_mm', 6, 1)->default(58);
            $table->decimal('height_mm', 6, 1)->default(40);
            $table->unsignedSmallInteger('rotation')->default(0); // whole label, clockwise: 0, 90, 180, 270
            $table->unsignedSmallInteger('dpi')->default(203);
            $table->string('font', 60)->default('0'); // ZPL font: "0" (built in) or a printer file like E:ARIAL.TTF
            $table->json('elements');
            $table->boolean('is_default')->default(false); // the design used for queued price tags
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('label_templates');
    }
};
