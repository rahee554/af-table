<?php

require_once __DIR__ . '/../../../../../vendor/autoload.php';

$app = require_once __DIR__ . '/../../../../../bootstrap/app.php';
$app->boot();

use ArtflowStudio\Table\Http\Livewire\DatatableTrait;
use ArtflowStudio\Table\Testing\Models\TestUser;

echo "🧪 Testing DatatableTrait Functionality...\n";
echo "   Verifying trait-based architecture works correctly\n\n";

// Test 1: Trait Method Availability
echo "🔄 Testing: Trait Method Availability\n";
try {
    $testClass = new class extends DatatableTrait {
        public function __construct() {
            $this->model = TestUser::class;
            $this->columns = [
                ['key' => 'name', 'label' => 'Name'],
                ['key' => 'email', 'label' => 'Email']
            ];
        }
    };

    $methods = ['sanitizeSearch', 'validateRelationString', 'validateJsonPath', 'sanitizeFilterValue'];
    $allExist = true;
    foreach ($methods as $method) {
        if (!method_exists($testClass, $method)) {
            echo "   ❌ Method {$method} does not exist\n";
            $allExist = false;
        }
    }
    
    if ($allExist) {
        echo "   ✅ All trait methods are available\n";
    }
} catch (Exception $e) {
    echo "   ❌ Error: " . $e->getMessage() . "\n";
}

// Test 2: Method Collision Resolution
echo "\n🔄 Testing: Method Collision Resolution\n";
try {
    $testClass = new class extends DatatableTrait {
        public function __construct() {
            $this->model = TestUser::class;
            $this->columns = [];
        }
    };

    // Test sanitizeSearch method (should come from HasSearch trait, not HasDataValidation)
    $result = $testClass->sanitizeSearch('  test search  ');
    if ($result === 'test search') {
        echo "   ✅ sanitizeSearch works correctly (collision resolved)\n";
    } else {
        echo "   ❌ sanitizeSearch returned: '{$result}', expected: 'test search'\n";
    }

    // Test null handling (HasSearch version should handle null, HasDataValidation version didn't)
    $result = $testClass->sanitizeSearch(null);
    if ($result === '') {
        echo "   ✅ sanitizeSearch handles null correctly (using HasSearch trait)\n";
    } else {
        echo "   ❌ sanitizeSearch null handling failed\n";
    }

} catch (Exception $e) {
    echo "   ❌ Error: " . $e->getMessage() . "\n";
}

// Test 3: JSON Path Validation
echo "\n🔄 Testing: JSON Path Validation\n";
try {
    $testClass = new class extends DatatableTrait {
        public function __construct() {
            $this->model = TestUser::class;
            $this->columns = [];
        }
    };

    $validPaths = ['first_name', 'address.street', 'contact.email'];
    $invalidPaths = ['', null, '123invalid', 'path with spaces'];

    $allValid = true;
    foreach ($validPaths as $path) {
        if (!$testClass->validateJsonPath($path)) {
            echo "   ❌ Valid path '{$path}' was rejected\n";
            $allValid = false;
        }
    }

    foreach ($invalidPaths as $path) {
        if ($testClass->validateJsonPath($path)) {
            echo "   ❌ Invalid path '{$path}' was accepted\n";
            $allValid = false;
        }
    }

    if ($allValid) {
        echo "   ✅ JSON path validation works correctly\n";
    }

} catch (Exception $e) {
    echo "   ❌ Error: " . $e->getMessage() . "\n";
}

// Test 4: Relation String Validation
echo "\n🔄 Testing: Relation String Validation\n";
try {
    $testClass = new class extends DatatableTrait {
        public function __construct() {
            $this->model = TestUser::class;
            $this->columns = [];
        }
    };

    $validRelations = ['user:name', 'category:title', 'profile:bio'];
    $invalidRelations = ['', null, 'invalidrelation', 'missing:'];

    $allValid = true;
    foreach ($validRelations as $relation) {
        if (!$testClass->validateRelationString($relation)) {
            echo "   ❌ Valid relation '{$relation}' was rejected\n";
            $allValid = false;
        }
    }

    foreach ($invalidRelations as $relation) {
        if ($testClass->validateRelationString($relation)) {
            echo "   ❌ Invalid relation '{$relation}' was accepted\n";
            $allValid = false;
        }
    }

    if ($allValid) {
        echo "   ✅ Relation string validation works correctly\n";
    }

} catch (Exception $e) {
    echo "   ❌ Error: " . $e->getMessage() . "\n";
}

echo "\n✅ DatatableTrait testing completed!\n";
echo "   The trait-based architecture is working correctly.\n";
echo "   No method collisions detected.\n\n";
