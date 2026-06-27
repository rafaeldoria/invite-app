<?php

namespace App\Support\Events;

use App\Models\Event;

final class EventShareMessages
{
    public function default(Event $event, string $locale): string
    {
        return __('events.share.default_message', ['name' => $event->name], $locale);
    }

    public function summary(Event $event, string $locale): string
    {
        return __('events.share.summary', [
            'date' => $this->localizedStartsAt($event, $locale),
            'location' => $event->location,
        ], $locale);
    }

    public function final(Event $event, string $locale, string $canonicalUrl): string
    {
        return $this->combine(
            $event->share_message ?: $this->default($event, $locale),
            $this->summary($event, $locale),
            $canonicalUrl,
        );
    }

    public function combine(string $message, string $summary, string $canonicalUrl): string
    {
        $message = $this->removeCanonicalUrl($message, $canonicalUrl);
        $summary = $this->removeCanonicalUrl($summary, $canonicalUrl);

        return implode("\n\n", array_filter([
            $message,
            $summary,
            $canonicalUrl,
        ], fn (string $part): bool => $part !== ''));
    }

    private function removeCanonicalUrl(string $text, string $canonicalUrl): string
    {
        $text = str_replace($canonicalUrl, '', $text);
        $text = preg_replace("/[ \t]+\n/u", "\n", $text) ?? $text;
        $text = preg_replace("/\n{3,}/u", "\n\n", $text) ?? $text;

        return trim($text);
    }

    private function localizedStartsAt(Event $event, string $locale): string
    {
        $date = $event->starts_at->copy()->setTimezone($event->timezone);

        return match ($locale) {
            'en-US' => $date->locale('en')->isoFormat('MMMM D, YYYY [at] h:mm A'),
            default => $date->locale('pt_BR')->isoFormat('D [de] MMMM [de] YYYY [às] HH:mm'),
        };
    }
}
