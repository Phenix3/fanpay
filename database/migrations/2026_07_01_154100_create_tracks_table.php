<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('tracks', function (Blueprint $table) {
            $table->id();
            $table->foreignId('album_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignId('artist_id')->constrained('users')->cascadeOnDelete();
            $table->string('title');
            $table->integer('duration')->default(0); // seconds
            $table->string('file_path')->nullable();
            $table->string('preview_path')->nullable();
            $table->integer('price')->default(0);
            $table->boolean('is_free')->default(false);
            $table->boolean('is_vip_exclusive')->default(false);
            $table->integer('play_count')->default(0);
            $table->integer('track_number')->default(0);
            $table->text('lyrics')->nullable();
            $table->softDeletes();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('tracks');
    }
};
