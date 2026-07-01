<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('artist_profiles', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->unique()->constrained()->cascadeOnDelete();
            $table->string('stage_name', 255);
            $table->string('slug', 255)->unique();
            $table->text('bio')->nullable();
            $table->text('bio_en')->nullable();
            $table->string('avatar', 500)->nullable();
            $table->string('cover', 500)->nullable();
            $table->foreignId('genre_id')->nullable()->constrained();
            $table->boolean('is_verified')->default(false);
            $table->json('social_links')->nullable();
            $table->json('stats')->nullable();
            $table->json('customization')->nullable();
            $table->timestamps();
            $table->softDeletes();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('artist_profiles');
    }
};
