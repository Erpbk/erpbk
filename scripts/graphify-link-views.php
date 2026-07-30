<?php

/**
 * Post-process graphify-out/graph.json:
 * 1) Resolve static Laravel view('…') / View::make('…') → blade "renders" edges
 * 2) Resolve Blade @extends / @include / @include* / @each / @component / <x-*>
 *    → "extends" / "includes" edges between blade file nodes
 *
 * Usage:
 *   php scripts/graphify-link-views.php
 *   php scripts/graphify-link-views.php --graph=graphify-out/graph.json
 */

declare(strict_types=1);

$root = dirname(__DIR__);
$graphPath = $root . DIRECTORY_SEPARATOR . 'graphify-out' . DIRECTORY_SEPARATOR . 'graph.json';

foreach ($argv as $arg) {
    if (str_starts_with($arg, '--graph=')) {
        $graphPath = substr($arg, strlen('--graph='));
        if (!preg_match('#^(?:[a-zA-Z]:)?[/\\\\]#', $graphPath)) {
            $graphPath = $root . DIRECTORY_SEPARATOR . str_replace(['/', '\\'], DIRECTORY_SEPARATOR, $graphPath);
        }
    }
}

if (!is_file($graphPath)) {
    fwrite(STDERR, "graphify-link-views: graph not found at {$graphPath}\n");
    exit(1);
}

$raw = file_get_contents($graphPath);
$graph = json_decode($raw, true);
if (!is_array($graph) || !isset($graph['nodes'], $graph['links'])) {
    fwrite(STDERR, "graphify-link-views: invalid graph.json\n");
    exit(1);
}

$ORIGIN = 'laravel-view-linker';

/**
 * Index blade file-level nodes by Laravel view name (dot notation).
 *
 * @return array<string, array{id:string,label:string,source_file:string}>
 */
function indexBladeNodes(array $nodes): array
{
    $best = [];
    foreach ($nodes as $node) {
        $source = str_replace('\\', '/', (string) ($node['source_file'] ?? ''));
        if (!preg_match('#(?:^|/)resources/views/(.+)\.blade\.php$#', $source, $m)) {
            continue;
        }
        $viewName = str_replace('/', '.', $m[1]);
        $basename = basename($source);
        $relFromViews = $m[1] . '.blade.php';
        $label = (string) ($node['label'] ?? '');

        $score = 0;
        if ($label === $basename || $label === $relFromViews) {
            $score = 3;
        } elseif (str_ends_with($label, '.blade.php')) {
            $score = 2;
        } elseif ($label === $viewName) {
            $score = 1;
        }

        if (!isset($best[$viewName]) || $score > $best[$viewName]['score']) {
            $best[$viewName] = [
                'id' => (string) $node['id'],
                'label' => $label,
                'source_file' => $source,
                'score' => $score,
            ];
        }
    }

    $out = [];
    foreach ($best as $name => $row) {
        unset($row['score']);
        $out[$name] = $row;
    }

    return $out;
}

/**
 * @return array<string, list<array{id:string,label:string,line:int,is_method:bool}>>
 */
function indexCodeNodesByFile(array $nodes): array
{
    $byFile = [];
    foreach ($nodes as $node) {
        $source = str_replace('\\', '/', (string) ($node['source_file'] ?? ''));
        if ($source === '' || !str_ends_with(strtolower($source), '.php')) {
            continue;
        }
        if (str_contains($source, 'resources/views/')) {
            continue;
        }

        $loc = (string) ($node['source_location'] ?? '');
        if (!preg_match('/L(\d+)/', $loc, $m)) {
            continue;
        }
        $line = (int) $m[1];
        $label = (string) ($node['label'] ?? '');
        $isMethod = str_starts_with($label, '.') && str_ends_with($label, '()');
        $isClassish = $label !== '' && !$isMethod && !str_contains($label, '/') && !str_ends_with($label, '.php');

        if (!$isMethod && !$isClassish && !str_ends_with($label, '.php')) {
            continue;
        }

        $byFile[$source][] = [
            'id' => (string) $node['id'],
            'label' => $label,
            'line' => $line,
            'is_method' => $isMethod,
        ];
    }

    foreach ($byFile as &$rows) {
        usort($rows, static function ($a, $b) {
            if ($a['line'] === $b['line']) {
                return ($b['is_method'] <=> $a['is_method']);
            }

            return $a['line'] <=> $b['line'];
        });
    }
    unset($rows);

    return $byFile;
}

/**
 * @param  list<array{id:string,label:string,line:int,is_method:bool}>  $fileNodes
 */
function resolveOwner(array $fileNodes, int $callLine): ?array
{
    $owner = null;
    foreach ($fileNodes as $node) {
        if ($node['line'] <= $callLine) {
            if ($owner === null || $node['line'] >= $owner['line']) {
                if ($owner && $owner['is_method'] && !$node['is_method'] && $node['line'] === $owner['line']) {
                    continue;
                }
                $owner = $node;
            }
        }
    }

    return $owner ?? ($fileNodes[0] ?? null);
}

/**
 * @return list<array{view:string,line:int}>
 */
function extractViewCalls(string $php): array
{
    $calls = [];
    $patterns = [
        "/\\bview\\s*\\(\\s*['\"]([a-zA-Z0-9_\\.|\\/-]+)['\"]/",
        "/\\bView\\s*::\\s*make\\s*\\(\\s*['\"]([a-zA-Z0-9_\\.|\\/-]+)['\"]/",
    ];

    foreach ($patterns as $pattern) {
        if (!preg_match_all($pattern, $php, $matches, PREG_OFFSET_CAPTURE)) {
            continue;
        }
        foreach ($matches[1] as $match) {
            $view = str_replace('/', '.', $match[0]);
            $offset = (int) $match[1];
            $line = substr_count(substr($php, 0, $offset), "\n") + 1;
            $calls[] = ['view' => $view, 'line' => $line];
        }
    }

    return $calls;
}

/**
 * Extract static Blade view references from a blade template.
 *
 * @return list<array{relation:string,view:string,line:int}>
 */
function extractBladeRelations(string $blade): array
{
    $out = [];

    $add = static function (string $relation, string $view, int $offset) use (&$out, $blade): void {
        $view = trim($view);
        if ($view === '' || str_starts_with($view, '$')) {
            return;
        }
        // Ignore mail/vendor namespace components with :: for now unless plain
        if (str_contains($view, '::')) {
            return;
        }
        $view = str_replace(['/', '\\'], '.', $view);
        $line = substr_count(substr($blade, 0, $offset), "\n") + 1;
        $out[] = ['relation' => $relation, 'view' => $view, 'line' => $line];
    };

    // @extends('layouts.app')
    if (preg_match_all("/@extends\\s*\\(\\s*['\"]([^'\"]+)['\"]/", $blade, $m, PREG_OFFSET_CAPTURE)) {
        foreach ($m[1] as $hit) {
            $add('extends', $hit[0], (int) $hit[1]);
        }
    }

    // @include / @includeIf / @includeWhen / @includeUnless
    if (preg_match_all("/@include(?:If|When|Unless)?\\s*\\(\\s*['\"]([^'\"]+)['\"]/", $blade, $m, PREG_OFFSET_CAPTURE)) {
        foreach ($m[1] as $hit) {
            $add('includes', $hit[0], (int) $hit[1]);
        }
    }

    // @includeFirst(['a', 'b'])
    if (preg_match_all("/@includeFirst\\s*\\(\\s*\\[([^\\]]*)\\]/", $blade, $m, PREG_OFFSET_CAPTURE)) {
        foreach ($m[1] as $hit) {
            if (preg_match_all("/['\"]([^'\"]+)['\"]/", $hit[0], $inner)) {
                foreach ($inner[1] as $name) {
                    $add('includes', $name, (int) $hit[1]);
                }
            }
        }
    }

    // @each('view', $items, 'item')
    if (preg_match_all("/@each\\s*\\(\\s*['\"]([^'\"]+)['\"]/", $blade, $m, PREG_OFFSET_CAPTURE)) {
        foreach ($m[1] as $hit) {
            $add('includes', $hit[0], (int) $hit[1]);
        }
    }

    // @component('alert')
    if (preg_match_all("/@component\\s*\\(\\s*['\"]([^'\"]+)['\"]/", $blade, $m, PREG_OFFSET_CAPTURE)) {
        foreach ($m[1] as $hit) {
            $add('includes', $hit[0], (int) $hit[1]);
        }
    }

    // Anonymous / class components: <x-alert />, <x-forms.input>
    if (preg_match_all('/<x-([a-zA-Z0-9][a-zA-Z0-9.\\-]*)/', $blade, $m, PREG_OFFSET_CAPTURE)) {
        foreach ($m[1] as $hit) {
            $name = str_replace('-', '.', $hit[0]);
            // <x-slot:...> is not a view
            if (str_starts_with(strtolower($name), 'slot')) {
                continue;
            }
            $add('includes', 'components.' . $name, (int) $hit[1]);
        }
    }

    return $out;
}

/**
 * @return list<string>
 */
function filesUnder(string $root, string $relativeDir, string $extension): array
{
    $dir = $root . DIRECTORY_SEPARATOR . str_replace('/', DIRECTORY_SEPARATOR, $relativeDir);
    if (!is_dir($dir)) {
        return [];
    }

    $out = [];
    $iterator = new RecursiveIteratorIterator(
        new RecursiveDirectoryIterator($dir, FilesystemIterator::SKIP_DOTS)
    );
    foreach ($iterator as $file) {
        /** @var SplFileInfo $file */
        if (!$file->isFile()) {
            continue;
        }
        $name = $file->getFilename();
        if ($extension === 'blade.php') {
            if (!str_ends_with(strtolower($name), '.blade.php')) {
                continue;
            }
        } elseif (strtolower($file->getExtension()) !== strtolower($extension)) {
            continue;
        }
        $full = $file->getPathname();
        $rel = substr($full, strlen($root) + 1);
        $out[] = str_replace('\\', '/', $rel);
    }

    return $out;
}

/**
 * Resolve a view name that may be relative to the current blade's folder.
 *
 * @param  array<string, array{id:string,label:string,source_file:string}>  $blades
 */
function resolveBladeTarget(string $viewName, string $currentBladeRel, array $blades): ?string
{
    $viewName = str_replace(['/', '\\'], '.', $viewName);

    if (isset($blades[$viewName])) {
        return $viewName;
    }

    // Relative to current blade directory: @include('fields') from riders/create.blade.php → riders.fields
    if (preg_match('#resources/views/(.+)/[^/]+\\.blade\\.php$#', $currentBladeRel, $m)) {
        $prefix = str_replace('/', '.', $m[1]);
        $candidate = $prefix . '.' . $viewName;
        if (isset($blades[$candidate])) {
            return $candidate;
        }
    }

    // components.foo already tried; also try without components. prefix if present in index
    if (str_starts_with($viewName, 'components.')) {
        $alt = substr($viewName, strlen('components.'));
        if (isset($blades[$alt])) {
            return $alt;
        }
    }

    return null;
}

/**
 * @param  array<string, bool>  $existing
 * @param  array<string, int>  $missing
 */
function addEdge(
    array &$graph,
    array &$existing,
    string $sourceId,
    string $targetId,
    string $relation,
    string $sourceFile,
    int $line,
    string $origin,
    int &$added
): void {
    if ($sourceId === '' || $targetId === '' || $sourceId === $targetId) {
        return;
    }
    $key = $sourceId . '|' . $targetId . '|' . $relation;
    if (isset($existing[$key])) {
        return;
    }

    $graph['links'][] = [
        'relation' => $relation,
        'confidence' => 'EXTRACTED',
        'source_file' => $sourceFile,
        'source_location' => 'L' . $line,
        'weight' => 1,
        '_origin' => $origin,
        'source' => $sourceId,
        'target' => $targetId,
        'confidence_score' => 1.0,
    ];
    $existing[$key] = true;
    $added++;
}

$blades = indexBladeNodes($graph['nodes']);
$codeByFile = indexCodeNodesByFile($graph['nodes']);

// Drop previous linker edges so re-runs stay clean (AST includes edges remain).
$graph['links'] = array_values(array_filter(
    $graph['links'],
    static fn ($link) => ($link['_origin'] ?? '') !== $ORIGIN
));

$existing = [];
foreach ($graph['links'] as $link) {
    $key = ($link['source'] ?? '') . '|' . ($link['target'] ?? '') . '|' . ($link['relation'] ?? '');
    $existing[$key] = true;
}

$addedRenders = 0;
$addedIncludes = 0;
$addedExtends = 0;
$resolvedPhp = 0;
$resolvedBlade = 0;
$missingViews = [];
$touchedSources = [];

// --- 1) PHP view() / View::make() → renders ---
foreach (filesUnder($root, 'app', 'php') as $relPath) {
    $absolute = $root . DIRECTORY_SEPARATOR . str_replace('/', DIRECTORY_SEPARATOR, $relPath);
    $php = @file_get_contents($absolute);
    if ($php === false || $php === '') {
        continue;
    }

    $calls = extractViewCalls($php);
    if ($calls === []) {
        continue;
    }

    $fileNodes = $codeByFile[$relPath] ?? [];

    foreach ($calls as $call) {
        $viewName = $call['view'];
        if (str_contains($viewName, '::')) {
            [$ns, $name] = explode('::', $viewName, 2);
            $alt = 'vendor.' . $ns . '.' . str_replace('/', '.', $name);
            if (isset($blades[$alt])) {
                $viewName = $alt;
            }
        }

        if (!isset($blades[$viewName])) {
            $missingViews[$viewName] = ($missingViews[$viewName] ?? 0) + 1;
            continue;
        }

        $owner = resolveOwner($fileNodes, $call['line']);
        if ($owner === null) {
            continue;
        }

        $resolvedPhp++;
        $touchedSources[$owner['id']] = true;
        addEdge(
            $graph,
            $existing,
            $owner['id'],
            $blades[$viewName]['id'],
            'renders',
            $relPath,
            $call['line'],
            $ORIGIN,
            $addedRenders
        );
    }
}

// --- 2) Blade @extends / @include / <x-*> → extends/includes ---
foreach (filesUnder($root, 'resources/views', 'blade.php') as $relPath) {
    if (!preg_match('#resources/views/(.+)\\.blade\\.php$#', $relPath, $vm)) {
        continue;
    }
    $currentView = str_replace('/', '.', $vm[1]);
    if (!isset($blades[$currentView])) {
        continue;
    }

    $absolute = $root . DIRECTORY_SEPARATOR . str_replace('/', DIRECTORY_SEPARATOR, $relPath);
    $blade = @file_get_contents($absolute);
    if ($blade === false || $blade === '') {
        continue;
    }

    $sourceId = $blades[$currentView]['id'];
    foreach (extractBladeRelations($blade) as $rel) {
        $targetName = resolveBladeTarget($rel['view'], $relPath, $blades);
        if ($targetName === null) {
            $missingViews[$rel['view']] = ($missingViews[$rel['view']] ?? 0) + 1;
            continue;
        }

        $resolvedBlade++;
        $relation = $rel['relation']; // includes | extends
        $counter = 0;
        addEdge(
            $graph,
            $existing,
            $sourceId,
            $blades[$targetName]['id'],
            $relation,
            $relPath,
            $rel['line'],
            $ORIGIN,
            $counter
        );
        if ($counter > 0) {
            if ($relation === 'extends') {
                $addedExtends += $counter;
            } else {
                $addedIncludes += $counter;
            }
        }
    }
}

// Drop bogus INFERRED calls → .view() from methods we successfully linked.
$removedBogus = 0;
$idToLabel = [];
foreach ($graph['nodes'] as $node) {
    $idToLabel[(string) $node['id']] = (string) ($node['label'] ?? '');
}

$filtered = [];
foreach ($graph['links'] as $link) {
    $sourceId = (string) ($link['source'] ?? '');
    $targetId = (string) ($link['target'] ?? '');
    $relation = (string) ($link['relation'] ?? '');
    $confidence = (string) ($link['confidence'] ?? '');
    $targetLabel = $idToLabel[$targetId] ?? '';

    $isBogusViewCall = isset($touchedSources[$sourceId])
        && $relation === 'calls'
        && $confidence === 'INFERRED'
        && $targetLabel === '.view()';

    if ($isBogusViewCall) {
        $removedBogus++;
        continue;
    }
    $filtered[] = $link;
}
$graph['links'] = $filtered;

$json = json_encode($graph, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE);
if ($json === false) {
    fwrite(STDERR, "graphify-link-views: failed to encode graph.json\n");
    exit(1);
}

if (!isset($graph['graph']) || $graph['graph'] === [] || $graph['graph'] === null) {
    $json = preg_replace('/"graph"\s*:\s*\[\s*\]/', '"graph":{}', $json, 1) ?? $json;
}

file_put_contents($graphPath, $json . "\n");

$missingCount = count($missingViews);
arsort($missingViews);
$missingSample = array_slice($missingViews, 0, 15, true);

echo "graphify-link-views: renders +{$addedRenders} (php sites {$resolvedPhp})\n";
echo "graphify-link-views: includes +{$addedIncludes}, extends +{$addedExtends} (blade refs {$resolvedBlade})\n";
echo "graphify-link-views: removed {$removedBogus} bogus INFERRED calls→.view()\n";
echo "graphify-link-views: blade index=" . count($blades) . ", missing names={$missingCount}\n";
if ($missingSample !== []) {
    echo "graphify-link-views: top missing:\n";
    foreach ($missingSample as $name => $count) {
        echo "  - {$name} ({$count})\n";
    }
}

exit(0);
