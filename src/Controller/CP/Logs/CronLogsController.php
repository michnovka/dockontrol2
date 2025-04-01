<?php

declare(strict_types=1);

namespace App\Controller\CP\Logs;

use App\Controller\CP\AbstractCPController;
use App\Entity\Log\CronLog;
use App\Form\Filter\CronLogFilterType;
use App\Helper\CustomPaginator;
use App\Helper\MeilisearchHelper;
use App\Repository\Log\CronLogRepository;
use Knp\Component\Pager\PaginatorInterface;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

/**
 * @psalm-import-type CronLogFilterArray from CronLogRepository
 */
#[Route('/logs/cron-logs')]
#[IsGranted('ROLE_SUPER_ADMIN')]
class CronLogsController extends AbstractCPController
{
    public function __construct(
        private readonly MeilisearchHelper $meilisearchHelper,
        private readonly CustomPaginator $customPaginator,
    ) {
    }

    #[Route('/', name: 'cp_logs_cron_logs', methods: ['GET'])]
    public function index(Request $request, PaginatorInterface $paginator): Response
    {
        $cronLogFilterForm = $this->createForm(CronLogFilterType::class);
        $cronLogFilterForm->handleRequest($request);
        $limit = $request->query->getInt('limit', 10);
        $currentPage = $request->query->getInt('page', 1);
        $filterArr = [];
        $sortArr = ['timeStart' => 'desc', 'timeEnd' => 'desc'];
        $query = '';

        if ($cronLogFilterForm->isSubmitted() && $cronLogFilterForm->isValid()) {
            $timeStartDateRange = $cronLogFilterForm->get('timeStart')->getData();
            $timeEndDateRange = $cronLogFilterForm->get('timeEnd')->getData();
            $cronGroup = $cronLogFilterForm->get('cronGroup')->getData();
            $cronType = $cronLogFilterForm->get('cronType')->getData();

            if ($cronGroup) {
                $filterArr[] = 'cronGroup = ' . $cronGroup->getName();
            }

            if ($timeStartDateRange) {
                $filterArr[] = sprintf(
                    'timeStart >= %d AND timeStart <= %d',
                    $timeStartDateRange->getStartDate()->getTimestamp(),
                    $timeStartDateRange->getEndDate()->getTimestamp()
                );
            }

            if ($timeStartDateRange) {
                $filterArr[] = sprintf(
                    'timeEnd >= %d AND timeEnd <= %d',
                    $timeEndDateRange->getStartDate()->getTimestamp(),
                    $timeEndDateRange->getEndDate()->getTimestamp()
                );
            }

            if ($cronType) {
                $query = $cronType->getReadable();
            }
        }

        $rawSearch = $this->meilisearchHelper->searchAndHydrate(
            CronLog::class,
            $query,
            $limit,
            $sortArr,
            $currentPage,
            $filterArr
        );

        $cronLogs = $rawSearch['data'];
        $hasNextPage = $rawSearch['hasNextPage'];

        $pages = $this->customPaginator->generatePagination($currentPage, $hasNextPage);

        return $this->render('cp/logs/cron_log/index.html.twig', [
            'cronLogs' => $cronLogs,
            'cronLogFilterForm' => $cronLogFilterForm,
            'pages' => $pages,
            'currentPage' => $currentPage,
            'recordsPerPage' => $limit,
            'hasNextPage' => $hasNextPage,
        ]);
    }

    #[Route('/{id}/show-output', name: 'cp_logs_cron_log_show_output')]
    public function showOutput(CronLog $cronLog): JsonResponse
    {
        return $this->json([
            'success' => true,
            'output' => $cronLog->getOutput(),
        ]);
    }
}
