<?php

namespace App\Http\Controllers;

use App\Http\Requests\Events\UpdateEventShareMessageRequest;
use App\Models\Event;
use Illuminate\Http\RedirectResponse;

class EventShareMessageController extends Controller
{
    public function update(UpdateEventShareMessageRequest $request, Event $event): RedirectResponse
    {
        $event->forceFill([
            'share_message' => $request->shareMessage(),
        ])->save();

        return back()->with('success', __('events.messages.share_updated'));
    }
}
