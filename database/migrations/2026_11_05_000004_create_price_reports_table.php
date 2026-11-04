<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('price_reports', function (Blueprint $table) {
            $table->id();
            $table->foreignId('commodity_id')->constrained()->cascadeOnDelete();
            $table->foreignId('market_id')->constrained()->cascadeOnDelete();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->decimal('price', 10, 2);
            $table->timestamp('reported_at');
            $table->timestamps();

            $table->index(['commodity_id', 'reported_at']);
            $table->index(['market_id', 'reported_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('price_reports');
    }
};
