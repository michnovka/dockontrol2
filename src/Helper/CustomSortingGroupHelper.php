<?php

declare(strict_types=1);

namespace App\Helper;

use App\Entity\Button;
use App\Entity\CustomSorting;
use App\Entity\CustomSortingGroup;
use App\Entity\Enum\ButtonStyle;
use App\Entity\User;
use App\Repository\ButtonRepository;
use App\Repository\CustomSortingGroupRepository;
use Doctrine\DBAL\LockMode;
use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\OptimisticLockException;
use RuntimeException;
use Symfony\Component\Validator\Validator\ValidatorInterface;

readonly class CustomSortingGroupHelper
{
    public function __construct(
        private CustomSortingGroupRepository $customSortingGroupRepository,
        private EntityManagerInterface $entityManager,
        private ButtonRepository $buttonRepository,
        private ValidatorInterface $validator,
    ) {
    }

    /**
     * @param array<int, array{
     *  sort_index: int,
     *  name: string,
     *  column_size: int,
     *  buttons: array<int, array{
     *      sort_index: int,
     *      id: string,
     *      name: string,
     *      value: string,
     *      custom_attributes: array<string, string>,
     *      order: int
     *    }>
     * }> $sortingData
     */
    public function saveSortingGroup(array $sortingData, User $user): void
    {
        $this->entityManager->beginTransaction();
        try {
            $this->entityManager->lock($user, LockMode::PESSIMISTIC_WRITE);
            $this->customSortingGroupRepository->deleteCustomSortingGroupForUser($user);

            foreach ($sortingData as $sortingGroupData) {
                /** @var int $sortIndex*/
                $sortingGroupSortIndex = $sortingGroupData['sort_index'];
                /** @var string $sortingGroupName*/
                $sortingGroupName = $sortingGroupData['name'];
                /** @var int $sortingGroupColumnSize*/
                $sortingGroupColumnSize = $sortingGroupData['column_size'];
                /** @var array $sortingGroupButtons*/
                $sortingGroupButtons = $sortingGroupData['buttons'];
                try {
                    $sortingGroup = new CustomSortingGroup();
                    $sortingGroup->setName($sortingGroupName);
                    $sortingGroup->setUser($user);
                    $sortingGroup->setColumnSize($sortingGroupColumnSize);
                    $sortingGroup->setSortIndex($sortingGroupSortIndex);
                    foreach ($sortingGroupButtons as $sortingGroupButton) {
                        $buttonSortIndex = $sortingGroupButton['sort_index'];
                        $buttonId = $sortingGroupButton['id'];
                        $buttonObj = $this->buttonRepository->find($buttonId);
                        $customAttributes = $sortingGroupButton['custom_attributes'];
                        $customName = array_key_exists('data-custom-name', $customAttributes) ? $customAttributes['data-custom-name'] : null;
                        $customColor = array_key_exists('data-custom-color', $customAttributes) ? $customAttributes['data-custom-color'] : null;
                        $modalButtons = array_key_exists('modal_buttons', $sortingGroupButton) ? $sortingGroupButton['modal_buttons'] : [];
                        $allow1min = array_key_exists('data-custom-allow1min', $customAttributes) ? (bool) (int) $customAttributes['data-custom-allow1min'] : false;
                        $modalButtonsColumnSize =  array_key_exists('modal_buttons_column_size', $sortingGroupButton) ? (int) $sortingGroupButton['modal_buttons_column_size'] : 1;
                        if ($buttonObj instanceof Button) {
                            $customSorting = new CustomSorting();
                            $customSorting->setCustomSortingGroup($sortingGroup);
                            $customSorting->setButton($buttonObj);
                            $customSorting->setCustomName($customName);
                            $customSorting->setSortIndex($buttonSortIndex);
                            $customSorting->setAllow1MinOpen($allow1min);
                            if ($customColor !== null) {
                                $customStyle = ButtonStyle::from($customColor);
                                $customSorting->setCustomButtonStyle($customStyle);
                            }
                            if (!empty($modalButtons)) {
                                $sortingGroupForModalConfig = new CustomSortingGroup();
                                $sortingGroupForModalConfig->setSortIndex(1);
                                $sortingGroupForModalConfig->setColumnSize($modalButtonsColumnSize);
                                $sortingGroupForModalConfig->setUser($user);
                                $sortingGroupForModalConfig->setIsGroupForModal($customSorting);
                                foreach ($modalButtons as $modalButton) {
                                    $buttonId = $modalButton['button'];
                                    $buttonObj = $this->buttonRepository->find($buttonId);
                                    $customButtonName = $modalButton['name'];
                                    $customButtonColor = $modalButton['color'];
                                    $sortIndex = $modalButton['index'];
                                    $allow1min = array_key_exists('allow1min', $modalButton) ? (bool)(int)$modalButton['allow1min'] : false;

                                    if ($buttonObj instanceof Button) {
                                        $customSortingForModalConfig = new CustomSorting();
                                        $customSortingForModalConfig->setCustomSortingGroup($sortingGroupForModalConfig);
                                        if (!empty($customButtonColor)) {
                                            $customSortingForModalConfig->setCustomButtonStyle(ButtonStyle::from($customButtonColor));
                                        }

                                        if (!empty($customButtonName)) {
                                            $customSortingForModalConfig->setCustomName($customButtonName);
                                        }

                                        $customSortingForModalConfig->setButton($buttonObj);
                                        $customSortingForModalConfig->setSortIndex($sortIndex);
                                        $customSortingForModalConfig->setAllow1MinOpen($allow1min);
                                        $this->validateCustomSortingOrCustomSortingGroup($customSortingForModalConfig);
                                        $this->entityManager->persist($customSortingForModalConfig);
                                    }
                                }
                                $this->validateCustomSortingOrCustomSortingGroup($sortingGroupForModalConfig);
                                $this->entityManager->persist($sortingGroupForModalConfig);
                            }
                            $this->validateCustomSortingOrCustomSortingGroup($customSorting);
                            $this->entityManager->persist($customSorting);
                        }
                    }
                    $this->validateCustomSortingOrCustomSortingGroup($sortingGroup);
                    $this->entityManager->persist($sortingGroup);
                } catch (RuntimeException $exception) {
                    throw new RuntimeException($exception->getMessage());
                }
            }
            $this->entityManager->flush();
            $this->entityManager->commit();
        } catch (OptimisticLockException) {
            $this->entityManager->rollback();
        }
    }

    private function validateCustomSortingOrCustomSortingGroup(CustomSorting|CustomSortingGroup $customSorting): void
    {
        $validationErrors = $this->validator->validate($customSorting);
        if (count($validationErrors) > 0) {
            $errorMsg = '';
            foreach ($validationErrors as $error) {
                $errorMsg .= (string) $error->getMessage();
            }
            throw new RuntimeException($errorMsg);
        }
    }
}
