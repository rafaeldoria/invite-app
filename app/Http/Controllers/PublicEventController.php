<?php

namespace App\Http\Controllers;

use App\Models\Event;
use App\Support\Events\EventPresenter;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Symfony\Component\HttpFoundation\Response;

class PublicEventController extends Controller
{
    public function __invoke(Request $request, Event $event, EventPresenter $events): Response
    {
        $response = Inertia::render('PublicEvent/Show', [
            'event' => $events->publicDetail($event, route('public.rsvp.create', $event)),
            'meta' => $events->publicMeta($event),
        ])->toResponse($request);

        $response->headers->set('Cache-Control', 'private, no-store');

        return $response;
    }
}
