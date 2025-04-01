<?php

declare(strict_types=1);

namespace App\Helper;

use App\Cache\RedisCameraCache;
use App\DTO\CameraSessionData;
use App\Entity\Camera;
use App\Entity\CameraBackup;
use App\Entity\Enum\DockontrolNodeStatus;
use App\Entity\Log\CameraLog;
use App\Entity\User;
use App\Repository\CameraBackupRepository;
use App\Security\Voter\CameraVoter;
use Carbon\CarbonImmutable;
use Doctrine\ORM\EntityManagerInterface;
use Exception;
use RuntimeException;
use Symfony\Bundle\SecurityBundle\Security;
use Symfony\Component\DependencyInjection\Attribute\Autowire;
use Symfony\Component\Lock\LockFactory;
use Symfony\Component\Uid\Uuid;
use Throwable;

readonly class CameraHelper
{
    public function __construct(
        private EntityManagerInterface $entityManager,
        private RedisHelper $redisHelper,
        private DockontrolNodeHelper $dockontrolNodeHelper,
        private RedisCameraCache $redisCameraCache,
        private LockFactory $lockFactory,
        private Security $security,
        private CameraBackupRepository $cameraBackupRepository,
        #[Autowire('%kernel.project_dir%')]
        private string $projectDir,
    ) {
    }

    public function saveCamera(Camera $camera): void
    {
        $this->entityManager->persist($camera);
        $this->entityManager->flush();
    }

    public function removeCamera(Camera $camera): void
    {
        $this->entityManager->remove($camera);
        $this->entityManager->flush();
    }

    /**
     * @param array<Camera> $cameras
     */
    public function addCameraSession(
        string $cameraSessionId,
        array $cameras,
        int $userId,
        int $ttlSeconds = 300,
    ): bool {
        $redis = $this->redisHelper->getRedisInstance();

        $cameraSessionData = new CameraSessionData($userId, $cameraSessionId, $cameras);

        try {
            $redis->setex('camera_session:' . $cameraSessionId, $ttlSeconds, serialize($cameraSessionData));
            return true;
        } catch (Throwable) {
            return false;
        }
    }

    public function getCameraSession(string $cameraSessionId): CameraSessionData|false
    {
        $redis = $this->redisHelper->getRedisInstance();

        try {
            /** @var string|false $cameraSessionData */
            $cameraSessionData = $redis->get('camera_session:' . $cameraSessionId);
            if ($cameraSessionData === false) {
                return false;
            }
            /** @var CameraSessionData $cameraSessionDataDTO */
            $cameraSessionDataDTO = unserialize($cameraSessionData);
            return $cameraSessionDataDTO;
        } catch (Throwable) {
            return false;
        }
    }

    /**
     * @param array<Camera> $cameras
     */
    public function checkPermissionAndCreateCameraSession(
        array $cameras,
        User $user,
        bool $addLog = true,
    ): string {
        foreach ($cameras as $camera) {
            if (!$this->security->isGranted(CameraVoter::SHOW, $camera)) {
                throw new RuntimeException('User does not have permission to access camera.');
            }
        }

        $cameraSessionId = Uuid::v4()->toString();
        $cameraSessionAdded = $this->addCameraSession($cameraSessionId, $cameras, $user->getId());

        if (!$cameraSessionAdded) {
            throw new RuntimeException('Failed to add camera session.');
        }

        if ($addLog) {
            foreach ($cameras as $camera) {
                $this->addLog($camera, $user, false);
            }

            $this->entityManager->flush();
        }

        return $cameraSessionId;
    }

    public function fetchCameraImage(Camera $camera): string
    {
        $cacheKey = 'camera:' . $camera->getNameId();
        $lockKey = 'lock_camera_image_' . $camera->getNameId();

        $maxWaitCycles = 50;
        $waitTime = 100000;
        $waitCycles = 0;

        do {
            $photoData = $this->redisCameraCache->getCacheItem($cacheKey);

            if (empty($photoData)) {
                $lock = null;
                try {
                    $lock = $this->lockFactory->createLock($lockKey);
                    if ($lock->acquire()) {
                        $photoData = $this->getCameraImageFromDockontrolNodeWithBackup($camera);
                    } else {
                        usleep($waitTime);
                        continue;
                    }
                } catch (Throwable $e) {
                    throw new RuntimeException('Failed to fetch image from camera.', 0, $e);
                } finally {
                    $lock?->release();
                }
            }

            if (!empty($photoData)) {
                return $photoData;
            }
        } while (++$waitCycles < $maxWaitCycles);

        throw new RuntimeException('Failed to fetch image from camera.');
    }

    public function addLog(Camera $camera, User $user, bool $flushEntityManager = true): void
    {
        $log = new CameraLog();
        $log->setCamera($camera);
        $log->setUser($user);
        $log->setTime(CarbonImmutable::now());
        $this->entityManager->persist($log);
        if ($flushEntityManager) {
            $this->entityManager->flush();
        }
    }

    /**
     * @throws Exception
     */
    private function getCameraImageFromDockontrolNodeWithBackup(Camera $camera): string
    {
        $cameraDockontrolNode = $camera->getDockontrolNode();
        $cacheKey = 'camera:' . $camera->getNameId();
        if ($cameraDockontrolNode->getStatus() !== DockontrolNodeStatus::ONLINE) {
            $cameraBackup = $this->cameraBackupRepository->findRandomBackupByParentCamera($camera);
            if ($cameraBackup instanceof CameraBackup) {
                $rawData = $this->dockontrolNodeHelper->callDockontrolNodeAPICamera($cameraBackup);
            }
        } else {
            $rawData = $this->dockontrolNodeHelper->callDockontrolNodeAPICamera($camera);
        }
        if (empty($rawData) || $rawData['httpCode'] !== 200) {
            $photoData = file_get_contents($this->projectDir . '/public/assets/images/camera_not_found.jpg');
        } else {
            $photoData = $rawData['rawData'];
        }
        /** @var string $photoData*/
        $this->redisCameraCache->saveCacheItem($cacheKey, $photoData, 500);
        return $photoData;
    }
}
