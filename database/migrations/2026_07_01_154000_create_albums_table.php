<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('albums', function (Blueprint $table) {
            $table->id();
            $table->foreignId('artist_id')->constrained('users')->cascadeOnDelete();
            $table->string('title');
            $table->string('slug')->unique();
            $table->string('cover')->nullable();
            $table->string('type')->default('album'); // album, single, ep, compilation
            $table->integer('price')->default(0);
            $table->date('release_date')->nullable();
            $table->foreignId('genre_id')->nullable()->constrained()->nullOnDelete();
            $table->boolean('is_published')->default(false);
            $table->boolean('is_vip_exclusive')->default(false);
            $table->text('description')->nullable();
            $table->softDeletes();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('albums');
    }
};
