<?php

declare(strict_types=1);

namespace App\Controller\CP\Logs;

use App\Controller\CP\AbstractCPController;
use App\Form\Filter\LoginLog\LoginLogFailedFilterType;
use App\Form\Filter\LoginLog\LoginLogSucceededFilterType;
use App\Repository\Log\LoginLogFailedRepository;
use App\Repository\Log\LoginLogRepository;
use Knp\Component\Pager\PaginatorInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

/**
 * @psalm-import-type LoginLogFilterArray from LoginLogRepository
 */
#[Route('/logs/login-logs')]
#[IsGranted('ROLE_SUPER_ADMIN')]
class LoginLogsController extends AbstractCPController
{
    public function __construct(
        private readonly LoginLogRepository $loginLogRepository,
        private readonly LoginLogFailedRepository $loginLogFailedRepository,
    ) {
    }

    #[Route('/succeeded', name: 'cp_logs_succeeded_login_logs')]
    public function succeededLogs(Request $request, PaginatorInterface $paginator): Response
    {
        $numberOfRecords = $request->query->getInt('numberOfRecords', 25);
        $loginLogsFilterForm = $this->createForm(LoginLogSucceededFilterType::class);

        $loginLogsFilterForm->handleRequest($request);
        $filter = [];

        if ($loginLogsFilterForm->isSubmitted() && $loginLogsFilterForm->isValid()) {
            $filter['time'] = $loginLogsFilterForm->get('time')->getData();
            $filter['user'] = $loginLogsFilterForm->get('user')->getData();
            $filter['ip'] = $loginLogsFilterForm->get('ip')->getData();
            $filter['browser'] = $loginLogsFilterForm->get('browser')->getData();
            $filter['platform'] = $loginLogsFilterForm->get('platform')->getData();
        }

        /** @psalm-var LoginLogFilterArray $filter*/
        $queryBuilder = $this->loginLogRepository->getQueryBuilder($filter);

        $loginLogs = $paginator->paginate($queryBuilder, $request->query->getInt('page', 1), $numberOfRecords, [
            'defaultSortFieldName' => 'l.time',
            'defaultSortDirection' => 'desc',
        ]);

        return $this->render("cp/logs/login_logs/succeeded/index.html.twig", [
            'loginLogs' => $loginLogs,
            'numberOfRecords' => $numberOfRecords,
            'loginLogFilterForm' => $loginLogsFilterForm->createView(),
        ]);
    }

    #[Route('/failed', name: 'cp_logs_failed_login_logs')]
    public function failedLOgs(Request $request, PaginatorInterface $paginator): Response
    {
        $numberOfRecords = $request->query->getInt('numberOfRecords', 25);
        $loginLogsFilterForm = $this->createForm(LoginLogFailedFilterType::class);

        $loginLogsFilterForm->handleRequest($request);
        $filter = [];

        if ($loginLogsFilterForm->isSubmitted() && $loginLogsFilterForm->isValid()) {
            $filter['time'] = $loginLogsFilterForm->get('time')->getData();
            $filter['user'] = $loginLogsFilterForm->get('user')->getData();
            $filter['ip'] = $loginLogsFilterForm->get('ip')->getData();
            $filter['browser'] = $loginLogsFilterForm->get('browser')->getData();
            $filter['platform'] = $loginLogsFilterForm->get('platform')->getData();
        }

        /** @psalm-var LoginLogFilterArray $filter*/
        $queryBuilder = $this->loginLogFailedRepository->getQueryBuilder($filter);

        $loginLogs = $paginator->paginate($queryBuilder, $request->query->getInt('page', 1), $numberOfRecords, [
            'defaultSortFieldName' => 'lf.time',
            'defaultSortDirection' => 'desc',
        ]);

        return $this->render("cp/logs/login_logs/failed/index.html.twig", [
            'loginLogs' => $loginLogs,
            'numberOfRecords' => $numberOfRecords,
            'loginLogFilterForm' => $loginLogsFilterForm->createView(),
        ]);
    }
}
