<?php

declare(strict_types=1);

namespace App\Controller\CP\Logs;

use App\Controller\CP\AbstractCPController;
use App\Repository\Log\EmailChangeLogRepository;
use Knp\Component\Pager\PaginatorInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

#[Route('/logs/email-change-logs')]
#[IsGranted('ROLE_SUPER_ADMIN')]
class EmailChangeLogsController extends AbstractCPController
{
    public function __construct(private readonly EmailChangeLogRepository $emailChangeLogRepository)
    {
    }

    #[Route('/', name: 'cp_logs_email_change_logs')]
    public function index(Request $request, PaginatorInterface $paginator): Response
    {
        $numberOfRecords = $request->query->getInt('numberOfRecords', 25);

        $queryBuilder = $this->emailChangeLogRepository->getQueryBuilder();
        $emailChangeLogs = $paginator->paginate($queryBuilder, $request->query->getInt('page', 1), $numberOfRecords, [
            'defaultSortFieldName' => 'ecl.timeCreated',
            'defaultSortDirection' => 'desc',
        ]);

        return $this->render('cp/logs/email_change_log/index.html.twig', [
            'emailChangeLogs' => $emailChangeLogs,
        ]);
    }
}
