<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('content_rights', function (Blueprint $table) {
            $table->id();
            $table->foreignId('artist_id')->constrained('users')->cascadeOnDelete();
            $table->morphs('rightable');
            $table->timestamp('declaration_accepted_at')->nullable();
            $table->boolean('socam_member')->default(false);
            $table->string('ip_address')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('content_rights');
    }
};
