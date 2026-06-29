<?php

namespace App\Http\Controllers;

use App\Actions\Rsvp\SubmitPublicRsvp;
use App\Http\Requests\Rsvp\SubmitRsvpRequest;
use App\Models\Event;
use App\Support\Rsvp\RsvpPresenter;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Inertia\Inertia;
use Symfony\Component\HttpFoundation\Response;

class PublicRsvpController extends Controller
{
    public function __construct(
        private readonly RsvpPresenter $rsvps,
        private readonly SubmitPublicRsvp $submitRsvp,
    ) {}

    public function create(Request $request, Event $event): Response
    {
        return $this->noStore(Inertia::render('Rsvp/Form', $this->rsvps->form(
            event: $event,
            mode: 'general',
            submitUrl: route('public.rsvp.store', $event),
            method: 'post',
            responseToken: Str::random(64),
            eventUrl: route('public.events.show', $event),
        ))->toResponse($request));
    }

    public function store(SubmitRsvpRequest $request, Event $event): RedirectResponse
    {
        $this->submitRsvp->createFromGeneralLink($event, $request->responseToken(), $request->rsvpData());

        return redirect()->route('public.rsvp.show', [
            'event' => $event,
            'token' => $request->responseToken(),
        ]);
    }

    private function noStore(Response $response): Response
    {
        $response->headers->set('Cache-Control', 'private, no-store');

        return $response;
    }
}
