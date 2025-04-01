<?php

declare(strict_types=1);

namespace App\Controller\CP\Logs;

use App\Controller\CP\AbstractCPController;
use App\Form\Filter\NukiLogFilterType;
use App\Repository\Log\NukiLogRepository;
use Knp\Component\Pager\PaginatorInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

/**
 * @psalm-import-type NukiLogFilterArray from NukiLogRepository
 */
#[Route('/logs/nuki-logs')]
#[IsGranted('ROLE_SUPER_ADMIN')]
class NukiLogsController extends AbstractCPController
{
    public function __construct(private readonly NukiLogRepository $nukiLogRepository)
    {
    }

    #[Route('/', name: 'cp_logs_nuki_logs')]
    public function index(Request $request, PaginatorInterface $paginator): Response
    {
        $numberOfRecords = $request->query->getInt('numberOfRecords', 25);
        $nukiLogFilterForm = $this->createForm(NukiLogFilterType::class);
        $nukiLogFilterForm->handleRequest($request);
        $filter = [];
        if ($nukiLogFilterForm->isSubmitted() && $nukiLogFilterForm->isValid()) {
            $filter['time'] = $nukiLogFilterForm->get('time')->getData();
            $filter['user'] = $nukiLogFilterForm->get('user')->getData();
            $filter['nuki'] = $nukiLogFilterForm->get('nuki')->getData();
            $filter['status'] = $nukiLogFilterForm->get('status')->getData();
            $filter['action'] = $nukiLogFilterForm->get('action')->getData();
        }

        /** @psalm-var NukiLogFilterArray $filter */
        $queryBuilder = $this->nukiLogRepository->getQueryBuilder($filter);
        $nukiLogs = $paginator->paginate($queryBuilder, $request->query->getInt('page', 1), $numberOfRecords, [
            'defaultSortFieldName' => 'nl.time',
            'defaultSortDirection' => 'desc',
        ]);

        return $this->render('cp/logs/nuki_log/index.html.twig', [
            'numberOfRecords' => $numberOfRecords,
            'nukiLogs' => $nukiLogs,
            'nukiLogFilterForm' => $nukiLogFilterForm->createView(),
        ]);
    }
}
