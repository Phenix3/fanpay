<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('otp_codes', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->nullable()->constrained();
            $table->string('phone', 20);
            $table->string('code', 100);
            $table->string('purpose', 50)->default('verification');
            $table->integer('attempts')->default(0);
            $table->integer('max_attempts')->default(3);
            $table->timestamp('expires_at');
            $table->timestamp('verified_at')->nullable();
            $table->timestamp('created_at')->useCurrent();

            $table->index(['phone', 'purpose']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('otp_codes');
    }
};
