<?php

declare(strict_types=1);

namespace App\Controller\PZ\Settings;

use App\Controller\PZ\AbstractPZController;
use App\Entity\User;
use App\Helper\CustomSortingGroupHelper;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;
use Symfony\Contracts\Translation\TranslatorInterface;
use Throwable;

#[Route('/custom-sorting-group')]
#[IsGranted('ROLE_TENANT')]
class CustomSortingGroupController extends AbstractPZController
{
    public function __construct(
        private readonly CustomSortingGroupHelper $customSortingGroupHelper,
        private readonly TranslatorInterface $translator,
    ) {
    }

    #[Route('/save', name: 'dockontrol_custom_sorting_group_save')]
    public function saveSortingGroup(Request $request): JsonResponse
    {
        $status = false;
        $errorMessage = null;
        $csrfToken = $request->request->getString('_csrf');
        $sortingGroupData = $request->request->getString('sortingGroupData');
        $sortingGroupData = json_decode($sortingGroupData, true);
        /** @var User $user*/
        $user = $this->getUser();

        if (!$this->isCsrfTokenValid('customsortinggroup', $csrfToken)) {
            $errorMessage = $this->translator->trans('dockontrol.global.invalid_csrf_token');
        } else {
            try {
                $status = true;
                $this->customSortingGroupHelper->saveSortingGroup($sortingGroupData, $user);
                $this->addFlash('success', $this->translator->trans('dockontrol.settings.custom_sorting.messages.updated_custom_sorting_group'));
            } catch (Throwable $e) {
                $errorMessage = $this->translator->trans('dockontrol.settings.custom_sorting.messages.sorting_group_updated_failed') . ', ' . $e->getMessage();
                $this->addFlash('danger', $errorMessage);
            }
        }

        return $this->json([
            'status' => $status,
            'errorMessage' => $errorMessage,
        ]);
    }
}
