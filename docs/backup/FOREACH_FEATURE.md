# ForEach Feature Documentation
*Using AF Table as a ForEach Iterator with Full Datatable Features*

## 🔄 Overview

The ForEach feature allows you to use AF Table not just as a traditional datatable, but as a powerful iteration tool that maintains all datatable functionality (pagination, search, filtering, sorting) while processing data in a foreach-like manner.

## 🎯 Use Cases

### 1. **Livewire Component Data Processing**
When a Livewire component needs to pass data to AF Table for iteration while maintaining table features:

```php
// In your Livewire component
class OrderProcessor extends Component
{
    public function processOrders()
    {
        $orders = Order::with('customer', 'items')->get();
        
        // Pass data to AF Table for foreach processing
        return $this->renderAFTable($orders);
    }
    
    private function renderAFTable($data)
    {
        return view('orders.process-table', [
            'tableData' => $data,
            'tableMode' => 'foreach'
        ]);
    }
}
```

### 2. **Blade Template Integration**
```blade
{{-- In your Blade template --}}
@php
    $orders = [
        ['id' => 1, 'customer' => 'John Doe', 'total' => 150.00, 'status' => 'pending'],
        ['id' => 2, 'customer' => 'Jane Smith', 'total' => 200.50, 'status' => 'completed'],
        ['id' => 3, 'customer' => 'Bob Johnson', 'total' => 75.25, 'status' => 'processing'],
    ];
@endphp

<div id="order-processor">
    @livewire('af-table-foreach', [
        'data' => $orders,
        'mode' => 'foreach',
        'columns' => [
            'id' => ['key' => 'id', 'label' => 'Order ID'],
            'customer' => ['key' => 'customer', 'label' => 'Customer Name'],
            'total' => ['key' => 'total', 'label' => 'Total Amount'],
            'status' => ['key' => 'status', 'label' => 'Status']
        ]
    ])
</div>
```

## 📋 Basic Implementation

### Component Setup
```php
use ArtflowStudio\Table\Http\Livewire\DatatableTrait;

class AFTableForeach extends DatatableTrait
{
    public function mount($data = null, $mode = 'database', $columns = [])
    {
        if ($mode === 'foreach') {
            // Enable foreach mode with the provided data
            $this->enableForeachMode($data);
        }
        
        parent::mount(null, $columns); // No model needed for foreach mode
    }
    
    public function render()
    {
        if ($this->isForeachMode()) {
            $data = $this->getPaginatedForeachData();
        } else {
            $data = $this->getData(); // Standard database mode
        }
        
        return view('livewire.af-table-foreach', compact('data'));
    }
}
```

### Blade Template
```blade
{{-- resources/views/livewire/af-table-foreach.blade.php --}}
<div class="af-table-container">
    <!-- Search Bar -->
    @if($searchable)
    <div class="mb-4">
        <input wire:model.live="search" 
               type="text" 
               placeholder="Search..." 
               class="form-input w-full">
    </div>
    @endif
    
    <!-- Filters -->
    @if(!empty($filters))
    <div class="mb-4 flex gap-4">
        @foreach($filters as $filter)
        <select wire:model.live="filterValue" class="form-select">
            <option value="">All {{ $filter['label'] }}</option>
            @foreach($filter['options'] as $option)
            <option value="{{ $option }}">{{ $option }}</option>
            @endforeach
        </select>
        @endforeach
    </div>
    @endif
    
    <!-- Table -->
    <div class="overflow-x-auto">
        <table class="min-w-full table-auto">
            <thead class="bg-gray-50">
                <tr>
                    @foreach($this->getVisibleColumns() as $columnKey => $column)
                    <th class="px-4 py-2 text-left">
                        @if($column['sortable'] ?? true)
                        <button wire:click="sortBy('{{ $columnKey }}')" 
                                class="flex items-center space-x-1 hover:text-blue-600">
                            <span>{{ $column['label'] }}</span>
                            @if($sortColumn === $columnKey)
                                @if($sortDirection === 'asc')
                                <svg class="w-4 h-4" fill="currentColor" viewBox="0 0 20 20">
                                    <path d="M5.293 7.293a1 1 0 011.414 0L10 10.586l3.293-3.293a1 1 0 111.414 1.414l-4 4a1 1 0 01-1.414 0l-4-4a1 1 0 010-1.414z"/>
                                </svg>
                                @else
                                <svg class="w-4 h-4" fill="currentColor" viewBox="0 0 20 20">
                                    <path d="M14.707 12.707a1 1 0 01-1.414 0L10 9.414l-3.293 3.293a1 1 0 01-1.414-1.414l4-4a1 1 0 011.414 0l4 4a1 1 0 010 1.414z"/>
                                </svg>
                                @endif
                            @endif
                        </button>
                        @else
                        {{ $column['label'] }}
                        @endif
                    </th>
                    @endforeach
                </tr>
            </thead>
            <tbody class="bg-white divide-y divide-gray-200">
                @if($this->isForeachMode())
                    @foreach($data->data as $index => $item)
                    @php
                        $processedItem = $this->processForeachItem($item, $index);
                    @endphp
                    <tr class="hover:bg-gray-50">
                        @foreach($this->getVisibleColumns() as $columnKey => $column)
                        <td class="px-4 py-2">
                            @php
                                $value = $this->getForeachItemValue($processedItem, $column);
                            @endphp
                            
                            @if(isset($column['template']))
                                {!! str_replace('{{value}}', $value, $column['template']) !!}
                            @elseif(isset($column['function']))
                                @php
                                    $functionResult = $this->executeFunction($column['function'], $processedItem);
                                @endphp
                                {!! $functionResult !!}
                            @else
                                {{ $value }}
                            @endif
                        </td>
                        @endforeach
                    </tr>
                    @endforeach
                @else
                    {{-- Standard database mode rendering --}}
                    @foreach($data as $row)
                    <tr class="hover:bg-gray-50">
                        @foreach($this->getVisibleColumns() as $columnKey => $column)
                        <td class="px-4 py-2">
                            {{-- Standard column rendering logic --}}
                        </td>
                        @endforeach
                    </tr>
                    @endforeach
                @endif
            </tbody>
        </table>
    </div>
    
    <!-- Pagination for ForEach Mode -->
    @if($this->isForeachMode() && $data->last_page > 1)
    <div class="mt-4 flex justify-between items-center">
        <div class="text-sm text-gray-700">
            Showing {{ $data->from }} to {{ $data->to }} of {{ $data->total }} results
        </div>
        <div class="flex space-x-2">
            @if($data->current_page > 1)
            <button wire:click="previousPage" class="px-3 py-1 bg-blue-500 text-white rounded">
                Previous
            </button>
            @endif
            
            @for($i = 1; $i <= $data->last_page; $i++)
            <button wire:click="gotoPage({{ $i }})" 
                    class="px-3 py-1 {{ $data->current_page == $i ? 'bg-blue-500 text-white' : 'bg-gray-200' }} rounded">
                {{ $i }}
            </button>
            @endfor
            
            @if($data->current_page < $data->last_page)
            <button wire:click="nextPage" class="px-3 py-1 bg-blue-500 text-white rounded">
                Next
            </button>
            @endif
        </div>
    </div>
    @endif
    
    <!-- ForEach Statistics -->
    @if($this->isForeachMode())
    <div class="mt-4 p-4 bg-gray-100 rounded">
        @php $stats = $this->getForeachStats(); @endphp
        <h4 class="font-medium text-gray-800">ForEach Statistics</h4>
        <div class="grid grid-cols-2 md:grid-cols-4 gap-4 mt-2 text-sm">
            <div>
                <span class="text-gray-600">Total Items:</span>
                <span class="font-medium">{{ $stats['total_items'] ?? 0 }}</span>
            </div>
            <div>
                <span class="text-gray-600">Filtered:</span>
                <span class="font-medium">{{ $stats['filtered_items'] ?? 0 }}</span>
            </div>
            <div>
                <span class="text-gray-600">Processed:</span>
                <span class="font-medium">{{ $stats['current_counter'] ?? 0 }}</span>
            </div>
            <div>
                <span class="text-gray-600">Chunk Size:</span>
                <span class="font-medium">{{ $stats['chunk_size'] ?? 0 }}</span>
            </div>
        </div>
    </div>
    @endif
</div>
```

## 🚀 Advanced Features

### 1. **Bulk Processing in ForEach Mode**
```php
public function processBulkItems()
{
    if (!$this->isForeachMode()) {
        return;
    }
    
    $results = $this->bulkProcessForeachItems(function($item, $index) {
        // Custom processing logic for each item
        if ($item['status'] === 'pending') {
            // Process pending orders
            return $this->processOrder($item);
        }
        return $item;
    });
    
    $this->dispatch('bulkProcessingComplete', count($results));
}
```

### 2. **Real-time Data Updates**
```php
public function updateForeachData($newData)
{
    $this->foreachData = collect($newData);
    $this->resetForeachCounter();
    $this->dispatch('dataUpdated');
}

#[On('refresh-data')]
public function refreshData()
{
    // Fetch fresh data and update foreach mode
    $freshData = $this->getFreshDataFromSource();
    $this->updateForeachData($freshData);
}
```

### 3. **Custom Item Processing**
```php
public function processForeachItem($item, $index = null)
{
    // Apply custom transformations
    if (is_array($item)) {
        $item['display_index'] = $index + 1;
        $item['processed_at'] = now()->toDateTimeString();
        
        // Apply business logic
        if ($item['total'] > 100) {
            $item['priority'] = 'high';
        }
    }
    
    return parent::processForeachItem($item, $index);
}
```

## 📊 Performance Considerations

### Memory Management
```php
// Set appropriate chunk size for large datasets
$this->enableForeachMode($largeDataset, $chunkSize = 50);

// Set processing limit to prevent memory overflow
$this->enableForeachMode($data, 100, $limit = 1000);
```

### Lazy Loading
```php
public function getLazyForeachData()
{
    // Implement lazy loading for very large datasets
    return $this->foreachData->lazy()
                             ->filter(/* your filters */)
                             ->take($this->perPage);
}
```

## 🎨 Styling and Customization

### Custom CSS Classes
```css
.af-table-foreach {
    /* Custom styles for foreach mode */
}

.af-table-foreach .processing-item {
    background-color: #f0f9ff;
    border-left: 4px solid #3b82f6;
}

.af-table-foreach .processed-item {
    background-color: #f0fdf4;
    border-left: 4px solid #10b981;
}
```

### Dynamic Row Classes
```php
public function getRowClass($item, $index)
{
    if ($this->isForeachMode()) {
        $classes = ['foreach-row'];
        
        if ($index % 2 === 0) {
            $classes[] = 'even-row';
        }
        
        if (isset($item['priority']) && $item['priority'] === 'high') {
            $classes[] = 'high-priority';
        }
        
        return implode(' ', $classes);
    }
    
    return 'standard-row';
}
```

## 🧪 Testing ForEach Functionality

```php
/** @test */
public function test_foreach_mode_processes_data_correctly()
{
    $testData = [
        ['id' => 1, 'name' => 'Item 1'],
        ['id' => 2, 'name' => 'Item 2'],
        ['id' => 3, 'name' => 'Item 3'],
    ];
    
    $component = Livewire::test(AFTableForeach::class)
        ->call('enableForeachMode', $testData)
        ->assertSet('foreachMode', true)
        ->assertSet('foreachData', collect($testData));
    
    $this->assertTrue($component->instance()->isForeachMode());
    $this->assertEquals(3, $component->instance()->getForeachStats()['total_items']);
}

/** @test */
public function test_foreach_search_functionality()
{
    $testData = [
        ['id' => 1, 'name' => 'Apple'],
        ['id' => 2, 'name' => 'Banana'],
        ['id' => 3, 'name' => 'Cherry'],
    ];
    
    Livewire::test(AFTableForeach::class)
        ->call('enableForeachMode', $testData)
        ->set('search', 'Apple')
        ->assertCount('getForeachData', 1);
}
```

## 📝 Best Practices

### 1. **Data Structure Consistency**
Ensure your foreach data maintains consistent structure:
```php
$consistentData = [
    ['id' => 1, 'name' => 'Item 1', 'status' => 'active'],
    ['id' => 2, 'name' => 'Item 2', 'status' => 'inactive'],
    // All items have same keys
];
```

### 2. **Performance Optimization**
- Use appropriate chunk sizes (50-100 items per chunk)
- Implement lazy loading for large datasets
- Set reasonable limits to prevent memory issues
- Consider using generators for very large datasets

### 3. **Error Handling**
```php
public function enableForeachMode($data, $chunkSize = 100, $limit = null)
{
    try {
        if (empty($data)) {
            throw new \InvalidArgumentException('Data cannot be empty');
        }
        
        if (!is_array($data) && !($data instanceof \Traversable)) {
            throw new \InvalidArgumentException('Data must be array or traversable');
        }
        
        return parent::enableForeachMode($data, $chunkSize, $limit);
        
    } catch (\Exception $e) {
        $this->dispatch('foreachError', $e->getMessage());
        return false;
    }
}
```

## 🔗 Integration Examples

### With Vue.js Frontend
```javascript
// Vue component
export default {
    data() {
        return {
            orders: [],
            tableMode: 'foreach'
        }
    },
    
    methods: {
        loadOrders() {
            axios.get('/api/orders')
                .then(response => {
                    this.orders = response.data;
                    // Pass to Livewire component
                    Livewire.emit('updateForeachData', this.orders);
                });
        }
    }
}
```

### With Alpine.js
```html
<div x-data="{ 
    mode: 'foreach', 
    data: @js($orders),
    processing: false 
}">
    <button @click="processing = true; $wire.processBulkItems()" 
            :disabled="processing">
        Process All Items
    </button>
    
    @livewire('af-table-foreach', ['mode' => 'foreach'])
</div>
```

---

## 📧 Support & Documentation

For more information about ForEach functionality:
- Check the main package documentation
- Review the test suite for examples
- See the roadmap for upcoming features

*Last Updated: December 2024*
