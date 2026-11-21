<?php

declare(strict_types=1);

namespace Botforge\Entities;

class Location
{
  public readonly float $longitude;
  public readonly float $latitude;

  public function __construct(array $data = [])
  {
    $longitude = 0.0;
    $latitude = 0.0;

    if (isset($data['longitude'])) {
      $longitude = (float) $data['longitude'];
    }

    if (isset($data['latitude'])) {
      $latitude = (float) $data['latitude'];
    }

    $this->longitude = $longitude;
    $this->latitude = $latitude;
  }
}
