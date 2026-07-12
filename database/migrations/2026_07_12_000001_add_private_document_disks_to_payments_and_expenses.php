<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('payments', function (Blueprint $table) {
            $table->string('proof_disk')->nullable()->after('proof_image');
        });

        Schema::table('expenses', function (Blueprint $table) {
            $table->string('invoice_disk')->nullable()->after('invoice_image');
        });
    }

    public function down(): void
    {
        Schema::table('expenses', function (Blueprint $table) {
            $table->dropColumn('invoice_disk');
        });

        Schema::table('payments', function (Blueprint $table) {
            $table->dropColumn('proof_disk');
        });
    }
};
