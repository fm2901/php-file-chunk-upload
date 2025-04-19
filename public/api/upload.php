<?php
error_reporting(E_ERROR);
require __DIR__ . '/../../vendor/autoload.php';

use App\FileUploader;
use App\UploadChunkDTO;

header('Content-Type: application/json');

try {
    if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
        throw new Exception('Invalid request method');
    }

    $dto = new UploadChunkDTO($_POST, $_FILES['chunk'] ?? []);

    $uploader = new FileUploader(
        $dto,
        dirname(__DIR__) . '/../uploads/temp/',
        dirname(__DIR__) . '/../uploads/final/'
    );

    if (!$uploader->saveChunk($dto->tempPath)) {
        throw new Exception('Failed to save chunk');
    }

    if ($uploader->isUploadComplete()) {
        $uploader->assembleFile();
    }

    $fileUrl = '/uploads/final/' . $dto->fileName;

    echo json_encode(['success' => true, 'fileUrl' => $fileUrl]);

} catch (Exception $e) {
    http_response_code(400);
    echo json_encode([
        'success' => false,
        'error' => $e->getMessage()
    ]);
}
