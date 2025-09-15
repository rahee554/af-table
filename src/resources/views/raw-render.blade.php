<!-- filepath: d:\Repositories\Al-Emaan_Travels\vendor\artflow-studio\table\src\resources\views\raw-render.blade.php -->
{{-- Use secure template rendering instead of direct Blade::render --}}
{!! $this->renderRawHtml($column['raw'], $row) !!}