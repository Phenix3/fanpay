<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('revenue_splits', function (Blueprint $table) {
            $table->id();
            $table->morphs('splittable'); // album, track, video, product
            $table->foreignId('artist_id')->constrained('users')->cascadeOnDelete();
            $table->decimal('percentage', 5, 2);
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('revenue_splits');
    }
};
