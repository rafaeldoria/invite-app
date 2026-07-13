<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('support_contacts', function (Blueprint $table): void {
            $table->id();
            $table->string('name', 120);
            $table->string('contact', 255);
            $table->string('subject', 160);
            $table->text('message');
            $table->timestamp('email_sent_at')->nullable();
            $table->timestamps();

            $table->index('created_at');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('support_contacts');
    }
};
