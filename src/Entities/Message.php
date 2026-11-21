<?php

declare(strict_types=1);

namespace Botforge\Entities;

class Message
{
  public readonly int $messageId;
  public readonly int $date;
  public readonly string $text;
  public readonly User $from;
  public readonly Chat $chat;
  public readonly Photo $photo;
  public readonly Contact $contact;
  public readonly Location $location;
  public readonly Document $document;
  public readonly bool $hasPhoto;
  public readonly bool $hasContact;
  public readonly bool $hasLocation;
  public readonly bool $hasDocument;

  public function __construct(array $data = [])
  {
    $messageId = 0;
    $date = 0;
    $text = '';
    $userData = [];
    $chatData = [];
    $photoData = [];
    $contactData = [];
    $locationData = [];
    $documentData = [];

    if (isset($data['message_id'])) {
      $messageId = (int) $data['message_id'];
    }

    if (isset($data['date'])) {
      $date = (int) $data['date'];
    }

    if (isset($data['text'])) {
      $text = (string) $data['text'];
    }

    if (isset($data['from']) && is_array($data['from'])) {
      $userData = $data['from'];
    }

    if (isset($data['chat']) && is_array($data['chat'])) {
      $chatData = $data['chat'];
    }

    if (isset($data['photo']) && is_array($data['photo'])) {
      $photoData = ['sizes' => $data['photo']];
    }

    if (isset($data['contact']) && is_array($data['contact'])) {
      $contactData = $data['contact'];
    }

    if (isset($data['location']) && is_array($data['location'])) {
      $locationData = $data['location'];
    }

    if (isset($data['document']) && is_array($data['document'])) {
      $documentData = $data['document'];
    }

    $this->messageId = $messageId;
    $this->date = $date;
    $this->text = $text;
    $this->from = new User($userData);
    $this->chat = new Chat($chatData);
    $this->photo = new Photo($photoData);
    $this->contact = new Contact($contactData);
    $this->location = new Location($locationData);
    $this->document = new Document($documentData);
    $this->hasPhoto = !empty($photoData);
    $this->hasContact = !empty($contactData);
    $this->hasLocation = !empty($locationData);
    $this->hasDocument = !empty($documentData);
  }
}
