<?php
declare(strict_types=1);

class Uploads
{
    private const MAX_SIZE = 5_242_880;
    private const ALLOWED_MIME = [
        'image/jpeg' => 'jpg',
        'image/png' => 'png',
        'image/webp' => 'webp',
    ];

    public static function storeImage(array $file, string $directory): ?string
    {
        if (($file['error'] ?? UPLOAD_ERR_NO_FILE) === UPLOAD_ERR_NO_FILE) {
            return null;
        }

        if (($file['error'] ?? UPLOAD_ERR_OK) !== UPLOAD_ERR_OK) {
            throw new RuntimeException('Le televersement de l image a echoue.');
        }

        $tmpName = (string)($file['tmp_name'] ?? '');
        if ($tmpName === '' || !is_uploaded_file($tmpName)) {
            throw new RuntimeException('Fichier image invalide.');
        }

        $size = (int)($file['size'] ?? 0);
        if ($size <= 0 || $size > self::MAX_SIZE) {
            throw new RuntimeException('L image depasse la taille maximale autorisee de 5 Mo.');
        }

        $mime = mime_content_type($tmpName) ?: '';
        $extension = self::ALLOWED_MIME[$mime] ?? null;
        if ($extension === null) {
            throw new RuntimeException('Format d image non pris en charge. Utilise JPG, PNG ou WebP.');
        }

        $relativeDirectory = '/public/uploads/' . trim($directory, '/');
        $absoluteDirectory = dirname(__DIR__, 2) . $relativeDirectory;
        if (!is_dir($absoluteDirectory) && !mkdir($absoluteDirectory, 0775, true) && !is_dir($absoluteDirectory)) {
            throw new RuntimeException('Impossible de creer le dossier de destination des images.');
        }

        $filename = date('YmdHis') . '-' . bin2hex(random_bytes(6)) . '.' . $extension;
        $absolutePath = $absoluteDirectory . '/' . $filename;
        if (!move_uploaded_file($tmpName, $absolutePath)) {
            throw new RuntimeException('Impossible d enregistrer l image telechargee.');
        }

        return $relativeDirectory . '/' . $filename;
    }

    public static function deleteIfPresent(?string $path): void
    {
        if ($path === null || trim($path) === '') {
            return;
        }

        $absolutePath = dirname(__DIR__, 2) . '/' . ltrim($path, '/');
        if (is_file($absolutePath)) {
            @unlink($absolutePath);
        }
    }
}
