<?php

namespace Database\Seeders;

use App\Models\Category;
use App\Models\Option;
use App\Models\OptionGroup;
use App\Models\Product;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Storage;

/**
 * Ported from legacy/admin.js INITIAL_SEED. Prices are TL there; stored here as kuruş (×100).
 */
class MenuSeeder extends Seeder
{
    private const CATEGORIES = [
        ['slug' => 'tea', 'name' => 'Özel Çaylar', 'icon' => '🍵', 'sort_order' => 1],
        ['slug' => 'coffee', 'name' => 'Kahve Sanatı', 'icon' => '☕', 'sort_order' => 2],
        ['slug' => 'bakery', 'name' => 'Kütüphane Fırını', 'icon' => '🥐', 'sort_order' => 3],
        ['slug' => 'dessert', 'name' => 'Tatlılar', 'icon' => '🍰', 'sort_order' => 4],
        ['slug' => 'sandwich', 'name' => 'Sandviç & Tost', 'icon' => '🥪', 'sort_order' => 5],
        ['slug' => 'books', 'name' => 'Kitap & Merch', 'icon' => '📖', 'sort_order' => 6],
    ];

    // [slug, category, name, price TL, description, image, bestseller, new]
    private const PRODUCTS = [
        ['tea_1', 'tea', 'Earl Grey Royal', 95, 'Bergamot harmanlı siyah çay, kurutulmuş peygamber çiçeği ve portakal kabuğu ile demlenmiş kraliyet serisi.', 'products/earl_grey.png', true, false],
        ['tea_2', 'tea', 'Japon Sencha Yeşil Çay', 90, 'Birinci hasat Japon yeşil çay yaprakları; ferahlatıcı ve zengin antioksidan deposu.', 'products/earl_grey.png', false, false],
        ['tea_3', 'tea', 'Papatya & Fransız Lavantası', 85, 'Dinlendirici organik papatya tomurcukları ve rahatlatıcı Provence lavantası.', 'products/earl_grey.png', false, false],
        ['tea_4', 'tea', 'Chai Tea Latte', 115, 'Geleneksel Hint baharatları, demlenmiş aromatik siyah çay ve ipeksi süt köpüğü.', 'products/earl_grey.png', true, false],
        ['tea_5', 'tea', 'Buzlu Şeftali & Hibiskus', 105, 'Ev yapımı organik şeftali püresi, soğuk demlenmiş ekşi hibiskus çayı ve taze nane.', 'products/earl_grey.png', false, true],
        ['coff_1', 'coffee', 'Kütüphane Özel Latte', 120, 'Çift shot nitelikli Kolombiya espresso, yulaf sütlü kadifemsi doku ve hafif karamel lezzeti.', 'products/latte.png', true, false],
        ['coff_2', 'coffee', 'Double Ristretto Espresso', 75, '%100 Arabica nitelikli harman, kısa ve yoğun aroma.', 'products/latte.png', false, false],
        ['coff_3', 'coffee', 'Velvet Cappuccino', 110, 'Dengeli espresso bazı ve ipeksi kıvamda yoğun mikro süt köpüğü.', 'products/latte.png', false, false],
        ['coff_4', 'coffee', 'Cold Brew Reserve', 125, 'Etiyopya Yirgacheffe çekirdeklerinden 18 saat soğuk demlenmiş yumuşak içimli nitelikli kahve.', 'products/latte.png', true, false],
        ['bakery_1', 'bakery', 'Avokadolu Ekşi Maya Toast', 165, 'Kendi fırınımızdan çıkan kızarmış ekşi maya ekmek, sızma zeytinyağlı avokado pürüzü ve poşe yumurta.', 'products/avocado_toast.png', true, false],
        ['bakery_2', 'bakery', 'Tereyağlı Fransız Kruvasanı', 85, 'Fransız tereyağı ile hazırlanan, kat kat çıtır ve yumuşak taze fırınlanmış kruvasan.', 'products/avocado_toast.png', false, false],
        ['dessert_1', 'dessert', 'San Sebastian Cheesecake', 155, 'İspanyol usulü fırınlanmış yanık üzeri ve kremsi akışkan içi ile; yanında karadut kompostosu.', 'products/cheesecake.png', true, false],
        ['dessert_2', 'dessert', 'Sıcak Çikolatalı Brownie', 135, '%70 Belçika çikolatası ve kıyılmış ceviz içi ile hazırlanan sıcak servis dilim.', 'products/cheesecake.png', false, false],
        ['dessert_3', 'dessert', 'Geleneksel Tiramisu', 160, 'Mascarpone peynirli hafif krema ve espressolu kedi dili bisküvileri katmanları.', 'products/cheesecake.png', false, true],
        ['sand_1', 'sandwich', 'Gurme Peynir & Şarküteri Tabağı', 195, 'Üç çeşit olgunlaştırılmış peynir, ceviz içi, kuru incir, kovan balı ve çıtır kıtır ekmekler.', 'products/avocado_toast.png', false, false],
        ['sand_2', 'sandwich', 'Fesleğenli Mozzarella Panini', 160, 'Sıcak ciabatta ekmeğinde erimiş taze mozzarella, domates ve ev yapımı fesleğen pesto sosu.', 'products/avocado_toast.png', false, false],
        ['book_1', 'books', 'Book & Tea Seramik Fincan', 250, 'Özel tasarım el yapımı toprak mat seramik fincan. Logolu özel kutusunda.', 'products/hero.png', false, false],
        ['book_2', 'books', 'Deri Kitap Ayracı & Not Defteri', 180, 'Hakiki deri kitap ayracı ve noktalı kütüphane not defteri seti.', 'products/hero.png', false, false],
    ];

    private const OPTION_GROUPS = [
        'milk' => ['name' => 'Süt Tercihi', 'is_multi_select' => false, 'options' => [
            ['m_1', 'Tam Yağlı Süt', 0],
            ['m_2', 'Laktozsuz Süt', 0],
            ['m_3', 'Yulaf Sütü', 15],
            ['m_4', 'Badem Sütü', 15],
        ]],
        'sugar' => ['name' => 'Şeker Derecesi', 'is_multi_select' => false, 'options' => [
            ['s_1', 'Şekersiz', 0],
            ['s_2', 'Az Şekerli', 0],
            ['s_3', 'Orta Şekerli', 0],
            ['s_4', 'Çok Şekerli', 0],
        ]],
        'extras' => ['name' => 'Ekstralar', 'is_multi_select' => true, 'options' => [
            ['e_1', 'Extra Shot', 20],
            ['e_2', 'Vanilya Şurubu', 15],
            ['e_3', 'Karamel Şurubu', 15],
            ['e_4', 'Krema', 15],
        ]],
    ];

    // productOptionMappings: only groups set to true.
    private const PRODUCT_OPTION_GROUPS = [
        'coff_1' => ['milk', 'sugar', 'extras'],
        'coff_3' => ['milk', 'sugar', 'extras'],
        'tea_1' => ['milk', 'sugar'],
        'tea_4' => ['milk', 'sugar', 'extras'],
    ];

    public function run(): void
    {
        $this->copySeedImages();

        $categories = [];
        foreach (self::CATEGORIES as $data) {
            $categories[$data['slug']] = Category::updateOrCreate(
                ['slug' => $data['slug']],
                $data + ['is_active' => true],
            );
        }

        $groups = [];
        foreach (self::OPTION_GROUPS as $key => $data) {
            $groups[$key] = OptionGroup::updateOrCreate(
                ['key' => $key],
                ['name' => $data['name'], 'is_multi_select' => $data['is_multi_select']],
            );

            foreach ($data['options'] as $i => [$slug, $name, $priceTl]) {
                Option::updateOrCreate(['slug' => $slug], [
                    'option_group_id' => $groups[$key]->id,
                    'name' => $name,
                    'price_cents' => $priceTl * 100,
                    'sort_order' => $i + 1,
                    'is_active' => true,
                ]);
            }
        }

        foreach (self::PRODUCTS as $i => [$slug, $category, $name, $priceTl, $description, $image, $bestseller, $new]) {
            $product = Product::updateOrCreate(['slug' => $slug], [
                'category_id' => $categories[$category]->id,
                'name' => $name,
                'description' => $description,
                'image_path' => $image,
                'price_cents' => $priceTl * 100,
                'is_active' => true,
                'is_bestseller' => $bestseller,
                'is_new' => $new,
                'sort_order' => $i + 1,
            ]);

            $groupKeys = self::PRODUCT_OPTION_GROUPS[$slug] ?? [];
            $product->optionGroups()->sync(
                array_map(fn (string $key) => $groups[$key]->id, $groupKeys)
            );
        }
    }

    /** Seed images ship in database/seeders/images and are served like admin uploads. */
    private function copySeedImages(): void
    {
        $disk = Storage::disk('public');

        foreach (glob(__DIR__.'/images/*') ?: [] as $file) {
            $target = Product::IMAGE_DIRECTORY.'/'.basename($file);
            if (! $disk->exists($target)) {
                $disk->put($target, file_get_contents($file));
            }
        }
    }
}
