<?php

declare(strict_types=1);

namespace App\DTO;

use App\Entity\Camera;
use InvalidArgumentException;

class CameraSessionData
{
    public int $userId;
    public string $sessionId;

    /** @var Camera[] */
    public array $cameras;

    /**
     * @param array<Camera> $cameras
     */
    public function __construct(int $userId, string $sessionId, array $cameras)
    {
        $this->userId = $userId;
        $this->sessionId = $sessionId;
        $this->cameras = $cameras;
    }

    /**
     * Magic method called when serializing the object
     * Uses JSON encoding for serialization
     *
     * @return array{user_id: int, session_id: string, cameras: array<int, Camera>}
     */
    public function __serialize(): array
    {
        return [
            'user_id'    => $this->userId,
            'session_id' => $this->sessionId,
            'cameras' => $this->cameras,
        ];
    }

    /**
     * Magic method called when unserializing the object
     * Uses JSON decoding for unserialization
     *
     * @param array{user_id: int, session_id: string, cameras: array<int, Camera>} $data
    */
    public function __unserialize(array $data): void
    {
        // Validate and assign data with appropriate type checks

        if (
            !isset($data['user_id'], $data['session_id'], $data['cameras']) ||
            !is_int($data['user_id']) ||
            !is_string($data['session_id']) ||
            !is_array($data['cameras'])
        ) {
            throw new InvalidArgumentException('Invalid data for camera session data.');
        }

        // Ensure that camera IDs are all strings
        foreach ($data['cameras'] as $camera) {
            if (!$camera instanceof Camera) {
                throw new InvalidArgumentException('Cameras must be an array of camera objects.');
            }
        }

        $this->userId = $data['user_id'];
        $this->sessionId = $data['session_id'];
        $this->cameras = $data['cameras'];
    }
}
