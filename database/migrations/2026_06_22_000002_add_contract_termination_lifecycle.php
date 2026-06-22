<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('contracts', function (Blueprint $table) {
            $table->timestamp('terminated_at')->nullable()->after('notes');
            $table->foreignId('terminated_by')->nullable()->after('terminated_at')->constrained('users')->nullOnDelete();
            $table->text('termination_reason')->nullable()->after('terminated_by');
            $table->date('termination_effective_date')->nullable()->after('termination_reason');
            $table->index(['organization_id', 'status', 'terminated_at'], 'contracts_org_status_terminated_at_index');
        });
    }

    public function down(): void
    {
        Schema::table('contracts', function (Blueprint $table) {
            $table->dropIndex('contracts_org_status_terminated_at_index');
            $table->dropConstrainedForeignId('terminated_by');
            $table->dropColumn(['terminated_at', 'termination_reason', 'termination_effective_date']);
        });
    }
};
