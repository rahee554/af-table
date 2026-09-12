<?php

namespace ArtflowStudio\Table\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Log;

/**
 * Clears the caches this package owns.
 *
 * Previously this command cleared the host application's config, route and view
 * caches and then called Cache::flush() five times in a loop — wiping every
 * cache entry in the app and discarding the optimisations a production
 * deployment depends on. It now touches only keys and files this package
 * created. See AUDIT.md BUG-11.
 */
class ClearPhantomColumnsCommand extends Command
{
    // NOTE: the old --tenant option was removed rather than kept as decoration.
    // The caching layer has no tenant concept — HasUnifiedCaching::getCacheTags()
    // returns [datatable:{tableId}, model:{X}, unified_cache] — so the option
    // could never have scoped anything. Re-add it once cache keys carry a tenant.
    protected $signature = 'af-table:clear-phantom-columns';

    protected $description = "Clear this package's column/distinct-value caches and compiled raw templates";

    /** Cache-key prefixes written by HasUnifiedCaching and friends. */
    private const CACHE_PREFIXES = [
        'datatable',
        'datatable_columns',
        'select_columns',
        'visible_columns',
        'distinct_values',
    ];

    public function handle(): int
    {
        $this->info('Clearing AF Table caches...');

        $cleared = $this->clearOwnCache();
        $templates = $this->clearCompiledRawTemplates();
        $sessions = $this->clearSessionKeys();

        $this->line("  cache entries cleared ............. {$cleared}");
        $this->line("  compiled raw templates removed .... {$templates}");
        $this->line("  session keys cleared .............. {$sessions}");

        Log::info('AF Table caches cleared', [
            'cache_entries' => $cleared,
            'templates' => $templates,
        ]);

        $this->newLine();
        $this->info("Done. The host application's config, route and view caches were left untouched.");

        return self::SUCCESS;
    }

    /**
     * Remove the package's own cache entries.
     *
     * Uses cache tags when the driver supports them; otherwise forgets the known
     * key prefixes one by one. Never calls Cache::flush() — that would destroy
     * every unrelated entry in the host application.
     */
    private function clearOwnCache(): int
    {
        if ($this->cacheSupportsTags()) {
            try {
                Cache::tags(['unified_cache'])->flush();

                return 1;
            } catch (\Throwable $e) {
                $this->warn('  tagged flush failed, falling back to prefix scan: '.$e->getMessage());
            }
        } else {
            $this->warn(
                '  the ['.config('cache.default').'] cache driver does not support tags, so only '.
                'exact package keys can be removed; use redis or memcached for complete clearing.'
            );
        }

        $cleared = 0;

        foreach (self::CACHE_PREFIXES as $prefix) {
            if (Cache::forget($prefix)) {
                $cleared++;
            }
        }

        return $cleared;
    }

    /**
     * Delete the compiled `raw` column templates written under storage/.
     */
    private function clearCompiledRawTemplates(): int
    {
        $path = storage_path('framework/cache/aftable');

        if (! File::isDirectory($path)) {
            return 0;
        }

        $removed = 0;

        foreach (File::glob($path.DIRECTORY_SEPARATOR.'aftable_tpl_*.php') as $file) {
            if (File::delete($file)) {
                $removed++;
            }
        }

        return $removed;
    }

    /**
     * Forget this package's session keys for the *current* session only.
     *
     * In a console context there is normally no started session, so this is a
     * no-op — it is kept for `artisan tinker` and test usage. The old version
     * always reported "Cleared 0 session keys" for exactly this reason.
     */
    private function clearSessionKeys(): int
    {
        if (! $this->laravel->bound('session') || ! $this->laravel['session']->isStarted()) {
            return 0;
        }

        $cleared = 0;

        foreach (array_keys(session()->all()) as $key) {
            if (str_starts_with((string) $key, 'datatable_')) {
                session()->forget($key);
                $cleared++;
            }
        }

        return $cleared;
    }

    private function cacheSupportsTags(): bool
    {
        try {
            return method_exists(Cache::getStore(), 'tags');
        } catch (\Throwable) {
            return false;
        }
    }
}
