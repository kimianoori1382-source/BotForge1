<?php

declare(strict_types=1);

namespace Botforge\Entities;

class Document
{
  public readonly string $fileId;
  public readonly string $fileUniqueId;
  public readonly string $fileName;
  public readonly string $mimeType;
  public readonly int $fileSize;

  public function __construct(array $data = [])
  {
    $fileId = '';
    $fileUniqueId = '';
    $fileName = '';
    $mimeType = '';
    $fileSize = 0;

    if (isset($data['file_id'])) {
      $fileId = (string) $data['file_id'];
    }

    if (isset($data['file_unique_id'])) {
      $fileUniqueId = (string) $data['file_unique_id'];
    }

    if (isset($data['file_name'])) {
      $fileName = (string) $data['file_name'];
    }

    if (isset($data['mime_type'])) {
      $mimeType = (string) $data['mime_type'];
    }

    if (isset($data['file_size'])) {
      $fileSize = (int) $data['file_size'];
    }

    $this->fileId = $fileId;
    $this->fileUniqueId = $fileUniqueId;
    $this->fileName = $fileName;
    $this->mimeType = $mimeType;
    $this->fileSize = $fileSize;
  }
}
