<?php

declare(strict_types=1);

namespace App\Controller\CP\Logs;

use App\Controller\CP\AbstractCPController;
use App\Entity\Log\UserActionLog;
use App\Extension\Type\DateRange;
use App\Form\Filter\UserActionLogFilterType;
use App\Helper\CustomPaginator;
use App\Helper\MeilisearchHelper;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

#[Route('/logs/user-action-log')]
#[IsGranted('ROLE_SUPER_ADMIN')]
class UserActionLogController extends AbstractCPController
{
    public function __construct(
        private readonly CustomPaginator $customPaginator,
        private readonly MeilisearchHelper $meiliSearchHelper,
    ) {
    }

    #[Route('/', name: 'cp_logs_user_action_log')]
    public function index(Request $request): Response
    {
        $limit = $request->query->getInt('limit', 10);
        $currentPage = $request->query->getInt('page', 1);
        $filterArr = [];
        $sortArr = ['time' => 'desc'];
        $query = '';

        $filterForm = $this->createForm(UserActionLogFilterType::class);
        $filterForm->handleRequest($request);

        if ($filterForm->isSubmitted() && $filterForm->isValid()) {
            if (!empty($filterForm->get('user')->getData())) {
                $filterArr[] = 'admin = ' . $filterForm->get('user')->getData()->getId();
            }

            if (!empty($filterForm->get('time')->getData())) {
                /** @var DateRange $dateRange*/
                $dateRange = $filterForm->get('time')->getData();
                $startTime = $dateRange->getStartDate()->getTimestamp();
                $endTime = $dateRange->getEndDate()->getTimestamp();
                if ($startTime && $endTime) {
                    $filterArr[] = sprintf('time >= %d AND time <= %d', $startTime, $endTime);
                }
            }
            if (!empty($filterForm->get('keyword')->getData())) {
                $query = $filterForm->get('keyword')->getData();
            }
        }

        $rawSearch = $this->meiliSearchHelper->searchAndHydrate(
            UserActionLog::class,
            $query,
            $limit,
            $sortArr,
            $currentPage,
            $filterArr
        );

        $userActionLogs = $rawSearch['data'];
        $hasNextPage = $rawSearch['hasNextPage'];

        $pages = $this->customPaginator->generatePagination($currentPage, $hasNextPage);

        return $this->render('cp/logs/user_action_log/index.html.twig', [
            'userActionLogs' => $userActionLogs,
            'pages' => $pages,
            'currentPage' => $currentPage,
            'recordsPerPage' => $limit,
            'hasNextPage' => $hasNextPage,
            'filterForm' => $filterForm->createView(),
        ]);
    }
}
