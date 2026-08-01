<?php

namespace App\Http\Middleware;

use App\Models\Settings;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class ApplyDynamicModuleLabels
{
    /**
     * Replace renamed module labels only inside the sidebar/top menu markup.
     *
     * Menu blades already prefer Settings::getMenuLabels(); this is a scoped
     * safety net for any remaining hardcoded defaults in #layout-menu only.
     * It must not rewrite page content (status badges, filters, etc.).
     */
    public function handle(Request $request, Closure $next): Response
    {
        $response = $next($request);

        // Settings forms render labels from the database; strtr() here duplicates words
        // (e.g. "Paid" → "Paid Ticket" inside value="Paid Ticket" → "Paid Ticket Ticket").
        if ($request->is('*/settings-panel/*') || $request->routeIs('settings-panel.*')) {
            return $response;
        }

        $contentType = (string) $response->headers->get('Content-Type', '');
        if ($contentType !== '' && stripos($contentType, 'text/html') === false) {
            return $response;
        }

        if (! method_exists($response, 'getContent') || ! method_exists($response, 'setContent')) {
            return $response;
        }

        $content = $response->getContent();
        if (! is_string($content) || $content === '') {
            return $response;
        }

        $replacements = $this->buildReplacements();
        if ($replacements === []) {
            return $response;
        }

        $updated = $this->replaceInsideLayoutMenu($content, $replacements);
        if ($updated !== $content) {
            $response->setContent($updated);
        }

        return $response;
    }

    /**
     * @return array<string, string> defaultLabel => customLabel
     */
    protected function buildReplacements(): array
    {
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
            return [];
        }

        // Longest first avoids partial replacement collisions (e.g. "VAT" vs "VAT Settings").
        uksort($replacements, static fn (string $a, string $b): int => strlen($b) <=> strlen($a));

        return $replacements;
    }

    /**
     * Apply label replacements only within <aside id="layout-menu">…</aside>.
     *
     * @param  array<string, string>  $replacements
     */
    protected function replaceInsideLayoutMenu(string $content, array $replacements): string
    {
        $updated = preg_replace_callback(
            '/(<aside\b[^>]*\bid=(["\'])layout-menu\2[^>]*>)(.*?)(<\/aside>)/is',
            static function (array $matches) use ($replacements): string {
                return $matches[1] . strtr($matches[3], $replacements) . $matches[4];
            },
            $content
        );

        return is_string($updated) ? $updated : $content;
    }
}
