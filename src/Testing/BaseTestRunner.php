<?php

namespace ArtflowStudio\Table\Testing;

use Illuminate\Console\Command;

abstract class BaseTestRunner
{
    /**
     * Console command instance
     */
    protected Command $command;

    /**
     * Test results
     */
    protected array $results = [];

    /**
     * Constructor
     */
    public function __construct(Command $command)
    {
        $this->command = $command;
    }

    /**
     * Run all tests in this suite
     */
    abstract public function run(): array;

    /**
     * Get test suite name
     */
    abstract public function getName(): string;

    /**
     * Get test suite description
     */
    abstract public function getDescription(): string;

    /**
     * Run a single test
     */
    protected function runTest(string $testName, callable $testFunction): bool
    {
        $this->command->line("  🔄 Running: {$testName}");
        
        try {
            $startTime = microtime(true);
            $result = $testFunction();
            $endTime = microtime(true);
            
            $duration = round(($endTime - $startTime) * 1000, 2);
            
            if ($result === true) {
                $this->command->info("    ✅ {$testName} - PASSED ({$duration}ms)");
                $this->results['passed'][] = $testName;
                return true;
            } else {
                $this->command->error("    ❌ {$testName} - FAILED ({$duration}ms)");
                $this->results['failed'][] = $testName;
                return false;
            }
        } catch (\Exception $e) {
            $this->command->error("    ❌ {$testName} - ERROR: " . $e->getMessage());
            $this->results['failed'][] = $testName;
            return false;
        }
    }

    /**
     * Get test results summary
     */
    protected function getResults(): array
    {
        $passed = count($this->results['passed'] ?? []);
        $failed = count($this->results['failed'] ?? []);
        $total = $passed + $failed;

        return [
            'total' => $total,
            'passed' => $passed,
            'failed' => $failed,
            'failures' => $this->results['failed'] ?? [],
            'success_rate' => $total > 0 ? round(($passed / $total) * 100, 2) : 0
        ];
    }

    /**
     * Assert that a condition is true
     */
    protected function assertTrue(bool $condition, string $message = ''): bool
    {
        if (!$condition) {
            throw new \Exception($message ?: 'Assertion failed: expected true');
        }
        return true;
    }

    /**
     * Assert that a condition is false
     */
    protected function assertFalse(bool $condition, string $message = ''): bool
    {
        if ($condition) {
            throw new \Exception($message ?: 'Assertion failed: expected false');
        }
        return true;
    }

    /**
     * Assert that two values are equal
     */
    protected function assertEquals($expected, $actual, string $message = ''): bool
    {
        if ($expected !== $actual) {
            throw new \Exception($message ?: "Assertion failed: expected '{$expected}', got '{$actual}'");
        }
        return true;
    }

    /**
     * Assert that string contains substring
     */
    protected function assertStringContainsString(string $needle, string $haystack, string $message = ''): bool
    {
        if (strpos($haystack, $needle) === false) {
            throw new \Exception($message ?: "Assertion failed: string '{$haystack}' does not contain '{$needle}'");
        }
        return true;
    }

    /**
     * Assert that string ends with substring
     */
    protected function assertStringEndsWith(string $suffix, string $string, string $message = ''): bool
    {
        if (!str_ends_with($string, $suffix)) {
            throw new \Exception($message ?: "Assertion failed: string '{$string}' does not end with '{$suffix}'");
        }
        return true;
    }

    /**
     * Assert that two values are not equal
     */
    protected function assertNotEquals($expected, $actual, string $message = ''): bool
    {
        if ($expected === $actual) {
            throw new \Exception($message ?: "Assertion failed: expected not to equal '{$expected}'");
        }
        return true;
    }

    /**
     * Assert that value is null
     */
    protected function assertNull($value, string $message = ''): bool
    {
        if ($value !== null) {
            throw new \Exception($message ?: 'Assertion failed: expected null');
        }
        return true;
    }

    /**
     * Assert that value is not null
     */
    protected function assertNotNull($value, string $message = ''): bool
    {
        if ($value === null) {
            throw new \Exception($message ?: 'Assertion failed: expected not null');
        }
        return true;
    }

    /**
     * Assert that array contains value
     */
    protected function assertContains($needle, array $haystack, string $message = ''): bool
    {
        if (!in_array($needle, $haystack)) {
            throw new \Exception($message ?: "Assertion failed: array does not contain '{$needle}'");
        }
        return true;
    }

    /**
     * Assert that string contains substring
     */
    protected function assertStringContains(string $needle, string $haystack, string $message = ''): bool
    {
        if (strpos($haystack, $needle) === false) {
            throw new \Exception($message ?: "Assertion failed: string does not contain '{$needle}'");
        }
        return true;
    }

    /**
     * Assert that value is instance of class
     */
    protected function assertInstanceOf(string $expected, $actual, string $message = ''): bool
    {
        if (!($actual instanceof $expected)) {
            throw new \Exception($message ?: "Assertion failed: expected instance of '{$expected}'");
        }
        return true;
    }

    /**
     * Assert that array has key
     */
    protected function assertArrayHasKey($key, array $array, string $message = ''): bool
    {
        if (!array_key_exists($key, $array)) {
            throw new \Exception($message ?: "Assertion failed: array does not have key '{$key}'");
        }
        return true;
    }

    /**
     * Assert that value is greater than
     */
    protected function assertGreaterThan($expected, $actual, string $message = ''): bool
    {
        if ($actual <= $expected) {
            throw new \Exception($message ?: "Assertion failed: expected '{$actual}' to be greater than '{$expected}'");
        }
        return true;
    }

    /**
     * Assert that value is less than
     */
    protected function assertLessThan($expected, $actual, string $message = ''): bool
    {
        if ($actual >= $expected) {
            throw new \Exception($message ?: "Assertion failed: expected '{$actual}' to be less than '{$expected}'");
        }
        return true;
    }

    /**
     * Assert that value is an array
     */
    protected function assertIsArray($value, string $message = ''): bool
    {
        if (!is_array($value)) {
            throw new \Exception($message ?: 'Assertion failed: expected array');
        }
        return true;
    }

    /**
     * Assert count of array or countable
     */
    protected function assertCount(int $expected, $actual, string $message = ''): bool
    {
        if (is_array($actual)) {
            $count = count($actual);
        } elseif ($actual instanceof \Countable) {
            $count = $actual->count();
        } else {
            throw new \Exception($message ?: 'Assertion failed: value is not countable');
        }

        if ($count !== $expected) {
            throw new \Exception($message ?: "Assertion failed: expected count {$expected}, got {$count}");
        }
        return true;
    }

    /**
     * Create a mock model for testing
     */
    protected function createMockModel(array $attributes = []): object
    {
        return (object) array_merge([
            'id' => 1,
            'name' => 'Test Model',
            'email' => 'test@example.com',
            'created_at' => now(),
            'updated_at' => now(),
        ], $attributes);
    }

    /**
     * Create mock collection for testing
     */
    protected function createMockCollection(int $count = 10): array
    {
        $items = [];
        for ($i = 1; $i <= $count; $i++) {
            $items[] = $this->createMockModel([
                'id' => $i,
                'name' => "Test Model {$i}",
                'email' => "test{$i}@example.com",
            ]);
        }
        return $items;
    }

    /**
     * Measure execution time
     */
    protected function measureTime(callable $callback): array
    {
        $startTime = microtime(true);
        $startMemory = memory_get_usage();
        
        $result = $callback();
        
        $endTime = microtime(true);
        $endMemory = memory_get_usage();
        
        return [
            'result' => $result,
            'time' => ($endTime - $startTime) * 1000, // milliseconds
            'memory' => $endMemory - $startMemory, // bytes
            'peak_memory' => memory_get_peak_usage()
        ];
    }

    /**
     * Log test message
     */
    protected function log(string $message, string $level = 'info'): void
    {
        switch ($level) {
            case 'error':
                $this->command->error("    🔴 {$message}");
                break;
            case 'warning':
                $this->command->warn("    🟡 {$message}");
                break;
            case 'success':
                $this->command->info("    🟢 {$message}");
                break;
            default:
                $this->command->line("    ℹ️  {$message}");
                break;
        }
    }
}
