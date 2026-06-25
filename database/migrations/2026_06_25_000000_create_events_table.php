<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('events', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->char('public_id', 26)->unique();
            $table->string('name', 120);
            $table->text('description');
            $table->timestamp('starts_at');
            $table->string('timezone', 64);
            $table->string('location');
            $table->string('theme', 80)->nullable();
            $table->string('cover_image_disk', 64)->nullable();
            $table->string('cover_image_key', 512)->nullable();
            $table->string('cover_image_mime', 100)->nullable();
            $table->unsignedInteger('cover_image_size')->nullable();
            $table->unsignedInteger('cover_image_width')->nullable();
            $table->unsignedInteger('cover_image_height')->nullable();
            $table->text('share_message')->nullable();
            $table->timestamps();

            $table->index(['user_id', 'starts_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('events');
    }
};
