<?php

namespace App\Http\Controllers;

use App\Actions\Support\SubmitSupportContact;
use App\Http\Requests\Support\StoreSupportContactRequest;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class SupportContactController extends Controller
{
    public function __construct(private readonly SubmitSupportContact $submitSupportContact) {}

    public function create(Request $request): Response
    {
        $user = $request->user();

        return Inertia::render('Support/Contact', [
            'defaults' => [
                'name' => $user?->name ?? '',
                'contact' => $user?->email ?? '',
            ],
            'links' => [
                'store' => route('support.store'),
                'home' => route('home'),
            ],
        ]);
    }

    public function store(StoreSupportContactRequest $request): RedirectResponse
    {
        $contact = $this->submitSupportContact->handle($request->validated());

        if ($contact->email_sent_at === null) {
            return redirect()
                ->route('support.create')
                ->with('error', __('support.messages.send_failed'));
        }

        $destination = $request->user() === null ? 'home' : 'events.index';

        return redirect()
            ->route($destination)
            ->with('success', __('support.messages.sent'));
    }
}
