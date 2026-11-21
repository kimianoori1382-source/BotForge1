<?php

declare(strict_types=1);

namespace Botforge\Exception;

use RuntimeException;
use TelegramApiClient;

class TelegramApiException extends RuntimeException{
  private array $response;
  public function __construct(string $message, int $code=0, array $response=[]){
    parent::__construct($message, $code);
    $this->response = $response;
  }

  public function getResponse(): array{
    return $this->response;
  }
}

  class ForbiddenException extends TelegramApiException{
    
  }

  class NotFoundException extends TelegramApiException{

  }

  class RateLimitException extends TelegramApiException{

  }
