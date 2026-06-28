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

class PublicInvitationRsvpController extends Controller
{
    public function __construct(
        private readonly RsvpPresenter $rsvps,
        private readonly SubmitPublicRsvp $submitRsvp,
    ) {}

    public function edit(Request $request, Event $event, string $token): Response
    {
        try {
            $guest = $event->guests()->where('invitation_token', $token)->firstOrFail();
        } catch (ModelNotFoundException) {
            $this->abortInvalidCapability($event, 'edit');
        }

        return $this->noStore(Inertia::render('Rsvp/Form', $this->rsvps->form(
            event: $event,
            mode: 'invitation',
            submitUrl: route('public.invitations.rsvp.update', [$event, $token]),
            method: 'patch',
            guest: $guest,
            updateUrl: route('public.invitations.rsvp.edit', [$event, $token]),
        ))->toResponse($request));
    }

    public function update(SubmitRsvpRequest $request, Event $event, string $token): RedirectResponse
    {
        try {
            $this->submitRsvp->updateFromInvitationToken($event, $token, $request->rsvpData());
        } catch (ModelNotFoundException) {
            $this->abortInvalidCapability($event, 'update');
        }

        return redirect()->route('public.invitations.rsvp.edit', [$event, $token]);
    }

    private function noStore(Response $response): Response
    {
        $response->headers->set('Cache-Control', 'private, no-store');

        return $response;
    }

    private function abortInvalidCapability(Event $event, string $action): never
    {
        Log::notice('Invalid public invitation RSVP capability.', [
            'event_public_id' => $event->public_id,
            'action' => $action,
        ]);

        abort(404);
    }
}
