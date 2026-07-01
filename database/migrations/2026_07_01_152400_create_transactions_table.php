<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('transactions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('wallet_id')->constrained()->cascadeOnDelete();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->string('type', 20); // credit, debit
            $table->bigInteger('amount');
            $table->bigInteger('balance_before')->default(0);
            $table->bigInteger('balance_after')->default(0);
            $table->string('source', 50); // tip, sale, vip, ticket, dedication, vote, payout, refund
            $table->string('reference', 100)->unique();
            $table->string('description', 500)->nullable();
            $table->string('status', 20)->default('pending');
            $table->json('meta')->nullable();
            $table->timestamps();

            $table->index(['wallet_id', 'created_at']);
            $table->index(['user_id', 'created_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('transactions');
    }
};
