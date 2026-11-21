<?php

declare(strict_types=1);

namespace Botforge\Conversation;

use Botforge\Storage\StorageInterface;

class ConversationManager
{
    private StorageInterface $storage;

    private int $defaultTtl;

    public function __construct(
        StorageInterface $storage,
        int $defaultTtl = 3600
    ) {
        $this->storage = $storage;
        $this->defaultTtl = $defaultTtl;
    }

    private function getKey(int $userId): string
    {
        return 'conversation:' . $userId;
    }

    public function start(
        int $userId,
        string $name,
        array $data = [],
        int $ttl = 0
    ): void {
        if ($ttl <= 0) {
            $ttl = $this->defaultTtl;
        }

        $this->storage->set(
            $this->getKey($userId),
            [
                'name' => $name,
                'step' => 'start',
                'data' => $data,
            ],
            $ttl
        );
    }

    public function get(int $userId): array
    {
        $conversation = $this->storage->get(
            $this->getKey($userId)
        );

        if (!is_array($conversation)) {
            return [];
        }

        return $conversation;
    }

    public function isActive(int $userId): bool
    {
        return !empty($this->get($userId));
    }

    public function getName(int $userId): string
    {
        $conversation = $this->get($userId);

        if (!isset($conversation['name'])) {
            return '';
        }

        return $conversation['name'];
    }

    public function getStep(int $userId): string
    {
        $conversation = $this->get($userId);

        if (!isset($conversation['step'])) {
            return '';
        }

        return $conversation['step'];
    }

    public function getData(int $userId, string $key, mixed $default = null): mixed
    {
        $conversation = $this->get($userId);

        if (
            !isset($conversation['data'])
            || !is_array($conversation['data'])
            || !array_key_exists($key, $conversation['data'])
        ) {
            return $default;
        }

        return $conversation['data'][$key];
    }

    public function setStep(int $userId, string $step): void
    {
        $conversation = $this->get($userId);

        if (empty($conversation)) {
            return;
        }

        $conversation['step'] = $step;

        $this->storage->set(
            $this->getKey($userId),
            $conversation,
            $this->defaultTtl
        );
    }

    public function setData(int $userId, string $key, mixed $value): void
    {
        $conversation = $this->get($userId);

        if (empty($conversation)) {
            return;
        }

        if (!isset($conversation['data']) || !is_array($conversation['data'])) {
            $conversation['data'] = [];
        }

        $conversation['data'][$key] = $value;

        $this->storage->set(
            $this->getKey($userId),
            $conversation,
            $this->defaultTtl
        );
    }

    public function validate(
        int $userId,
        mixed $value,
        callable $validator
    ): bool {
        if (!$this->isActive($userId)) {
            return false;
        }

        return (bool) $validator($value);
    }

    public function cancel(int $userId): void
    {
        $this->finish($userId);
    }

    public function finish(int $userId): void
    {
        $this->storage->delete(
            $this->getKey($userId)
        );
    }
}
