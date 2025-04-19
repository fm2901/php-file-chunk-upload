<?php
namespace App;
use InvalidArgumentException;

class UploadChunkDTO
{
    public string $uploadId;
    public string $fileName;
    public int $chunkIndex;
    public int $totalChunks;
    public string $tempPath;
    public function __construct(array $post, array $file)
    {
        if (
            !isset($post['uploadId'], $post['fileName'], $post['chunkIndex'], $post['totalChunks']) ||
            !isset($file['tmp_name'])
        ) {
            throw new InvalidArgumentException('Недостаточно данных для загрузки чанка ');
        }

        $this->uploadId = $post['uploadId'];
        $this->fileName = basename($post['fileName']);
        $this->chunkIndex = (int) $post['chunkIndex'];
        $this->totalChunks = (int) $post['totalChunks'];
        $this->tempPath = $file['tmp_name'];
    }
}
