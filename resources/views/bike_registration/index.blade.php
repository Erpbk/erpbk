@extends($bikes ?? null ? 'bikes.view' : 'layouts.app')
@section('title','Bike Registration')
@section($bikes ?? null ? 'page_content' : 'content')
<div class="content">
  @include('flash::message')
  @include('bike_registration.partials.expenses_panel', [
      'account' => $account,
      'data' => $data,
      'expenseTotals' => $expenseTotals,
      'embeddedInModal' => false,
  ])
</div>
@endsection
@section('page-script')
@include('bike_registration.scripts.table_inline_edit')
<script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
<script type="text/javascript">
  function confirmDelete(url) {
    Swal.fire({
      title: 'Are you sure?',
      text: "You won't be able to revert this!",
      icon: 'warning',
      showCancelButton: true,
      confirmButtonColor: '#3085d6',
      cancelButtonColor: '#d33',
      confirmButtonText: 'Yes, delete it!'
    }).then((result) => {
      if (result.isConfirmed) {
        window.location.href = url;
      }
    })
  }
  $(document).ready(function() {
    $('#payment_status').select2({
      dropdownParent: $('#searchTopbody'),
      placeholder: "Filter By Payment Status",
      allowClear: true
    });
    $('#registration_status').select2({
      dropdownParent: $('#searchTopbody'),
      placeholder: "Filter By Registration Status",
      allowClear: true
    });
    $('#bike_id').select2({
      dropdownParent: $('#searchTopbody'),
      placeholder: "Filter By Bike Plate",
      allowClear: true
    });
  });
</script>

<script type="text/javascript">
  $(document).ready(function() {
    $('#filterForm').on('submit', function(e) {
      e.preventDefault();

      $('#loading-overlay').show();
      $('#searchModal').modal('hide');

      const loaderStartTime = Date.now();

      let filteredFields = $(this).serializeArray().filter(field => field.name !== '_token' && field.value.trim() !== '');
      let formData = $.param(filteredFields);

      $.ajax({
        url: "{{ route('BikeRegistration.generatentries', $account->id) }}",
        type: "GET",
        data: formData,
        success: function(data) {
          $('#table-data').html(data.tableData);
          if (data.expenseTotals) {
            $('#br-total-unpaid').text('{{ \App\Helpers\Currency::symbol() }} ' + parseFloat(data.expenseTotals.totalUnpaid || 0).toLocaleString(undefined, {minimumFractionDigits: 2, maximumFractionDigits: 2}));
            $('#br-total-paid').text('{{ \App\Helpers\Currency::symbol() }} ' + parseFloat(data.expenseTotals.totalPaid || 0).toLocaleString(undefined, {minimumFractionDigits: 2, maximumFractionDigits: 2}));
            $('#br-count-unpaid').text(data.expenseTotals.unpaidCount ?? 0);
            $('#br-count-paid').text(data.expenseTotals.paidCount ?? 0);
          }

          let newUrl = "{{ route('BikeRegistration.generatentries', $account->id) }}" + (formData ? '?' + formData : '');
          history.pushState(null, '', newUrl);

          const elapsed = Date.now() - loaderStartTime;
          const remaining = 1000 - elapsed;
          setTimeout(() => $('#loading-overlay').hide(), remaining > 0 ? remaining : 0);
        },
        error: function(xhr, status, error) {
          console.error(error);

          const elapsed = Date.now() - loaderStartTime;
          const remaining = 1000 - elapsed;
          setTimeout(() => $('#loading-overlay').hide(), remaining > 0 ? remaining : 0);
        }
      });
    });
  });
</script>
<script>
  document.addEventListener('DOMContentLoaded', function() {
    const table = document.querySelector('#bikeRegistrationDataTable');
    if (!table) return;
    const headers = table.querySelectorAll('th.sorting');
    const tbody = table.querySelector('tbody');

    headers.forEach((header, colIndex) => {
      header.addEventListener('click', () => {
        const rows = Array.from(tbody.querySelectorAll('tr'));
        const isAsc = header.classList.contains('sorted-asc');

        headers.forEach(h => h.classList.remove('sorted-asc', 'sorted-desc'));

        header.classList.add(isAsc ? 'sorted-desc' : 'sorted-asc');

        rows.sort((a, b) => {
          let aText = a.children[colIndex]?.textContent.trim().toLowerCase();
          let bText = b.children[colIndex]?.textContent.trim().toLowerCase();

          const aVal = isNaN(aText) ? aText : parseFloat(aText);
          const bVal = isNaN(bText) ? bText : parseFloat(bText);

          if (aVal < bVal) return isAsc ? 1 : -1;
          if (aVal > bVal) return isAsc ? -1 : 1;
          return 0;
        });

        rows.forEach(row => tbody.appendChild(row));
      });
    });
  });
</script>

<script>
  document.addEventListener('click', function(e) {
    var deleteLink = e.target.closest('.js-delete-bike-registration');
    if (!deleteLink) return;
    var url = deleteLink.getAttribute('data-delete-url');
    if (url) {
      confirmDelete(url);
    }
  });
</script>

@endsection
