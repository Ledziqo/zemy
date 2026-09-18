<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        $restaurant = DB::table('restaurants')->where('slug', 'tulip-olympia')->first();
        if (! $restaurant) {
            return;
        }

        $catalog = [
            'Hot Beverage (Cup)' => [
                'Coffee Americano' => 'tulip-coffee-americano.png',
                'Café Latte' => 'tulip-cafe-latte.png',
                'Coffee' => 'tulip-coffee.png',
                'Cappuccino' => 'tulip-cappuccino.png',
                'Double Espresso' => 'tulip-double-espresso.png',
                'Double Macchiato' => 'tulip-double-macchiato.png',
                'Espresso' => 'tulip-espresso.png',
                'Hot Chocolate' => 'tulip-hot-chocolate.png',
                'Macchiato' => 'tulip-macchiato.png',
                'Milk' => 'tulip-milk.png',
                'Selection of Tea' => 'tulip-selection-of-tea.png',
                'Tea with Coffee' => 'tulip-tea-with-coffee.png',
                'Tea with Milk' => 'tulip-tea-with-milk.png',
                'Special Tea' => 'tulip-special-tea.png',
                'Tea with Honey' => 'tulip-tea-with-honey.png',
                'Traditional Coffee' => 'tulip-traditional-coffee.png',
            ],
            'Beer (Bottle)' => [
                'St. George' => 'tulip-st-george.png', 'Habesha' => 'tulip-habesha.png', 'Castel' => 'tulip-castel.png',
                'Dashan' => 'tulip-dashan.png', 'Kagan' => 'tulip-kagan.png', 'Heineken' => 'tulip-heineken.png', 'Arad' => 'tulip-arad.png',
            ],
            'Soft Drinks (Bottle)' => [
                'Soft Drinks' => 'tulip-soft-drinks.png', 'Small Water (600 ml)' => 'tulip-small-water-600ml.png',
                'Medium Water (1000 ml)' => 'tulip-medium-water-1000ml.png', 'Big Water (2000 ml)' => 'tulip-big-water-2000ml.png',
            ],
            'Wines' => [
                'Roberson (Bottle)' => 'tulip-roberson-bottle.png', 'Local Glass of Wine' => 'tulip-local-glass-of-wine.png',
                'Acacia Dry Red (Glass)' => 'tulip-acacia-dry-red-glass.png', 'Acacia M.S. Rose (Glass)' => 'tulip-acacia-ms-rose-glass.png',
                'Acacia M.S. Red (Glass)' => 'tulip-acacia-ms-red-glass.png', 'Acacia M.S. White (Glass)' => 'tulip-acacia-ms-white-glass.png',
                'Acacia Medium Sweet White (Bottle)' => 'tulip-acacia-medium-sweet-white-bottle.png',
                'Acacia Medium Sweet Red (Bottle)' => 'tulip-acacia-medium-sweet-red-bottle.png',
                'Acacia Medium Sweet Rose (Bottle)' => 'tulip-acacia-medium-sweet-rose-bottle.png',
                'Acacia Dry Red (Bottle)' => 'tulip-acacia-dry-red-bottle.png',
                'Rift Valley Merlot (Bottle)' => 'tulip-rift-valley-merlot-bottle.png',
                'Rift Valley Chardonnay (Bottle)' => 'tulip-rift-valley-chardonnay-bottle.png',
                'Rift Valley Syrah (Bottle)' => 'tulip-rift-valley-syrah-bottle.png',
                'Rift Valley Dry Rosé (Bottle)' => 'tulip-rift-valley-dry-rose-bottle.png',
                'Rift Valley Cabernet Sauvignon (Bottle)' => 'tulip-rift-valley-cabernet-sauvignon-bottle.png',
            ],
            'Aperitif' => [
                'Campari Bitter (Glass)' => 'tulip-campari-bitter.png', 'Campari Bitter (Bottle)' => 'tulip-campari-bitter-bottle.png',
                'Fernet Bianca (Glass)' => 'tulip-fernet-bianca.png', 'Fernet Bianca (Bottle)' => 'tulip-fernet-bianca-bottle.png',
                'Martini Bianco (Glass)' => 'tulip-martini-bianco.png', 'Martini Bianco (Bottle)' => 'tulip-martini-bianco-bottle.png',
                'Martini Extra Dry' => 'tulip-martini-extra-dry.png',
                'Martini Rosso (Glass)' => 'tulip-martini-rosso.png', 'Martini Rosso (Bottle)' => 'tulip-martini-rosso-bottle.png',
                'Pastis 51 (Glass)' => 'tulip-pastis-51.png', 'Pastis 51 (Bottle)' => 'tulip-pastis-51-bottle.png',
            ],
            'Gin' => [
                'Bombay Sapphire (Glass)' => 'tulip-bombay-sapphire.png', 'Bombay Sapphire (Bottle)' => 'tulip-bombay-sapphire-bottle.png',
                'Gordon’s (Glass)' => 'tulip-gordon-gin.png', 'Gordon’s (Bottle)' => 'tulip-gordon-gin-bottle.png',
                'Tanqueray (Glass)' => 'tulip-tanqueray-gin.png', 'Tanqueray (Bottle)' => 'tulip-tanqueray-gin-bottle.png',
            ],
            'Vodka' => [
                'Absolut Blue (Glass)' => 'tulip-absolut-blue.png', 'Absolut Blue (Bottle)' => 'tulip-absolut-blue-bottle.png',
                'Cîroc (Glass)' => 'tulip-ciroc.png', 'Cîroc (Bottle)' => 'tulip-ciroc-bottle.png',
                'Grey Goose (Glass)' => 'tulip-grey-goose.png', 'Grey Goose (Bottle)' => 'tulip-grey-goose-bottle.png',
                'Smirnoff Red (750 ml) (Glass)' => 'tulip-smirnoff-red-750ml.png', 'Smirnoff Red (750 ml) (Bottle)' => 'tulip-smirnoff-red-750ml-bottle.png',
                'Stolichnaya Red (750 ml) (Glass)' => 'tulip-stolichnaya-red-750ml.png', 'Stolichnaya Red (750 ml) (Bottle)' => 'tulip-stolichnaya-red-750ml-bottle.png',
                'Stolichnaya Red (50 cl) (Glass)' => 'tulip-stolichnaya-red-50cl.png', 'Stolichnaya Red (50 cl) (Bottle)' => 'tulip-stolichnaya-red-50cl-bottle.png',
                'Winter Palace (1000 ml) (Glass)' => 'tulip-winter-palace-1000ml.png', 'Winter Palace (1000 ml) (Bottle)' => 'tulip-winter-palace-1000ml-bottle.png',
            ],
            'Liquor' => [
                'Cointreau (Glass)' => 'tulip-cointreau.png', 'Cointreau (Bottle)' => 'tulip-cointreau-bottle.png',
                'Kahlúa (Glass)' => 'tulip-kahlua.png', 'Kahlúa (Bottle)' => 'tulip-kahlua-bottle.png',
                'Sambuca (700 ml) (Glass)' => 'tulip-sambuca-700ml.png', 'Sambuca (700 ml) (Bottle)' => 'tulip-sambuca-700ml-bottle.png',
                'Tia Maria (Glass)' => 'tulip-tia-maria.png', 'Tia Maria (Bottle)' => 'tulip-tia-maria-bottle.png',
            ],
            'Rum' => [
                'Bacardi (Glass)' => 'tulip-bacardi.png', 'Bacardi (Bottle)' => 'tulip-bacardi-bottle.png',
                'Bacardi White (Glass)' => 'tulip-bacardi-white.png', 'Bacardi White (Bottle)' => 'tulip-bacardi-white-bottle.png',
                'Captain Morgan Black (Glass)' => 'tulip-captain-morgan-black.png', 'Captain Morgan Black (Bottle)' => 'tulip-captain-morgan-black-bottle.png',
                'Captain Morgan Gold (Glass)' => 'tulip-captain-morgan-gold.png', 'Captain Morgan Gold (Bottle)' => 'tulip-captain-morgan-gold-bottle.png',
                'Havana Club (1000 ml) (Glass)' => 'tulip-havana-club-1000ml.png', 'Havana Club (1000 ml) (Bottle)' => 'tulip-havana-club-1000ml-bottle.png',
                'Malibu (Glass)' => 'tulip-malibu.png', 'Malibu (Bottle)' => 'tulip-malibu-bottle.png',
            ],
            'Regular Blended Whisky' => [
                'Johnnie Walker Double Black (Glass)' => 'tulip-johnnie-walker-double-black.png', 'Johnnie Walker Double Black (Bottle)' => 'tulip-johnnie-walker-double-black-bottle.png',
                'Johnnie Walker Black Label (Glass)' => 'tulip-johnnie-walker-black-label.png', 'Johnnie Walker Black Label (Bottle)' => 'tulip-johnnie-walker-black-label-bottle.png',
                'Jack Daniel’s (Glass)' => 'tulip-jack-daniels.png', 'Jack Daniel’s (Bottle)' => 'tulip-jack-daniels-bottle.png',
                'J&B Rare (Glass)' => 'tulip-jb-rare.png', 'J&B Rare (Bottle)' => 'tulip-jb-rare-bottle.png',
            ],
            'Deluxe Whisky' => [
                'Chivas Regal 12 Years (Glass)' => 'tulip-chivas-regal-12-years.png', 'Chivas Regal 12 Years (Bottle)' => 'tulip-chivas-regal-12-years-bottle.png',
                'Chivas Regal 18 Years (Bottle)' => 'tulip-chivas-regal-18-years-bottle.png',
                'Johnnie Walker Gold (Bottle)' => 'tulip-johnnie-walker-gold-bottle.png', 'Johnnie Walker Platinum (Bottle)' => 'tulip-johnnie-walker-platinum-bottle.png',
                'Johnnie Walker Blue (Bottle)' => 'tulip-johnnie-walker-blue-bottle.png',
            ],
            'Single Malt Whisky' => [
                'Glenfiddich 12 Years (Glass)' => 'tulip-glenfiddich-12-years.png', 'Glenfiddich 12 Years (Bottle)' => 'tulip-glenfiddich-12-years-bottle.png',
                'Glenfiddich 18 Years (Bottle)' => 'tulip-glenfiddich-18-years-bottle.png',
            ],
            'Tequila' => [
                'Jose Cuervo Silver (Glass)' => 'tulip-jose-cuervo-silver.png', 'Jose Cuervo Silver (Bottle)' => 'tulip-jose-cuervo-silver-bottle.png',
                'Tequila Camino Silver (Glass)' => 'tulip-tequila-camino-silver.png', 'Tequila Camino Silver (Bottle)' => 'tulip-tequila-camino-silver-bottle.png',
                'Tequila Camino Gold (Glass)' => 'tulip-tequila-camino-gold.png', 'Tequila Camino Gold (Bottle)' => 'tulip-tequila-camino-gold-bottle.png',
            ],
            'Cognac & Brandy' => [
                'Courvoisier VS (Bottle)' => 'tulip-courvoisier-vs-bottle.png', 'Courvoisier VSOP (Bottle)' => 'tulip-courvoisier-vsop-bottle.png',
            ],
            'Pizza Corner' => [
                'Four Season Pizza' => 'tulip-four-season-pizza.png', 'Vegetarian Pizza' => 'tulip-vegetarian-pizza.png',
                'Margarita Pizza' => 'tulip-margarita-pizza.png', 'Tuna Pizza' => 'tulip-tuna-pizza.png',
            ],
            'Fresh Healthy Organic Fruit Juice' => [
                'Orange Juice' => 'tulip-orange-juice.png', 'Papaya' => 'tulip-papaya.png', 'Watermelon' => 'tulip-watermelon.png',
                'Mixed Juice' => 'tulip-mixed-juice.png', 'Avocado' => 'tulip-avocado.png',
            ],
            'Cake Corner' => [
                'Carrot Cake' => 'tulip-carrot-cake.png', 'English Cake' => 'tulip-english-cake.png', 'Banana Cake' => 'tulip-banana-cake.png',
                'Soft Cake' => 'tulip-soft-cake.png', 'Cookies (1 kg)' => 'tulip-cookies-1kg.png', 'Torta Cake' => 'tulip-torta-cake.png',
            ],
        ];

        $specificDescriptions = [
            'Coffee Americano' => 'Espresso lengthened with hot water for a clean, full cup.',
            'Café Latte' => 'Espresso softened with steamed milk and a light layer of foam.',
            'Coffee' => 'Freshly brewed black coffee served hot.',
            'Cappuccino' => 'Espresso with steamed milk and a generous cap of milk foam.',
            'Double Espresso' => 'Two concentrated espresso shots with rich crema.',
            'Double Macchiato' => 'Two espresso shots each marked with a small amount of milk foam.',
            'Espresso' => 'A concentrated espresso shot served with golden crema.',
            'Hot Chocolate' => 'A warm, smooth chocolate drink prepared with milk.',
            'Macchiato' => 'Espresso marked with a small amount of steamed milk foam.',
            'Milk' => 'Fresh milk served chilled or warm according to preference.',
            'Selection of Tea' => 'A choice of freshly brewed teas; ask staff about today’s selection.',
            'Tea with Coffee' => 'A house tea-and-coffee combination; ask staff about the preparation.',
            'Tea with Milk' => 'Brewed tea softened with milk and served hot.',
            'Special Tea' => 'A house tea blend served hot; ask staff about the ingredients available today.',
            'Tea with Honey' => 'Hot brewed tea sweetened with honey.',
            'Traditional Coffee' => 'Traditional Ethiopian buna served in a small cup; preparation follows the house coffee service.',
            'Four Season Pizza' => 'Pizza with four distinct topping sections, tomato sauce, melted cheese, and mixed savory toppings.',
            'Vegetarian Pizza' => 'Pizza topped with vegetables, tomato sauce, melted cheese, and herbs.',
            'Margarita Pizza' => 'Pizza with tomato sauce, mozzarella, and basil.',
            'Tuna Pizza' => 'Pizza with tuna, tomato sauce, melted cheese, and savory vegetable toppings.',
            'Orange Juice' => 'Fresh orange juice with natural citrus flavor.',
            'Papaya' => 'Fresh papaya juice blended until smooth.',
            'Watermelon' => 'Refreshing fresh watermelon juice.',
            'Mixed Juice' => 'A blended combination of fresh seasonal fruit juices.',
            'Avocado' => 'A smooth, creamy avocado fruit drink.',
            'Carrot Cake' => 'Moist carrot cake with a tender crumb and light topping.',
            'English Cake' => 'Plain golden tea cake with a soft, fine crumb.',
            'Banana Cake' => 'Moist cake made with ripe banana.',
            'Soft Cake' => 'Light, soft sponge cake served as a simple slice.',
            'Cookies (1 kg)' => 'A one-kilogram bakery serving of assorted cookies.',
            'Torta Cake' => 'Layered torta-style cake with a light cream filling.',
        ];

        $hasImageSource = Schema::hasColumn('menu_items', 'image_source_url');
        foreach ($catalog as $categoryName => $items) {
            $categoryId = DB::table('categories')
                ->where('restaurant_id', $restaurant->id)
                ->where('name', $categoryName)
                ->value('id');

            if (! $categoryId) {
                continue;
            }

            foreach ($items as $name => $image) {
                $baseName = preg_replace('/ \((Glass|Bottle)\)$/', '', $name);
                $unit = str_ends_with($name, ' (Glass)') ? ' Served by the glass.' : (str_ends_with($name, ' (Bottle)') ? ' Served by the bottle.' : '');
                $description = isset($specificDescriptions[$baseName])
                    ? $specificDescriptions[$baseName].$unit
                    : match ($categoryName) {
                        'Beer (Bottle)' => $baseName.' beer served chilled.',
                        'Soft Drinks (Bottle)' => $name.' served chilled.',
                        'Wines' => $unit === '' ? 'A bottle of '.$baseName.' wine.' : 'A serving of '.$baseName.' wine by the glass.',
                        default => $baseName.' served as a '.($unit === '' ? 'house pour.' : strtolower(trim($unit))),
                    };

                $updates = [
                    'description' => trim($description),
                    'image_path' => 'uploads/menu-items/'.$image,
                    'updated_at' => now(),
                ];
                if ($hasImageSource) {
                    $updates['image_source_url'] = null;
                }

                DB::table('menu_items')
                    ->where('restaurant_id', $restaurant->id)
                    ->where('category_id', $categoryId)
                    ->where('name', $name)
                    ->update($updates);
            }
        }
    }

    public function down(): void
    {
        // Content and generated assets are intentionally not reverted by a rollback.
    }
};
