<?php

declare(strict_types=1);

namespace App\Controller\CP\Settings;

use App\Controller\CP\AbstractCPController;
use App\Controller\CP\SearchAPIInterface;
use App\Entity\Apartment;
use App\Entity\User;
use App\Form\ApartmentType;
use App\Form\Filter\ApartmentFilterType;
use App\Helper\ApartmentHelper;
use App\Helper\UserActionLogHelper;
use App\Repository\ApartmentRepository;
use App\Security\Voter\ApartmentVoter;
use Knp\Component\Pager\PaginatorInterface;
use Override;
use RuntimeException;
use Symfony\Bridge\Doctrine\Attribute\MapEntity;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;
use Throwable;

/** @psalm-import-type ApartmentFilterArray from ApartmentRepository*/

#[Route('/setting/apartments')]
class ApartmentController extends AbstractCPController implements SearchAPIInterface
{
    public function __construct(
        private readonly ApartmentRepository $apartmentRepository,
        private readonly ApartmentHelper $apartmentHelper,
        private readonly UserActionLogHelper $userActionLogHelper,
    ) {
    }

    #[Route('/', name: 'cp_settings_apartment')]
    #[IsGranted('ROLE_SUPER_ADMIN')]
    public function index(Request $request, PaginatorInterface $paginator): Response
    {
        $numberOfRecords = $request->query->getInt('numberOfRecords', 25);
        $apartmentFilterType = $this->createForm(ApartmentFilterType::class);
        $apartmentFilterType->handleRequest($request);
        $filter = [];

        if ($apartmentFilterType->isSubmitted()) {
            $filter['name'] = $apartmentFilterType->get('name')->getData();
            $filter['building'] = $apartmentFilterType->get('building')->getData();
        }

        /** @psalm-var ApartmentFilterArray $filter*/
        $queryBuilder = $this->apartmentRepository->getQueryBuilderWithUserCount($filter);
        $apartments = $paginator->paginate($queryBuilder, $request->query->getInt('page', 1), $numberOfRecords, [
            'defaultSortFieldName' => 'a.id',
            'defaultSortDirection' => 'desc',
        ]);

        return $this->render('cp/settings/apartment/index.html.twig', [
            'apartments' => $apartments,
            'numberOfRecords' => $numberOfRecords,
            'apartmentFilterForm' => $apartmentFilterType->createView(),
        ]);
    }

    #[Route('/new', name: 'cp_settings_apartment_new')]
    #[IsGranted('ROLE_SUPER_ADMIN')]
    public function new(Request $request): Response
    {
        /** @var User $user*/
        $user = $this->getUser();
        $apartment = new Apartment();
        $apartmentForm = $this->createForm(ApartmentType::class, $apartment);
        $apartmentForm->handleRequest($request);

        if ($apartmentForm->isSubmitted() && $apartmentForm->isValid()) {
            try {
                if (!$this->isGranted(ApartmentVoter::CREATE, $apartment)) {
                    throw new RuntimeException('You don\'t have permission to create apartment.');
                }

                $this->apartmentHelper->saveApartment($apartment);
                $this->userActionLogHelper->addUserActionLog('Added apartment ' . $apartment->getName() . ' for building ' . $apartment->getBuilding()->getName(), $user);
                $this->addFlash('success', 'Apartment created successfully.');
            } catch (Throwable $throwable) {
                $this->addFlash('danger', 'Failed to add apartment, ' . $throwable->getMessage());
            }

            return $this->redirectToRoute('cp_settings_apartment');
        }
        return $this->render('cp/settings/apartment/new.html.twig', [
            'form' => $apartmentForm->createView(),
        ]);
    }

    #[Route('/{id}/edit', name: 'cp_settings_apartment_edit')]
    #[IsGranted(ApartmentVoter::EDIT, 'apartment')]
    public function edit(Request $request, #[MapEntity(id: 'id')] Apartment $apartment): Response
    {
        /** @var User $user*/
        $user = $this->getUser();
        $apartmentForm = $this->createForm(ApartmentType::class, $apartment);
        $apartmentForm->handleRequest($request);

        if ($apartmentForm->isSubmitted() && $apartmentForm->isValid()) {
            try {
                if (!$this->isGranted(ApartmentVoter::EDIT, $apartment)) {
                    throw new RuntimeException('You don\'t have permission to edit apartment.');
                }
                $this->apartmentHelper->saveApartment($apartment);
                $this->userActionLogHelper->addUserActionLog('Updated apartment ' . $apartment->getName() . ' for building ' . $apartment->getBuilding()->getName(), $user);
                $this->addFlash('success', 'Apartment updated successfully.');
            } catch (Throwable $throwable) {
                $this->addFlash('danger', 'Failed to update apartment, ' . $throwable->getMessage());
            }

            return $this->redirectToRoute('cp_settings_apartment');
        }
        return $this->render('cp/settings/apartment/edit.html.twig', [
            'form' => $apartmentForm->createView(),
            'apartment' => $apartment,
        ]);
    }

    #[Route('/{id}/delete', name: 'cp_settings_apartment_delete')]
    #[IsGranted(ApartmentVoter::DELETE, 'apartment')]
    public function delete(Request $request, #[MapEntity(id: 'id')] Apartment $apartment): JsonResponse
    {
        /** @var User $user*/
        $user = $this->getUser();

        $csrfToken = (string) $request->getPayload()->get('_csrf');
        $status = false;
        $errorMessage = null;

        if (!$this->isCsrfTokenValid('apartmentcsrf', $csrfToken)) {
            $errorMessage = 'Invalid CSRF token.';
        } else {
            try {
                $deleteLogDesc = 'Deleted apartment #' . $apartment->getId() . ' (' . $apartment->getName() . ')';
                $this->apartmentHelper->removeApartment($apartment);
                $this->userActionLogHelper->addUserActionLog($deleteLogDesc, $user);
                $status = true;
                $this->addFlash('danger', 'Apartment deleted successfully.');
            } catch (Throwable $throwable) {
                $errorMessage = 'Failed to delete apartment ' . $throwable->getMessage();
            }
        }

        return $this->json(['status' => $status, 'errorMessage' => $errorMessage]);
    }

    #[Route('/search-api', name: 'cp_settings_apartment_api_search')]
    #[IsGranted('ROLE_ADMIN')]
    #[Override]
    public function searchAPI(Request $request): JsonResponse
    {
        /** @var User $user */
        $user = $this->getUser();
        $searchText = (string) $request->query->get('searchText');
        $apartments = $this->apartmentRepository->searchApartment($searchText, $user);


        $apartmentArr = [];
        if (!empty($apartments)) {
            foreach ($apartments as $apartment) {
                $apartmentData = [];
                $apartmentData['id'] = $apartment->getId();
                $apartmentData['title'] = $apartment->getName();
                $apartmentData['text'] = $apartment->getTwigDisplayValue();

                $apartmentArr[] = $apartmentData;
            }
        }

        return $this->json([
            'items' => $apartmentArr,
            'totalCount' => count($apartmentArr),
        ], Response::HTTP_OK);
    }
}
