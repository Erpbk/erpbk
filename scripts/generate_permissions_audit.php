<?php

/**
 * Scans the ERPBK codebase for permission usage and generates a PDF audit report.
 * Run: php scripts/generate_permissions_audit.php
 */

require __DIR__ . '/../vendor/autoload.php';

use Dompdf\Dompdf;
use Dompdf\Options;

$basePath = realpath(__DIR__ . '/..');

// ── 1. Collect all defined tenant permissions from config ──────────────────
$tenantConfig = require $basePath . '/config/tenant_module_permissions.php';
$definedPermissions = [];
$moduleMap = [];

$slugify = static fn (string $name): string => str_replace(' ', '_', strtolower(trim($name)));

foreach ($tenantConfig['modules'] as $mod) {
    $slug = $mod['slug'] ?? $slugify($mod['parent']);
    $parent = $mod['parent'];
    $submodules = array_values(array_filter(array_map('trim', $mod['submodules'] ?? [])));

    if ($submodules !== []) {
        foreach ($submodules as $sub) {
            $subSlug = $slugify($sub);
            foreach (['view', 'create', 'edit', 'delete'] as $action) {
                $name = "{$slug}_{$subSlug}_{$action}";
                $definedPermissions[$name] = $parent;
                $moduleMap[$name] = $parent;
            }
        }
    } else {
        foreach (['view', 'create', 'edit', 'delete'] as $action) {
            $name = "{$slug}_{$action}";
            $definedPermissions[$name] = $parent;
            $moduleMap[$name] = $parent;
        }
        foreach ($mod['extras'] ?? [] as $extra) {
            $extra = trim((string) $extra);
            if ($extra === '') {
                continue;
            }
            // Full name if already prefixed; otherwise suffix via PermissionTreeBuilder rules
            $extraName = str_starts_with($extra, $slug . '_')
                ? $extra
                : $slug . '_' . $slugify($extra);
            $definedPermissions[$extraName] = $parent;
            $moduleMap[$extraName] = $parent;
        }
    }
}
foreach ($tenantConfig['additional_permissions'] ?? [] as $group) {
    $parent = $group['parent'] ?? 'Additional';
    foreach ($group['permissions'] ?? [] as $perm) {
        $definedPermissions[$perm] = $parent;
        $moduleMap[$perm] = $parent;
    }
}

// Activity log seeder extras
$definedPermissions['activity_logs_view'] = 'Activity Logs';
$definedPermissions['activity_logs_delete'] = 'Activity Logs';
$definedPermissions['activity_logs_export'] = 'Activity Logs';

// Admin panel permissions
$adminPermissions = [
    'companies_view', 'companies_approve', 'companies_reject',
    'blogs_view', 'blogs_create', 'blogs_edit', 'blogs_delete',
    'testimonials_view', 'testimonials_create', 'testimonials_edit', 'testimonials_delete',
    'privacy_policy_view', 'privacy_policy_edit', 'terms_conditions_view', 'terms_conditions_edit',
    'users_view', 'users_edit',
];

// ── 2. Scan directories for permission references ────────────────────────
$scanDirs = [
    $basePath . '/app',
    $basePath . '/resources/views',
    $basePath . '/routes',
    $basePath . '/config',
    $basePath . '/public/js',
];

$patterns = [
    'hasPermissionTo'   => "/hasPermissionTo\(['\"]([a-z0-9_]+)['\"]\)/",
    'can_blade'         => "/@can\(['\"]([a-z0-9_]+)['\"]\)/",
    'cannot_blade'      => "/@cannot\(['\"]([a-z0-9_]+)['\"]\)/",
    'can_method'        => "/->can\(['\"]([a-z0-9_]+)['\"]\)/",
    'gate_allows'       => "/Gate::allows\(['\"]([a-z0-9_]+)['\"]\)/",
    'middleware_perm'   => "/middleware\('permission:([a-z0-9_]+)'\)/",
    'admin_middleware'  => "/middleware\('admin\.permission:([a-z0-9_]+)'\)/",
    'permission_config' => "/'permission'\s*=>\s*'([a-z0-9_]+)'/",
    'permission_array'  => "/'permission'\s*=>\s*\[([^\]]+)\]/",
];

$occurrences = []; // permission => [ [file, line, type, context] ]
$usedPermissions = [];

$iterator = function (string $dir) use (&$iterator, $patterns, &$occurrences, &$usedPermissions, $basePath) {
    if (!is_dir($dir)) {
        return;
    }
    foreach (scandir($dir) as $entry) {
        if ($entry === '.' || $entry === '..') {
            continue;
        }
        $path = $dir . DIRECTORY_SEPARATOR . $entry;
        if (is_dir($path)) {
            if (in_array(basename($path), ['vendor', 'node_modules', 'storage', 'bootstrap', 'cache'], true)) {
                continue;
            }
            $iterator($path);
            continue;
        }
        $ext = pathinfo($path, PATHINFO_EXTENSION);
        if (!in_array($ext, ['php', 'blade.php', 'js'], true)) {
            continue;
        }
        $lines = file($path, FILE_IGNORE_NEW_LINES);
        if ($lines === false) {
            continue;
        }
        $relPath = str_replace('\\', '/', substr($path, strlen($basePath) + 1));

        foreach ($lines as $num => $line) {
            $lineNum = $num + 1;
            foreach ($patterns as $type => $regex) {
                if ($type === 'permission_array') {
                    if (preg_match($regex, $line, $m)) {
                        preg_match_all("/'([a-z0-9_]+)'/", $m[1], $perms);
                        foreach ($perms[1] as $perm) {
                            $usedPermissions[$perm] = true;
                            $occurrences[$perm][] = [
                                'file' => $relPath,
                                'line' => $lineNum,
                                'type' => 'config_permission_array',
                                'context' => trim($line),
                            ];
                        }
                    }
                    continue;
                }
                if (preg_match_all($regex, $line, $matches)) {
                    foreach ($matches[1] as $perm) {
                        $usedPermissions[$perm] = true;
                        $occurrences[$perm][] = [
                            'file' => $relPath,
                            'line' => $lineNum,
                            'type' => $type,
                            'context' => trim(substr($line, 0, 120)),
                        ];
                    }
                }
            }
        }
    }
};

foreach ($scanDirs as $dir) {
    $iterator($dir);
}

// ── 3. Identify controllers with/without permission checks ───────────────
$controllerDir = $basePath . '/app/Http/Controllers';
$controllersWithChecks = [];
$controllersWithoutChecks = [];

foreach (glob($controllerDir . '/*.php') as $file) {
    $name = basename($file, '.php');
    if (in_array($name, ['Controller'], true)) {
        continue;
    }
    $content = file_get_contents($file);
    $hasCheck = (bool) preg_match(
        '/hasPermissionTo|middleware\([\'"]permission:|Gate::allows|->can\(/',
        $content
    );
    if ($hasCheck) {
        $controllersWithChecks[] = $name;
    } else {
        $controllersWithoutChecks[] = $name;
    }
}

// Also scan Admin controllers
foreach (glob($controllerDir . '/Admin/*.php') as $file) {
    $name = 'Admin\\' . basename($file, '.php');
    $content = file_get_contents($file);
    $hasCheck = (bool) preg_match(
        '/hasPermissionTo|middleware\([\'"]permission:|hasRole|Gate::allows|admin\.permission/',
        $content
    );
    if ($hasCheck) {
        $controllersWithChecks[] = $name;
    } else {
        $controllersWithoutChecks[] = $name;
    }
}
sort($controllersWithChecks);
sort($controllersWithoutChecks);

// ── 4. Gap analysis ──────────────────────────────────────────────────────
$definedSet = array_keys($definedPermissions);
$usedSet = array_keys($usedPermissions);

// Tenant permissions defined but never referenced in code
$definedNotUsed = array_diff($definedSet, $usedSet);
sort($definedNotUsed);

// Permissions used in code but not in tenant config (may be admin or typos)
$usedNotDefined = array_diff($usedSet, $definedSet, $adminPermissions);
sort($usedNotDefined);

// Known typos / inconsistencies
$typos = [];
if (isset($occurrences['penality_view'])) {
    $typos[] = 'penality_view (typo — config defines penalty_view and penality_view as extras)';
}
if (isset($occurrences['bike_maintenance_create']) && !isset($definedPermissions['bike_maintenance_create'])) {
    $typos[] = 'bike_maintenance_create used in views but not defined in tenant_module_permissions (use maintenance_create)';
}
if (isset($occurrences['visaloan_create']) && !isset($definedPermissions['visaloan_create'])) {
    $typos[] = 'visaloan_create used in views but not defined (use installment_create?)';
}
if (isset($occurrences['ticket_edit'])) {
    $typos[] = 'ticket_edit used in users/show.blade.php — not defined in permissions config';
}
if (isset($occurrences['receipt_delete'])) {
    $typos[] = 'receipt_delete used but not in tenant_module_permissions';
}
if (isset($occurrences['billing_invoice_edit']) && !isset($definedPermissions['billing_invoice_edit'])) {
    $typos[] = 'billing_invoice_edit used but only billing_invoice_view/create defined in config';
}
if (isset($occurrences['payments_create']) && !isset($definedPermissions['payments_create'])) {
    $typos[] = 'payments_create used in views but config defines payment_create';
}

// Admin middleware is commented out
$adminMiddlewareDisabled = true;

// ── 5. Build HTML report ─────────────────────────────────────────────────
$date = date('F j, Y');
$totalDefined = count($definedSet);
$totalUsed = count($usedSet);
$totalOccurrences = array_sum(array_map('count', $occurrences));
$ctrlWithCount = count($controllersWithChecks);
$ctrlWithoutCount = count($controllersWithoutChecks);
$ctrlTotal = $ctrlWithCount + $ctrlWithoutCount;
$adminPermCount = count($adminPermissions);

$html = <<<HTML
<!DOCTYPE html>
<html>
<head>
<meta charset="utf-8">
<style>
  body { font-family: DejaVu Sans, sans-serif; font-size: 9px; color: #222; line-height: 1.4; }
  h1 { font-size: 18px; color: #1a365d; border-bottom: 2px solid #1a365d; padding-bottom: 6px; }
  h2 { font-size: 13px; color: #2c5282; margin-top: 18px; border-bottom: 1px solid #bee3f8; padding-bottom: 3px; }
  h3 { font-size: 11px; color: #2d3748; margin-top: 12px; }
  table { width: 100%; border-collapse: collapse; margin: 8px 0; }
  th { background: #ebf8ff; text-align: left; padding: 4px 6px; border: 1px solid #bee3f8; font-size: 8px; }
  td { padding: 3px 6px; border: 1px solid #e2e8f0; vertical-align: top; font-size: 8px; }
  tr:nth-child(even) td { background: #f7fafc; }
  .badge { display: inline-block; padding: 1px 5px; border-radius: 3px; font-size: 7px; font-weight: bold; }
  .badge-ctrl { background: #c6f6d5; color: #22543d; }
  .badge-view { background: #bee3f8; color: #2a4365; }
  .badge-route { background: #faf089; color: #744210; }
  .badge-config { background: #e9d8fd; color: #44337a; }
  .badge-warn { background: #fed7d7; color: #742a2a; }
  .summary-box { background: #f0fff4; border: 1px solid #9ae6b4; padding: 8px 12px; margin: 10px 0; border-radius: 4px; }
  .warn-box { background: #fff5f5; border: 1px solid #fc8181; padding: 8px 12px; margin: 10px 0; border-radius: 4px; }
  .info-box { background: #ebf8ff; border: 1px solid #90cdf4; padding: 8px 12px; margin: 10px 0; border-radius: 4px; }
  ul { margin: 4px 0; padding-left: 16px; }
  li { margin: 2px 0; }
  .page-break { page-break-before: always; }
  code { font-family: DejaVu Sans Mono, monospace; font-size: 8px; background: #edf2f7; padding: 0 2px; }
  .mono { font-family: DejaVu Sans Mono, monospace; font-size: 7px; }
</style>
</head>
<body>

<h1>ERPBK Permissions Audit Report</h1>
<p>Generated: {$date} &nbsp;|&nbsp; Project: erpbk Laravel ERP</p>

<div class="summary-box">
  <strong>Summary</strong><br>
  Defined tenant permissions (config): <strong>{$totalDefined}</strong><br>
  Unique permissions referenced in code: <strong>{$totalUsed}</strong><br>
  Total permission check occurrences: <strong>{$totalOccurrences}</strong><br>
  Controllers with permission checks: <strong>{$ctrlWithCount}</strong><br>
  Controllers without permission checks: <strong>{$ctrlWithoutCount}</strong><br>
  Admin panel route permissions: <strong>{$adminPermCount}</strong>
</div>

<div class="info-box">
  <strong>Permission System Architecture</strong>
  <ul>
    <li><strong>Tenant ERP</strong> — Spatie Laravel Permission (<code>permission</code> middleware, <code>hasPermissionTo()</code>, <code>@can</code> blade directives). Permissions defined in <code>config/tenant_module_permissions.php</code> and synced via <code>TenantModulePermissionsSync</code>.</li>
    <li><strong>Gate bypass</strong> — <code>AppServiceProvider::Gate::before</code> grants all abilities to users with <em>Administrator</em> or <em>Super Admin</em> roles.</li>
    <li><strong>Admin panel</strong> — Separate guard; routes use <code>admin.permission:*</code> middleware, but <code>AdminPermissionMiddleware</code> check is <strong>currently commented out</strong> (all admin routes pass through).</li>
    <li><strong>UI-only checks</strong> — Many blade <code>@can</code> directives hide buttons/links; backend controller checks are inconsistent across modules.</li>
  </ul>
</div>

<h2>1. Critical Gaps &amp; Issues</h2>
<div class="warn-box">
  <strong>Security / Consistency Issues Found:</strong>
  <ul>
    <li><strong>AdminPermissionMiddleware disabled</strong> — Permission enforcement on admin routes is bypassed (check commented out in <code>app/Http/Middleware/AdminPermissionMiddleware.php</code>).</li>
    <li><strong>Most controllers lack backend permission checks</strong> — Only ~{$ctrlWithCount} of {$ctrlTotal} controllers enforce permissions; others rely solely on UI <code>@can</code> directives (bypassable via direct URL/API).</li>
    <li><strong>ActivityLogController</strong> — Only controller using Spatie <code>permission:</code> middleware besides admin routes.</li>
HTML;

foreach ($typos as $t) {
    $html .= "<li><strong>Possible typo/mismatch:</strong> {$t}</li>";
}

$html .= <<<HTML
  </ul>
</div>

<h2>2. Controllers Without Permission Checks</h2>
<p>These controllers handle authenticated routes but contain no <code>hasPermissionTo</code>, <code>Gate::allows</code>, <code>->can()</code>, or <code>permission</code> middleware. They should likely enforce view/create/edit/delete permissions.</p>
<table>
<tr><th>#</th><th>Controller</th><th>Suggested Permission Prefix</th></tr>
HTML;

$suggestions = [
    'AttendanceController' => 'attendance_',
    'ChequesController' => 'cheques_',
    'CustomerInvoicesController' => 'customer_invoice_',
    'DepartmentsController' => 'department_',
    'DropdownsController' => 'dropdown_',
    'EmployeeController' => 'employees_',
    'EmployeeInvoicesController' => 'employeeinvoice_',
    'ExpenseController' => 'expenses_',
    'PaymentController' => 'payments_',
    'ReceiptController' => 'receipt_',
    'RecruitersController' => 'recruiter_',
    'ReportController' => 'dashboard_',
    'RidersController' => 'rider_',
    'RiderAttendanceController' => 'attendance_',
    'SalikController' => 'salik_',
    'SupplierInvoicesController' => 'supplier_',
    'TrashController' => 'trash_',
    'FilesController' => 'documents_',
    'VendorsController' => 'vendor_',
    'BranchesController' => 'branches_',
    'BikeMaintenanceController' => 'maintenance_',
    'LegalCaseController' => 'legalcase_',
    'LedgerController' => 'accounts_ledger_view / accounts_coa_',
    'PermissionsController' => 'permissions_view / role_',
    'RolesController' => 'role_ (partial — only index)',
];

$i = 1;
foreach ($controllersWithoutChecks as $ctrl) {
    $short = str_replace('Admin\\', '', $ctrl);
    $suggest = $suggestions[$short] ?? '(derive from module slug)';
    $html .= "<tr><td>{$i}</td><td><code>{$ctrl}</code></td><td>{$suggest}</td></tr>";
    $i++;
}

$html .= '</table><div class="page-break"></div>';

// ── Section 3: All occurrences grouped by permission ─────────────────────
$html .= '<h2>3. All Permission Occurrences (by permission name)</h2>';
$html .= '<p>Every unique permission name found in code, with file locations.</p>';

ksort($occurrences);
$permNum = 1;
foreach ($occurrences as $perm => $hits) {
    $module = $moduleMap[$perm] ?? (in_array($perm, $adminPermissions) ? 'Admin Panel' : 'undefined');
    $moduleLabel = is_array($module) ? implode(', ', $module) : (string) $module;
    $inConfig = isset($definedPermissions[$perm]) || in_array($perm, $adminPermissions) ? '' : ' <span class="badge badge-warn">NOT IN CONFIG</span>';
    $html .= "<h3>{$permNum}. <code>{$perm}</code> ({$moduleLabel}){$inConfig} — " . count($hits) . " occurrence(s)</h3>";
    $html .= '<table><tr><th>File</th><th>Line</th><th>Check Type</th><th>Context</th></tr>';
    foreach ($hits as $hit) {
        $typeBadge = match (true) {
            str_contains($hit['type'], 'middleware') => 'badge-route',
            str_contains($hit['type'], 'blade') => 'badge-view',
            str_contains($hit['type'], 'config') => 'badge-config',
            default => 'badge-ctrl',
        };
        $ctx = htmlspecialchars($hit['context']);
        $html .= "<tr><td class=\"mono\">{$hit['file']}</td><td>{$hit['line']}</td>";
        $html .= "<td><span class=\"badge {$typeBadge}\">{$hit['type']}</span></td>";
        $html .= "<td class=\"mono\">{$ctx}</td></tr>";
    }
    $html .= '</table>';
    $permNum++;
    if ($permNum % 15 === 0) {
        $html .= '<div class="page-break"></div>';
    }
}

$html .= '<div class="page-break"></div>';

// ── Section 4: Defined but unused ────────────────────────────────────────
$html .= '<h2>4. Defined Permissions Not Referenced in Code</h2>';
$html .= '<p>These permissions exist in <code>config/tenant_module_permissions.php</code> but have no <code>@can</code>, <code>hasPermissionTo</code>, or middleware reference. They may still be assigned to roles but never enforced.</p>';
$html .= '<table><tr><th>Permission</th><th>Module</th></tr>';
foreach ($definedNotUsed as $perm) {
    $mod = $definedPermissions[$perm] ?? '';
    $html .= "<tr><td><code>{$perm}</code></td><td>{$mod}</td></tr>";
}
$html .= '</table>';

// ── Section 5: Used but not defined ───────────────────────────────────────
$html .= '<h2>5. Used in Code But Not in Tenant Config</h2>';
$html .= '<table><tr><th>Permission</th><th>Occurrences</th><th>Notes</th></tr>';
$notes = [
    'bike_maintenance_create' => 'Should be maintenance_create',
    'visaloan_create' => 'Likely should be installment_create',
    'ticket_edit' => 'Undefined — possibly legacy',
    'receipt_delete' => 'Missing from Receipt module extras',
    'billing_invoice_edit' => 'Missing from Leasing module extras',
    'payments_create' => 'Config has payment_create (singular)',
    'penality_view' => 'Typo variant — also has penalty_* permissions',
    'penality_create' => 'Typo variant in config extras',
    'activity_view' => 'Rider activity — in Rider extras',
    'bike_rent_edit' => 'In config extras',
    'expense_voucher_create' => 'In config extras',
    'permissions_view' => 'In config Roles extras',
    'gn_settings' => 'In config System extras',
    'agreements_view' => 'CRUD from agreements slug',
    'agreements_edit' => 'CRUD from agreements slug',
    'agreements_generate' => 'In config extras',
    'agreements_manage_templates' => 'In config extras',
];
foreach ($usedNotDefined as $perm) {
    $count = count($occurrences[$perm] ?? []);
    $note = $notes[$perm] ?? 'Review — may need adding to config or is admin-only';
    $html .= "<tr><td><code>{$perm}</code></td><td>{$count}</td><td>{$note}</td></tr>";
}
$html .= '</table>';

// ── Section 6: Admin permissions ─────────────────────────────────────────
$html .= '<h2>6. Admin Panel Permissions (routes/web.php)</h2>';
$html .= '<table><tr><th>Permission</th><th>Route Usage</th></tr>';
foreach ($adminPermissions as $ap) {
    $hits = $occurrences[$ap] ?? [];
    $routes = implode(', ', array_map(fn ($h) => basename($h['file']) . ':' . $h['line'], $hits));
    $html .= "<tr><td><code>{$ap}</code></td><td class=\"mono\">" . ($routes ?: 'defined but no route middleware found') . "</td></tr>";
}
$html .= '</table>';

// ── Section 7: Controllers with checks ────────────────────────────────────
$html .= '<h2>7. Controllers With Permission Checks</h2><ul>';
foreach ($controllersWithChecks as $c) {
    $html .= "<li><code>{$c}</code></li>";
}
$html .= '</ul>';

$html .= <<<HTML
<h2>8. Recommendations</h2>
<ol>
  <li><strong>Enable AdminPermissionMiddleware</strong> — Uncomment the permission check in <code>AdminPermissionMiddleware.php</code>.</li>
  <li><strong>Add controller-level checks</strong> — Follow the pattern used in <code>LoansController</code> and <code>PassportHandoverController</code> for all CRUD controllers listed in Section 2.</li>
  <li><strong>Standardize naming</strong> — Fix <code>penality_*</code> vs <code>penalty_*</code>, <code>payment_create</code> vs <code>payments_create</code>, and add missing extras (<code>receipt_delete</code>, <code>billing_invoice_edit</code>).</li>
  <li><strong>Add route middleware</strong> — Consider applying <code>permission:*</code> middleware at route group level for each module (like ActivityLogController).</li>
  <li><strong>Audit UI-only modules</strong> — Riders, Employees, Expenses, Cheques, Vouchers, and Payments have blade checks but weak/no controller enforcement.</li>
  <li><strong>Review defined-but-unused permissions</strong> — Section 4 lists permissions that may be dead weight or need enforcement added.</li>
</ol>

<p style="margin-top:20px;color:#718096;font-size:8px;">Report generated by scripts/generate_permissions_audit.php</p>
</body></html>
HTML;

// ── 6. Generate PDF ──────────────────────────────────────────────────────
$options = new Options();
$options->set('isHtml5ParserEnabled', true);
$options->set('isRemoteEnabled', false);
$options->set('defaultFont', 'DejaVu Sans');

$dompdf = new Dompdf($options);
$dompdf->loadHtml($html);
$dompdf->setPaper('A4', 'portrait');
$dompdf->render();

$outputPath = $basePath . '/storage/app/permissions_audit_report.pdf';
$desktopPath = $basePath . '/permissions_audit_report.pdf';
file_put_contents($outputPath, $dompdf->output());
copy($outputPath, $desktopPath);

echo "PDF generated:\n  - {$outputPath}\n  - {$desktopPath}\n";
echo "Defined permissions: {$totalDefined}\n";
echo "Used permissions: {$totalUsed}\n";
echo "Total occurrences: {$totalOccurrences}\n";
echo "Controllers without checks: " . count($controllersWithoutChecks) . "\n";
