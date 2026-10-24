<?php
declare(strict_types=1);

function maple_accommodation_allowed_types(): array
{
    return [
        'Premium Cottage',
        'Normal Cottage',
        'Wild Cottage',
    ];
}

function maple_accommodation_type_mapping($pdo = null): array
{
    return [
        'Premium Cottage' => 1,
        'Normal Cottage' => 2,
        'Wild Cottage' => 3,
    ];
}

function maple_accommodation_type_key(string $type): string
{
    $type = strtolower(trim($type));
    $type = preg_replace('/\s+/', ' ', $type) ?? '';

    return $type;
}

function maple_accommodation_type_canonical_name(string $type): string
{
    $typeKey = maple_accommodation_type_key($type);

    foreach (maple_accommodation_type_mapping() as $name => $id) {
        if (maple_accommodation_type_key($name) === $typeKey) {
            return $name;
        }
    }

    return '';
}

function maple_accommodation_type_preset_key(string $type): string
{
    switch (maple_accommodation_type_canonical_name($type)) {
        case 'Normal Cottage':
            return 'normal';
        case 'Premium Cottage':
            return 'premium';
        case 'Wild Cottage':
            return 'wild';
        default:
            return '';
    }
}

function maple_accommodation_type_image_id(
    $pdo,
    string $type
) {
    $type = maple_accommodation_type_canonical_name($type);

    if ($type === '') {
        return null;
    }

    $mapping = maple_accommodation_type_mapping($pdo);

    return $mapping[$type] ?? null;
}

function maple_accommodation_type_name_for_id(
    $pdo,
    int $imageSourceId
) {
    foreach (
        maple_accommodation_type_mapping($pdo)
        as $type => $id
    ) {
        if ($id === $imageSourceId) {
            return $type;
        }
    }

    return null;
}

function maple_accommodation_type_is_valid(
    $pdo,
    string $type
): bool {
    return maple_accommodation_type_canonical_name($type) !== '';
}

function maple_accommodation_type_rows($pdo = null): array
{
    $rows = [];

    foreach (
        maple_accommodation_type_mapping($pdo)
        as $name => $id
    ) {
        $rows[] = [
            'id' => $id,
            'name' => $name,
            'preset' => maple_accommodation_type_preset_key($name),
        ];
    }

    return $rows;
}

function maple_accommodation_type_options($pdo = null): array
{
    return maple_accommodation_type_rows($pdo);
}
