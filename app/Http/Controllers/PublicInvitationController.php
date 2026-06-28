<?php

namespace App\Http\Controllers;

use App\Models\Event;
use App\Support\Events\EventPresenter;
use Inertia\Inertia;
use Inertia\Response;

class PublicInvitationController extends Controller
{
    public function __construct(
        private readonly EventPresenter $events,
    ) {}

    public function __invoke(Event $event, string $token): Response
    {
        abort_unless($event->guests()->where('invitation_token', $token)->exists(), 404);

        return Inertia::render('PublicEvent/Show', [
            'event' => $this->events->publicDetail($event),
            'meta' => $this->events->publicMeta($event),
        ])->withViewData([
            'meta' => $this->events->publicMeta($event),
        ]);
    }
}
