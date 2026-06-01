<?php

namespace App\Http\Middleware;

use App\Models\Settings;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class ApplyDynamicModuleLabels
{
    /**
     * Replace module labels in rendered HTML so renamed labels are reflected globally.
     */
    public function handle(Request $request, Closure $next): Response
    {
        $response = $next($request);

        // Settings forms render labels from the database; global strtr() here duplicates words
        // (e.g. "Paid" → "Paid Ticket" inside value="Paid Ticket" → "Paid Ticket Ticket").
        if ($request->is('*/settings-panel/*') || $request->routeIs('settings-panel.*')) {
            return $response;
        }

        $contentType = (string) $response->headers->get('Content-Type', '');
        if ($contentType !== '' && stripos($contentType, 'text/html') === false) {
            return $response;
        }

        if (!method_exists($response, 'getContent') || !method_exists($response, 'setContent')) {
            return $response;
        }

        $content = $response->getContent();
        if (!is_string($content) || $content === '') {
            return $response;
        }

        $defaults = config('menu_labels.defaults', []);
        $currentLabels = Settings::getMenuLabels();

        $replacements = [];
        foreach ($defaults as $key => $defaultLabel) {
            $newLabel = trim((string) ($currentLabels[$key] ?? ''));
            $defaultLabel = trim((string) $defaultLabel);
            if ($defaultLabel === '' || $newLabel === '' || $newLabel === $defaultLabel) {
                continue;
            }

            // Avoid strtr() compounding when the custom label still contains the default
            // (e.g. "Paid" → "Paid Ticket" would rewrite value="Paid Ticket" to "Paid Ticket Ticket").
            if (stripos($newLabel, $defaultLabel) !== false) {
                continue;
            }

            $replacements[$defaultLabel] = $newLabel;
        }

        if ($replacements === []) {
            return $response;
        }

        // Longest first avoids partial replacement collisions (e.g. "VAT" vs "VAT Settings").
        uksort($replacements, static fn (string $a, string $b): int => strlen($b) <=> strlen($a));

        $response->setContent(strtr($content, $replacements));

        return $response;
    }
}
