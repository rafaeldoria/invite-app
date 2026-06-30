<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    private const int MAX_COMPANIONS = 5;

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

        DB::table('guests')
            ->select(['id', 'adult_companions', 'child_companions'])
            ->whereRaw('(adult_companions + child_companions) > ?', [self::MAX_COMPANIONS])
            ->orderBy('id')
            ->get()
            ->each(function (object $guest): void {
                $adultCompanions = min((int) $guest->adult_companions, self::MAX_COMPANIONS);
                $childCompanions = min((int) $guest->child_companions, self::MAX_COMPANIONS - $adultCompanions);

                DB::table('guests')
                    ->where('id', $guest->id)
                    ->update([
                        'adult_companions' => $adultCompanions,
                        'child_companions' => $childCompanions,
                    ]);
            });
    }

    public function down(): void
    {
        Schema::dropIfExists('guest_companions');
    }
};
