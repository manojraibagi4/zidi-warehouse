<?php
// File: src/utils/ImageHandler.php

namespace App\Utils;

class ImageHandler
{
    private const UPLOAD_BASE_DIR = __DIR__ . '/../../public/img/uploaded/';
    private const RELATIVE_UPLOAD_DIR = 'public/img/uploaded/';
    private const ALLOWED_EXTENSIONS = ['jpg', 'jpeg', 'png', 'gif'];

    /**
     * Copies an image from a local path to the upload directory unless it already exists.
     * Returns the relative path to the uploaded file (or existing one).
     */
    public static function handleImageUploadFromPath(string $sourceFilePath): ?string
    {
        if (!file_exists($sourceFilePath)) {
            error_log("ERROR: Source image does not exist: {$sourceFilePath}");
            return null;
        }

        $extension = strtolower(pathinfo($sourceFilePath, PATHINFO_EXTENSION));
        if (!in_array($extension, self::ALLOWED_EXTENSIONS)) {
            error_log("ERROR: Invalid image extension: {$extension}");
            return null;
        }

        if (!is_dir(self::UPLOAD_BASE_DIR)) {
            if (!mkdir(self::UPLOAD_BASE_DIR, 0755, true)) {
                error_log("ERROR: Failed to create upload directory: " . self::UPLOAD_BASE_DIR);
                return null;
            }
        }


        $originalFilename = basename($sourceFilePath);
        $destinationPathWithOriginalName = self::UPLOAD_BASE_DIR . $originalFilename;

        // Step 1: Check if a file with the same name already exists
        if (file_exists($destinationPathWithOriginalName)) {
            error_log("INFO: File with same name already exists: {$destinationPathWithOriginalName}");
            return self::RELATIVE_UPLOAD_DIR . $originalFilename;
        }

        $hash = uniqid() . '-' . md5_file($sourceFilePath);
        $fileName = $hash . '.' . $extension;
        $destinationPath = self::UPLOAD_BASE_DIR . $fileName;

        // If the file already exists (based on hash), no need to copy
        if (!file_exists($destinationPath)) {
            if (!copy($sourceFilePath, $destinationPath)) {
                error_log("ERROR: Failed to copy file from {$sourceFilePath} to {$destinationPath}");
                return null;
            }
            error_log("INFO: Copied new image to {$destinationPath}");
        } else {
            error_log("INFO: Image already exists, skipping copy: {$destinationPath}");
        }

        return self::RELATIVE_UPLOAD_DIR . $fileName;
    }
}
