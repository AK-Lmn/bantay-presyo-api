<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('price_reports', function (Blueprint $table) {
            $table->decimal('srp_price', 10, 2)->nullable()->after('price');
            $table->boolean('is_flagged')->default(false)->after('srp_price');
            $table->timestamp('hidden_at')->nullable()->after('reported_at');
            $table->foreignId('hidden_by')->nullable()->after('hidden_at')->constrained('users')->nullOnDelete();
            $table->string('hidden_reason')->nullable()->after('hidden_by');

            $table->index(['is_flagged', 'reported_at']);
        });
    }

    public function down(): void
    {
        Schema::table('price_reports', function (Blueprint $table) {
            $table->dropIndex(['is_flagged', 'reported_at']);
            $table->dropConstrainedForeignId('hidden_by');
            $table->dropColumn(['srp_price', 'is_flagged', 'hidden_at', 'hidden_reason']);
        });
    }
};
