<?php

require_once __DIR__ . '/../../../../autoload.php';

use ArtflowStudio\Table\Http\Livewire\DatatableTrait;

echo "=== Testing Validation Methods in DatatableTrait ===\n\n";

// Create a test class that extends DatatableTrait
class TestValidationTrait extends DatatableTrait
{
    public function __construct()
    {
        // Set up some test data
        $this->model = 'App\\Models\\User'; // Assume this exists
        $this->columns = [
            'name' => ['key' => 'name', 'label' => 'Name'],
            'email' => ['key' => 'email', 'label' => 'Email'],
            'user_profile' => ['relation' => 'profile:name', 'label' => 'Profile Name'],
            'invalid_relation' => ['relation' => 'invalid', 'label' => 'Invalid'], // Invalid format
            'json_field' => ['key' => 'settings', 'json' => 'theme.color', 'label' => 'Theme Color'],
            'function_column' => ['function' => 'custom_function', 'label' => 'Custom'],
            'invalid_column' => 'not_an_array', // Invalid - should be array
        ];
    }
}

try {
    $testTrait = new TestValidationTrait();
    
    echo "1. Testing validateColumns method:\n";
    if (method_exists($testTrait, 'validateColumns')) {
        echo "✓ validateColumns method exists\n";
        
        // Use reflection to call protected method
        $reflection = new ReflectionClass($testTrait);
        $method = $reflection->getMethod('validateColumns');
        $method->setAccessible(true);
        
        $result = $method->invoke($testTrait);
        echo "✓ validateColumns executed successfully\n";
        echo "  - Valid columns: " . count($result['valid']) . "\n";
        echo "  - Invalid columns: " . count($result['invalid']) . "\n";
        echo "  - Errors: " . count($result['errors']) . "\n";
        
        if (!empty($result['errors'])) {
            echo "  - Error details:\n";
            foreach ($result['errors'] as $error) {
                echo "    • {$error}\n";
            }
        }
    } else {
        echo "✗ validateColumns method NOT found\n";
    }
    
    echo "\n2. Testing validateRelationColumns method:\n";
    if (method_exists($testTrait, 'validateRelationColumns')) {
        echo "✓ validateRelationColumns method exists\n";
        
        // Use reflection to call protected method
        $reflection = new ReflectionClass($testTrait);
        $method = $reflection->getMethod('validateRelationColumns');
        $method->setAccessible(true);
        
        $result = $method->invoke($testTrait);
        echo "✓ validateRelationColumns executed successfully\n";
        echo "  - Valid relation columns: " . count($result['valid']) . "\n";
        echo "  - Invalid relation columns: " . count($result['invalid']) . "\n";
        echo "  - Warnings: " . count($result['warnings'] ?? []) . "\n";
        
        if (!empty($result['invalid'])) {
            echo "  - Invalid details:\n";
            foreach ($result['invalid'] as $column => $error) {
                echo "    • {$column}: {$error}\n";
            }
        }
        
        if (!empty($result['warnings'])) {
            echo "  - Warning details:\n";
            foreach ($result['warnings'] as $column => $warning) {
                echo "    • {$column}: {$warning}\n";
            }
        }
    } else {
        echo "✗ validateRelationColumns method NOT found\n";
    }
    
    echo "\n3. Testing validateConfiguration method:\n";
    if (method_exists($testTrait, 'validateConfiguration')) {
        echo "✓ validateConfiguration method exists\n";
        
        try {
            // Use reflection to call protected method
            $reflection = new ReflectionClass($testTrait);
            $method = $reflection->getMethod('validateConfiguration');
            $method->setAccessible(true);
            
            $method->invoke($testTrait);
            echo "✓ validateConfiguration executed successfully\n";
        } catch (Exception $e) {
            echo "✓ validateConfiguration executed (threw validation exception as expected)\n";
            echo "  - Exception: {$e->getMessage()}\n";
        }
    } else {
        echo "✗ validateConfiguration method NOT found\n";
    }
    
    echo "\n4. Testing all required trait methods are available:\n";
    $requiredMethods = [
        'validateColumns',
        'validateRelationColumns', 
        'validateConfiguration',
        'validateColumnConfiguration',
        'validateBasicRelationString',
        'validateJsonPath',
        'validateExportFormat',
        'isValidColumn',
        'isAllowedColumn'
    ];
    
    $missingMethods = [];
    foreach ($requiredMethods as $methodName) {
        if (method_exists($testTrait, $methodName)) {
            echo "✓ {$methodName}\n";
        } else {
            echo "✗ {$methodName} - MISSING\n";
            $missingMethods[] = $methodName;
        }
    }
    
    if (empty($missingMethods)) {
        echo "\n🎉 SUCCESS: All validation methods are available in DatatableTrait!\n";
    } else {
        echo "\n❌ FAILED: Missing methods: " . implode(', ', $missingMethods) . "\n";
    }
    
} catch (Exception $e) {
    echo "❌ ERROR: " . $e->getMessage() . "\n";
    echo "Trace: " . $e->getTraceAsString() . "\n";
}

echo "\n=== Test Complete ===\n";
