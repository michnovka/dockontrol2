<?php

declare(strict_types=1);

namespace App\Controller\CP\Settings;

use App\Controller\CP\AbstractCPController;
use App\Entity\ActionQueueCronGroup;
use App\Entity\User;
use App\Form\ActionQueueCronGroupType;
use App\Helper\ActionHelper;
use App\Helper\CronHelper;
use App\Helper\RedisHelper;
use App\Helper\UserActionLogHelper;
use App\Repository\ActionQueueCronGroupRepository;
use App\Repository\ActionQueueRepository;
use App\Security\Voter\ActionQueueCronGroupVoter;
use InvalidArgumentException;
use Knp\Component\Pager\PaginatorInterface;
use RuntimeException;
use Symfony\Bridge\Doctrine\Attribute\MapEntity;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;
use Throwable;

#[Route('/settings/action-cron-group')]
class ActionQueueCronGroupController extends AbstractCPController
{
    public function __construct(
        private readonly ActionQueueCronGroupRepository $cronGroupRepository,
        private readonly CronHelper $cronHelper,
        private readonly UserActionLogHelper $userActionLogHelper,
        private readonly RedisHelper $redisHelper,
        private readonly ActionQueueRepository $actionQueueRepository,
    ) {
    }

    #[Route('/', name: 'cp_settings_action_cron_group')]
    #[IsGranted('ROLE_SUPER_ADMIN')]
    public function index(Request $request, PaginatorInterface $paginator): Response
    {
        $numberOfRecords = $request->query->getInt('numberOfRecords', 25);
        $redis = $this->redisHelper->getRedisInstance();
        $plannedRedisActionQueued = [];
        $immediateRedisActionQueued = [];

        $queryBuilder = $this->cronGroupRepository->getQueryBuilder();
        $cronGroupsArr = $queryBuilder->getResult();

        foreach ($cronGroupsArr as $cronGroup) {
            $plannedRedisKey = ActionHelper::REDIS_ACTION_QUEUE_KEY_PREFIX . $cronGroup['name'];
            /** @var array $plannedRedisData*/
            $plannedRedisData = $redis->zRange($plannedRedisKey, 0, -1);
            $plannedRedisActionQueued[$cronGroup['name']] = count($plannedRedisData);

            $immediateRedisKey = ActionHelper::REDIS_IMMEDIATE_ACTION_QUEUE_KEY_PREFIX . $cronGroup['name'];
            /** @var int $immediateRedisData*/
            $immediateRedisData = $redis->lLen($immediateRedisKey);
            $immediateRedisActionQueued[$cronGroup['name']] = $immediateRedisData;
        }

        $cronGroups = $paginator->paginate($queryBuilder, $request->query->getInt('page', 1), $numberOfRecords);

        return $this->render('cp/settings/action_queue_cron_group/index.html.twig', [
            'cronGroups' => $cronGroups,
            'numberOfRecords' => $numberOfRecords,
            'plannedRedisActionQueued' => $plannedRedisActionQueued,
            'immediateRedisActionQueued' => $immediateRedisActionQueued,
        ]);
    }

    #[Route('/new', name: 'cp_settings_action_cron_group_new')]
    #[IsGranted('ROLE_SUPER_ADMIN')]
    public function new(Request $request): Response
    {
        /** @var User $adminUser*/
        $adminUser = $this->getUser();
        $cronGroup = new ActionQueueCronGroup();
        $cronGroupFormType = $this->createForm(ActionQueueCronGroupType::class, $cronGroup);
        $cronGroupFormType->handleRequest($request);

        if ($cronGroupFormType->isSubmitted() && $cronGroupFormType->isValid()) {
            try {
                if (!$this->isGranted(ActionQueueCronGroupVoter::CREATE, $cronGroup)) {
                    throw new RuntimeException('You don\'t have permission to create cron group.');
                }
                $this->cronHelper->saveCronGroup($cronGroup);
                $this->userActionLogHelper->addUserActionLog('Created action queue cron group ' . $cronGroup->getName(), $adminUser);
                $this->addFlash('success', 'Action Queue cron group created successfully.');
            } catch (Throwable $exception) {
                $this->addFlash('danger', 'Failed to create action queue cron group, ' . $exception->getMessage());
            }

            return $this->redirectToRoute('cp_settings_action_cron_group');
        }

        return $this->render('cp/settings/action_queue_cron_group/new.html.twig', [
            'form' => $cronGroupFormType->createView(),
        ]);
    }

    #[Route('/{name}/edit', name: 'cp_settings_action_cron_group_edit')]
    #[IsGranted(ActionQueueCronGroupVoter::EDIT, 'actionQueueCronGroup')]
    public function edit(
        Request $request,
        #[MapEntity(id: 'name')]ActionQueueCronGroup $actionQueueCronGroup,
    ): Response {
        /** @var User $adminUser*/
        $adminUser = $this->getUser();
        $actionQueueCronGroupFormType = $this->createForm(ActionQueueCronGroupType::class, $actionQueueCronGroup);
        $actionQueueCronGroupFormType->handleRequest($request);

        if ($actionQueueCronGroupFormType->isSubmitted() && $actionQueueCronGroupFormType->isValid()) {
            try {
                if (!$this->isGranted(ActionQueueCronGroupVoter::EDIT, $actionQueueCronGroup)) {
                    throw new RuntimeException('You don\'t have permission to edit action queue cron group.');
                }
                $this->cronHelper->saveCronGroup($actionQueueCronGroup);
                $this->userActionLogHelper->addUserActionLog('Updated action queue cron group ' . $actionQueueCronGroup->getName(), $adminUser);
                $this->addFlash('success', 'Action queue cron group updated successfully.');
            } catch (Throwable $exception) {
                $this->addFlash('danger', 'Failed to update action queue cron group, ' . $exception->getMessage());
            }

            return $this->redirectToRoute('cp_settings_action_cron_group');
        }

        return $this->render('cp/settings/action_queue_cron_group/edit.html.twig', [
            'form' => $actionQueueCronGroupFormType->createView(),
            'cronGroup' => $actionQueueCronGroup,
        ]);
    }

    #[Route('/{name}/delete', name: 'cp_settings_action_cron_group_delete')]
    #[IsGranted(ActionQueueCronGroupVoter::DELETE, 'actionQueueCronGroup')]
    public function delete(
        Request $request,
        #[MapEntity(id: 'name')]ActionQueueCronGroup $actionQueueCronGroup,
    ): JsonResponse {
        /** @var User $adminUser*/
        $adminUser = $this->getUser();
        $csrfToken = (string) $request->getPayload()->get('_csrf');
        $errorMessage = null;
        $status = false;
        if (!$this->isCsrfTokenValid('crongroupcsrf', $csrfToken)) {
            $errorMessage = 'Invalid CSRF token.';
        } else {
            try {
                if ($this->cronHelper->checkCronGroupIsAssigned($actionQueueCronGroup)) {
                    $errorMessage = 'Can not delete cron group because it is already assigned.';
                } else {
                    $this->userActionLogHelper->addUserActionLog('Deleted cron group ' . $actionQueueCronGroup->getName(), $adminUser, false);
                    $this->cronHelper->remove($actionQueueCronGroup);
                    $status = true;
                    $this->addFlash('danger', 'Cron group deleted successfully.');
                }
            } catch (Throwable $throwable) {
                $errorMessage = 'Failed to delete cron group ' . $throwable->getMessage();
            }
        }

        return $this->json(['status' => $status, 'errorMessage' => $errorMessage]);
    }

    #[Route('/{name}/clear-queue', name: 'cp_settings_action_cron_group_clear_queue')]
    #[IsGranted(ActionQueueCronGroupVoter::DELETE, 'actionQueueCronGroup')]
    public function clearQueue(
        Request $request,
        #[MapEntity(id: 'name')]ActionQueueCronGroup $actionQueueCronGroup,
    ): JsonResponse {
        /** @var User $adminUser*/
        $adminUser = $this->getUser();
        $csrfToken = (string) $request->getPayload()->get('_csrf');
        $errorMessage = null;
        $status = false;
        $queueType = $request->request->get('queue_type');

        if (!$this->isCsrfTokenValid('crongroupcsrf', $csrfToken)) {
            $errorMessage = 'Invalid CSRF token.';
        } else {
            try {
                if ($queueType === 'database') {
                    $this->actionQueueRepository->clearPendingQueueForCronGroup($actionQueueCronGroup);
                    $this->userActionLogHelper->addUserActionLog('Cleared DB queue for ' . $actionQueueCronGroup->getName(), $adminUser);
                    $status = true;
                } elseif ($queueType === 'redis') {
                    $redis = $this->redisHelper->getRedisInstance();
                    $redisKey = ActionHelper::REDIS_ACTION_QUEUE_KEY_PREFIX . $actionQueueCronGroup->getName();
                    $redis->del($redisKey);
                    $immediateRedisKey = ActionHelper::REDIS_IMMEDIATE_ACTION_QUEUE_KEY_PREFIX . $actionQueueCronGroup->getName();
                    $redis->del($immediateRedisKey);
                    $this->userActionLogHelper->addUserActionLog('Cleared Redis queue for ' . $actionQueueCronGroup->getName(), $adminUser);
                    $status = true;
                } else {
                    throw new InvalidArgumentException('Invalid queue type.');
                }
                $this->addFlash('danger', 'Cron group queue cleared successfully.');
            } catch (Throwable $throwable) {
                $errorMessage = 'Failed to clear cron group queue ' . $throwable->getMessage();
            }
        }

        return $this->json([
            'status' => $status,
            'errorMessage' => $errorMessage,
        ]);
    }
}
