<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('payments', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->string('gateway', 50); // campay, notchpay
            $table->bigInteger('amount');
            $table->bigInteger('fees')->default(0);
            $table->string('phone', 20);
            $table->string('operator', 20)->nullable(); // orange, mtn
            $table->string('reference', 100)->unique();
            $table->string('external_ref', 100)->nullable()->index();
            $table->string('status', 20)->default('pending'); // pending, completed, failed, expired
            $table->json('webhook_payload')->nullable();
            $table->timestamp('paid_at')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('payments');
    }
};
