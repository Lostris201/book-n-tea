<?php

namespace Tests\Feature\Admin;

use App\Enums\UserRole;
use App\Filament\Resources\Categories\Pages\CreateCategory;
use App\Filament\Resources\OptionGroups\Pages\EditOptionGroup;
use App\Filament\Resources\OptionGroups\RelationManagers\OptionsRelationManager;
use App\Filament\Resources\Products\Pages\CreateProduct;
use App\Filament\Resources\Products\Pages\EditProduct;
use App\Models\Category;
use App\Models\OptionGroup;
use App\Models\Product;
use Filament\Actions\Testing\TestAction;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Livewire\Livewire;
use Spatie\Activitylog\Models\Activity;

class MenuAdminTest extends AdminTestCase
{
    public function test_manager_edit_is_reflected_in_customer_menu_and_audit_log(): void
    {
        $manager = $this->actingAsRole(UserRole::Manager);
        $product = Product::where('slug', 'tea_1')->firstOrFail();

        Livewire::test(EditProduct::class, ['record' => $product->getRouteKey()])
            ->assertSchemaStateSet(['price_cents' => '95.00'])
            ->fillForm([
                'name' => 'Earl Grey Royal Special',
                'price_cents' => '130.50',
            ])
            ->call('save')
            ->assertHasNoFormErrors();

        $product->refresh();
        $this->assertSame(13050, $product->price_cents);

        // Customer menu reflects the change on the next request.
        $this->getJson('/api/menu')
            ->assertJsonPath('products.0.id', 'tea_1')
            ->assertJsonPath('products.0.name', 'Earl Grey Royal Special')
            ->assertJsonPath('products.0.price', 130.5);

        // Audit log records the edit with the manager as causer.
        $activity = Activity::where('subject_type', Product::class)
            ->where('subject_id', $product->id)
            ->where('event', 'updated')
            ->latest('id')
            ->firstOrFail();

        $this->assertTrue($activity->causer->is($manager));
        $this->assertSame(13050, $activity->properties['attributes']['price_cents']);
        $this->assertSame(9500, $activity->properties['old']['price_cents']);
        $this->assertSame('Earl Grey Royal', $activity->properties['old']['name']);
    }

    public function test_manager_creates_product_with_upload_and_option_groups(): void
    {
        $this->actingAsRole(UserRole::Manager);
        $category = Category::where('slug', 'coffee')->firstOrFail();
        $milk = OptionGroup::where('key', 'milk')->firstOrFail();

        Livewire::test(CreateProduct::class)
            ->fillForm([
                'name' => 'Türk Kahvesi',
                'category_id' => $category->id,
                'price_cents' => '80',
                'description' => 'Közde pişirilmiş.',
                // Real 1×1 PNG (the GD-based fake image generator isn't available everywhere).
                'image_path' => UploadedFile::fake()->createWithContent('turk-kahvesi.png', base64_decode(
                    'iVBORw0KGgoAAAANSUhEUgAAAAEAAAABCAYAAAAfFcSJAAAADUlEQVR42mP8z8BQDwAEhQGAhKmMIQAAAABJRU5ErkJggg=='
                )),
                'optionGroups' => [$milk->id],
                'is_active' => true,
            ])
            ->call('create')
            ->assertHasNoFormErrors();

        $product = Product::where('name', 'Türk Kahvesi')->firstOrFail();
        $this->assertSame('turk_kahvesi', $product->slug);
        $this->assertSame(8000, $product->price_cents);
        $this->assertSame(Product::max('sort_order'), $product->sort_order);
        $this->assertSame(['milk'], $product->optionGroups->pluck('key')->all());
        $this->assertStringStartsWith('products/', $product->image_path);
        Storage::disk('public')->assertExists($product->image_path);

        $menuProduct = collect($this->getJson('/api/menu')->json('products'))->firstWhere('id', 'turk_kahvesi');
        $this->assertSame(80, $menuProduct['price']);
        $this->assertTrue($menuProduct['customizable']);
        $this->assertStringContainsString('/storage/products/', $menuProduct['image']);
    }

    public function test_product_validation(): void
    {
        $this->actingAsRole(UserRole::Manager);

        Livewire::test(CreateProduct::class)
            ->fillForm(['name' => '', 'category_id' => null, 'price_cents' => '-5'])
            ->call('create')
            ->assertHasFormErrors(['name' => 'required', 'category_id' => 'required', 'price_cents']);
    }

    public function test_slug_is_generated_uniquely_and_locked_after_create(): void
    {
        $this->actingAsRole(UserRole::Manager);

        Livewire::test(CreateCategory::class)->fillForm(['name' => 'Özel Çaylar'])->call('create')->assertHasNoFormErrors();
        $this->assertTrue(Category::where('slug', 'ozel_caylar')->exists());

        Livewire::test(CreateCategory::class)->fillForm(['name' => 'Özel Çaylar'])->call('create')->assertHasNoFormErrors();
        $this->assertTrue(Category::where('slug', 'ozel_caylar_2')->exists());
    }

    public function test_manager_adds_option_to_group(): void
    {
        $this->actingAsRole(UserRole::Manager);
        $milk = OptionGroup::where('key', 'milk')->firstOrFail();

        Livewire::test(OptionsRelationManager::class, ['ownerRecord' => $milk, 'pageClass' => EditOptionGroup::class])
            ->callAction(TestAction::make('create')->table(), data: [
                'name' => 'Hindistan Cevizi Sütü',
                'price_cents' => '20',
                'is_active' => true,
            ])
            ->assertHasNoFormErrors();

        $option = $milk->options()->where('name', 'Hindistan Cevizi Sütü')->firstOrFail();
        $this->assertSame(2000, $option->price_cents);
        $this->assertSame(5, $option->sort_order);
        $this->assertSame('milk_hindistan_cevizi_sutu', $option->slug);

        $this->getJson('/api/menu')->assertJsonPath('options.milk.4.name', 'Hindistan Cevizi Sütü');
    }

    public function test_category_with_products_cannot_be_deleted(): void
    {
        $manager = $this->actingAsRole(UserRole::Manager);

        $this->assertFalse($manager->can('delete', Category::where('slug', 'tea')->first()));
        $this->assertTrue($manager->can('delete', Category::create(['name' => 'Boş'])));
    }
}
