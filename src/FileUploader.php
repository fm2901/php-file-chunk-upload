<?php

namespace App;

class FileUploader
{
    protected UploadChunkDTO $data;
    protected string $tempDir;
    protected string $finalDir;

    public function __construct(
        UploadChunkDTO $data,
        ?string $tempDir = null,
        ?string $finalDir = null
    ) {
        $this->data = $data;

        $this->tempDir = rtrim($tempDir ?? __DIR__ . '/../uploads/temp/', '/') . '/';
        $this->finalDir = rtrim($finalDir ?? __DIR__ . '/../uploads/final/', '/') . '/';

        if (!is_dir($this->tempDir)) {
            mkdir($this->tempDir, 0777, true);
        }

        if (!is_dir($this->finalDir)) {
            mkdir($this->finalDir, 0777, true);
        }
    }

    public function saveChunk(string $tmpPath): bool
    {
        $chunkPath = $this->getChunkPath();

        if (is_uploaded_file($tmpPath)) {
            return move_uploaded_file($tmpPath, $chunkPath);
        }

        return rename($tmpPath, $chunkPath);
    }

    public function isUploadComplete(): bool
    {
        for ($i = 0; $i < $this->data->totalChunks; $i++) {
            if (!file_exists($this->getChunkPath($i))) {
                return false;
            }
        }
        return true;
    }

    public function assembleFile(): bool
    {
        $finalPath = $this->finalDir . basename($this->data->fileName);
        $output = fopen($finalPath, 'ab');

        for ($i = 0; $i < $this->data->totalChunks; $i++) {
            $chunk = file_get_contents($this->getChunkPath($i));
            fwrite($output, $chunk);
        }

        fclose($output);
        $this->cleanUp();
        return true;
    }

    protected function getChunkPath(?int $index = null): string
    {
        $index = $index ?? $this->data->chunkIndex;
        $safeUploadId = preg_replace('/[^a-zA-Z0-9_\-]/', '_', $this->data->uploadId);
        return "{$this->tempDir}{$safeUploadId}_chunk_{$index}";
    }

    protected function cleanUp(): void
    {
        for ($i = 0; $i < $this->data->totalChunks; $i++) {
            $chunkPath = $this->getChunkPath($i);
            if (file_exists($chunkPath)) {
                unlink($chunkPath);
            }
        }
    }
}