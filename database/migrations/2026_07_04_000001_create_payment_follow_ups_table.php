<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('payment_follow_ups', function (Blueprint $table) {
            $table->id();
            $table->foreignId('organization_id')->constrained()->cascadeOnDelete();
            $table->foreignId('payment_id')->constrained()->cascadeOnDelete();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->string('type', 32);
            $table->text('note')->nullable();
            $table->decimal('promised_amount', 12, 2)->nullable();
            $table->date('promised_date')->nullable();
            $table->timestamps();

            $table->index(['organization_id', 'payment_id']);
            $table->index(['organization_id', 'type', 'promised_date']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('payment_follow_ups');
    }
};
