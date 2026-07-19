<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::table('organizations', function (Blueprint $table) {
            $table->foreignId('country_id')->nullable()->constrained()->nullOnDelete();
            $table->string('currency_code', 3)->nullable()->index();
            $table->string('locale', 12)->nullable();
            $table->string('timezone', 64)->nullable();
        });

        Schema::table('users', function (Blueprint $table) {
            $table->string('preferred_locale', 12)->nullable();
        });

        Schema::table('buildings', function (Blueprint $table) {
            $table->foreignId('country_id')->nullable()->constrained()->nullOnDelete();
            $table->string('currency_code', 3)->nullable()->index();
            $table->string('timezone', 64)->nullable();
        });
    }

    public function down(): void
    {
        Schema::table('buildings', function (Blueprint $table) {
            $table->dropConstrainedForeignId('country_id');
            $table->dropColumn(['currency_code', 'timezone']);
        });

        Schema::table('users', function (Blueprint $table) {
            $table->dropColumn('preferred_locale');
        });

        Schema::table('organizations', function (Blueprint $table) {
            $table->dropConstrainedForeignId('country_id');
            $table->dropColumn(['currency_code', 'locale', 'timezone']);
        });
    }
};
