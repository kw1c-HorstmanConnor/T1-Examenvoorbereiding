<?php
declare(strict_types=1);

const MAPLE_ACCOMMODATION_IMAGE_MAX_BYTES = 5242880;

function maple_accommodation_image_upload_directory(): string
{
    return dirname(__DIR__, 2) . DIRECTORY_SEPARATOR . 'Images' . DIRECTORY_SEPARATOR . 'Accommodations';
}

function maple_accommodation_image_fallback_url(string $assetBase = ''): string
{
    return $assetBase . 'Images/background.png';
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

/**
 * @return string|null
 */
function maple_accommodation_image_path($value)
{
    $filename = maple_accommodation_image_filename($value);

    if ($filename === '') {
        return null;
    }

    return maple_accommodation_image_upload_directory() . DIRECTORY_SEPARATOR . $filename;
}

function maple_accommodation_image_exists($value): bool
{
    $path = maple_accommodation_image_path($value);

    return $path !== null && is_file($path);
}

function maple_accommodation_image_url($value, string $assetBase = ''): string
{
    $filename = maple_accommodation_image_filename($value);

    if ($filename !== '' && maple_accommodation_image_exists($filename)) {
        return $assetBase . 'Images/Accommodations/' . rawurlencode($filename);
    }

    return maple_accommodation_image_fallback_url($assetBase);
}

function maple_accommodation_uploaded_file_present($file): bool
{
    return is_array($file)
        && isset($file['error'])
        && (int) $file['error'] !== UPLOAD_ERR_NO_FILE;
}

function maple_accommodation_ensure_upload_directory()
{
    $directory = maple_accommodation_image_upload_directory();

    if (is_dir($directory)) {
        return;
    }

    if (!mkdir($directory, 0755, true) && !is_dir($directory)) {
        throw new RuntimeException('The accommodation image directory could not be created.');
    }
}

function maple_accommodation_image_slug(string $name): string
{
    $slug = strtolower(trim(preg_replace('/[^a-z0-9]+/i', '-', $name) ?? '', '-'));

    return $slug !== '' ? $slug : 'accommodation';
}

function maple_accommodation_store_uploaded_image(array $file, string $accommodationName): string
{
    $error = (int) ($file['error'] ?? UPLOAD_ERR_NO_FILE);

    if ($error === UPLOAD_ERR_NO_FILE) {
        return '';
    }

    if ($error !== UPLOAD_ERR_OK) {
        throw new RuntimeException('The image upload failed. Please choose another JPG, PNG or WebP image.');
    }

    $temporaryPath = (string) ($file['tmp_name'] ?? '');

    if ($temporaryPath === '' || !is_uploaded_file($temporaryPath)) {
        throw new RuntimeException('The uploaded image could not be verified.');
    }

    $size = filesize($temporaryPath);

    if ($size === false || $size > MAPLE_ACCOMMODATION_IMAGE_MAX_BYTES) {
        throw new RuntimeException('Accommodation images may not be larger than 5 MB.');
    }

    $finfo = new finfo(FILEINFO_MIME_TYPE);
    $mimeType = $finfo->file($temporaryPath);
    $allowedTypes = [
        'image/jpeg' => 'jpg',
        'image/png' => 'png',
        'image/webp' => 'webp',
    ];

    if (!is_string($mimeType) || !isset($allowedTypes[$mimeType])) {
        throw new RuntimeException('Only JPG, PNG or WebP accommodation images are allowed.');
    }

    $imageInfo = @getimagesize($temporaryPath);

    if ($imageInfo === false || !isset($allowedTypes[(string) ($imageInfo['mime'] ?? '')])) {
        throw new RuntimeException('The selected file is not a valid JPG, PNG or WebP image.');
    }

    maple_accommodation_ensure_upload_directory();

    $extension = $allowedTypes[$mimeType];
    $directory = maple_accommodation_image_upload_directory();

    do {
        $filename = sprintf(
            '%s-%d-%s.%s',
            maple_accommodation_image_slug($accommodationName),
            time(),
            bin2hex(random_bytes(4)),
            $extension
        );
        $destination = $directory . DIRECTORY_SEPARATOR . $filename;
    } while (is_file($destination));

    if (!move_uploaded_file($temporaryPath, $destination)) {
        throw new RuntimeException('The accommodation image could not be saved.');
    }

    return $filename;
}

/**
 * @return string|null
 */
function maple_accommodation_current_image(PDO $pdo, int $huisId)
{
    $statement = $pdo->prepare('
        SELECT Afbeelding
        FROM accomodaties
        WHERE Huis_id = :huis_id
        LIMIT 1
    ');
    $statement->execute(['huis_id' => $huisId]);
    $image = $statement->fetchColumn();

    return is_string($image) && trim($image) !== '' ? $image : null;
}

function maple_accommodation_delete_file(string $image)
{
    $path = maple_accommodation_image_path($image);

    if ($path === null || !is_file($path)) {
        return;
    }

    $directory = realpath(maple_accommodation_image_upload_directory());
    $target = realpath($path);

    if ($directory === false || $target === false) {
        return;
    }

    $directory = rtrim($directory, DIRECTORY_SEPARATOR) . DIRECTORY_SEPARATOR;

    if (strncmp($target, $directory, strlen($directory)) === 0) {
        @unlink($target);
    }
}

/**
 * @param string|null $image
 */
function maple_accommodation_delete_file_if_unused(PDO $pdo, $image)
{
    $filename = maple_accommodation_image_filename($image);

    if ($filename === '') {
        return;
    }

    $pathValue = 'Images/Accommodations/' . $filename;
    $statement = $pdo->prepare('
        SELECT COUNT(*)
        FROM accomodaties
        WHERE Afbeelding = :filename
            OR Afbeelding = :path_value
    ');
    $statement->execute([
        'filename' => $filename,
        'path_value' => $pathValue,
    ]);

    if ((int) $statement->fetchColumn() === 0) {
        maple_accommodation_delete_file($filename);
    }
}
