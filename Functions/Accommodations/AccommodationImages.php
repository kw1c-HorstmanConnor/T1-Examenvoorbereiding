<?php
declare(strict_types=1);

require_once __DIR__ . '/../Helpers/Database.php';
require_once __DIR__ . '/AccommodationTypes.php';

function maple_accommodation_image_upload_directory(): string
{
    return dirname(__DIR__, 2)
        . DIRECTORY_SEPARATOR
        . 'Images'
        . DIRECTORY_SEPARATOR
        . 'Accommodations';
}

function maple_accommodation_image_fallback_path(): string
{
    return maple_accommodation_image_upload_directory()
        . DIRECTORY_SEPARATOR
        . 'no-image-placeholder.png';
}

function maple_accommodation_image_fallback_url(string $assetBase = ''): string
{
    return $assetBase . 'Images/Accommodations/no-image-placeholder.png';
}

function maple_accommodation_image_placeholder_text(): string
{
    return 'No image available yet';
}

function maple_accommodation_image_filename($value): string
{
    $image = trim((string) $value);

    if ($image === '') {
        return '';
    }

    $image = str_replace('\\', '/', $image);
    $filename = basename($image);

    if (!preg_match('/^[a-zA-Z0-9][a-zA-Z0-9._-]*\.(?:jpe?g|png|webp)$/i', $filename)) {
        return '';
    }

    return $filename;
}

function maple_accommodation_image_path($value): ?string
{
    $filename = maple_accommodation_image_filename($value);

    if ($filename === '') {
        return null;
    }

    return maple_accommodation_image_upload_directory()
        . DIRECTORY_SEPARATOR
        . $filename;
}

function maple_accommodation_image_exists($value): bool
{
    $path = maple_accommodation_image_path($value);

    return $path !== null && is_file($path);
}

function maple_accommodation_image_is_placeholder($value): bool
{
    return !maple_accommodation_image_exists($value);
}

function maple_accommodation_image_url($value, string $assetBase = ''): string
{
    $filename = maple_accommodation_image_filename($value);

    if ($filename !== '' && maple_accommodation_image_exists($filename)) {
        return $assetBase
            . 'Images/Accommodations/'
            . rawurlencode($filename);
    }

    return maple_accommodation_image_fallback_url($assetBase);
}

function maple_accommodation_gallery_directory(int $imageSourceId): string
{
    return maple_accommodation_image_upload_directory()
        . DIRECTORY_SEPARATOR
        . $imageSourceId;
}

function maple_accommodation_gallery_file_type(string $filename): string
{
    return preg_match(
        '/(?:floorplan|floor-plan|floor_plan|foorplan|floor|plattegrond|map|plan)/i',
        $filename
    )
        ? 'floorplan'
        : 'photo';
}

function maple_accommodation_gallery_label(string $filename): string
{
    $label = preg_replace('/\.[^.]+$/', '', $filename) ?? '';
    $label = preg_replace('/^\d+\s*[-_. ]*/', '', $label) ?? '';
    $label = trim(str_replace(['-', '_'], ' ', $label));

    return $label === ''
        ? 'Image'
        : ucwords(strtolower($label));
}

function maple_accommodation_gallery_slide(
    string $src,
    string $filename,
    string $type = 'photo',
    bool $placeholder = false
): array {
    return [
        'src' => $src,
        'type' => $type === 'floorplan' ? 'floorplan' : 'photo',
        'label' => maple_accommodation_gallery_label($filename),
        'placeholder' => $placeholder,
    ];
}

function maple_accommodation_path_is_inside(
    string $path,
    string $directory
): bool {
    $realDirectory = realpath($directory);
    $realPath = realpath($path);

    if ($realDirectory === false || $realPath === false) {
        return false;
    }

    $realDirectory = rtrim(
            $realDirectory,
            DIRECTORY_SEPARATOR
        ) . DIRECTORY_SEPARATOR;

    return strncmp(
            $realPath,
            $realDirectory,
            strlen($realDirectory)
        ) === 0;
}

function maple_accommodation_gallery_file_url(
    int $imageSourceId,
    string $filename,
    string $assetBase = ''
): string {
    return $assetBase
        . 'Images/Accommodations/'
        . rawurlencode((string) $imageSourceId)
        . '/'
        . rawurlencode($filename);
}

function maple_accommodation_gallery_files(int $imageSourceId): array
{
    if (!in_array($imageSourceId, [1, 2, 3], true)) {
        return [];
    }

    $directory = maple_accommodation_gallery_directory($imageSourceId);

    if (!is_dir($directory)) {
        return [];
    }

    $rootDirectory = realpath(
        maple_accommodation_image_upload_directory()
    );
    $realDirectory = realpath($directory);

    if ($rootDirectory === false || $realDirectory === false) {
        return [];
    }

    $rootDirectory = rtrim(
            $rootDirectory,
            DIRECTORY_SEPARATOR
        ) . DIRECTORY_SEPARATOR;

    $realDirectoryWithSeparator = rtrim(
            $realDirectory,
            DIRECTORY_SEPARATOR
        ) . DIRECTORY_SEPARATOR;

    if (
        strncmp(
            $realDirectoryWithSeparator,
            $rootDirectory,
            strlen($rootDirectory)
        ) !== 0
    ) {
        return [];
    }

    $files = scandir($realDirectory);

    if ($files === false) {
        return [];
    }

    $images = [];

    foreach ($files as $file) {
        if (
            !is_string($file)
            || $file === '.'
            || $file === '..'
        ) {
            continue;
        }

        if (
            basename($file) !== $file
            || !preg_match(
                '/^[a-zA-Z0-9][a-zA-Z0-9._-]*\.(?:jpe?g|png|webp)$/i',
                $file
            )
        ) {
            continue;
        }

        $path = $realDirectory
            . DIRECTORY_SEPARATOR
            . $file;

        if (
            !is_file($path)
            || !maple_accommodation_path_is_inside(
                $path,
                $realDirectory
            )
        ) {
            continue;
        }

        $images[] = $file;
    }

    natcasesort($images);

    return array_values($images);
}

function maple_accommodation_fixed_gallery_images(
    int $imageSourceId,
    string $assetBase = ''
): array {
    $slides = [];

    foreach (
        maple_accommodation_gallery_files($imageSourceId)
        as $filename
    ) {
        $slides[] = maple_accommodation_gallery_slide(
            maple_accommodation_gallery_file_url(
                $imageSourceId,
                $filename,
                $assetBase
            ),
            $filename,
            maple_accommodation_gallery_file_type($filename)
        );
    }

    if ($slides === []) {
        $slides[] = maple_accommodation_gallery_slide(
            maple_accommodation_image_fallback_url($assetBase),
            'no-image-placeholder.png',
            'photo',
            true
        );
    }

    return $slides;
}

function maple_accommodation_type_for_huis_id(
    PDO $pdo,
    int $huisId
): ?string {
    if ($huisId <= 0) {
        return null;
    }

    $statement = $pdo->prepare('
        SELECT `Huis_naam`
        FROM `accomodaties`
        WHERE `Huis_id` = :huis_id
        LIMIT 1
    ');

    $statement->execute([
        'huis_id' => $huisId,
    ]);

    $type = $statement->fetchColumn();

    return is_string($type) && trim($type) !== ''
        ? trim($type)
        : null;
}

function maple_accommodation_gallery_images(
    int $huisId,
    $coverImage = '',
    string $assetBase = '',
    ?string $huisNaam = null
): array {
    $type = trim((string) ($huisNaam ?? ''));

    if ($type === '') {
        try {
            $type = (string) (
                maple_accommodation_type_for_huis_id(
                    maple_pdo(),
                    $huisId
                ) ?? ''
            );
        } catch (Throwable $exception) {
            error_log(
                'Accommodation gallery type lookup failed: '
                . $exception->getMessage()
            );
            $type = '';
        }
    }

    $imageSourceId = maple_accommodation_type_image_id(
        null,
        $type
    );

    if ($imageSourceId === null) {
        return [
            maple_accommodation_gallery_slide(
                maple_accommodation_image_fallback_url(
                    $assetBase
                ),
                'no-image-placeholder.png',
                'photo',
                true
            ),
        ];
    }

    return maple_accommodation_fixed_gallery_images(
        $imageSourceId,
        $assetBase
    );
}

function maple_accommodation_gallery_json(
    array $accommodation,
    string $assetBase = ''
): string {
    $json = json_encode(
        maple_accommodation_gallery_images(
            (int) ($accommodation['Huis_id'] ?? 0),
            '',
            $assetBase,
            (string) ($accommodation['Huis_naam'] ?? '')
        ),
        JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE
    );

    return is_string($json)
        ? $json
        : '[]';
}
