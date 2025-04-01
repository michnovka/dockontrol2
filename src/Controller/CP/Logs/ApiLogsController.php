<?php

declare(strict_types=1);

namespace App\Controller\CP\Logs;

use App\Controller\CP\AbstractCPController;
use App\Form\Filter\APILog\Failed\API2LogFailedFilterType;
use App\Form\Filter\APILog\Failed\DockontrolNodeAPILogFailedFilterType;
use App\Form\Filter\APILog\Failed\LegacyAPILogFailedFilterType;
use App\Form\Filter\APILog\Succeeded\API2LogSuccessFilterType;
use App\Form\Filter\APILog\Succeeded\DockontrolNodeAPILogSuccessFilterType;
use App\Form\Filter\APILog\Succeeded\LegacyAPISuccessFilterType;
use App\Repository\Log\ApiCallFailedLog\API2CallFailedLogRepository;
use App\Repository\Log\ApiCallFailedLog\DockontrolNodeAPICallFailedLogRepository;
use App\Repository\Log\ApiCallFailedLog\LegacyAPICallFailedLogRepository;
use App\Repository\Log\ApiCallLog\API2CallLogRepository;
use App\Repository\Log\ApiCallLog\DockontrolNodeAPICallLogRepository;
use App\Repository\Log\ApiCallLog\LegacyAPICallLogRepository;
use Knp\Component\Pager\PaginatorInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

/**
 * @psalm-import-type LegacyApiCallFilterArray from LegacyAPICallLogRepository
 * @psalm-import-type Api2CallFilterArray from API2CallLogRepository
 * @psalm-import-type Api2FailedCallFilterArray from API2CallFailedLogRepository
 * @psalm-import-type DockontrolNodeApiCallFilterArray from DockontrolNodeAPICallLogRepository
 * @psalm-import-type DockontrolNodeApiFailedCallFilterArray from DockontrolNodeAPICallFailedLogRepository
 */
#[Route('/logs/api-logs')]
#[IsGranted('ROLE_SUPER_ADMIN')]
class ApiLogsController extends AbstractCPController
{
    public function __construct(
        private readonly LegacyAPICallLogRepository $legacyAPICallLogRepository,
        private readonly LegacyAPICallFailedLogRepository $legacyAPICallFailedLogRepository,
        private readonly API2CallLogRepository $api2CallLogRepository,
        private readonly API2CallFailedLogRepository $api2CallFailedLogRepository,
        private readonly DockontrolNodeAPICallLogRepository $dockontrolNodeAPICallLogRepository,
        private readonly DockontrolNodeAPICallFailedLogRepository $dockontrolNodeAPICallFailedLogRepository,
    ) {
    }

    #[Route('/legacy/succeeded', name: 'cp_logs_legacy_api_succeeded_logs')]
    public function legacyAPISucceededCallLogs(Request $request, PaginatorInterface $paginator): Response
    {
        $numberOfRecords = $request->query->getInt('numberOfRecords', 25);

        $apiLogFilterForm = $this->createForm(LegacyAPISuccessFilterType::class);

        $apiLogFilterForm->handleRequest($request);
        $filter = [];

        if ($apiLogFilterForm->isSubmitted() && $apiLogFilterForm->isValid()) {
            $filter['time'] = $apiLogFilterForm->get('time')->getData();
            $filter['user'] = $apiLogFilterForm->get('user')->getData();
            $filter['ip'] = $apiLogFilterForm->get('ip')->getData();
            $filter['apiAction'] = $apiLogFilterForm->get('apiAction')->getData();
        }

        /** @psalm-var LegacyApiCallFilterArray $filter*/
        $queryBuilder = $this->legacyAPICallLogRepository->getQueryBuilder($filter);

        $apiLogs = $paginator->paginate($queryBuilder, $request->query->getInt('page', 1), $numberOfRecords, [
            'defaultSortFieldName' => 'lal.time',
            'defaultSortDirection' => 'desc',
        ]);

        return $this->render("cp/logs/api_logs/legacy/succeeded/index.html.twig", [
            'numberOfRecords' => $numberOfRecords,
            'apiLogs' => $apiLogs,
            'apiLogFilterForm' => $apiLogFilterForm->createView(),
        ]);
    }

    #[Route('/legacy/failed', name: 'cp_logs_legacy_api_failed_logs')]
    public function legacyAPIFailedCallLogs(Request $request, PaginatorInterface $paginator): Response
    {
        $numberOfRecords = $request->query->getInt('numberOfRecords', 25);
        $apiLogFilterForm = $this->createForm(LegacyAPILogFailedFilterType::class);

        $apiLogFilterForm->handleRequest($request);
        $filter = [];

        if ($apiLogFilterForm->isSubmitted() && $apiLogFilterForm->isValid()) {
            $filter['time'] = $apiLogFilterForm->get('time')->getData();
            $filter['user'] = $apiLogFilterForm->get('user')->getData();
            $filter['ip'] = $apiLogFilterForm->get('ip')->getData();
            $filter['apiAction'] = $apiLogFilterForm->get('apiAction')->getData();
        }

        /** @psalm-var LegacyApiCallFilterArray $filter*/
        $queryBuilder = $this->legacyAPICallFailedLogRepository->getQueryBuilder($filter);

        $apiLogs = $paginator->paginate($queryBuilder, $request->query->getInt('page', 1), $numberOfRecords, [
            'defaultSortFieldName' => 'lafl.time',
            'defaultSortDirection' => 'desc',
        ]);

        return $this->render("cp/logs/api_logs/legacy/failed/index.html.twig", [
            'apiLogs' => $apiLogs,
            'numberOfRecords' => $numberOfRecords,
            'apiLogFilterForm' => $apiLogFilterForm->createView(),
        ]);
    }

    #[Route('/api2/succeeded', name: 'cp_logs_api2_succeeded_logs')]
    public function API2SucceededCallLogs(Request $request, PaginatorInterface $paginator): Response
    {
        $numberOfRecords = $request->query->getInt('numberOfRecords', 25);
        $apiLogFilterForm = $this->createForm(API2LogSuccessFilterType::class);

        $apiLogFilterForm->handleRequest($request);
        $filter = [];

        if ($apiLogFilterForm->isSubmitted() && $apiLogFilterForm->isValid()) {
            $filter['time'] = $apiLogFilterForm->get('time')->getData();
            $filter['apiKey'] = $apiLogFilterForm->get('apiKey')->getData();
            $filter['ip'] = $apiLogFilterForm->get('ip')->getData();
            $filter['user'] = $apiLogFilterForm->get('user')->getData();
        }

        /** @psalm-var Api2CallFilterArray $filter*/
        $queryBuilder = $this->api2CallLogRepository->getQueryBuilder($filter);

        $apiLogs = $paginator->paginate($queryBuilder, $request->query->getInt('page', 1), $numberOfRecords, [
            'defaultSortFieldName' => 'v.time',
            'defaultSortDirection' => 'desc',
        ]);

        return $this->render("cp/logs/api_logs/api2/succeeded/index.html.twig", [
            'apiLogs' => $apiLogs,
            'numberOfRecords' => $numberOfRecords,
            'apiLogFilterForm' => $apiLogFilterForm->createView(),
        ]);
    }

    #[Route('/api2/failed', name: 'cp_logs_api2_failed_logs')]
    public function API2CallLogs(Request $request, PaginatorInterface $paginator): Response
    {
        $numberOfRecords = $request->query->getInt('numberOfRecords', 25);
        $apiLogFilterForm = $this->createForm(API2LogFailedFilterType::class);

        $apiLogFilterForm->handleRequest($request);
        $filter = [];

        if ($apiLogFilterForm->isSubmitted() && $apiLogFilterForm->isValid()) {
            $filter['time'] = $apiLogFilterForm->get('time')->getData();
            $filter['apiKey'] = $apiLogFilterForm->get('apiKey')->getData();
            $filter['ip'] = $apiLogFilterForm->get('ip')->getData();
            $filter['apiEndpoint'] = $apiLogFilterForm->get('apiEndpoint')->getData();
        }


        /** @psalm-var Api2FailedCallFilterArray $filter*/
        $queryBuilder = $this->api2CallFailedLogRepository->getQueryBuilder($filter);

        $apiLogs = $paginator->paginate($queryBuilder, $request->query->getInt('page', 1), $numberOfRecords, [
            'defaultSortFieldName' => 'vfl.time',
            'defaultSortDirection' => 'desc',
        ]);

        return $this->render("cp/logs/api_logs/api2/failed/index.html.twig", [
            'apiLogs' => $apiLogs,
            'numberOfRecords' => $numberOfRecords,
            'apiLogFilterForm' => $apiLogFilterForm->createView(),
        ]);
    }

    #[Route('/dockontrol-node/succeeded', name: 'cp_logs_dockontrol_node_api_succeeded_logs')]
    public function dockontrolNodeAPISucceededCallLogs(Request $request, PaginatorInterface $paginator): Response
    {
        $numberOfRecords = $request->query->getInt('numberOfRecords', 25);
        $apiLogFilterForm = $this->createForm(DockontrolNodeAPILogSuccessFilterType::class);

        $apiLogFilterForm->handleRequest($request);
        $filter = [];

        if ($apiLogFilterForm->isSubmitted() && $apiLogFilterForm->isValid()) {
            $filter['time'] = $apiLogFilterForm->get('time')->getData();
            $filter['ip'] = $apiLogFilterForm->get('ip')->getData();
            $filter['dockontrolNode'] = $apiLogFilterForm->get('dockontrolNode')->getData();
        }

        /** @psalm-var DockontrolNodeApiCallFilterArray $filter*/
        $queryBuilder = $this->dockontrolNodeAPICallLogRepository->getQueryBuilder($filter);

        $apiLogs = $paginator->paginate($queryBuilder, $request->query->getInt('page', 1), $numberOfRecords, [
            'defaultSortFieldName' => 'dnal.time',
            'defaultSortDirection' => 'desc',
        ]);

        return $this->render("cp/logs/api_logs/dockontrol_node/succeeded/index.html.twig", [
            'apiLogs' => $apiLogs,
            'numberOfRecords' => $numberOfRecords,
            'apiLogFilterForm' => $apiLogFilterForm->createView(),
        ]);
    }

    #[Route('/dockontrol-node/failed', name: 'cp_logs_dockontrol_node_api_failed_logs')]
    public function dockontrolNodeAPIFailedCallLogs(Request $request, PaginatorInterface $paginator): Response
    {
        $numberOfRecords = $request->query->getInt('numberOfRecords', 25);

        $apiLogFilterForm = $this->createForm(DockontrolNodeAPILogFailedFilterType::class);

        $apiLogFilterForm->handleRequest($request);
        $filter = [];

        if ($apiLogFilterForm->isSubmitted() && $apiLogFilterForm->isValid()) {
            $filter['time'] = $apiLogFilterForm->get('time')->getData();
            $filter['ip'] = $apiLogFilterForm->get('ip')->getData();
            $filter['dockontrolNodeApiKey'] = $apiLogFilterForm->get('apiKey')->getData();
            $filter['apiEndpoint'] = $apiLogFilterForm->get('apiEndpoint')->getData();
        }

        /** @psalm-var DockontrolNodeApiFailedCallFilterArray $filter*/
        $queryBuilder = $this->dockontrolNodeAPICallFailedLogRepository->getQueryBuilder($filter);

        $apiLogs = $paginator->paginate($queryBuilder, $request->query->getInt('page', 1), $numberOfRecords, [
            'defaultSortFieldName' => 'dnafl.time',
            'defaultSortDirection' => 'desc',
        ]);

        return $this->render("cp/logs/api_logs/dockontrol_node/failed/index.html.twig", [
            'apiLogs' => $apiLogs,
            'numberOfRecords' => $numberOfRecords,
            'apiLogFilterForm' => $apiLogFilterForm->createView(),
        ]);
    }
}
