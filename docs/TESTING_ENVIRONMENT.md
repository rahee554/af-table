# AF Table Testing Environment Documentation

## Overview
This testing environment provides a comprehensive, real-world test setup for the AFTable package with:
- 10 interconnected database tables with complex relationships
- 118,100+ test records
- Multiple relation types (one-to-many, many-to-many, polymorphic)
- JSON columns with nested data
- Date ranges, enums, and various data types
- Web interface for live testing

---

## Quick Start

### 1. Run Migration
```bash
cd vendor/artflow-studio/table
php artisan migrate --path=database/migrations/2024_01_01_000000_create_aftable_test_tables.php
```

### 2. Seed Data
```bash
php artisan db:seed --class="ArtflowStudio\Table\Database\Seeders\AftableTestSeeder"
```

### 3. Access Test Interface
```
http://your-app.test/aftable/test
```

---

## Database Structure

### Tables Created

| Table | Records | Purpose | Relationships |
|-------|---------|---------|---------------|
| `test_companies` | 100 | Parent organization | Has many departments, employees, projects, clients |
| `test_departments` | 500 | Org structure | Belongs to company, has many employees |
| `test_employees` | 10,000 | Staff records | Belongs to company & department, many-to-many with projects |
| `test_projects` | 2,000 | Project tracking | Belongs to company, many-to-many with employees |
| `test_tasks` | 15,000 | Task management | Belongs to project, assigned to employee |
| `test_employee_project` | 25,000 | Pivot table | Many-to-many relationship |
| `test_clients` | 3,000 | Client management | Belongs to company, has many invoices |
| `test_invoices` | 8,000 | Billing | Belongs to client and project |
| `test_timesheets` | 50,000 | Time tracking | Belongs to employee and task |
| `test_documents` | 5,000 | Polymorphic docs | Belongs to multiple models |

**Total Records: 118,100+**

---

## Complex Relationships Tested

### 1. One-to-Many
```php
Company → Departments
Company → Employees
Company → Projects
Project → Tasks
Employee → Timesheets
```

### 2. Many-to-Many
```php
Employee ↔ Project (with pivot data: role, hours_allocated, joined_at)
```

### 3. Nested Relationships
```php
Company → Department → Manager (Employee)
Company → Project → Tasks → Assignee (Employee)
Task → Project → Company
```

### 4. Polymorphic
```php
Document → documentable (Company | Project | Employee)
```

### 5. Self-Referencing
```php
Department → Manager (Employee)
Employee → Managed Department
```

---

## JSON Column Testing

### Companies Table (`metadata` column)
```json
{
    "industry": "Technology",
    "website": "https://example.com",
    "employees_range": "51-200",
    "year_founded": 1995
}
```

### Employees Table (`skills` and `preferences` columns)
```json
// skills
["PHP", "JavaScript", "Python", "Laravel", "React"]

// preferences
{
    "theme": "dark",
    "language": "en",
    "notifications": true,
    "timezone": "America/New_York"
}
```

### Projects Table (`milestones` column)
```json
[
    {"name": "Kickoff", "date": "2024-01-15", "completed": true},
    {"name": "Phase 1", "date": "2024-03-01", "completed": false}
]
```

### Invoices Table (`line_items` column)
```json
[
    {"description": "Development services", "quantity": 40, "rate": 150.00},
    {"description": "Consulting", "quantity": 10, "rate": 200.00}
]
```

---

## Data Type Coverage

### Tested Column Types
- `id` - Primary keys
- `string` - Names, emails, codes
- `text` - Descriptions, notes
- `integer` - Counts, hours
- `decimal` - Money, percentages
- `date` - Dates without time
- `datetime` - Timestamps
- `time` - Time values
- `boolean` - Flags
- `enum` - Status, priority, type fields
- `json` - Complex structured data
- `morphs` - Polymorphic relations

### Index Coverage
- Single column indexes
- Composite indexes
- Unique indexes
- Foreign key indexes

---

## Test Scenarios

### 1. Performance Tests
- **Query Caching**: 10,000 employees with filters
- **N+1 Prevention**: Nested relations (Company → Department → Manager)
- **Distinct Values**: Status, priority enums across tables
- **Filter Consolidation**: Multiple simultaneous filters

### 2. Relationship Tests
- Simple one-to-many (Company → Employees)
- Nested three-level (Task → Project → Company)
- Many-to-many with pivot (Employee ↔ Project)
- Polymorphic (Document → various models)

### 3. Search Tests
- Text columns (names, emails)
- JSON paths (metadata.industry, skills[])
- Relation columns (employee.company.name)
- Date ranges

### 4. Filter Tests
- Text filters (minimum 3 characters)
- Number filters (with operators: =, !=, <, >, <=, >=)
- Date filters (single date, date ranges)
- Enum filters (distinct values dropdown)
- Relation filters (foreign key lookups)

### 5. Sort Tests
- String columns
- Numeric columns
- Date columns
- Relation columns (e.g., sort by company.name)

### 6. Export Tests
- Small datasets (< 1000 records)
- Medium datasets (1000-10000 records)
- Large datasets (> 10000 records with chunking)

---

## Test Models

All models located in: `vendor/artflow-studio/table/src/Tests/Models/`

```
TestCompany.php
TestDepartment.php
TestEmployee.php
TestProject.php
TestTask.php
TestClient.php
TestInvoice.php
TestTimesheet.php
TestDocument.php
```

### Model Features
- Proper fillable arrays
- Type casting for all fields
- All relationship methods defined
- Accessors and mutators where appropriate
- Soft deletes on appropriate models

---

## Testing the Component

### Example Usage in Blade
```blade
@livewire('aftable', [
    'model' => '\ArtflowStudio\Table\Tests\Models\TestEmployee',
    'columns' => [
        ['key' => 'id', 'label' => 'ID', 'sortable' => true],
        ['key' => 'full_name', 'label' => 'Name', 'function' => 'full_name'],
        ['key' => 'email', 'label' => 'Email', 'searchable' => true],
        ['key' => 'company_name', 'label' => 'Company', 'relation' => 'company:name'],
        ['key' => 'dept_name', 'label' => 'Department', 'relation' => 'department:name'],
        ['key' => 'position', 'label' => 'Position', 'sortable' => true],
        ['key' => 'salary', 'label' => 'Salary', 'sortable' => true],
        ['key' => 'hire_date', 'label' => 'Hire Date', 'sortable' => true],
        ['key' => 'skills', 'label' => 'Skills', 'json' => '*'],
        ['key' => 'preferences', 'label' => 'Theme', 'json' => 'theme'],
        ['key' => 'is_active', 'label' => 'Status', 
         'raw' => '<span class="badge badge-{{ $row->is_active ? \'success\' : \'danger\' }}">
                    {{ $row->is_active ? \'Active\' : \'Inactive\' }}
                   </span>'],
    ],
    'filters' => [
        'first_name' => ['type' => 'text'],
        'email' => ['type' => 'text'],
        'salary' => ['type' => 'number'],
        'hire_date' => ['type' => 'date'],
        'employment_type' => ['type' => 'distinct'],
        'company_id' => ['type' => 'distinct', 'relation' => 'company:name'],
        'is_active' => ['type' => 'distinct'],
    ],
    'records' => 25,
    'exportable' => true,
    'searchable' => true,
    'sort' => 'desc',
    'sortColumn' => 'created_at',
])
```

---

## Performance Benchmarks

Expected performance with optimizations:

| Operation | Records | Before | After | Improvement |
|-----------|---------|--------|-------|-------------|
| Initial Load | 10,000 | ~2.5s | ~0.4s | 84% faster |
| With Filters | 5,000 | ~1.8s | ~0.3s | 83% faster |
| With Relations | 10,000 | ~5.0s | ~0.6s | 88% faster |
| Distinct Values | 5 filters | ~0.5s | ~0.05s | 90% faster |
| Export CSV | 10,000 | ~3.0s | ~1.2s | 60% faster |

---

## Cleanup

### Drop All Test Tables
```bash
php artisan migrate:rollback --path=vendor/artflow-studio/table/database/migrations/2024_01_01_000000_create_aftable_test_tables.php
```

### Truncate Data Only
```php
DB::statement('SET FOREIGN_KEY_CHECKS=0;');
// Truncate each table
DB::statement('SET FOREIGN_KEY_CHECKS=1;');
```

---

## Troubleshooting

### Foreign Key Errors
- Ensure tables are created in correct order
- Check that parent IDs exist before inserting child records

### Memory Issues During Seeding
- The seeder uses chunking (1000-2000 records per batch)
- Increase PHP memory limit if needed: `php -d memory_limit=512M artisan db:seed`

### Slow Query Performance
- Ensure indexes are created
- Run `ANALYZE TABLE` after seeding
- Check query cache is working

---

## Files Created

```
vendor/artflow-studio/table/
├── database/
│   ├── migrations/
│   │   └── 2024_01_01_000000_create_aftable_test_tables.php
│   └── seeders/
│       └── AftableTestSeeder.php
├── src/
│   ├── Http/
│   │   └── Livewire/
│   │       └── TestTableComponent.php
│   └── Tests/
│       ├── Models/
│       │   ├── TestCompany.php
│       │   ├── TestDepartment.php
│       │   ├── TestEmployee.php
│       │   ├── TestProject.php
│       │   ├── TestTask.php
│       │   ├── TestClient.php
│       │   ├── TestInvoice.php
│       │   ├── TestTimesheet.php
│       │   └── TestDocument.php
│       └── Views/
│           └── test-interface.blade.php
└── routes/
    └── test.php
```

---

## Next Steps

1. ✅ Run migration
2. ✅ Seed data
3. ✅ Access test interface
4. Test all features:
   - Searching
   - Filtering (all types)
   - Sorting
   - Pagination
   - Export
   - Column visibility
   - Relation loading
   - JSON column extraction
5. Run performance tests
6. Review query logs
7. Test with large datasets (50k+ records)

---

## Support

For issues or questions:
- Check package documentation
- Review test scenarios above
- Examine generated SQL queries
- Enable query logging for debugging

---

**Version**: 1.0  
**Last Updated**: 2024  
**Records Generated**: 118,100+  
**Tables**: 10  
**Complex Relationships**: 15+
