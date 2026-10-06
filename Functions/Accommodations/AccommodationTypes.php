<?php
declare(strict_types=1);

function maple_accommodation_allowed_types(): array
{
    return [
        'Normal Cottage',
        'Premium Cottage',
        'Wild Cottage',
    ];
}

function maple_accommodation_type_mapping(PDO $pdo): array
{
    return [
        'Premium Cottage' => 1,
        'Normal Cottage' => 2,
        'Wild Cottage' => 3,
    ];
}

function maple_accommodation_type_preset_key(string $type): string
{
    return match (trim($type)) {
        'Normal Cottage' => 'normal',
        'Premium Cottage' => 'premium',
        'Wild Cottage' => 'wild',
        default => '',
    };
}

function maple_accommodation_type_image_id(
    PDO $pdo,
    string $type
): ?int {
    $type = trim($type);

    $mapping = maple_accommodation_type_mapping($pdo);

    return $mapping[$type] ?? null;
}

function maple_accommodation_type_name_for_id(
    PDO $pdo,
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
    PDO $pdo,
    string $type
): bool {
    return array_key_exists(
        trim($type),
        maple_accommodation_type_mapping($pdo)
    );
}

function maple_accommodation_type_rows(PDO $pdo): array
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

function maple_accommodation_type_options(PDO $pdo): array
{
    return maple_accommodation_type_rows($pdo);
}