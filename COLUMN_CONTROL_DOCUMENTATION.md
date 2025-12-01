# Advanced Column Control Panel Documentation

## Overview

The Advanced Column Control Panel is a reusable component that provides comprehensive table column management features including:

- **Dynamic Column Visibility**: Show/hide columns without page reload
- **Drag & Drop Reordering**: Rearrange columns by dragging
- **Smart Export**: Export only visible columns in the current order
- **Persistent Settings**: Column preferences saved in localStorage
- **AJAX Compatibility**: Works with dynamically loaded table content

## Features

### 1. Filter Icon → Sidebar Panel
- Clicking the column control icon (📊) opens a sidebar panel
- The panel contains all available table columns with management options

### 2. Column Visibility (Show/Hide)
- Each column has a checkbox for visibility control
- ✅ Checked = column visible
- ☐ Unchecked = column hidden
- Changes apply instantly without page reload

### 3. Column Reordering (Drag & Drop)
- Columns in the sidebar are draggable using the grip handle (⋮⋮)
- Drag and drop to reorder columns
- Table column order updates immediately

### 4. Export Rules
- Export buttons: Excel, CSV, PDF
- Only currently visible columns are exported
- Export follows the current column order from sidebar
- Respects current filters and search parameters

### 5. Reusability
- Component works with any data table
- Not hardcoded to specific tables
- Easy integration with existing tables

## Implementation Guide

### For New Tables

#### Step 1: Create Customizable Export Class

```php
<?php
// app/Exports/CustomizableYourModelExport.php

namespace App\Exports;

use App\Models\YourModel;
use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithMapping;

class CustomizableYourModelExport implements FromCollection, WithHeadings, WithMapping
{
    protected $visibleColumns;
    protected $columnOrder;
    protected $filters;

    // Define all available columns
    protected $availableColumns = [
        'id' => ['title' => 'ID', 'data' => 'id'],
        'name' => ['title' => 'Name', 'data' => 'name'],
        // Add your columns here...
    ];

    public function __construct($visibleColumns = null, $columnOrder = null, $filters = [])
    {
        $this->visibleColumns = $visibleColumns ?: array_keys($this->availableColumns);
        $this->columnOrder = $columnOrder ?: array_keys($this->availableColumns);
        $this->filters = $filters;
    }

    public function collection()
    {
        $query = YourModel::query();
        
        // Apply filters
        if (!empty($this->filters)) {
            foreach ($this->filters as $key => $value) {
                if (!empty($value)) {
                    $query->where($key, 'like', "%{$value}%");
                }
            }
        }
        
        return $query->get();
    }

    public function map($model): array
    {
        $data = [];
        foreach ($this->columnOrder as $columnKey) {
            if (in_array($columnKey, $this->visibleColumns)) {
                $data[] = $this->getColumnValue($model, $columnKey);
            }
        }
        return $data;
    }

    public function headings(): array
    {
        $headings = [];
        foreach ($this->columnOrder as $columnKey) {
            if (in_array($columnKey, $this->visibleColumns)) {
                $headings[] = $this->availableColumns[$columnKey]['title'];
            }
        }
        return $headings;
    }

    protected function getColumnValue($model, $columnKey)
    {
        // Implement your column value logic here
        return $model->{$columnKey} ?? '-';
    }
}
```

#### Step 2: Update Your Controller

```php
<?php
// app/Http/Controllers/YourController.php

use App\Traits\HasColumnControl;

class YourController extends Controller
{
    use HasColumnControl;

    protected function getTableColumns()
    {
        return [
            ['data' => 'id', 'title' => 'ID'],
            ['data' => 'name', 'title' => 'Name'],
            ['data' => 'email', 'title' => 'Email'],
            // Define your table columns
        ];
    }

    protected function getExportClass()
    {
        return \App\Exports\CustomizableYourModelExport::class;
    }

    public function index()
    {
        $data = YourModel::paginate(50);
        
        return view('your-module.index', $this->withColumnControl([
            'data' => $data
        ], 'dataTableBuilder', route('your-module.exportCustomizable')));
    }

    // Add export route
    public function exportCustomizable(Request $request)
    {
        return $this->exportCustomizable($request);
    }
}
```

#### Step 3: Add Route

```php
// routes/web.php
Route::get('your-module/export-customizable', [YourController::class, 'exportCustomizable'])->name('your-module.exportCustomizable');
```

#### Step 4: Update Your Table View

```blade
{{-- resources/views/your-module/table.blade.php --}}
<table class="table table-striped dataTable no-footer" id="dataTableBuilder">
    <thead class="text-center">
        <tr role="row">
            <th title="ID">ID</th>
            <th title="Name">Name</th>
            <th title="Email">Email</th>
            <!-- Your table headers -->
            
            {{-- Add column control icon --}}
            <th tabindex="0" rowspan="1" colspan="1">
                <a class="openColumnControlSidebar" href="javascript:void(0);" title="Column Control">
                    <i class="fa fa-columns"></i>
                </a>
            </th>
        </tr>
    </thead>
    <tbody>
        @foreach($data as $item)
        <tr class="text-center">
            <td>{{ $item->id }}</td>
            <td>{{ $item->name }}</td>
            <td>{{ $item->email }}</td>
            <!-- Your table data -->
            
            {{-- Add empty cell for column control --}}
            <td></td>
        </tr>
        @endforeach
    </tbody>
</table>
```

#### Step 5: Update Your Index View

```blade
{{-- resources/views/your-module/index.blade.php --}}
@extends('layouts.app')

@push('third_party_stylesheets')
<script src="https://cdn.jsdelivr.net/npm/sortablejs@1.15.0/Sortable.min.js"></script>
@endpush

@section('content')
<div class="content px-0">
    <div class="card">
        <div class="card-body table-responsive px-2 py-0" id="table-data">
            @include('your-module.table', ['data' => $data])
        </div>
    </div>
    
    {{-- Include Column Control Panel --}}
    @include('components.column-control-panel', [
        'tableColumns' => $tableColumns, 
        'exportRoute' => $exportRoute
    ])
</div>
@endsection

@section('page-script')
<script>
// If you have AJAX table updates, add this after successful updates:
if (window.ColumnController) {
    setTimeout(() => {
        window.ColumnController.reapplySettings();
    }, 100);
}
</script>
@endsection
```

## Usage Instructions

### For Users

1. **Open Column Control Panel**
   - Click the columns icon (📊) in the table header
   - The sidebar panel will slide in from the right

2. **Show/Hide Columns**
   - Use checkboxes next to column names
   - Changes apply immediately to the table

3. **Reorder Columns**
   - Drag columns using the grip handle (⋮⋮)
   - Drop in desired position
   - Table updates instantly

4. **Quick Actions**
   - **Show All**: Make all columns visible
   - **Hide All**: Hide all columns (except first)
   - **Reset**: Restore default configuration

5. **Export Data**
   - Click Excel, CSV, or PDF export buttons
   - Only visible columns are exported
   - Exported in current column order

6. **Settings Persistence**
   - Column preferences are automatically saved
   - Settings persist across page reloads and sessions

### For Developers

#### Column Configuration

```php
$tableColumns = [
    [
        'data' => 'column_key',        // Database column or accessor
        'title' => 'Display Name',     // Column header text
        'sortable' => true,           // Optional: if column is sortable
        'searchable' => true,         // Optional: if column is searchable
    ]
];
```

#### Export Integration

The export functionality automatically:
- Includes only visible columns
- Maintains column order from sidebar
- Applies current filters and search
- Supports Excel, CSV, and PDF formats

#### AJAX Table Updates

When updating table content via AJAX, call:

```javascript
if (window.ColumnController) {
    window.ColumnController.reapplySettings();
}
```

This ensures column visibility and order persist after content updates.

## Technical Details

### Files Structure

```
resources/views/components/
├── column-control-panel.blade.php    # Main component

app/Traits/
├── HasColumnControl.php              # Reusable trait

app/Exports/
├── CustomizableRiderExport.php       # Example export class
```

### Dependencies

- **SortableJS**: For drag & drop functionality
- **Laravel Excel**: For export functionality
- **FontAwesome**: For icons
- **Bootstrap**: For styling (optional)

### Browser Compatibility

- **Modern Browsers**: Chrome, Firefox, Safari, Edge
- **Storage**: localStorage for settings persistence
- **JavaScript**: ES6+ features used

### Performance Considerations

- Settings stored in localStorage (lightweight)
- Column operations use CSS display properties
- AJAX reapplication uses minimal DOM manipulation
- Export processing handles large datasets efficiently

## Troubleshooting

### Common Issues

1. **SortableJS not working**
   - Ensure SortableJS CDN is loaded
   - Check browser console for errors

2. **Settings not persisting**
   - Verify localStorage is enabled
   - Check for JavaScript errors

3. **Export not working**
   - Ensure export class exists
   - Check route configuration
   - Verify Laravel Excel is installed

4. **AJAX updates breaking column control**
   - Add `reapplySettings()` call after AJAX success
   - Ensure table ID remains consistent

### Debug Mode

Enable debug logging by adding to component:

```javascript
// In column-control-panel.blade.php
console.log('Column settings:', this.getSettings());
```

This implementation provides a complete, reusable column control system that can be easily integrated into any data table in your Laravel application.
