<?php
declare(strict_types=1);

namespace App\Core;

class Upload
{
    private string  $dir;
    private array   $allowedTypes;
    private int     $maxSize;
    private ?string $error = null;

    public function __construct(string $subDir = '', array $allowedTypes = [], int $maxSize = 0)
    {
        $this->dir          = rtrim(UPLOAD_PATH . '/' . ltrim($subDir, '/'), '/');
        $this->allowedTypes = $allowedTypes ?: UPLOAD_ALLOWED_TYPES;
        $this->maxSize      = $maxSize      ?: UPLOAD_MAX_SIZE;
    }

    /**
     * Process a single uploaded file from $_FILES[$key].
     * Returns the generated filename on success, null on failure.
     */
    public function handle(array $file): ?string
    {
        $this->error = null;

        if (!isset($file['error'])) {
            $this->error = 'Invalid file data.';
            return null;
        }

        if ($file['error'] !== UPLOAD_ERR_OK) {
            $this->error = $this->codeToMessage($file['error']);
            return null;
        }

        if ($file['size'] > $this->maxSize) {
            $mb = round($this->maxSize / 1048576, 1);
            $this->error = "File size exceeds the {$mb} MB limit.";
            return null;
        }

        $mime = mime_content_type($file['tmp_name']);
        if (!in_array($mime, $this->allowedTypes, true)) {
            $this->error = 'File type not allowed (' . $mime . ').';
            return null;
        }

        if (!is_dir($this->dir) && !mkdir($this->dir, 0755, true)) {
            $this->error = 'Could not create upload directory.';
            return null;
        }

        $ext      = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));
        $filename = bin2hex(random_bytes(16)) . '.' . $ext;
        $dest     = $this->dir . '/' . $filename;

        if (!move_uploaded_file($file['tmp_name'], $dest)) {
            $this->error = 'Failed to save uploaded file.';
            return null;
        }

        return $filename;
    }

    public function getError(): ?string
    {
        return $this->error;
    }

    public function hasError(): bool
    {
        return $this->error !== null;
    }

    /** Delete a file in the upload directory by filename. */
    public function delete(string $filename): bool
    {
        $path = $this->dir . '/' . basename($filename);
        return file_exists($path) && unlink($path);
    }

    private function codeToMessage(int $code): string
    {
        return match ($code) {
            UPLOAD_ERR_INI_SIZE, UPLOAD_ERR_FORM_SIZE => 'File too large.',
            UPLOAD_ERR_PARTIAL   => 'File was only partially uploaded.',
            UPLOAD_ERR_NO_FILE   => 'No file was uploaded.',
            UPLOAD_ERR_NO_TMP_DIR => 'Missing temporary folder.',
            UPLOAD_ERR_CANT_WRITE => 'Failed to write file to disk.',
            UPLOAD_ERR_EXTENSION  => 'Upload stopped by extension.',
            default               => 'Unknown upload error.',
        };
    }
}
