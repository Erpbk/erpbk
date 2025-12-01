$(document).ready(function() {
    // Check if dataTableBuilder exists and only initialize if it's not already initialized
    if ($('#dataTableBuilder').length > 0 && !$.fn.DataTable.isDataTable('#dataTableBuilder')) {
        try {
            console.log('Initializing DataTable for #dataTableBuilder');
            console.log('Table exists:', $('#dataTableBuilder').length > 0);
            console.log('Table columns:', $('#dataTableBuilder thead th').length);
            console.log('Table rows:', $('#dataTableBuilder tbody tr').length);

            // Initialize with more verbose configuration
            $('#dataTableBuilder').DataTable({
                "paging": false,
                "searching": false,
                "ordering": true,
                "info": false,
                "autoWidth": false,
                "processing": true,  // Show processing indicator
                "serverSide": false,
                "dom": 'rt',
                "language": {
                    "emptyTable": "No data available",
                    "processing": "Loading data..."
                },
                // More detailed error handling
                "ajax": {
                    "error": function(xhr, error, thrown) {
                        console.error("DataTables AJAX error:", error);
                        console.error("XHR status:", xhr.status);
                        console.error("Thrown error:", thrown);
                        return true;
                    }
                },
                // Add error callback
                "initComplete": function(settings, json) {
                    console.log('DataTable initialization complete');
                },
                "drawCallback": function(settings) {
                    console.log('DataTable draw callback');
                    console.log('Rows:', settings.aiDisplay.length);
                }
            });
        } catch (e) {
            console.error("DataTables initialization error:", e);
            console.error("Error stack:", e.stack);
        }
    } else {
        console.log('DataTable already initialized or table not found');
    }
});
