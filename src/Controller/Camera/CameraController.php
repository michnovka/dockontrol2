<?php

declare(strict_types=1);

namespace App\Controller\Camera;

use App\Entity\Camera;
use App\Helper\CameraHelper;
use App\Repository\CameraRepository;
use Exception;
use RuntimeException;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

class CameraController extends AbstractController
{
    public function __construct(
        private readonly CameraRepository $cameraRepository,
        private readonly CameraHelper $cameraHelper,
    ) {
    }

    /**
     * @throws Exception
     */
    #[Route('/camera/{cameraSessionId}/{cameraId}', name: 'dockontrol_camera_view')]
    #[IsGranted('ROLE_TENANT')]
    public function view(string $cameraId): Response
    {
        $camera = $this->cameraRepository->findOneBy(['nameId' => $cameraId]);

        if (!$camera instanceof Camera) {
            throw new RuntimeException('Camera session ID expired.');
        }

        $photoData = $this->cameraHelper->fetchCameraImage($camera);
        $response = new Response($photoData);
        $response->headers->set('Content-Type', 'image/jpeg');

        return $response;
    }
}
