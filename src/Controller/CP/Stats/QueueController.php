<?php

declare(strict_types=1);

namespace App\Controller\CP\Stats;

use App\Controller\CP\AbstractCPController;
use App\Form\Filter\ActionQueueFilterType;
use App\Helper\CustomPaginator;
use App\Repository\ActionQueueRepository;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

/**
 * @psalm-import-type ActionQueueFilterType from ActionQueueRepository
 */

#[Route('/stats/queue')]
#[IsGranted('ROLE_SUPER_ADMIN')]
class QueueController extends AbstractCPController
{
    public function __construct(
        private readonly ActionQueueRepository $actionQueueRepository,
        private readonly CustomPaginator $customPaginator,
    ) {
    }

    #[Route('/', name: 'cp_stats_queue')]
    public function index(Request $request): Response
    {
        $limit = $request->query->getInt('limit', 50);
        $currentPage = $request->query->getInt('page', 1);
        $filter = [];

        $actionQueueFilterForm = $this->createForm(ActionQueueFilterType::class);
        $actionQueueFilterForm->handleRequest($request);

        if ($actionQueueFilterForm->isSubmitted()) {
            $filter['timeStart'] = $actionQueueFilterForm->get('timeStart')->getData();
            $filter['user'] = $actionQueueFilterForm->get('user')->getData();
            $filter['action'] = $actionQueueFilterForm->get('action')->getData();
            $filter['status'] = $actionQueueFilterForm->get('status')->getData();
        }

        /** @psalm-var ActionQueueFilterType $filter */
        $actionQueues = $this->actionQueueRepository->getCombinedQueueQueryBuilder($filter, $currentPage, $limit);
        $pages = $this->customPaginator->generatePagination($currentPage, $actionQueues['hasNextPage']);

        return $this->render('cp/stats/queue/index.html.twig', [
            'actionQueues' => $actionQueues['items'],
            'pages' => $pages,
            'currentPage' => $currentPage,
            'recordsPerPage' => $limit,
            'hasNextPage' => $actionQueues['hasNextPage'],
            'actionQueueFilterForm' => $actionQueueFilterForm->createView(),
        ]);
    }
}
