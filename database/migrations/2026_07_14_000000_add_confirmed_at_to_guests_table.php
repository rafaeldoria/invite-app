<?php

use App\Enums\GuestStatus;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('guests', function (Blueprint $table): void {
            $table->timestamp('confirmed_at')->nullable()->after('responded_at');
        });

        DB::table('guests')
            ->where('status', GuestStatus::Confirmed->value)
            ->whereNotNull('responded_at')
            ->update(['confirmed_at' => DB::raw('responded_at')]);
    }

    public function down(): void
    {
        Schema::table('guests', function (Blueprint $table): void {
            $table->dropColumn('confirmed_at');
        });
    }
};
