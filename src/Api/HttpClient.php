<?php

declare(strict_types=1);

namespace Botforge\Api;

use GuzzleHttp\ClientInterface as GuzzleClient;
use GuzzleHttp\Psr7\Utils;
use RuntimeException;

interface HttpClientInterface
{
  public function post(string $url, array $data = []): array;

  public function postMultipart(
    string $url,
    array $data = [],
    array $files = []
  ): array;

  public function get(string $url): string;
}

class HttpClient implements HttpClientInterface
{
  private GuzzleClient $client;

  public function __construct(GuzzleClient $client)
  {
    $this->client = $client;
  }

  public function post(string $url, array $data = []): array
  {
    $response = $this->client->request('POST', $url, [
      'json' => $data,
    ]);

    return $this->decode($response->getBody()->getContents());
  }

  public function postMultipart(
    string $url,
    array $data = [],
    array $files = []
  ): array {
    $parts = [];
    $streams = [];

    foreach ($data as $name => $value) {
      $parts[] = [
        'name' => (string) $name,
        'contents' => (string) $value,
      ];
    }

    foreach ($files as $name => $path) {
      $stream = Utils::tryFopen($path, 'r');
      $streams[] = $stream;

      $parts[] = [
        'name' => (string) $name,
        'contents' => $stream,
        'filename' => basename($path),
      ];
    }

    try {
      $response = $this->client->request('POST', $url, [
        'multipart' => $parts,
      ]);

      return $this->decode($response->getBody()->getContents());
    } finally {
      foreach ($streams as $stream) {
        if (is_resource($stream)) {
          fclose($stream);
        }
      }
    }
  }

  public function get(string $url): string
  {
    $response = $this->client->request('GET', $url);

    return $response->getBody()->getContents();
  }

  private function decode(string $body): array
  {
    $result = json_decode($body, true);

    if (!is_array($result)) {
      throw new RuntimeException('Invalid JSON response.');
    }

    return $result;
  }
}
