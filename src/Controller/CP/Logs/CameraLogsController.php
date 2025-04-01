<?php

declare(strict_types=1);

namespace App\Controller\CP\Logs;

use App\Controller\CP\AbstractCPController;
use App\Form\Filter\CameraLogFilterType;
use App\Repository\Log\CameraLogRepository;
use Knp\Component\Pager\PaginatorInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

/**
 * @psalm-import-type CameraLogFilterArray from CameraLogRepository
 */
#[Route('/logs/camera-logs')]
#[IsGranted('ROLE_SUPER_ADMIN')]
class CameraLogsController extends AbstractCPController
{
    public function __construct(private readonly CameraLogRepository $cameraLogRepository)
    {
    }

    #[Route('/', name: 'cp_logs_camera_logs')]
    public function index(Request $request, PaginatorInterface $paginator): Response
    {
        $numberOfRecords = $request->query->getInt('numberOfRecords', 25);
        $cameraLogFilterForm = $this->createForm(CameraLogFilterType::class);
        $cameraLogFilterForm->handleRequest($request);
        $filter = [];
        if ($cameraLogFilterForm->isSubmitted() && $cameraLogFilterForm->isValid()) {
            $filter['time'] = $cameraLogFilterForm->get('time')->getData();
            $filter['user'] = $cameraLogFilterForm->get('user')->getData();
            $filter['camera'] = $cameraLogFilterForm->get('camera')->getData();
        }
        /** @psalm-var CameraLogFilterArray $filter */
        $queryBuilder = $this->cameraLogRepository->getQueryBuilder($filter);
        $cameraLogs = $paginator->paginate($queryBuilder, $request->query->getInt('page', 1), $numberOfRecords, [
            'defaultSortFieldName' => 'cl.time',
            'defaultSortDirection' => 'desc',
        ]);

        return $this->render('cp/logs/camera_logs/index.html.twig', [
            'cameraLogFilterForm' => $cameraLogFilterForm->createView(),
            'numberOfRecords' => $numberOfRecords,
            'cameraLogs' => $cameraLogs,
        ]);
    }
}
