@php
    $ncAll = $notificationChannels ?? \App\Services\Notifications\CompanyNotificationChannels::allChannels();
    $ncLabels = $notificationChannelLabels ?? [
        'in_app' => __('In-App'),
        'email' => __('Email'),
    ];
    $ncEnabled = \App\Services\Notifications\CompanyNotificationChannels::enabledFromSettings(
        $company->notification_channels_settings ?? null
    );
    $modalId = 'channelsModal'.$company->id;
@endphp
<div class="modal fade" id="{{ $modalId }}" tabindex="-1" aria-labelledby="{{ $modalId }}Label" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content">
            <form action="{{ route('admin.companies.notification-channels.update', $company) }}" method="post">
                @csrf
                @method('PUT')
                <div class="modal-header">
                    <h5 class="modal-title" id="{{ $modalId }}Label">
                        {{ __('Notification channels') }} — {{ $company->name }}
                    </h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="{{ __('Close') }}"></button>
                </div>
                <div class="modal-body">
                    <p class="text-muted small mb-3">
                        {{ __('Only enabled channels appear when the company configures notification recipients.') }}
                    </p>
                    <div class="d-flex flex-column gap-2">
                        @foreach($ncAll as $channel)
                            <label class="border rounded-3 p-3 d-flex align-items-center justify-content-between gap-3 mb-0"
                                   style="cursor: pointer;">
                                <span>
                                    <span class="fw-medium d-block">{{ $ncLabels[$channel] ?? $channel }}</span>
                                    <span class="text-muted small"><code>{{ $channel }}</code></span>
                                </span>
                                <input class="form-check-input m-0"
                                       type="checkbox"
                                       role="switch"
                                       name="enabled[]"
                                       value="{{ $channel }}"
                                       @checked(in_array($channel, $ncEnabled, true))>
                            </label>
                        @endforeach
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">{{ __('Cancel') }}</button>
                    <button type="submit" class="btn btn-primary">{{ __('Save channels') }}</button>
                </div>
            </form>
        </div>
    </div>
</div>
