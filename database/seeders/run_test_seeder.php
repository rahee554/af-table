<?php

require __DIR__ . '/../../../../../../bootstrap/app.php';

use ArtflowStudio\Table\Database\Seeders\TestTablesSeeder;

// Boot the Laravel application
$app = require_once __DIR__ . '/../../../../../../bootstrap/app.php';

$seeder = new TestTablesSeeder();
$seeder->run();

echo "Seeding completed successfully!\n";
