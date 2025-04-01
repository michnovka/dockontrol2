<?php

declare(strict_types=1);

namespace App\Helper;

use App\Entity\Group;
use App\Entity\Permission;
use App\Entity\User;
use Doctrine\ORM\EntityManagerInterface;

readonly class GroupHelper
{
    public function __construct(
        private EntityManagerInterface $entityManager,
        private UserActionLogHelper $userActionLogHelper,
    ) {
    }

    public function saveGroup(Group $group): void
    {
        $this->entityManager->persist($group);
        $this->entityManager->flush();
    }

    public function deleteGroup(Group $group): void
    {
        $this->entityManager->remove($group);
        $this->entityManager->flush();
    }

    /**
     * @param Group[] $groups
     */
    public function updateGroupsForUserOrPermission(
        User|Permission $userOrPermission,
        array $groups,
        User $admin,
    ): void {
        $groupDiff = function (Group $group1, Group $group2) {
            return $group1 <=> $group2;
        };

        $assignedGroups = $userOrPermission->getGroups()->getValues();

        $addedGroups = array_udiff($groups, $assignedGroups, $groupDiff);
        $removedGroups = array_udiff($assignedGroups, $groups, $groupDiff);
        $addedGroupNames = [];
        $removedGroupsName = [];

        if (!empty($addedGroups)) {
            /** @var Group $group */
            foreach ($addedGroups as $group) {
                $userOrPermission->addGroup($group);
                $addedGroupNames[] = $group->getName();
            }
        }

        if (!empty($removedGroups)) {
            /** @var Group $group */
            foreach ($removedGroups as $group) {
                $userOrPermission->removeGroup($group);
                $removedGroupsName[] = $group->getName();
            }
        }

        if ($userOrPermission instanceof User) {
            $description = 'Updated user: ' . $userOrPermission->getEmail();
        } else {
            $description = 'Updated permission: ' . $userOrPermission->getName();
        }

        if (!empty($addedGroups)) {
            $description .= ', added groups: ' . implode(',', $addedGroupNames);
        }

        if (!empty($removedGroups)) {
            $description .= ', removed groups: ' . implode(',', $removedGroupsName);
        }

        $this->userActionLogHelper->addUserActionLog($description, $admin);
    }
}
