<?php
namespace App\Tests;

use PHPUnit\Framework\TestCase;
use App\FileUploader;
use App\UploadChunkDTO;

class FileUploaderTest extends TestCase
{
    private string $tempDir;
    private string $finalDir;

    protected function setUp(): void
    {
        $this->tempDir = __DIR__ . '/temp/';
        $this->finalDir = __DIR__ . '/final/';

        if (!is_dir($this->tempDir)) mkdir($this->tempDir, 0777, true);
        if (!is_dir($this->finalDir)) mkdir($this->finalDir, 0777, true);
    }

    protected function tearDown(): void
    {
        array_map('unlink', glob($this->tempDir . '*'));
        array_map('unlink', glob($this->finalDir . '*'));
        @rmdir($this->tempDir);
        @rmdir($this->finalDir);
    }

    public function testChunkIsSaved()
    {
        $dto = new UploadChunkDTO([
            'uploadId' => 'test123',
            'fileName' => 'file.txt',
            'chunkIndex' => 0,
            'totalChunks' => 1
        ], [
            'tmp_name' => tempnam(sys_get_temp_dir(), 'chunk'),
        ]);

        file_put_contents($dto->tempPath, 'Hello world');

        $uploader = new FileUploader($dto, $this->tempDir, $this->finalDir);
        $this->assertTrue($uploader->saveChunk($dto->tempPath));
        $this->assertFileExists($this->tempDir . 'test123_chunk_0');
    }

    public function testUploadCompletionDetection()
    {
        $uploadId = 'upload_test';
        $fileName = 'file.txt';
        $totalChunks = 2;

        for ($i = 0; $i < $totalChunks; $i++) {
            $dto = new UploadChunkDTO([
                'uploadId' => $uploadId,
                'fileName' => $fileName,
                'chunkIndex' => $i,
                'totalChunks' => $totalChunks
            ], [
                'tmp_name' => tempnam(sys_get_temp_dir(), 'chunk'),
            ]);
            file_put_contents($dto->tempPath, "Chunk{$i}");

            $uploader = new FileUploader($dto, $this->tempDir, $this->finalDir);
            $uploader->saveChunk($dto->tempPath);
        }

        $dtoCheck = new UploadChunkDTO([
            'uploadId' => $uploadId,
            'fileName' => $fileName,
            'chunkIndex' => 0,
            'totalChunks' => $totalChunks
        ], [
            'tmp_name' => '',
        ]);

        $uploader = new FileUploader($dtoCheck, $this->tempDir, $this->finalDir);
        $this->assertTrue($uploader->isUploadComplete());
    }

    public function testAssembleFinalFile()
    {
        $uploadId = 'test_assemble';
        $fileName = 'file.txt';
        $totalChunks = 2;

        $contents = ['first part', 'second part'];

        foreach ($contents as $i => $text) {
            $dto = new UploadChunkDTO([
                'uploadId' => $uploadId,
                'fileName' => $fileName,
                'chunkIndex' => $i,
                'totalChunks' => $totalChunks
            ], [
                'tmp_name' => tempnam(sys_get_temp_dir(), 'chunk'),
            ]);
            file_put_contents($dto->tempPath, $text);

            $uploader = new FileUploader($dto, $this->tempDir, $this->finalDir);
            $uploader->saveChunk($dto->tempPath);
        }

        $dtoFinal = new UploadChunkDTO([
            'uploadId' => $uploadId,
            'fileName' => $fileName,
            'chunkIndex' => 0,
            'totalChunks' => $totalChunks
        ], [
            'tmp_name' => '',
        ]);

        $uploader = new FileUploader($dtoFinal, $this->tempDir, $this->finalDir);
        $uploader->assembleFile();

        $finalPath = $this->finalDir . $fileName;
        $this->assertFileExists($finalPath);
        $this->assertEquals('first partsecond part', file_get_contents($finalPath));
    }
}
