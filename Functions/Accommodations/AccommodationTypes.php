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

function maple_accommodation_type_mapping(?PDO $pdo = null): array
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
    return match (maple_accommodation_type_canonical_name($type)) {
        'Normal Cottage' => 'normal',
        'Premium Cottage' => 'premium',
        'Wild Cottage' => 'wild',
        default => '',
    };
}

function maple_accommodation_type_image_id(
    ?PDO $pdo,
    string $type
): ?int {
    $type = maple_accommodation_type_canonical_name($type);

    if ($type === '') {
        return null;
    }

    $mapping = maple_accommodation_type_mapping($pdo);

    return $mapping[$type] ?? null;
}

function maple_accommodation_type_name_for_id(
    ?PDO $pdo,
    int $imageSourceId
): ?string {
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
    ?PDO $pdo,
    string $type
): bool {
    return maple_accommodation_type_canonical_name($type) !== '';
}

function maple_accommodation_type_rows(?PDO $pdo = null): array
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

function maple_accommodation_type_options(?PDO $pdo = null): array
{
    return maple_accommodation_type_rows($pdo);
}
