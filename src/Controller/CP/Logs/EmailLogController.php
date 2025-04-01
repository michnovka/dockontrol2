<?php

declare(strict_types=1);

namespace App\Controller\CP\Logs;

use App\Controller\CP\AbstractCPController;
use App\Form\Filter\EmailLogFilterType;
use App\Repository\Log\EmailLogRepository;
use Knp\Component\Pager\PaginatorInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

/**
 * @psalm-import-type EmailLogFilterArray from EmailLogRepository
 */
#[Route('/logs/email-logs')]
#[IsGranted('ROLE_SUPER_ADMIN')]
class EmailLogController extends AbstractCPController
{
    public function __construct(private readonly EmailLogRepository $emailLogRepository)
    {
    }

    #[Route('/', name: 'cp_logs_email_logs')]
    public function index(Request $request, PaginatorInterface $paginator): Response
    {
        $numberOfRecords = $request->query->getInt('numberOfRecords', 25);
        $emailLogFilterForm = $this->createForm(EmailLogFilterType::class);
        $emailLogFilterForm->handleRequest($request);
        $filter = [];

        if ($emailLogFilterForm->isSubmitted() && $emailLogFilterForm->isValid()) {
            $filter['time'] = $emailLogFilterForm->get('time')->getData();
        }

        /** @psalm-var EmailLogFilterArray $filter*/
        $queryBuilder = $this->emailLogRepository->getQueryBuilder($filter);
        $emailLogs = $paginator->paginate($queryBuilder, $request->query->getInt('page', 1), $numberOfRecords, [
            'defaultSortFieldName' => 'el.time',
            'defaultSortDirection' => 'desc',
        ]);

        return $this->render('cp/logs/email_logs/index.html.twig', [
            'emailLogs' => $emailLogs,
            'emailLogFilterForm' => $emailLogFilterForm->createView(),
        ]);
    }
}
