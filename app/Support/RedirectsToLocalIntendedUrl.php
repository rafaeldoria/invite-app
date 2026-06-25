<?php

namespace App\Support;

use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

final class RedirectsToLocalIntendedUrl
{
    public function toResponse(Request $request): RedirectResponse
    {
        $intended = $request->session()->pull('url.intended');

        if (is_string($intended) && $this->isLocalUrl($request, $intended)) {
            return redirect()->to($intended);
        }

        return redirect()->route('home');
    }

    private function isLocalUrl(Request $request, string $url): bool
    {
        if (str_starts_with($url, '/') && ! str_starts_with($url, '//')) {
            return true;
        }

        $host = parse_url($url, PHP_URL_HOST);

        return is_string($host) && $host === $request->getHost();
    }
}
