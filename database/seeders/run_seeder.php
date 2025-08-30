<?php

require_once __DIR__ . '/../../../../../autoload.php';

$app = require_once __DIR__ . '/../../../../../bootstrap/app.php';
$app->boot();

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Carbon\Carbon;

// Clear existing data
DB::table('test_post_tags')->delete();
DB::table('test_profiles')->delete();
DB::table('test_comments')->delete();
DB::table('test_posts')->delete();
DB::table('test_tags')->delete();
DB::table('test_users')->delete();
DB::table('test_categories')->delete();

// Seed test_categories
$categories = [];
$categoryNames = ['Technology', 'Travel', 'Food', 'Health', 'Education', 'Entertainment', 'Sports', 'Business'];
foreach ($categoryNames as $name) {
    $categories[] = [
        'name' => $name,
        'slug' => Str::slug($name),
        'description' => "Description for {$name} category",
        'is_active' => rand(0, 1) ? true : false,
        'metadata' => json_encode([
            'featured' => rand(0, 1) ? true : false,
            'order' => rand(1, 10),
            'icon' => 'icon-' . strtolower($name)
        ]),
        'created_at' => Carbon::now()->subDays(rand(1, 30)),
        'updated_at' => Carbon::now()->subDays(rand(0, 5))
    ];
}
DB::table('test_categories')->insert($categories);
$categoryIds = DB::table('test_categories')->pluck('id')->toArray();

// Seed test_users
$users = [];
$statuses = ['active', 'inactive', 'pending'];
for ($i = 1; $i <= 50; $i++) {
    $users[] = [
        'name' => "Test User {$i}",
        'email' => "testuser{$i}@example.com",
        'username' => "testuser{$i}",
        'status' => $statuses[array_rand($statuses)],
        'birth_date' => Carbon::now()->subYears(rand(18, 65))->format('Y-m-d'),
        'profile' => json_encode([
            'first_name' => "Test",
            'last_name' => "User {$i}",
            'phone' => "+1234567890" . str_pad($i, 2, '0', STR_PAD_LEFT),
            'address' => [
                'street' => "{$i} Test Street",
                'city' => 'Test City',
                'country' => 'Test Country'
            ]
        ]),
        'preferences' => json_encode([
            'theme' => rand(0, 1) ? 'dark' : 'light',
            'notifications' => rand(0, 1) ? true : false,
            'language' => ['en', 'fr', 'es'][array_rand(['en', 'fr', 'es'])],
            'timezone' => 'UTC'
        ]),
        'created_at' => Carbon::now()->subDays(rand(1, 100)),
        'updated_at' => Carbon::now()->subDays(rand(0, 10))
    ];
}
DB::table('test_users')->insert($users);
$userIds = DB::table('test_users')->pluck('id')->toArray();

echo "Test tables seeded successfully!\n";
echo "Categories: " . count($categories) . "\n";
echo "Users: " . count($users) . "\n";
