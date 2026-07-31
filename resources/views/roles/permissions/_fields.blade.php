<table class="table rfp-fields-table align-middle mb-0">
    <thead>
        <tr>
            <th>Field Label</th>
            <th>Field Name</th>
            <th class="text-center">Show in Form</th>
            <th class="text-center">Editable</th>
            <th class="text-center">Required</th>
        </tr>
    </thead>
    <tbody>
        @forelse ($rows as $row)
        @php
            $fieldDomId = 'rfp-' . $moduleId . '-' . preg_replace('/[^A-Za-z0-9_\-]/', '_', (string) $row['name']);
        @endphp
        <tr class="rfp-field-row" data-module-id="{{ $moduleId }}" data-field-name="{{ $row['name'] }}"
            data-field-label="{{ \Illuminate\Support\Str::lower($row['label']) }} {{ \Illuminate\Support\Str::lower($row['name']) }}">
            <td>
                <span class="fw-medium">{{ $row['label'] }}</span>
                @if ($row['type'] === 'custom')
                <span class="badge bg-label-info ms-1">Custom</span>
                @endif
            </td>
            <td><code class="text-muted">{{ $row['name'] }}</code></td>
            <td class="text-center">
                <div class="form-check form-switch d-inline-flex justify-content-center m-0">
                    <input type="checkbox" class="form-check-input rfp-field-visible" role="switch"
                        id="{{ $fieldDomId }}-visible"
                        {{ $row['visible'] ? 'checked' : '' }}>
                </div>
            </td>
            <td class="text-center">
                <div class="form-check form-switch d-inline-flex justify-content-center m-0">
                    <input type="checkbox" class="form-check-input rfp-field-editable" role="switch"
                        id="{{ $fieldDomId }}-editable"
                        {{ $row['editable'] ? 'checked' : '' }} {{ $row['visible'] ? '' : 'disabled' }}>
                </div>
            </td>
            <td class="text-center">
                <div class="form-check d-inline-flex justify-content-center m-0">
                    <input type="checkbox" class="form-check-input rfp-field-required"
                        id="{{ $fieldDomId }}-required"
                        {{ $row['required'] ? 'checked' : '' }} {{ $row['visible'] ? '' : 'disabled' }}>
                </div>
            </td>
        </tr>
        @empty
        <tr>
            <td colspan="5" class="text-center text-muted py-5">
                <i class="ti ti-database-off fs-2 d-block mb-2"></i>
                This module has no manageable fields.
            </td>
        </tr>
        @endforelse
    </tbody>
</table>
