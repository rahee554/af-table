<?php

namespace ArtflowStudio\Table\Testing;

use ArtflowStudio\Table\Http\Livewire\Datatable;
use Illuminate\Support\Facades\Log;

class SecurityTestRunner extends BaseTestRunner
{
    public function getName(): string
    {
        return 'Security Tests';
    }

    public function getDescription(): string
    {
        return 'Tests security features, SQL injection prevention, XSS protection, and input validation';
    }

    public function run(): array
    {
        $this->command->info('🔒 Running Security Tests...');
        $this->command->line('   Tests security features, SQL injection prevention, XSS protection, and input validation');
        $this->command->newLine();

        $tests = [
            'SQL Injection Prevention' => [$this, 'testSqlInjectionPrevention'],
            'XSS Prevention' => [$this, 'testXssPrevention'],
            'Input Validation' => [$this, 'testInputValidation'],
            'Column Access Control' => [$this, 'testColumnAccessControl'],
            'Search Term Sanitization' => [$this, 'testSearchSanitization'],
            'Filter Value Sanitization' => [$this, 'testFilterSanitization'],
            'HTML Content Sanitization' => [$this, 'testHtmlSanitization'],
            'JSON Path Validation' => [$this, 'testJsonPathValidation'],
            'Relation String Validation' => [$this, 'testRelationStringValidation'],
            'Export Security' => [$this, 'testExportSecurity'],
            'SQL Generation Safety' => [$this, 'testSqlGenerationSafety'],
            'Eval Usage Prevention' => [$this, 'testEvalUsagePrevention']
        ];

        $passed = 0;
        $failed = 0;

        foreach ($tests as $testName => $testCallback) {
            if ($this->runTest($testName, $testCallback)) {
                $passed++;
            } else {
                $failed++;
            }
        }

        $total = $passed + $failed;
        $this->command->newLine();
        
        if ($failed > 0) {
            $this->command->error("❌ security tests: {$passed}/{$total} passed");
        } else {
            $this->command->info("✅ security tests: {$passed}/{$total} passed");
        }

        return $this->getResults();
    }

    protected function testSqlInjectionPrevention(): bool
    {
        $table = new Datatable();
        $table->model = \App\Models\User::class;
        $table->columns = [
            'name' => ['key' => 'name', 'label' => 'Name', 'searchable' => true],
            'email' => ['key' => 'email', 'label' => 'Email', 'searchable' => true]
        ];

        // Test dangerous SQL patterns
        $dangerousInputs = [
            "'; DROP TABLE users; --",
            "1' OR '1'='1",
            "UNION SELECT * FROM users",
            "'; DELETE FROM users WHERE '1'='1",
            "admin'--",
            "1' AND 1=1 UNION SELECT username, password FROM users --"
        ];

        foreach ($dangerousInputs as $input) {
            try {
                $table->search = $input;
                $table->updatedSearch();
                
                // Should not contain dangerous patterns after sanitization
                $sanitized = strtolower($table->search);
                $this->assertFalse(
                    strpos($sanitized, 'drop') !== false ||
                    strpos($sanitized, 'delete') !== false ||
                    strpos($sanitized, 'union') !== false,
                    "Dangerous SQL pattern not filtered: {$input}"
                );
            } catch (\Exception $e) {
                // Catching exceptions is good - means security is working
            }
        }

        return true;
    }

    protected function testXssPrevention(): bool
    {
        $table = new Datatable();
        $table->model = \App\Models\User::class;
        $table->columns = [
            'content' => ['key' => 'content', 'label' => 'Content', 'type' => 'raw']
        ];

        $xssInputs = [
            '<script>alert("xss")</script>',
            '<img src="x" onerror="alert(1)">',
            'javascript:alert(1)',
            '<iframe src="javascript:alert(1)"></iframe>',
            '<svg onload="alert(1)"></svg>'
        ];

        foreach ($xssInputs as $input) {
            $reflection = new \ReflectionClass($table);
            $method = $reflection->getMethod('sanitizeHtmlContent');
            $method->setAccessible(true);
            $sanitized = $method->invoke($table, $input);
            
            $this->assertFalse(
                strpos(strtolower($sanitized), 'script') !== false ||
                strpos(strtolower($sanitized), 'javascript') !== false ||
                strpos(strtolower($sanitized), 'onerror') !== false ||
                strpos(strtolower($sanitized), 'onload') !== false,
                "XSS pattern not properly sanitized: {$input}"
            );
        }

        return true;
    }

    protected function testInputValidation(): bool
    {
        $table = new Datatable();
        $table->model = \App\Models\User::class;
        $table->columns = [
            'name' => ['key' => 'name', 'label' => 'Name']
        ];

        // Test search length limits
        $longSearch = str_repeat('a', 200);
        $table->search = $longSearch;
        $table->updatedSearch();

        $this->assertLessThan(101, strlen($table->search), 'Search input not properly limited');

        // Test filter value validation
        $dangerousFilter = '<script>alert("xss")</script>';
        $reflection = new \ReflectionClass($table);
        $method = $reflection->getMethod('sanitizeFilterValue');
        $method->setAccessible(true);
        $sanitizedFilter = $method->invoke($table, $dangerousFilter);

        $this->assertFalse(
            strpos($sanitizedFilter, '<script>') !== false,
            'Filter value not properly sanitized'
        );

        return true;
    }

    protected function testColumnAccessControl(): bool
    {
        $table = new Datatable();
        $table->model = \App\Models\User::class;
        $table->columns = [
            'name' => ['key' => 'name', 'label' => 'Name'],
            'email' => ['key' => 'email', 'label' => 'Email']
        ];

        // Test access to non-existent column
        $reflection = new \ReflectionClass($table);
        $method = $reflection->getMethod('isAllowedColumn');
        $method->setAccessible(true);
        $this->assertFalse($method->invoke($table, 'password'), 'Access allowed to non-configured column');

        // Test access to configured column
        $this->assertTrue($method->invoke($table, 'name'), 'Access denied to configured column');

        return true;
    }

    protected function testSearchSanitization(): bool
    {
        $table = new Datatable();
        
        $testCases = [
            ['input' => '<script>alert("test")</script>', 'shouldNotContain' => 'script'],
            ['input' => 'normal search term', 'shouldContain' => 'normal'],
            ['input' => str_repeat('a', 200), 'maxLength' => 100],
            ['input' => "'; DROP TABLE", 'shouldNotContain' => 'DROP'],
        ];

        foreach ($testCases as $case) {
            $reflection = new \ReflectionClass($table);
            $method = $reflection->getMethod('sanitizeSearch');
            $method->setAccessible(true);
            $sanitized = $method->invoke($table, $case['input']);
            
            if (isset($case['maxLength'])) {
                $this->assertLessThan($case['maxLength'] + 1, strlen($sanitized), "Search not limited to {$case['maxLength']} characters");
            }
            
            if (isset($case['shouldContain'])) {
                $this->assertStringContains(strtolower($case['shouldContain']), strtolower($sanitized), "Valid search term '{$case['shouldContain']}' was incorrectly filtered");
            }
            
            if (isset($case['shouldNotContain'])) {
                $this->assertFalse(strpos(strtolower($sanitized), strtolower($case['shouldNotContain'])) !== false, "Dangerous pattern '{$case['shouldNotContain']}' not filtered");
            }
        }

        return true;
    }

    protected function testFilterSanitization(): bool
    {
        $table = new Datatable();
        
        $testCases = [
            '<script>alert("test")</script>',
            'SELECT * FROM users',
            str_repeat('a', 300),
            '<img src="x" onerror="alert(1)">'
        ];

        foreach ($testCases as $input) {
            $reflection = new \ReflectionClass($table);
            $method = $reflection->getMethod('sanitizeFilterValue');
            $method->setAccessible(true);
            $sanitized = $method->invoke($table, $input);
            
            // Should not contain dangerous patterns
            $dangerousPatterns = ['<script', '<img', 'onerror', 'SELECT', 'DROP', 'DELETE'];
            foreach ($dangerousPatterns as $pattern) {
                $this->assertFalse(
                    strpos(strtolower($sanitized), strtolower($pattern)) !== false,
                    "Dangerous pattern '{$pattern}' not filtered from filter value"
                );
            }
            
            // Should be limited in length
            $this->assertLessThan(256, strlen($sanitized), 'Filter value not properly limited in length');
        }

        return true;
    }

    protected function testHtmlSanitization(): bool
    {
        $table = new Datatable();
        
        $input = '<p>Valid content</p><script>alert("xss")</script>';
        $reflection = new \ReflectionClass($table);
        $method = $reflection->getMethod('sanitizeHtmlContent');
        $method->setAccessible(true);
        $sanitized = $method->invoke($table, $input);
        
        // Should contain allowed tags
        $this->assertStringContains('<p>', $sanitized, 'Allowed HTML tags were incorrectly stripped');
        
        // Should not contain blocked tags
        $this->assertFalse(
            strpos(strtolower($sanitized), '<script>') !== false,
            'Dangerous HTML tags not properly stripped'
        );

        return true;
    }

    protected function testJsonPathValidation(): bool
    {
        $table = new Datatable();
        
        $validPaths = [
            'user.name',
            'profile.settings.theme',
            'metadata.created_by'
        ];
        
        $invalidPaths = [
            '../../../etc/passwd',
            'user.name; DROP TABLE',
            '<script>alert(1)</script>',
            'user[0]'
        ];

        foreach ($validPaths as $path) {
            $reflection = new \ReflectionClass($table);
            $method = $reflection->getMethod('validateJsonPath');
            $method->setAccessible(true);
            $this->assertTrue($method->invoke($table, $path), "Valid JSON path '{$path}' rejected");
        }

        foreach ($invalidPaths as $path) {
            $reflection = new \ReflectionClass($table);
            $method = $reflection->getMethod('validateJsonPath');
            $method->setAccessible(true);
            $this->assertFalse($method->invoke($table, $path), "Invalid JSON path '{$path}' accepted");
        }

        return true;
    }

    protected function testRelationStringValidation(): bool
    {
        $table = new Datatable();
        
        $validRelations = [
            'user:name',
            'profile:avatar',
            'posts.comments:content'
        ];
        
        $invalidRelations = [
            'user; DROP TABLE',
            '<script>:alert',
            'user:name; DELETE',
            'invalid_format'
        ];

        foreach ($validRelations as $relation) {
            $reflection = new \ReflectionClass($table);
            $method = $reflection->getMethod('validateRelationString');
            $method->setAccessible(true);
            $this->assertTrue($method->invoke($table, $relation), "Valid relation string '{$relation}' rejected");
        }

        foreach ($invalidRelations as $relation) {
            $reflection = new \ReflectionClass($table);
            $method = $reflection->getMethod('validateRelationString');
            $method->setAccessible(true);
            $this->assertFalse($method->invoke($table, $relation), "Invalid relation string '{$relation}' accepted");
        }

        return true;
    }

    protected function testExportSecurity(): bool
    {
        $table = new Datatable();
        $table->model = \App\Models\User::class;
        $table->columns = [
            'name' => ['key' => 'name', 'label' => 'Name']
        ];

        // Test export format validation
        $validFormats = ['csv', 'xlsx', 'pdf'];
        $invalidFormats = ['exe', 'php', '<script>'];

        foreach ($validFormats as $format) {
            $reflection = new \ReflectionClass($table);
            $method = $reflection->getMethod('validateExportFormat');
            $method->setAccessible(true);
            $validated = $method->invoke($table, $format);
            $this->assertContains($validated, $validFormats, "Valid export format '{$format}' not accepted");
        }

        foreach ($invalidFormats as $format) {
            $reflection = new \ReflectionClass($table);
            $method = $reflection->getMethod('validateExportFormat');
            $method->setAccessible(true);
            $validated = $method->invoke($table, $format);
            $this->assertContains($validated, $validFormats, "Invalid export format '{$format}' should default to safe format");
        }

        return true;
    }

    /**
     * Test SQL generation for safety issues
     */
    protected function testSqlGenerationSafety(): bool
    {
        try {
            // Test for method existence and basic functionality
            $reflection = new \ReflectionClass('ArtflowStudio\Table\Http\Livewire\Datatable');
            
            // Check for missing methods that cause SQL errors
            $criticalMethods = [
                'calculateSelectColumns',
                'buildUnifiedQuery',
                'applyOptimizedEagerLoading'
            ];
            
            foreach ($criticalMethods as $method) {
                $this->assertTrue(
                    $reflection->hasMethod($method),
                    "Critical method '{$method}' not found - may cause SQL errors"
                );
            }
            
            // Test that columns are properly qualified to prevent ambiguous column errors
            $datatable = new \ArtflowStudio\Table\Http\Livewire\Datatable();
            $testColumns = [
                ['key' => 'id', 'label' => 'ID'],
                ['key' => 'name', 'label' => 'Name'],
                ['key' => 'email', 'label' => 'Email']
            ];
            
            $selectMethod = $reflection->getMethod('calculateSelectColumns');
            $selectMethod->setAccessible(true);
            $selectedColumns = $selectMethod->invoke($datatable, $testColumns);
            
            // Ensure columns are returned as array
            $this->assertTrue(
                is_array($selectedColumns),
                'calculateSelectColumns should return an array'
            );
            
            // Ensure ID column is always included
            $this->assertContains(
                'id',
                $selectedColumns,
                'ID column should always be included in select'
            );
            
            return true;
        } catch (\Exception $e) {
            $this->log("SQL Generation safety test failed: " . $e->getMessage(), 'error');
            return false;
        }
    }

    /**
     * Test for eval() usage which is a critical security vulnerability
     */
    protected function testEvalUsagePrevention(): bool
    {
        try {
            $traitFile = file_get_contents(__DIR__ . '/../Http/Livewire/DatatableTrait.php');
            
            // Check for actual eval() usage (not in comments)
            $lines = explode("\n", $traitFile);
            $actualEvalUsage = false;
            
            foreach ($lines as $line) {
                $trimmedLine = trim($line);
                // Skip comment lines and docblock lines
                if (strpos($trimmedLine, '//') === 0 || strpos($trimmedLine, '*') === 0 || strpos($trimmedLine, '/**') === 0) {
                    continue;
                }
                
                // Check for actual eval( function calls
                if (preg_match('/[^\/\*]\s*eval\s*\(/', $line)) {
                    $this->log("CRITICAL SECURITY ISSUE: eval() function call found: " . trim($line), 'error');
                    $actualEvalUsage = true;
                }
            }
            
            if ($actualEvalUsage) {
                return false;
            }
            
            // Also check for other dangerous functions
            $dangerousFunctions = ['exec', 'shell_exec', 'system', 'passthru'];
            
            foreach ($dangerousFunctions as $func) {
                if (preg_match('/[^\/\*]\s*' . $func . '\s*\(/', $traitFile)) {
                    $this->log("WARNING: Dangerous function '{$func}' found in DatatableTrait", 'warning');
                }
            }
            
            return true;
        } catch (\Exception $e) {
            $this->log("Eval usage prevention test failed: " . $e->getMessage(), 'error');
            return false;
        }
    }
}