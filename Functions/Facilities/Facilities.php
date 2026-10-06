<?php
declare(strict_types=1);

require_once __DIR__ . '/../Helpers/Database.php';
require_once __DIR__ . '/../Helpers/View.php';
require_once __DIR__ . '/../Accommodations/AccommodationImages.php';

function maple_supported_facility_languages(): array
{
    return ['en', 'de', 'fr', 'es'];
}

function maple_facility_category_order(): array
{
    return [
        'Kitchen',
        'Bathroom',
        'Bedroom',
        'Living',
        'Outdoor',
        'Technology',
        'Convenience',
        'Activities',
    ];
}

function maple_facility_slug(string $value): string
{
    $slug = strtolower(trim($value));
    $slug = preg_replace('/[^a-z0-9]+/', '-', $slug) ?? '';
    $slug = trim($slug, '-');

    return $slug !== '' ? $slug : 'facility';
}

function maple_facility_icon_class(string $icon): string
{
    $icon = trim($icon);

    if ($icon !== '' && preg_match('/\bfa-(?:solid|regular|brands|light|thin|duotone)\b/', $icon)) {
        return $icon;
    }

    $icons = [
        'wifi' => 'fa-solid fa-wifi',
        'terrace' => 'fa-solid fa-umbrella-beach',
        'lake' => 'fa-solid fa-water',
        'mountain' => 'fa-solid fa-mountain-sun',
        'fireplace' => 'fa-solid fa-fire-flame-curved',
        'bbq' => 'fa-solid fa-burger',
        'parking' => 'fa-solid fa-square-parking',
        'dishwasher' => 'fa-solid fa-kitchen-set',
        'fridge' => 'fa-solid fa-snowflake',
        'stove' => 'fa-solid fa-fire-burner',
        'microwave' => 'fa-solid fa-clock',
        'coffee' => 'fa-solid fa-mug-saucer',
        'kettle' => 'fa-solid fa-mug-hot',
        'utensils' => 'fa-solid fa-utensils',
        'bathroom' => 'fa-solid fa-bath',
        'shower' => 'fa-solid fa-shower',
        'towel' => 'fa-solid fa-soap',
        'bed' => 'fa-solid fa-bed',
        'linen' => 'fa-solid fa-layer-group',
        'sofa' => 'fa-solid fa-couch',
        'dining' => 'fa-solid fa-chair',
        'heating' => 'fa-solid fa-temperature-three-quarters',
        'tv' => 'fa-solid fa-tv',
        'chair' => 'fa-solid fa-chair',
        'fire' => 'fa-solid fa-fire',
        'power' => 'fa-solid fa-plug',
        'pet' => 'fa-solid fa-paw',
        'accessibility' => 'fa-solid fa-wheelchair',
        'canoe' => 'fa-solid fa-sailboat',
        'trail' => 'fa-solid fa-person-hiking',
        'fishing' => 'fa-solid fa-fish',
        'bike' => 'fa-solid fa-bicycle',
    ];

    return $icons[maple_facility_slug($icon)] ?? 'fa-solid fa-circle-info';
}

function maple_facility_short_description(string $description): string
{
    $description = trim($description);

    if ($description === '') {
        return '';
    }

    $firstSentence = preg_split('/(?<=[.!?])\s+/', $description, 2)[0] ?? $description;
    if (strlen($firstSentence) <= 126) {
        return $firstSentence;
    }

    return rtrim(substr($firstSentence, 0, 123)) . '...';
}

function maple_facility_definitions(): array
{
    $facilities = [
        ['wifi', 'Wi-Fi', 'Technology', 'wifi', true, 'Free Wi-Fi throughout the cottage.'],
        ['fireplace', 'Fireplace', 'Living', 'fireplace', true, 'Private fireplace for colder Canadian evenings.'],
        ['lake-view', 'Lake view', 'Outdoor', 'lake', true, 'Cottage with views toward the lake.'],
        ['mountain-view', 'Mountain view', 'Outdoor', 'mountain', true, 'Views of the surrounding mountain landscape.'],
        ['terrace', 'Private terrace', 'Outdoor', 'terrace', true, 'A private outdoor terrace for quiet mornings and evenings.'],
        ['bbq', 'BBQ', 'Outdoor', 'bbq', true, 'Outdoor BBQ space for relaxed meals after a day outside.'],
        ['parking', 'Parking', 'Convenience', 'parking', true, 'Parking available close to the cottage area.'],
        ['dishwasher', 'Dishwasher', 'Kitchen', 'dishwasher', true, 'A dishwasher is available for easy cleanup.'],
        ['fridge', 'Refrigerator', 'Kitchen', 'fridge', true, 'A refrigerator for groceries, drinks and trail snacks.'],
        ['stove', 'Stove', 'Kitchen', 'stove', true, 'A practical stove for preparing cottage meals.'],
        ['microwave', 'Microwave', 'Kitchen', 'microwave', true, 'A microwave for quick meals and warm drinks.'],
        ['coffee', 'Coffee machine', 'Kitchen', 'coffee', true, 'A coffee machine for slow mornings at camp.'],
        ['kettle', 'Kettle', 'Kitchen', 'kettle', true, 'A kettle for tea, coffee and warm drinks.'],
        ['utensils', 'Cooking utensils', 'Kitchen', 'utensils', true, 'Essential pans, tools and tableware for easy cottage cooking.'],
        ['bathroom', 'Private bathroom', 'Bathroom', 'bathroom', true, 'A private bathroom inside the cottage.'],
        ['shower', 'Shower', 'Bathroom', 'shower', true, 'A shower for refreshing after the outdoors.'],
        ['towels', 'Towels', 'Bathroom', 'towel', true, 'Bathroom towels are provided for selected stays.'],
        ['double-bed', 'Double bed', 'Bedroom', 'bed', true, 'A comfortable double bed in selected cottages.'],
        ['single-beds', 'Single beds', 'Bedroom', 'bed', true, 'Single beds for flexible sleeping layouts.'],
        ['linen', 'Bed linen', 'Bedroom', 'linen', true, 'Fresh bed linen is included.'],
        ['sofa', 'Sofa', 'Living', 'sofa', true, 'A sofa for relaxing indoors.'],
        ['dining', 'Dining table', 'Living', 'dining', true, 'A dining table for shared meals.'],
        ['heating', 'Heating', 'Living', 'heating', true, 'Heating keeps the cottage comfortable in colder weather.'],
        ['television', 'Television', 'Technology', 'tv', true, 'A television is available for quiet evenings indoors.'],
        ['smart-tv', 'Smart TV', 'Technology', 'tv', true, 'A smart TV for using your own streaming accounts.'],
        ['power', 'Power outlets', 'Technology', 'power', true, 'Convenient power outlets for charging daily essentials.'],
        ['outdoor-seating', 'Outdoor seating', 'Outdoor', 'chair', true, 'Outdoor seating beside the cottage.'],
        ['fire-pit', 'Fire pit', 'Outdoor', 'fire', true, 'A fire pit for evenings outside when camp rules allow.'],
        ['pet-friendly', 'Pet friendly', 'Convenience', 'pet', true, 'Selected cottages welcome pets when arranged before arrival.'],
        ['wheelchair-accessible', 'Wheelchair accessible', 'Convenience', 'accessibility', true, 'Selected accommodations are easier to access for guests using a wheelchair.'],
        ['canoe-access', 'Canoe access', 'Activities', 'canoe', true, 'Stay close to canoe access for calm paddling moments on the lake.'],
        ['trail', 'Hiking trail access', 'Activities', 'trail', true, 'Reach nearby forest and mountain trails from the camp area.'],
        ['fishing-equipment', 'Fishing equipment', 'Activities', 'fishing', true, 'Basic fishing equipment is available in limited numbers for selected stays.'],
        ['bike', 'Bicycle storage', 'Activities', 'bike', true, 'A practical place for storing bicycles during your stay.'],
    ];

    $definitions = [];
    foreach ($facilities as $index => $facility) {
        list($key, $name, $category, $icon, $filter, $description) = $facility;
        $definitions[] = [
            'id' => $index + 1,
            'key' => $key,
            'value' => $key,
            'slug' => $key,
            'name' => $name,
            'category' => $category,
            'icon' => $icon,
            'popular' => in_array($key, ['wifi', 'fireplace', 'lake-view', 'terrace', 'bbq', 'parking'], true),
            'filter' => $filter,
            'description' => $description,
            'short' => maple_facility_short_description($description),
            'accommodations' => [],
        ];
    }

    return $definitions;
}

function maple_facility_presets(): array
{
    return [
        'normal' => [
            'key' => 'normal',
            'name' => 'Normal Cottage',
            'description' => 'Comfortable basic cottage facilities',
            'facilities' => [
                'wifi',
                'parking',
                'bathroom',
                'shower',
                'towels',
                'double-bed',
                'linen',
                'heating',
                'fridge',
                'stove',
                'microwave',
                'coffee',
                'kettle',
                'utensils',
                'dining',
                'sofa',
                'power',
                'outdoor-seating',
            ],
        ],
        'premium' => [
            'key' => 'premium',
            'name' => 'Premium Cottage',
            'description' => 'More luxurious facilities and extras',
            'facilities' => [
                'wifi',
                'parking',
                'bathroom',
                'shower',
                'towels',
                'double-bed',
                'linen',
                'heating',
                'fridge',
                'stove',
                'microwave',
                'coffee',
                'kettle',
                'utensils',
                'dishwasher',
                'dining',
                'sofa',
                'smart-tv',
                'fireplace',
                'terrace',
                'outdoor-seating',
                'bbq',
                'lake-view',
                'mountain-view',
                'power',
            ],
        ],
        'wild' => [
            'key' => 'wild',
            'name' => 'Wild Cottage',
            'description' => 'More basic and nature-focused facilities',
            'facilities' => [
                'parking',
                'bathroom',
                'shower',
                'linen',
                'heating',
                'fridge',
                'stove',
                'kettle',
                'utensils',
                'dining',
                'outdoor-seating',
                'fire-pit',
                'bbq',
                'mountain-view',
                'trail',
                'bike',
            ],
        ],
        'custom' => [
            'key' => 'custom',
            'name' => 'Custom',
            'description' => 'Choose everything manually',
            'facilities' => [],
        ],
    ];
}

function maple_facility_key_map(): array
{
    $map = [];

    foreach (maple_facility_definitions() as $facility) {
        $key = (string) $facility['key'];
        $name = (string) $facility['name'];
        $map[strtolower($key)] = $key;
        $map[maple_facility_slug($key)] = $key;
        $map[strtolower($name)] = $key;
        $map[maple_facility_slug($name)] = $key;
    }

    return array_merge($map, [
        'wi-fi' => 'wifi',
        'wifi' => 'wifi',
        'private-terrace' => 'terrace',
        'refrigerator' => 'fridge',
        'cooking-utensils' => 'utensils',
        'private-bathroom' => 'bathroom',
        'bed-linen' => 'linen',
        'power-outlets' => 'power',
        'hiking-trail-access' => 'trail',
        'bicycle-storage' => 'bike',
    ]);
}

function maple_facility_parse_values(string $value): array
{
    $allowed = maple_facility_key_map();
    $selected = [];

    foreach (explode(',', $value) as $rawValue) {
        $rawValue = trim($rawValue);
        if ($rawValue === '') {
            continue;
        }

        $key = strtolower($rawValue);
        $slug = maple_facility_slug($rawValue);
        $facilityKey = $allowed[$key] ?? $allowed[$slug] ?? null;

        if ($facilityKey !== null && !in_array($facilityKey, $selected, true)) {
            $selected[] = $facilityKey;
        }
    }

    return maple_facility_sort_values($selected);
}

function maple_facility_values_from_post($value): array
{
    $values = is_array($value) ? $value : [];
    $allowed = maple_facility_key_map();
    $selected = [];

    foreach ($values as $rawValue) {
        $rawValue = trim((string) $rawValue);
        $key = strtolower($rawValue);
        $slug = maple_facility_slug($rawValue);
        $facilityKey = $allowed[$key] ?? $allowed[$slug] ?? null;

        if ($facilityKey !== null && !in_array($facilityKey, $selected, true)) {
            $selected[] = $facilityKey;
        }
    }

    return maple_facility_sort_values($selected);
}

function maple_facility_sort_values(array $values): array
{
    $order = [];
    foreach (maple_facility_definitions() as $index => $facility) {
        $order[(string) $facility['key']] = $index;
    }

    usort($values, static function (string $left, string $right) use ($order): int {
        return ($order[$left] ?? 999) <=> ($order[$right] ?? 999);
    });

    return $values;
}

function maple_facility_csv(array $values): string
{
    return implode(',', maple_facility_sort_values($values));
}

function maple_facility_keys_equal(array $left, array $right): bool
{
    return maple_facility_csv($left) === maple_facility_csv($right);
}

function maple_facility_detect_preset(array $facilityKeys): string
{
    foreach (maple_facility_presets() as $presetKey => $preset) {
        if ($presetKey !== 'custom' && maple_facility_keys_equal($facilityKeys, $preset['facilities'])) {
            return $presetKey;
        }
    }

    return 'custom';
}

/**
 * @return array|null
 */
function maple_facility_by_value(array $facilities, string $value)
{
    $keys = maple_facility_parse_values($value);
    $key = $keys[0] ?? $value;

    foreach ($facilities as $facility) {
        if ((string) $facility['key'] === $key) {
            return $facility;
        }
    }

    return null;
}

function maple_facility_names_from_keys(array $facilityKeys): array
{
    $names = [];
    $facilities = maple_facility_definitions();

    foreach (maple_facility_sort_values($facilityKeys) as $facilityKey) {
        $facility = maple_facility_by_value($facilities, $facilityKey);
        if ($facility !== null) {
            $names[] = (string) $facility['name'];
        }
    }

    return $names;
}

function maple_facility_apply_accommodations(array $facilities, array $accommodations): array
{
    $facilityIndexByKey = [];

    foreach ($facilities as $index => $facility) {
        $facilityIndexByKey[(string) $facility['key']] = $index;
    }

    foreach ($accommodations as $accommodationIndex => $accommodation) {
        $facilityKeys = maple_facility_parse_values((string) ($accommodation['legacyFacilities'] ?? ''));
        $accommodations[$accommodationIndex]['facilityValues'] = $facilityKeys;

        foreach ($facilityKeys as $facilityKey) {
            if (!isset($facilityIndexByKey[$facilityKey])) {
                continue;
            }

            $facilityIndex = $facilityIndexByKey[$facilityKey];
            $facilities[$facilityIndex]['accommodations'][] = [
                'id' => (int) $accommodation['id'],
                'name' => (string) $accommodation['name'],
                'url' => 'Accomodatie.php#huis-' . (int) $accommodation['id'],
            ];
        }
    }

    return [$facilities, $accommodations];
}

function maple_facility_load_accommodations_from_database(): array
{
    $imageClasses = ['comfort', 'luxe', 'premium'];
    $pdo = maple_pdo();

    $columns = [];
    $columnsResult = $pdo->query('SHOW COLUMNS FROM `accomodaties`');
    foreach ($columnsResult->fetchAll() as $column) {
        $field = (string) ($column['Field'] ?? '');
        if ($field !== '') {
            $columns[strtolower($field)] = $field;
        }
    }

    $findColumn = static function (array $names) use ($columns): string {
        foreach ($names as $name) {
            $key = strtolower((string) $name);
            if (isset($columns[$key])) {
                return $columns[$key];
            }
        }

        return '';
    };

    $descriptionColumn = $findColumn(['Omschrijving', 'Omschr']);
    $imageColumn = $findColumn(['Afbeelding']);
    $descriptionSelect = $descriptionColumn !== ''
        ? '`' . str_replace('`', '``', $descriptionColumn) . '` AS `Omschrijving`'
        : "'' AS `Omschrijving`";
    $imageSelect = $imageColumn !== ''
        ? '`' . str_replace('`', '``', $imageColumn) . '` AS `Afbeelding`'
        : "'' AS `Afbeelding`";

    $statement = $pdo->prepare('
        SELECT Huis_id, Huis_naam, Locatie, PPN, Voorzieningen, `Max`, ' . $descriptionSelect . ', ' . $imageSelect . '
        FROM accomodaties
        ORDER BY Huis_id ASC
    ');
    $statement->execute();

    $accommodations = [];

    foreach ($statement->fetchAll() as $index => $row) {
        $facilities = (string) ($row['Voorzieningen'] ?? '');
        $facilityKeys = maple_facility_parse_values($facilities);
        $accommodations[] = [
            'id' => (int) $row['Huis_id'],
            'name' => (string) ($row['Huis_naam'] ?? ''),
            'location' => (string) ($row['Locatie'] ?? ''),
            'price' => (float) ($row['PPN'] ?? 0),
            'maxGuests' => (int) ($row['Max'] ?? 0),
            'description' => (string) ($row['Omschrijving'] ?? ''),
            'image' => (string) ($row['Afbeelding'] ?? ''),
            'legacyFacilities' => $facilities,
            'facilityValues' => $facilityKeys,
            'facilityNames' => maple_facility_names_from_keys($facilityKeys),
            'imageClass' => $imageClasses[$index % count($imageClasses)],
        ];
    }

    return $accommodations;
}

function maple_facility_sort(array $facilities): array
{
    $categoryOrder = array_flip(maple_facility_category_order());

    usort($facilities, static function (array $left, array $right) use ($categoryOrder): int {
        $leftCategory = $categoryOrder[$left['category']] ?? 99;
        $rightCategory = $categoryOrder[$right['category']] ?? 99;

        if ($leftCategory !== $rightCategory) {
            return $leftCategory <=> $rightCategory;
        }

        return strcasecmp((string) $left['name'], (string) $right['name']);
    });

    return $facilities;
}

function maple_facilities_data(): array
{
    $facilities = maple_facility_sort(maple_facility_definitions());
    $accommodations = [];
    $source = 'database';

    try {
        $accommodations = maple_facility_load_accommodations_from_database();
    } catch (Throwable $exception) {
        $source = 'unavailable';
        error_log('Facilities accommodations could not be loaded: ' . $exception->getMessage());
    }

    list($facilities, $accommodations) = maple_facility_apply_accommodations($facilities, $accommodations);

    return [
        'facilities' => $facilities,
        'accommodations' => $accommodations,
        'categories' => maple_facility_category_order(),
        'presets' => maple_facility_presets(),
        'source' => $source,
    ];
}

function maple_facilities_by_category(array $facilities): array
{
    $grouped = [];

    foreach (maple_facility_category_order() as $category) {
        $grouped[$category] = [];
    }

    foreach ($facilities as $facility) {
        $category = (string) ($facility['category'] ?? 'Convenience');
        if (!isset($grouped[$category])) {
            $grouped[$category] = [];
        }
        $grouped[$category][] = $facility;
    }

    return array_filter($grouped, static function (array $items): bool {
        return $items !== [];
    });
}

function maple_facility_price($price): string
{
    return maple_home_price((float) $price);
}
