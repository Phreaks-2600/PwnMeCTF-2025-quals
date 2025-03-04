<?php
namespace App\Security;

use Exception;

class FileScanner {
    private string $baseDirectory;
    private array $allowedExtensions;
    private int $maxFileSize;
    private array $allowedMimeTypes;
    private array $dangerousPatterns;

    public function __construct(?string $baseDirectory = null) {
        $this->baseDirectory = realpath($baseDirectory) ?? realpath(dirname(__DIR__, 2));
        $this->maxFileSize = 5 * 1024 * 1024; // 5MB
        $this->allowedExtensions = [];
        $this->allowedMimeTypes = [];
        $this->dangerousPatterns = [
            '/\b(exec|system|passthru|shell_exec|eval)\b/i',
            '/<\?php/i',
            '/\binclude\b/i',
            '/\brequire\b/i',
            '/\bfile_\w+\b/i',
            '/\bcurl_\w+\b/i',
            '/\bftp_\w+\b/i',
            '/\bsocket_\w+\b/i'
        ];
    }

    public function validateFile(array $file, string $type): array {
        try {
            $this->validateFileBasics($file);
            $this->validateFileType($file, $type);
            $this->validateFileContent($file, $type);

            return [
                'success' => true,
                'message' => 'File validated'
            ];
        } catch (Exception $e) {
            return [
                'success' => false,
                'message' => $e->getMessage()
            ];
        }
    }

    private function validateFileBasics(array $file): void {
        if (!isset($file['tmp_name']) || !is_uploaded_file($file['tmp_name'])) {
            throw new Exception('Invalid file or not uploaded');
        }

        if ($file['size'] <= 0) {
            throw new Exception('Empty file');
        }

        if ($file['size'] > $this->maxFileSize) {
            throw new Exception('File too large (max 5MB)');
        }
    }

    private function validateFileType(array $file, string $type): void {
        if ($type === 'less' || $type === 'css') {
            $this->allowedExtensions = ['less', 'css'];
            $this->allowedMimeTypes = ['text/plain'];
        }
        if ($type === 'image') {
            $this->allowedExtensions = ['jpg', 'jpeg', 'png', 'gif', 'svg'];
            $this->allowedMimeTypes = ['image/jpeg', 'image/png', 'image/gif', 'image/svg+xml'];
        }
        $extension = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));
        if (!in_array($extension, $this->allowedExtensions)) {
            throw new Exception('Unauthorized file type');
        }

        $finfo = finfo_open(FILEINFO_MIME_TYPE);
        $mimeType = finfo_file($finfo, $file['tmp_name']);
        finfo_close($finfo);
        if (!in_array($mimeType, $this->allowedMimeTypes)) {
            throw new Exception('Unauthorized MIME type');
        }
    }

    private function validateFileContent(array $file): void {
            $content = file_get_contents($file['tmp_name']);
            if (!mb_check_encoding($content, 'UTF-8')) {
                throw new Exception('The file encoding must be UTF-8');
            }
            foreach ($this->dangerousPatterns as $pattern) {
                if (preg_match($pattern, $content)) {
                    throw new Exception('Potentially dangerous content detected');
                }
            }
        }
    public function validatePath(string $path): bool {
        $realPath = realpath($path);
        if (!$realPath) {
            return false;
        }
        if (strpos($realPath, $this->baseDirectory) !== 0) {
            return false;
        }
        return true;
    }
    public function generateSafeFileName(string $originalName): string {
        $info = pathinfo($originalName);
        $extension = strtolower($info['extension']);
        $name = preg_replace('/[^a-zA-Z0-9_-]/', '', $info['filename']);
        $name = empty($name) ? 'file_' . uniqid() : $name;
        return $name . '.' . $extension;
    }
} 