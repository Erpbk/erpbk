{{--
  Standardized soft-delete confirm (Swal loader + server message / queued title).

  @param string $entityName        Human label, e.g. "Fuel Card"
  @param string|null $confirmText   Optional confirm body
  @param string $method             HTTP method: DELETE (default) or GET
  @param bool $askReason            Show optional reason textarea
  @param string $functionName       JS function name (default confirmDelete)
  @param string|null $fallbackMessage  Success HTML if response has no message
  @param string|null $reasonField   POST field for reason (default delete_reason)
--}}
@php
    $entityName = $entityName ?? 'Record';
    $confirmText = $confirmText
        ?? ('This will submit a delete request or move the ' . strtolower($entityName) . ' to the Recycle Bin.');
    $method = strtoupper($method ?? 'DELETE');
    $askReason = (bool) ($askReason ?? false);
    $functionName = $functionName ?? 'confirmDelete';
    $fallbackMessage = $fallbackMessage ?? ($entityName . ' moved to Recycle Bin.');
    $reasonField = $reasonField ?? 'delete_reason';
    $failFallback = 'Failed to delete ' . $entityName . '.';
@endphp
<script>
(function () {
    if (typeof window.{{ $functionName }} === 'function' && window.{{ $functionName }}.__erpbkDeleteConfirm) {
        return;
    }

    function extractDeleteError(xhr) {
        var data = xhr && xhr.responseJSON ? xhr.responseJSON : null;
        if (!data) {
            return (xhr && xhr.statusText) || @json($failFallback);
        }
        if (data.message) {
            return data.message;
        }
        if (data.errors) {
            if (typeof data.errors === 'string') {
                return data.errors;
            }
            if (data.errors.error) {
                return Array.isArray(data.errors.error) ? data.errors.error.join('<br>') : data.errors.error;
            }
            try {
                return Object.values(data.errors).flat().join('<br>');
            } catch (e) {
                return @json($failFallback);
            }
        }
        return @json($failFallback);
    }

    window.{{ $functionName }} = function (url) {
        var swalOptions = {
            title: 'Are you sure?',
            text: @json($confirmText),
            icon: 'warning',
            showCancelButton: true,
            confirmButtonColor: '#3085d6',
            cancelButtonColor: '#d33',
            confirmButtonText: 'Yes, delete it!',
            showLoaderOnConfirm: true,
            allowOutsideClick: function () { return !Swal.isLoading(); },
            preConfirm: function (reason) {
                var data = { _token: @json(csrf_token()) };
                @if($askReason)
                data[@json($reasonField)] = reason || '';
                @endif

                return $.ajax({
                    url: url,
                    type: @json($method),
                    data: data,
                    headers: {
                        'Accept': 'application/json',
                        'X-Requested-With': 'XMLHttpRequest'
                    }
                }).catch(function (xhr) {
                    Swal.showValidationMessage(extractDeleteError(xhr));
                });
            }
        };

        @if($askReason)
        swalOptions.input = 'textarea';
        swalOptions.inputPlaceholder = 'Reason (optional)';
        @endif

        Swal.fire(swalOptions).then(function (result) {
            if (!result.isConfirmed || !result.value) {
                return;
            }

            var response = result.value || {};
            var queued = !!(response.queued);
            Swal.fire({
                icon: queued ? 'warning' : 'success',
                title: queued ? 'Delete requested' : 'Deleted!',
                html: response.message || @json($fallbackMessage),
                confirmButtonText: 'OK'
            }).then(function () {
                location.reload();
            });
        });
    };

    window.{{ $functionName }}.__erpbkDeleteConfirm = true;
})();
</script>
