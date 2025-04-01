<?php

declare(strict_types=1);

namespace App\EventListener;

use App\Doctrine\ORM\Attribute\SyncWithLandlord;
use App\Entity\Enum\UserRole;
use App\Entity\User;
use Doctrine\Bundle\DoctrineBundle\Attribute\AsEntityListener;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\EntityManagerInterface;
use ReflectionClass;
use ReflectionProperty;
use Symfony\Component\PropertyAccess\PropertyAccessorInterface;

#[AsEntityListener(event: 'postUpdate', method: 'syncWithLandlordWhenUpdated', entity: User::class)]
#[AsEntityListener(event: 'postPersist', method: 'syncWithLandlordWhenTenantCreatedOrUpdated', entity: User::class)]
#[AsEntityListener(event: 'postUpdate', method: 'syncWithLandlordWhenTenantCreatedOrUpdated', entity: User::class)]
readonly class SyncWithLandlordListener
{
    public function __construct(
        private PropertyAccessorInterface $propertyAccessor,
        private EntityManagerInterface $entityManager,
    ) {
    }

    public function syncWithLandlordWhenUpdated(User $user): void
    {
        if ($user->getRole() === UserRole::TENANT) {
            return;
        }

        /** @var User[] $tenants */
        $tenants = $user->getTenants()->getValues();

        $this->syncFieldsWithTenants($user, $tenants);
    }

    public function syncWithLandlordWhenTenantCreatedOrUpdated(User $user): void
    {
        if ($user->getRole() !== UserRole::TENANT) {
            return;
        }

        /** @var User $landlord */
        $landlord = $user->getLandlord();

        $this->syncFieldsWithTenants($landlord, [$user]);
    }

    /**
     * @param User[] $tenants
     */
    private function syncFieldsWithTenants(User $landlord, array $tenants): void
    {
        $syncFields = $this->getSyncableFields();

        foreach ($tenants as $tenant) {
            foreach ($syncFields as $field) {
                $fieldName = $field['name'];
                $updatedValue = $this->propertyAccessor->getValue($landlord, $fieldName);

                if ($fieldName === 'enabled') {
                    if (!$landlord->isEnabled()) {
                        $this->propertyAccessor->setValue($tenant, $fieldName, false);
                    }
                } elseif ($fieldName === 'groups' && $updatedValue instanceof Collection) {
                    $this->syncGroups($tenant, $landlord);
                } else {
                    $this->propertyAccessor->setValue($tenant, $fieldName, $updatedValue);
                }
            }
            $this->entityManager->persist($tenant);
        }

        $this->entityManager->flush();
    }

    private function syncGroups(User $tenant, User $landlord): void
    {
        $tenantGroups = $tenant->getGroups();
        $landlordGroups = $landlord->getGroups();

        foreach ($tenantGroups as $group) {
            if (!$landlordGroups->contains($group)) {
                $tenant->removeGroup($group);
            }
        }

        foreach ($landlordGroups as $group) {
            if (!$tenant->getGroups()->contains($group)) {
                $tenant->addGroup($group);
            }
        }
    }

    /**
     * @return array<int, array{name: string}>
     */
    private function getSyncableFields(): array
    {
        $syncFields = [];
        $refClass = new ReflectionClass(User::class);

        foreach ($refClass->getProperties() as $property) {
            if ($this->hasSyncAttribute($property)) {
                $syncFields[] = [
                    'name' => $property->getName(),
                ];
            }
        }

        return $syncFields;
    }

    private function hasSyncAttribute(ReflectionProperty $property): bool
    {
        return count($property->getAttributes(SyncWithLandlord::class)) > 0;
    }
}
