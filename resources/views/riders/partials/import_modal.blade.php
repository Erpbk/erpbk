{{-- Master Import Riders modal (demo structure) --}}
<div class="rl-ov" id="impOv" hidden>
  <div class="rl-modal" role="dialog" aria-labelledby="impT">
    <div class="mh">
      <h3 id="impT">Import Riders</h3>
      <button type="button" class="x" data-close aria-label="Close">×</button>
    </div>
    <div class="mb" id="impBody">
      <label class="drop" id="drop" for="impFile">
        <svg width="22" height="22" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M12 19V5M6 11l6-6 6 6"/></svg>
        <b>Click or drop an Excel / CSV file here</b>
        <small>.xlsx, .csv or a file exported from this page</small>
      </label>
      <input type="file" id="impFile" accept=".xlsx,.csv,.xls" hidden>
      <ul class="rules" id="impRules">
        <li><b>Rider Id</b> column is required.</li>
        <li>If the Rider Id already exists, that rider is <b>updated</b> (only filled cells change; empty cells stay as they are).</li>
        <li>If the Rider Id is new, a <b>new rider</b> is created (Name required).</li>
        <li>Dates can be 02 Oct 2026, 2026-10-02, 02/10/2026 or Excel dates. List fields (Nationality, Fleet Supervisor, Branch, Designation) must match FleetValu names.</li>
        <li>Nothing is saved until you check the preview and press Import.</li>
      </ul>
    </div>
    <div class="mf">
      <span class="rl-note" id="impNote"></span>
      <div class="r" id="impBtns">
        <a class="rl-ghost" id="smpBtn" href="{{ route('riders.import.template', ['sample' => 1]) }}">Download sample file</a>
        <a class="rl-ghost" id="blkBtn" href="{{ route('riders.import.template') }}">Blank template</a>
        <button type="button" class="rl-ghost" data-close>Cancel</button>
      </div>
    </div>
  </div>
</div>
