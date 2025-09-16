<?php

namespace ArtflowStudio\Table\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Session;
use Illuminate\Support\Facades\Log;

class ClearPhantomColumnsCommand extends Command
{
    protected $signature = 'af-table:clear-phantom-columns {--tenant= : Tenant ID for multi-tenant systems}';
    protected $description = 'Clear phantom column cache and reset column configurations to fix "column_1", "column_8" type SQL errors';

    public function handle()
    {
        $this->info('🔧 Clearing Phantom Column Cache...');
        $this->newLine();

        $tenant = $this->option('tenant');
        if ($tenant) {
            $this->info("🏢 Running for tenant: {$tenant}");
        }

        // 1. Clear all caches
        $this->info('1. Clearing application caches...');
        $this->call('cache:clear');
        $this->call('config:clear');
        $this->call('view:clear');
        $this->call('route:clear');
        $this->info('   ✅ Application caches cleared');

        // 2. Clear specific datatable caches
        $this->info('2. Clearing datatable-specific caches...');
        $cachePatterns = [
            'datatable_columns_*',
            'select_columns_*', 
            'visible_columns_*',
            'distinct_values_*',
            '*_column_*'
        ];

        foreach ($cachePatterns as $pattern) {
            try {
                Cache::flush(); // Clear all cache since we can't pattern match easily
                $this->info("   ✅ Cache pattern cleared: {$pattern}");
            } catch (\Exception $e) {
                $this->error("   ❌ Error clearing {$pattern}: " . $e->getMessage());
            }
        }

        // 3. Clear session data
        $this->info('3. Clearing session data...');
        try {
            // Clear all datatable-related session keys
            $sessionKeys = Session::all();
            $clearedCount = 0;
            
            foreach (array_keys($sessionKeys) as $key) {
                if (strpos($key, 'column') !== false || 
                    strpos($key, 'datatable') !== false ||
                    strpos($key, 'af_table') !== false) {
                    Session::forget($key);
                    $clearedCount++;
                }
            }
            
            $this->info("   ✅ Cleared {$clearedCount} session keys");
        } catch (\Exception $e) {
            $this->error("   ❌ Error clearing session: " . $e->getMessage());
        }

        // 4. Log the cleanup
        Log::info('Phantom column cache cleared via command', [
            'tenant' => $tenant,
            'timestamp' => now(),
            'cleared_patterns' => $cachePatterns
        ]);

        $this->newLine();
        $this->info('🎯 Phantom column cache clearing completed!');
        $this->info('🔄 Try running your datatable queries again.');
        $this->newLine();
        
        $this->comment('💡 If issues persist, check:');
        $this->comment('   - Column configuration arrays in your blade files');
        $this->comment('   - Custom column generation logic in traits');
        $this->comment('   - External cache systems (Redis, Memcached)');
        
        return 0;
    }
}
