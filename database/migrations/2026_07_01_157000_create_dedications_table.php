<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('dedications', function (Blueprint $table) {
            $table->id();
            $table->foreignId('artist_id')->constrained('users')->cascadeOnDelete();
            $table->foreignId('fan_id')->nullable()->constrained('users')->nullOnDelete();
            $table->text('message_request');
            $table->integer('price')->default(0);
            $table->string('status')->default('pending'); // pending, paid, completed, cancelled
            $table->string('response_type')->nullable(); // text, audio, video
            $table->string('response_media')->nullable();
            $table->text('response_message')->nullable();
            $table->timestamp('delivered_at')->nullable();
            $table->string('reference')->unique();
            $table->string('payment_ref')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('dedications');
    }
};
