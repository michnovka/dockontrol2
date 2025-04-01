<?php

declare(strict_types=1);

namespace App\Helper;

use App\Entity\Building;
use App\Entity\Permission;
use App\Entity\User;
use Doctrine\ORM\EntityManagerInterface;

readonly class BuildingHelper
{
    public function __construct(
        private EntityManagerInterface $entityManager,
        private UserActionLogHelper $userActionLogHelper,
    ) {
    }

    public function saveBuilding(Building $building): void
    {
        $this->entityManager->persist($building);
        $this->entityManager->flush();
    }

    public function removeBuilding(Building $building): void
    {
        $this->entityManager->remove($building);
        $this->entityManager->flush();
    }

    /**
     * @param Building[] $buildings
     */
    public function updateBuildingsPermission(Permission $permission, array $buildings, User $adminUser): void
    {
        $buildingDiff = function (Building $building1, Building $building2) {
            return $building1 <=> $building2;
        };

        $assignedBuildings = $permission->getBuildings()->getValues();
        $addedBuildings = array_udiff($buildings, $assignedBuildings, $buildingDiff);
        $removedBuildings = array_udiff($assignedBuildings, $buildings, $buildingDiff);
        $addedBuildingsNames = [];
        $removedBuildingsName = [];

        if (!empty($addedBuildings)) {
            /** @var Building $building */
            foreach ($addedBuildings as $building) {
                $permission->addBuilding($building);
                $addedBuildingsNames[] = $building->getName();
            }
        }

        if (!empty($removedBuildings)) {
            /** @var Building $building */
            foreach ($removedBuildings as $building) {
                $permission->removeBuilding($building);
                $removedBuildingsName[] = $building->getName();
            }
        }

        $description = 'Updated Permission: ' . $permission->getName();

        if (!empty($addedBuildingsNames)) {
            $description .= ', added buildings: ' . implode(',', $addedBuildingsNames);
        }

        if (!empty($removedBuildingsName)) {
            $description .= ', removed groups: ' . implode(',', $removedBuildingsName);
        }

        $this->userActionLogHelper->addUserActionLog($description, $adminUser);
    }
}
