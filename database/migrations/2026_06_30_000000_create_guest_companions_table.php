<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('guest_companions', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('guest_id')->constrained()->cascadeOnDelete();
            $table->string('name', 120);
            $table->boolean('is_child')->default(false);
            $table->timestamps();

            $table->index(['guest_id', 'is_child']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('guest_companions');
    }
};
