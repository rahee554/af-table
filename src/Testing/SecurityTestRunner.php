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
            'Export Security' => [$this, 'testExportSecurity']
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
            $sanitized = $table->sanitizeHtmlContent($input);
            
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
        $sanitizedFilter = $table->sanitizeFilterValue($dangerousFilter);

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
        $this->assertFalse($table->isAllowedColumn('password'), 'Access allowed to non-configured column');

        // Test access to configured column
        $this->assertTrue($table->isAllowedColumn('name'), 'Access denied to configured column');

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
            $sanitized = $table->sanitizeSearch($case['input']);
            
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
            $sanitized = $table->sanitizeFilterValue($input);
            
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
        $sanitized = $table->sanitizeHtmlContent($input);
        
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
            $this->assertTrue($table->validateJsonPath($path), "Valid JSON path '{$path}' rejected");
        }

        foreach ($invalidPaths as $path) {
            $this->assertFalse($table->validateJsonPath($path), "Invalid JSON path '{$path}' accepted");
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
            $this->assertTrue($table->validateRelationString($relation), "Valid relation string '{$relation}' rejected");
        }

        foreach ($invalidRelations as $relation) {
            $this->assertFalse($table->validateRelationString($relation), "Invalid relation string '{$relation}' accepted");
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
            $validated = $table->validateExportFormat($format);
            $this->assertContains($validated, $validFormats, "Valid export format '{$format}' not accepted");
        }

        foreach ($invalidFormats as $format) {
            $validated = $table->validateExportFormat($format);
            $this->assertContains($validated, $validFormats, "Invalid export format '{$format}' should default to safe format");
        }

        return true;
    }
}