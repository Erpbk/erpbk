<script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
<script type="text/javascript">
    $(document).ready(function() {
        $(document).on('click', '.delete-file', function(e) {
            e.preventDefault();
            const url = $(this).data('url');
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
                    $.ajax({
                        url: url,
                        type: 'DELETE',
                        data: { _token: '{{ csrf_token() }}' },
                        success: function() {
                            Swal.fire('Deleted!', 'File has been deleted.', 'success').then(() => location.reload());
                        },
                        error: function() {
                            Swal.fire('Error!', 'Failed to delete file.', 'error');
                        }
                    });
                }
            });
        });

        $('#file-search').on('keyup', function() {
            const searchTerm = $(this).val().toLowerCase();
            const rows = $('.file-row');
            let visibleRows = 0;
            rows.each(function() {
                const fileName = String($(this).data('name') || '');
                if (fileName.includes(searchTerm)) {
                    $(this).show();
                    visibleRows++;
                } else {
                    $(this).hide();
                }
            });
            let counter = 1;
            rows.filter(':visible').each(function() {
                $(this).find('.row-counter').text(counter++);
            });
            if (visibleRows === 0 && searchTerm !== '') {
                $('#no-results').show();
                $('#files-table-body').hide();
            } else {
                $('#no-results').hide();
                $('#files-table-body').show();
            }
        });

        $('#clear-search').on('click', function() {
            $('#file-search').val('');
            $('.file-row').show();
            $('#no-results').hide();
            $('#files-table-body').show();
            let counter = 1;
            $('.file-row').each(function() {
                $(this).find('.row-counter').text(counter++);
            });
        });
    });
</script>
