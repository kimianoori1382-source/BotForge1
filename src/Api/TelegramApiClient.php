<?php

declare(strict_types=1);

namespace Botforge\Api;

use Botforge\Exception\TelegramApiException;
use Botforge\Exception\ForbiddenException;
use Botforge\Exception\NotFoundException;
use Botforge\Exception\RateLimitException;

class TelegramApiClient
{
  private HttpClientInterface $http;

  private string $token;

  private string $baseurl;

  private int $maxRetries;

  private int $retryDelay;

  public function __construct(
    HttpClientInterface $http,
    string $token,
    int $maxRetries = 3,
    int $retryDelay = 2
  ) {
    $this->http = $http;
    $this->token = $token;
    $this->baseurl = 'https://api.telegram.org/bot' . $token . '/';
    $this->maxRetries = $maxRetries;
    $this->retryDelay = $retryDelay;
  }

  public function getMe(): array
  {
    return $this->call('getMe');
  }

  public function getUpdates(array $data = []): array
  {
    $response = $this->call('getUpdates', $data);

    if (isset($response['result']) && is_array($response['result'])) {
      return $response['result'];
    }

    return [];
  }

  public function sendMessage(array $data = []): array
  {
    return $this->call('sendMessage', $data);
  }

  public function editMessageText(array $data): array
  {
    return $this->call('editMessageText', $data);
  }

  public function deleteMessage(array $data): array
  {
    return $this->call('deleteMessage', $data);
  }

  public function answerCallbackQuery(array $data): array
  {
    return $this->call('answerCallbackQuery', $data);
  }

  public function sendChatAction(array $data): array
  {
    return $this->call('sendChatAction', $data);
  }

  public function getChatMember(array $data): array
  {
    return $this->call('getChatMember', $data);
  }

  private function handleErrorResponse(array $response): void
  {
    if (isset($response['ok']) && $response['ok'] === true) {
      return;
    }

    $message = 'Telegram API request failed';
    $code = 0;

    if (isset($response['description'])) {
      $message = $response['description'];
    }

    if (isset($response['error_code'])) {
      $code = (int) $response['error_code'];
    }

    if ($code === 403) {
      throw new ForbiddenException($message, $code, $response);
    }

    if ($code === 404) {
      throw new NotFoundException($message, $code, $response);
    }

    if ($code === 429) {
      throw new RateLimitException($message, $code, $response);
    }

    throw new TelegramApiException($message, $code, $response);
  }

  private function callMultipart(
    string $method,
    array $data = [],
    array $files = []
  ): array {
    $response = $this->http->post(
      $this->baseurl . $method,
      $data,
      $files
    );

    $this->handleErrorResponse($response);

    return $response;
  }

  public function sendPhoto(array $data = [], array $files = []): array
  {
    return $this->callMultipart('sendPhoto', $data, $files);
  }

  public function sendDocument(array $data = [], array $files = []): array
  {
    return $this->callMultipart('sendDocument', $data, $files);
  }

  public function getFile(array $data): array
  {
    return $this->call('getFile', $data);
  }

  public function downloadFile(string $filePath): string
  {
    $url = 'https://api.telegram.org/file/bot'
      . $this->token
      . '/'
      . $filePath;

    return $this->http->get($url);
  }

  public function setWebhook(array $data): array
  {
    return $this->call('setWebhook', $data);
  }

  public function deleteWebhook(array $data = []): array
  {
    return $this->call('deleteWebhook', $data);
  }

  public function getWebhookInfo(): array
  {
    return $this->call('getWebhookInfo');
  }

  public function call(string $method, array $data = []): array
  {
    $attempt = 0;

    while (true) {
      try {
        $response = $this->http->post(
          $this->baseurl . $method,
          $data
        );

        $this->handleErrorResponse($response);

        return $response;
      } catch (RateLimitException $error) {
        if ($attempt >= $this->maxRetries) {
          throw $error;
        }

        $delay = $this->retryDelay;
        $response = $error->getResponse();

        if (isset($response['parameters']['retry_after'])) {
          $delay = (int) $response['parameters']['retry_after'];
        }

        $attempt++;

        sleep($delay);
      }
    }
  }
}
