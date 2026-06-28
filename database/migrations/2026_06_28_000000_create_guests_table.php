<?php

use App\Enums\GuestStatus;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('guests', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('event_id')->constrained()->cascadeOnDelete();
            $table->string('name', 120);
            $table->enum('status', GuestStatus::values())->default(GuestStatus::Pending->value);
            $table->unsignedTinyInteger('adult_companions')->default(0);
            $table->unsignedTinyInteger('child_companions')->default(0);
            $table->string('invitation_token', 80)->unique();
            $table->string('response_token_hash', 64)->nullable()->unique();
            $table->timestamp('responded_at')->nullable();
            $table->timestamps();

            $table->index(['event_id', 'status']);
            $table->index(['event_id', 'name', 'id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('guests');
    }
};
