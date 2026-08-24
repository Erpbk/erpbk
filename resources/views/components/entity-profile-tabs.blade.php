<div class="nav-align-top entity-profile-tabs mb-4" style="position: sticky; top: 0; z-index: 1000; width: 100%;">
    <div class="card" style="z-index: 1;">
        <div class="card-body p-2">
            <div class="d-flex justify-content-between align-items-center flex-wrap" style="gap: 0.5rem;">
                <div class="flex-grow-1" style="min-width: 0;">
                    <ul class="nav nav-pills flex-nowrap mb-0 overflow-hidden" id="mainNavigation" style="gap: 0.25rem;">
                        {{ $slot }}
                    </ul>
                </div>
                <div class="dropdown">
                    <button class="btn btn-outline-secondary rounded-pill p-2 waves-effect"
                            type="button" id="actiondropdown" data-bs-toggle="dropdown"
                            aria-haspopup="true" aria-expanded="false">
                        <i class="ti ti-dots icon-md"></i>
                    </button>
                    <div class="dropdown-menu dropdown-menu-end" aria-labelledby="actiondropdown" id="dropdownMenu">
                        <div id="overflowItems"></div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
