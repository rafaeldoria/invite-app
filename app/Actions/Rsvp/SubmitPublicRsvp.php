<?php

namespace App\Actions\Rsvp;

use App\Enums\GuestStatus;
use App\Models\Event;
use App\Models\Guest;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;

final class SubmitPublicRsvp
{
    /**
     * @param  array{name: string, attendance: string, adult_companions: int, child_companions: int}  $data
     */
    public function createFromGeneralLink(Event $event, string $responseToken, array $data): Guest
    {
        return DB::transaction(function () use ($event, $responseToken, $data): Guest {
            $hash = $this->hashToken($responseToken);

            Event::query()
                ->whereKey($event->id)
                ->lockForUpdate()
                ->firstOrFail();

            $guest = $event->guests()
                ->where('response_token_hash', $hash)
                ->lockForUpdate()
                ->first();

            if ($guest === null) {
                if (Guest::query()->where('response_token_hash', $hash)->exists()) {
                    throw new ModelNotFoundException;
                }

                return $event->guests()->create([
                    'name' => $data['name'],
                    'response_token_hash' => $hash,
                    ...$this->responseAttributes($data),
                ]);
            }

            $guest->update([
                ...$this->responseAttributes($data),
            ]);

            return $guest;
        });
    }

    /**
     * @param  array{attendance: string, adult_companions: int, child_companions: int}  $data
     */
    public function updateFromManagementToken(Event $event, string $responseToken, array $data): Guest
    {
        return DB::transaction(function () use ($event, $responseToken, $data): Guest {
            $guest = $this->guestForManagementToken($event, $responseToken, true);

            $guest->update($this->responseAttributes($data));

            return $guest;
        });
    }

    /**
     * @param  array{attendance: string, adult_companions: int, child_companions: int}  $data
     */
    public function updateFromInvitationToken(Event $event, string $invitationToken, array $data): Guest
    {
        return DB::transaction(function () use ($event, $invitationToken, $data): Guest {
            $guest = $this->guestForInvitationToken($event, $invitationToken, true);

            $guest->update($this->responseAttributes($data));

            return $guest;
        });
    }

    public function guestForManagementToken(Event $event, string $responseToken, bool $lock = false): Guest
    {
        $query = $event->guests()->where('response_token_hash', $this->hashToken($responseToken));

        if ($lock) {
            $query->lockForUpdate();
        }

        return $query->firstOrFail();
    }

    public function guestForInvitationToken(Event $event, string $invitationToken, bool $lock = false): Guest
    {
        $query = $event->guests()->where('invitation_token', $invitationToken);

        if ($lock) {
            $query->lockForUpdate();
        }

        return $query->firstOrFail();
    }

    public function hashToken(string $token): string
    {
        return hash('sha256', $token);
    }

    /**
     * @param  array{attendance: string, adult_companions: int, child_companions: int}  $data
     * @return array{status: GuestStatus, adult_companions: int, child_companions: int, responded_at: Carbon}
     */
    private function responseAttributes(array $data): array
    {
        $status = GuestStatus::from($data['attendance']);

        return [
            'status' => $status,
            'adult_companions' => $status->allowsCompanions() ? $data['adult_companions'] : 0,
            'child_companions' => $status->allowsCompanions() ? $data['child_companions'] : 0,
            'responded_at' => now(),
        ];
    }
}
