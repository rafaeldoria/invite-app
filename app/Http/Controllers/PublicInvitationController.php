<?php

namespace App\Http\Controllers;

use App\Models\Event;
use App\Support\Events\EventPresenter;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Symfony\Component\HttpFoundation\Response;

class PublicInvitationController extends Controller
{
    public function __construct(
        private readonly EventPresenter $events,
    ) {}

    public function __invoke(Request $request, Event $event, string $token): Response
    {
        abort_unless($event->guests()->where('invitation_token', $token)->exists(), 404);

        $response = Inertia::render('PublicEvent/Show', [
            'event' => $this->events->publicDetail($event, route('public.invitations.rsvp.edit', [$event, $token])),
            'meta' => $this->events->publicMeta($event),
        ])->withViewData([
            'meta' => $this->events->publicMeta($event),
        ])->toResponse($request);

        $response->headers->set('Cache-Control', 'private, no-store');

        return $response;
    }
}
