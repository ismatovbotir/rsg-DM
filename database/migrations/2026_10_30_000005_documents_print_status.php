<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // Price check documents are not sent to Adines; they track whether their price tags were printed instead.
        Schema::table('documents', function (Blueprint $table) {
            $table->unsignedInteger('print_count')->default(0); // tags to print, fixed when the document is completed
            $table->timestamp('printed_at')->nullable();
        });

        $docs = DB::table('documents')->where('type', 'pricecheck')->where('status', 'completed')->pluck('id');

        foreach ($docs as $id) {
            $tags = DB::table('document_lines')->where('document_id', $id)->where('check_action', 'print')->count();
            $queued = DB::table('price_checks')->where('document_id', $id)->where('action', 'print')->whereNull('printed_at')->exists();

            DB::table('documents')->where('id', $id)->update([
                'onec_status' => 'none', 'print_count' => $tags, 'printed_at' => ($tags && ! $queued) ? now() : null,
            ]);
        }
    }

    public function down(): void
    {
        Schema::table('documents', fn (Blueprint $table) => $table->dropColumn(['print_count', 'printed_at']));
    }
};
