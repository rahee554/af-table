<?php

namespace ArtflowStudio\Table\Concerns;

use Illuminate\Database\Eloquent\Builder;

/**
 * Export entry points. The heavy lifting lives on the component.
 *
 * Consolidated in 1.6.0 from: HasExportFeatures.
 *
 * This trait used to carry a second, parallel export API — `export()`,
 * `exportToExcel()`, `exportWithChunking()` and an abstract `exportInChunks()`
 * — that nothing reached and that did not work:
 *
 *   - `exportInChunks()` chunked the query and then appended every row to one
 *     in-memory array, so it bought none of the memory safety its name
 *     promised, and it wrote CSV whatever format it was handed;
 *   - `exportToExcel()` therefore returned a CSV named `.xlsx`, which is the
 *     defect BUG-10 closed on the other export path;
 *   - `exportToCsv()` was declared here AND on the component with an
 *     incompatible signature, so this copy never ran at all.
 *
 * None of them was in the action allow-list and nothing internal called them.
 * `handleExport()` on the component is the one export path. See AUDIT.md
 * BUG-51.
 */
trait ExportsData
{
    /**
     * Abstract methods that must be implemented in the main class or other traits
     */
    abstract protected function buildUnifiedQuery(): Builder;
}
