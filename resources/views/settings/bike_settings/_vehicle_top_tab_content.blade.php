@php
$bikeTopCategories = $bikeTopCategories ?? collect();
$bikeTopSelectableColumns = $bikeTopSelectableColumns ?? [];
$bikeTopUserVisibleOptionIds = $bikeTopUserVisibleOptionIds ?? null;
@endphp

<div class="d-flex align-items-center justify-content-between flex-wrap gap-2 mb-3">
  <p class="text-muted small mb-0">Create a Vehicle Top category linked to a column on <code>bikes</code>, then add option values. Cards can filter the Vehicles list; each user can choose which cards appear for their account.</p>
  <button type="button" class="btn btn-primary btn-sm" data-bs-toggle="modal" data-bs-target="#addBikeTopCategoryModal">
    <i class="ti ti-plus me-1"></i> Add Category
  </button>
</div>

<div id="bikeTopAccordionContainer">
  @include('settings.bike_settings._bike_top_accordion', ['bikeTopCategories' => $bikeTopCategories])
</div>

<div class="card border mt-4">
  <div class="card-body">
    <h6 class="mb-1">My Vehicles top cards</h6>
    <p class="text-muted small mb-3">Choose which option cards appear at the top of your Vehicles page. When no boxes are selected and you save, all top-bar options are shown. Select specific options to limit what you see.</p>
    @php
    $bikeTopOptionCount = 0;
    foreach ($bikeTopCategories as $__c) {
        $bikeTopOptionCount += $__c->options->count();
    }
    @endphp
    @if($bikeTopCategories->isEmpty() || $bikeTopOptionCount === 0)
    <p class="text-muted small mb-0">Add categories and options above to customize your personal top cards.</p>
    @else
    <form id="bikeTopUserPrefsForm" class="row g-2 align-items-end">
      @csrf
      <div class="col-12">
        <div class="row g-2">
          @foreach($bikeTopCategories as $cat)
          @foreach($cat->options as $opt)
          @php
          $selectedPref = $bikeTopUserVisibleOptionIds;
          if (is_array($selectedPref) && count($selectedPref) > 0) {
              $prefChecked = in_array((int) $opt->id, array_map('intval', $selectedPref), true);
          } else {
              $prefChecked = true;
          }
          $optPrefLabel = \App\Models\BikeCustomField::displayLabelForFixedFieldValue($cat->bike_column ?? null, (string) $opt->name);
          @endphp
          <div class="col-md-4 col-lg-3">
            <div class="form-check">
              <input class="form-check-input bike-top-user-pref-option" type="checkbox" name="visible_option_ids[]" value="{{ (int) $opt->id }}" id="bikeTopPref{{ $opt->id }}" {{ $prefChecked ? 'checked' : '' }}>
              <label class="form-check-label small" for="bikeTopPref{{ $opt->id }}">{{ $optPrefLabel }} <span class="text-muted">({{ $cat->name }})</span></label>
            </div>
          </div>
          @endforeach
          @endforeach
        </div>
      </div>
      <div class="col-12 mt-2">
        <button type="submit" class="btn btn-sm btn-primary" id="bikeTopUserPrefsSaveBtn">Save my cards</button>
        <button type="button" class="btn btn-sm btn-outline-secondary" id="bikeTopUserPrefsResetBtn">Show all options</button>
      </div>
    </form>
    @endif
  </div>
</div>

@include('settings.bike_settings._bike_top_modals')
