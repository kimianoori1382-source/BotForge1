<?php

declare(strict_types=1);

namespace Botforge\Entities;

class Photo
{
  public readonly array $sizes;

  public function __construct(array $data = [])
  {
    $sizes = [];

    if (isset($data['sizes']) && is_array($data['sizes'])) {
      $sizes = $data['sizes'];
    }

    $this->sizes = $sizes;
  }
}
