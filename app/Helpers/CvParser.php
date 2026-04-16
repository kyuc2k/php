<?php

/**
 * CV Parser Helper - wraps the cv_parser.php functions.
 * Delegates to the existing cv_parser.php file.
 */
class CvParser
{
    public static function parse(string $filePath): array
    {
        require_once BASE_PATH . '/cv_parser.php';
        return cv_parseFromFile($filePath);
    }

    public static function extractPhoto(string $pdfPath, string $savePath): bool
    {
        require_once BASE_PATH . '/cv_parser.php';
        return cv_extractPhoto($pdfPath, $savePath);
    }
}
