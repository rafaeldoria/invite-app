<?php

namespace App\Http\Controllers;

use App\Actions\Rsvp\SubmitPublicRsvp;
use App\Http\Requests\Rsvp\SubmitRsvpRequest;
use App\Models\Event;
use App\Support\Rsvp\RsvpPresenter;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Inertia\Inertia;
use Symfony\Component\HttpFoundation\Response;

class PublicRsvpManagementController extends Controller
{
    public function __construct(
        private readonly RsvpPresenter $rsvps,
        private readonly SubmitPublicRsvp $submitRsvp,
    ) {}

    public function show(Request $request, Event $event, string $token): Response
    {
        try {
            $guest = $this->submitRsvp->guestForManagementToken($event, $token);
        } catch (ModelNotFoundException) {
            $this->abortInvalidCapability($event, 'show');
        }

        return $this->noStore(Inertia::render('Rsvp/Form', $this->rsvps->form(
            event: $event,
            mode: 'management',
            submitUrl: route('public.rsvp.update', [$event, $token]),
            method: 'patch',
            guest: $guest,
            updateUrl: route('public.rsvp.show', [$event, $token]),
        ))->toResponse($request));
    }

    public function update(SubmitRsvpRequest $request, Event $event, string $token): RedirectResponse
    {
        try {
            $this->submitRsvp->updateFromManagementToken($event, $token, $request->rsvpData());
        } catch (ModelNotFoundException) {
            $this->abortInvalidCapability($event, 'update');
        }

        return redirect()->route('public.rsvp.show', [$event, $token]);
    }

    private function noStore(Response $response): Response
    {
        $response->headers->set('Cache-Control', 'private, no-store');

        return $response;
    }

    private function abortInvalidCapability(Event $event, string $action): never
    {
        Log::notice('Invalid public RSVP management capability.', [
            'event_public_id' => $event->public_id,
            'action' => $action,
        ]);

        abort(404);
    }
}
