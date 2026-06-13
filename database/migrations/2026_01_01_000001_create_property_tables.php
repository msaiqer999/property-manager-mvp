<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('buildings', function (Blueprint $table) {
            $table->id();
            $table->foreignId('organization_id')->constrained()->cascadeOnDelete();
            $table->string('name');
            $table->string('location')->nullable();
            $table->text('description')->nullable();
            $table->softDeletes();
            $table->timestamps();
        });

        Schema::create('units', function (Blueprint $table) {
            $table->id();
            $table->foreignId('building_id')->constrained()->cascadeOnDelete();
            $table->string('unit_number');
            $table->string('type')->default('apartment');
            $table->decimal('size', 10, 2)->nullable();
            $table->unsignedSmallInteger('rooms')->nullable();
            $table->string('status')->default('vacant');
            $table->decimal('rent_amount', 12, 2)->default(0);
            $table->text('notes')->nullable();
            $table->softDeletes();
            $table->timestamps();
            $table->unique(['building_id', 'unit_number']);
        });

        Schema::create('tenants', function (Blueprint $table) {
            $table->id();
            $table->foreignId('organization_id')->constrained()->cascadeOnDelete();
            $table->string('full_name');
            $table->string('phone')->nullable();
            $table->string('email')->nullable();
            $table->string('id_number')->nullable();
            $table->string('nationality')->nullable();
            $table->text('notes')->nullable();
            $table->timestamps();
        });

        Schema::create('contracts', function (Blueprint $table) {
            $table->id();
            $table->foreignId('organization_id')->constrained()->cascadeOnDelete();
            $table->foreignId('unit_id')->constrained()->cascadeOnDelete();
            $table->foreignId('tenant_id')->constrained()->cascadeOnDelete();
            $table->string('contract_number');
            $table->date('start_date');
            $table->date('end_date');
            $table->decimal('rent_amount', 12, 2);
            $table->string('payment_frequency')->default('monthly');
            $table->decimal('deposit_amount', 12, 2)->default(0);
            $table->string('status')->default('active');
            $table->text('notes')->nullable();
            $table->timestamps();
            $table->unique(['organization_id', 'contract_number']);
        });

        Schema::create('payments', function (Blueprint $table) {
            $table->id();
            $table->foreignId('organization_id')->constrained()->cascadeOnDelete();
            $table->foreignId('contract_id')->constrained()->cascadeOnDelete();
            $table->date('due_date');
            $table->decimal('amount_due', 12, 2);
            $table->decimal('amount_paid', 12, 2)->default(0);
            $table->date('payment_date')->nullable();
            $table->string('status')->default('pending');
            $table->string('payment_method')->nullable();
            $table->string('proof_image')->nullable();
            $table->text('notes')->nullable();
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();
        });

        Schema::create('expenses', function (Blueprint $table) {
            $table->id();
            $table->foreignId('organization_id')->constrained()->cascadeOnDelete();
            $table->foreignId('building_id')->constrained()->cascadeOnDelete();
            $table->foreignId('unit_id')->nullable()->constrained()->nullOnDelete();
            $table->string('category');
            $table->decimal('amount', 12, 2);
            $table->date('expense_date');
            $table->string('invoice_image')->nullable();
            $table->text('notes')->nullable();
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();
        });

        Schema::create('activity_logs', function (Blueprint $table) {
            $table->id();
            $table->foreignId('organization_id')->constrained()->cascadeOnDelete();
            $table->foreignId('user_id')->nullable()->constrained()->nullOnDelete();
            $table->string('action');
            $table->string('subject_type');
            $table->unsignedBigInteger('subject_id');
            $table->text('description')->nullable();
            $table->timestamps();
            $table->index(['subject_type', 'subject_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('activity_logs');
        Schema::dropIfExists('expenses');
        Schema::dropIfExists('payments');
        Schema::dropIfExists('contracts');
        Schema::dropIfExists('tenants');
        Schema::dropIfExists('units');
        Schema::dropIfExists('buildings');
    }
};
