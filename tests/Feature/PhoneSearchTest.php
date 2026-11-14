<?php

namespace ArtflowStudio\Table\Tests\Feature;

use ArtflowStudio\Table\Tests\Models\TestEmployee;
use ArtflowStudio\Table\Tests\Support\ColumnSets;
use ArtflowStudio\Table\Tests\TestCase;
use Livewire\Livewire;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\Test;

/**
 * Phone numbers are typed in many shapes and stored as bare digits with a country code.
 */
class PhoneSearchTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();
        $this->seedSmallDataset();
        TestEmployee::where('first_name', 'Bob')->update(['phone' => '923139089173']);
    }

    private function names(string $search): array
    {
        return collect(Livewire::test('aftable', [
            'model' => ColumnSets::MODEL,
            'columns' => [['key' => 'first_name', 'label' => 'Name'], ['key' => 'phone', 'label' => 'Phone']],
        ])->set('search', $search)->viewData('data')->items())->pluck('first_name')->all();
    }

    public static function shapes(): array
    {
        return [
            'as stored' => ['923139089173'],
            'local, leading zero' => ['03139089173'],
            'local with a dash' => ['0313-9089173'],
            'international with spaces' => ['+92 313 9089173'],
            'a fragment' => ['908917'],
        ];
    }

    #[Test]
    #[DataProvider('shapes')]
    public function a_phone_number_is_found_however_it_is_typed(string $typed): void
    {
        $this->assertSame(['Bob'], $this->names($typed));
    }

    #[Test]
    public function a_word_is_still_searched_exactly_as_typed(): void
    {
        $this->assertSame(['Alice'], $this->names('Alice'));
    }
}
