<?php

namespace ArtflowStudio\Table\Tests\Support;

use ArtflowStudio\Table\Tests\Models\TestCompany;
use ArtflowStudio\Table\Tests\Models\TestDepartment;
use ArtflowStudio\Table\Tests\Models\TestEmployee;

/**
 * Deterministic micro-dataset used by the whole test suite.
 *
 * Deliberately tiny (1 company / 2 departments / 5 employees) so that every
 * assertion can name exact rows instead of counting fuzzy results. The
 * package's own `AftableTestSeeder` generates 118k rows and is unusable in
 * tests — see AUDIT.md §Commands.
 */
final class DatasetSeeder
{
    /** @var array<int, array<string, mixed>> */
    public const EMPLOYEES = [
        // first_name, last_name, salary, position, department, status
        ['Alice', 'Anderson', 90000, 'Engineer', 'ENG', 'active'],
        ['Bob', 'Brown', 70000, 'Designer', 'DES', 'active'],
        ['Chloé', 'Château', 85000, 'Engineer', 'ENG', 'on_leave'],
        ['Dmitri', 'Volkov', 60000, 'Intern', 'ENG', 'inactive'],
        ['Émile', 'Zola', 120000, 'Director', 'DES', 'active'],
    ];

    public static function run(): void
    {
        $company = TestCompany::create([
            'name' => 'Artflow Studio',
            'email' => 'hq@artflow.test',
            'country' => 'Pakistan',
            'city' => 'Lahore',
            'employee_count' => 5,
            'revenue' => 1250000.00,
            'founded_date' => '2015-04-01',
            'metadata' => ['tier' => 'gold', 'region' => 'apac'],
            'status' => 'active',
            'is_verified' => true,
        ]);

        $departments = [];
        foreach ([['ENG', 'Engineering', 500000], ['DES', 'Design', 250000]] as [$code, $name, $budget]) {
            $departments[$code] = TestDepartment::create([
                'company_id' => $company->id,
                'name' => $name,
                'code' => $code,
                'description' => "{$name} department",
                'budget' => $budget,
                'settings' => ['remote' => $code === 'ENG', 'headcount' => $budget / 50000],
            ]);
        }

        foreach (self::EMPLOYEES as $i => [$first, $last, $salary, $position, $dept, $status]) {
            $employee = new TestEmployee([
                'company_id' => $company->id,
                'department_id' => $departments[$dept]->id,
                'first_name' => $first,
                'last_name' => $last,
                'email' => strtolower("{$first}.{$last}@artflow.test"),
                'phone' => '+92-300-000000'.$i,
                'birth_date' => '1990-01-0'.($i + 1),
                'hire_date' => '2020-0'.($i + 1).'-15',
                'salary' => $salary,
                'employment_type' => $position === 'Intern' ? 'intern' : 'full-time',
                'skills' => ['php', $position === 'Designer' ? 'figma' : 'laravel'],
                'preferences' => ['theme' => $i % 2 === 0 ? 'dark' : 'light', 'lang' => 'en'],
                'position' => $position,
                'years_experience' => 10 - $i,
                'is_active' => $status === 'active',
            ]);

            // `status` and `employee_code` are columns on the table but are missing
            // from TestEmployee::$fillable — set them outside mass assignment.
            $employee->status = $status;
            $employee->employee_code = sprintf('EMP%05d', $i + 1);
            $employee->save();
        }
    }
}
