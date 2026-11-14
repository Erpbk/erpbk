@php
  $demoMode = $demoMode ?? 'activities';
  $insightCards = $insightCards ?? [];
  $projects = $projects ?? collect();
  $riders = collect($riders ?? []);
  $alertTypes = $demoMode === 'live'
    ? [
        ['key' => 'labs', 'label' => 'Absent today', 'match' => ['labs']],
        ['key' => 'lhrs', 'label' => 'Low hours today', 'match' => ['low_hours', 'hrs']],
        ['key' => 'lord', 'label' => 'Low orders today', 'match' => ['low_orders', 'ord']],
      ]
    : ($demoMode === 'summary'
      ? [
          ['key' => 'off2', 'label' => 'Not working 2–3 days', 'match' => ['off2']],
          ['key' => 'off4', 'label' => 'Not working 4–5 days', 'match' => ['off4']],
          ['key' => 'off6', 'label' => 'Not working 6+ days', 'match' => ['off6']],
          ['key' => 'hrs', 'label' => 'Less than 10 hours', 'match' => ['low_hours', 'hrs']],
          ['key' => 'ord', 'label' => 'Less than 15 orders', 'match' => ['low_orders', 'ord']],
        ]
      : [
          ['key' => 'abs', 'label' => 'Absent yesterday', 'match' => ['abs']],
          ['key' => 'off', 'label' => 'Not working', 'match' => ['off2', 'off4', 'off6']],
          ['key' => 'hrs', 'label' => 'Duty hours not completed', 'match' => ['low_hours', 'hrs']],
          ['key' => 'ord', 'label' => 'Daily target not met', 'match' => ['low_orders', 'ord']],
        ]);

  $ridersById = $riders->keyBy('id');
  $typeCounts = [];
  $typeRiders = [];
  $referenceDate = null;

  foreach ($alertTypes as $type) {
    $ids = [];
    foreach ($insightCards as $card) {
      $key = $card['key'] ?? '';
      if (! in_array($key, $type['match'], true)) {
        continue;
      }
      if (! empty($card['reference_date'])) {
        $referenceDate = $card['reference_date'];
      }
      foreach ($card['rider_ids'] ?? [] as $id) {
        $ids[(int) $id] = true;
      }
    }
    $typeCounts[$type['key']] = count($ids);
    $rows = [];
    foreach (array_keys($ids) as $id) {
      $r = $ridersById->get($id);
      if (! $r) {
        continue;
      }
      $initials = collect(preg_split('/\s+/', trim((string) $r->name)))
        ->filter()
        ->take(2)
        ->map(fn ($w) => mb_strtoupper(mb_substr($w, 0, 1)))
        ->implode('');
      $imageName = trim((string) ($r->image_name ?? ''));
      $photo = ($imageName !== '' && ! in_array(strtolower($imageName), ['default.png', 'default.jpg'], true))
        ? (storage_url('profile/' . $imageName) ?: '')
        : '';
      $rows[] = [
        'id' => (int) $r->id,
        'code' => (string) ($r->rider_id ?? $r->id),
        'name' => (string) $r->name,
        'sup' => (string) ($r->fleet_supervisor ?? ''),
        'ini' => $initials !== '' ? $initials : 'R',
        'photo' => $photo,
      ];
    }
    usort($rows, fn ($a, $b) => strcasecmp($a['name'], $b['name']));
    $typeRiders[$type['key']] = $rows;
  }

  $allRidersPreview = $riders->map(function ($r) {
    $initials = collect(preg_split('/\s+/', trim((string) $r->name)))
      ->filter()
      ->take(2)
      ->map(fn ($w) => mb_strtoupper(mb_substr($w, 0, 1)))
      ->implode('');
    $imageName = trim((string) ($r->image_name ?? ''));
    $photo = ($imageName !== '' && ! in_array(strtolower($imageName), ['default.png', 'default.jpg'], true))
      ? (storage_url('profile/' . $imageName) ?: '')
      : '';

    return [
      'id' => (int) $r->id,
      'code' => (string) ($r->rider_id ?? $r->id),
      'name' => (string) $r->name,
      'sup' => (string) ($r->fleet_supervisor ?? ''),
      'ini' => $initials !== '' ? $initials : 'R',
      'photo' => $photo,
    ];
  })->sortBy('name', SORT_NATURAL | SORT_FLAG_CASE)->values()->all();

  if (! $referenceDate) {
    $referenceDate = now()->subDay()->toDateString();
  }

  $defaultTpl = "Hello {name} (ID: {id}),\nyou have not worked for {days} days (last worked: {last_date}).\nIf there is any problem, please contact {supervisor}.\n– Express Fast Delivery";

  $waPayload = [
    'types' => $typeRiders,
    'all' => $allRidersPreview,
    'date' => $referenceDate,
    'mode' => $demoMode,
  ];
@endphp

<div id="raWaOv" hidden></div>
<aside id="raWaDrawer" hidden aria-label="WhatsApp Alerts"
       data-wa-payload='@json($waPayload)'>
  <div class="hd">
    <h3>
      <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="#16a34a" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round"><path d="M21 11.5a8.4 8.4 0 0 1-12.6 7.3L3 20l1.3-5.1A8.5 8.5 0 1 1 21 11.5z"/></svg>
      <span id="raChTitle">WhatsApp Alerts</span>
    </h3>
    <div class="ra-chsw" id="raChSw">
      <button type="button" data-ch="wa" class="on">WhatsApp</button>
      <button type="button" data-ch="em">Email</button>
    </div>
    <span class="tag">Preview – no real messages are sent</span>
    <button type="button" class="x" id="raWaX" aria-label="Close">×</button>
  </div>

  <div class="ra-wa-types" id="raWaTypes">
    @foreach($alertTypes as $i => $type)
      <button type="button" class="{{ $i === 0 ? 'on' : '' }}" data-type="{{ $type['key'] }}">
        {{ $type['label'] }}
        <span data-cnt="{{ $type['key'] }}">{{ number_format($typeCounts[$type['key']] ?? 0) }}</span>
      </button>
    @endforeach
  </div>

  <div class="ra-wa-set">
    <label>Send to
      <select id="raWaMode">
        <option value="rider">Each rider (1-to-1)</option>
        <option value="group">WhatsApp group</option>
      </select>
    </label>
    <label id="raWaGWrap" hidden>Group <input type="text" id="raWaGroup" value="Express – Fleet Supervisors"></label>
    <label id="raEmToWrap" hidden>To <input type="email" id="raEmTo" value="fleet-supervisors@yourcompany.com"></label>
    <label><input type="checkbox" id="raWaAuto" checked> Auto-send daily at</label>
    <input type="time" id="raWaTime" value="09:00" aria-label="Auto-send time">
    <label class="ra-wa-datefld">
      <span>Alert date</span>
      <input type="date" id="raWaDate" value="{{ $referenceDate }}" aria-label="Alert date">
    </label>
    <label>Project
      <select id="raWaProj">
        <option value="">All Projects</option>
        @foreach($projects as $project)
          <option value="{{ $project->id }}">{{ $project->name }}</option>
        @endforeach
      </select>
    </label>
    <label>Alert when not working for <input type="number" id="raWaDays" min="1" max="7" value="2"> days or more</label>
    <label>Daily duty hours target <input type="number" id="raWaHrs" min="1" max="16" step="0.5" value="10"> hours</label>
    <label>Daily orders target <input type="number" id="raWaOrd" min="1" max="60" value="15"> orders</label>
    <label><input type="checkbox" id="raWaPhoto" checked> Attach rider photo</label>
    <label><input type="checkbox" id="raWaSup" checked> Copy to Fleet Supervisor</label>
  </div>

  <div class="ra-wa-body">
    <div class="ra-wa-list">
      <div class="lh">
        <input type="checkbox" id="raWaAll" checked aria-label="Select all">
        <span id="raWaLh">Preview list (no live send)</span>
      </div>
      <div id="raWaRows">
        <div class="ra-wa-empty">Loading riders…</div>
      </div>
    </div>
    <div class="ra-wa-side">
      <div id="raEmPrev" hidden>
        <div class="eh">
          <div class="sj" id="raEmSubjPrev">Subject preview</div>
          <div>To: <b id="raEmToPrev">fleet-supervisors@yourcompany.com</b></div>
        </div>
        <div class="eb" id="raEmBodyPrev">Email body preview will appear here.</div>
      </div>
      <div class="ra-wa-phone" id="raWaPhone">
        <div class="ra-wa-top">
          <div class="av" id="raWaPAv">RA</div>
          <div>
            <b id="raWaPName">—</b>
            <small id="raWaPSub">Rider · WhatsApp</small>
          </div>
        </div>
        <div class="ra-wa-chat" id="raWaChat">
          <span class="ra-wa-day">Today</span>
          <div class="ra-wa-stack" id="raWaMsgStack">
            <div class="ra-wa-msg" id="raWaMsgPreview" data-id="">
              <div class="ra-wa-photo" id="raWaPhotoAttach" hidden>
                <img id="raWaPhotoImg" alt="Rider photo" src="">
                <span>Rider photo</span>
              </div>
              <div class="txt" id="raWaMsgText">{{ $defaultTpl }}</div>
              <div class="meta"><span>Preview</span></div>
            </div>
          </div>
        </div>
      </div>
      <div class="ra-wa-tpl" id="raWaTplWrap">
        <label for="raWaTpl">Message template</label>
        <textarea id="raWaTpl">{{ $defaultTpl }}</textarea>
        <small>Tokens: <code>{name}</code> <code>{id}</code> <code>{mobile}</code> <code>{days}</code> <code>{last_date}</code> <code>{supervisor}</code> <code>{project}</code> <code>{hours}</code> <code>{orders}</code> <code>{target}</code> <code>{date}</code></small>
      </div>
    </div>
  </div>

  <div class="ra-wa-ft">
    <span class="note" id="raWaNote">Each rider gets a separate message on their own WhatsApp number.</span>
    <div class="ra-wa-ft-actions">
      <button type="button" class="ra-wa-btn ghost" id="raWaRunAuto">Run auto-send now (test)</button>
      <button type="button" class="ra-wa-btn primary" id="raWaSend">Send now</button>
    </div>
  </div>
</aside>

<div class="ra-toast" id="raToast"></div>
