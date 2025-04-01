<?php

declare(strict_types=1);

namespace App\Repository;

use App\Entity\Apartment;
use App\Entity\Building;
use App\Entity\Enum\UserRole;
use App\Entity\Group;
use App\Entity\User;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\ORM\Query;
use Doctrine\Persistence\ManagerRegistry;
use InvalidArgumentException;
use Symfony\Component\Security\Core\User\PasswordAuthenticatedUserInterface;
use Symfony\Component\Security\Core\User\PasswordUpgraderInterface;

/**
 * @extends ServiceEntityRepository<User>
 *
 * @psalm-type UserFilterArray = array{
 *        name: ?string,
 *        email: ?string,
 *        phone: ?string,
 *        apartment: ?Apartment,
 *        group: ?Group,
 *        role: ?UserRole,
 *        landlord: ?User
 *  }
 */
class UserRepository extends ServiceEntityRepository implements PasswordUpgraderInterface
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, User::class);
    }

    /**
     * @psalm-param UserFilterArray $filter
     */
    public function getQueryBuilder(User $adminUser, bool $getOnlyAdminUsers = false, $filter = []): Query
    {
        $qb = $this->createQueryBuilder('u')
        ->leftJoin('u.groups', 'g')
        ->leftJoin('u.apartment', 'a')
        ->leftJoin('a.building', 'b')
        ->leftJoin('u.adminBuildings', 'ab')
        ->leftJoin('u.landlord', 'l')
        ->addSelect('g, a, b, ab, l');

        if ($getOnlyAdminUsers) {
            $qb
                ->andWhere('u.role = :role')
                ->setParameter('role', UserRole::ADMIN);
        }

        if (!empty($filter['name'])) {
            $qb->andWhere('u.name LIKE :name')
                ->setParameter('name', '%' . $filter['name'] . '%');
        }

        if (!empty($filter['email'])) {
            $qb->andWhere('u.email LIKE :email')
                ->setParameter('email', '%' . $filter['email'] . '%');
        }

        if (!empty($filter['phone'])) {
            $qb->andWhere('u.phone LIKE :phone')
                ->setParameter('phone', '%' . $filter['phone'] . '%');
        }

        if (!empty($filter['apartment'])) {
            $qb->andWhere('u.apartment = :apartment')
                ->setParameter('apartment', $filter['apartment']);
        }

        if (!empty($filter['group'])) {
            $qb->andWhere('g = :group')
                ->setParameter('group', $filter['group']);
        }

        if (!empty($filter['role'])) {
            $qb->andWhere('u.role = :role')
                ->setParameter('role', $filter['role']);
        }

        if (!empty($filter['landlord'])) {
            $qb->andWhere('u.landlord = :landlord')
                ->setParameter('landlord', $filter['landlord']);
        }

        if ($adminUser->getRole() === UserRole::SUPER_ADMIN) {
            // allow all users
        } elseif ($adminUser->getRole() === UserRole::ADMIN) {
            $qb
                ->andWhere('b in (:adminBuildings)')
                ->setParameter('adminBuildings', $adminUser->getAdminBuildings());
        } else {
            throw new InvalidArgumentException('Invalid user role.');
        }

        return $qb->getQuery();
    }

    /**
     * @return User[]|null
     */
    public function searchUser(string $searchText, User $adminUser, int $maxResults = 10): ?array
    {
        $qb = $this->createQueryBuilder('u')
            ->andWhere('u.enabled = :enabled')
            ->setParameter('enabled', true);

        if ($adminUser->getRole() === UserRole::SUPER_ADMIN) {
            $qb->leftJoin('u.apartment', 'a');
        } elseif ($adminUser->getRole() === UserRole::ADMIN) {
            $qb->innerJoin('u.apartment', 'a')
                ->andWhere('a.building in (:adminBuildings)')
                ->setParameter('adminBuildings', $adminUser->getAdminBuildings());
        } else {
            throw new InvalidArgumentException('Invalid user role.');
        }

        if (is_numeric($searchText)) {
            $qb->where('u.id = :id');
            $qb->setParameter('id', $searchText);
        } else {
            $qb->andWhere('u.email LIKE :search')
                ->orWhere('u.name LIKE :search')
                ->orWhere('a.name LIKE :search')
                ->setParameter('search', '%' . $searchText . '%');
        }

        if ($maxResults > 0) {
            $qb->setMaxResults($maxResults);
        }

        return $qb->getQuery()->getResult();
    }

    /**
     * @return array<User>
     */
    public function getLastFiveUsersForAdmin(User $admin): array
    {
        $qb = $this->createQueryBuilder('u')
            ->leftJoin('u.apartment', 'a')
            ->addSelect('a');
        if ($admin->getRole() === UserRole::SUPER_ADMIN) {
            // allow all users
        } elseif ($admin->getRole() === UserRole::ADMIN) {
            $qb
                ->andWhere('a.building in (:adminBuildings)')
                ->setParameter('adminBuildings', $admin->getAdminBuildings());
        } else {
            throw new InvalidArgumentException('Invalid user role.');
        }
        return $qb
            ->orderBy('u.createdTime', 'DESC')
            ->setMaxResults(5)
            ->getQuery()
            ->getResult();
    }

    public function doesAdminManageBuilding(User $user, Building $building): bool
    {
        if ($user->getRole() === UserRole::SUPER_ADMIN) {
            return true;
        } elseif ($user->getRole() === UserRole::ADMIN) {
            $qb = $this->createQueryBuilder('u')
                ->select('1')
                ->innerJoin('u.adminBuildings', 'b')
                ->where('u.id = :userId')
                ->andWhere('b.id = :buildingId')
                ->setParameter('userId', $user->getId())
                ->setParameter('buildingId', $building->getId())
                ->setMaxResults(1);
            return (bool) $qb->getQuery()->getOneOrNullResult();
        } else {
            throw new InvalidArgumentException('Invalid user role.');
        }
    }

    public function upgradePassword(PasswordAuthenticatedUserInterface $user, string $newHashedPassword): void
    {
        $user->setPassword($newHashedPassword);

        $this->getEntityManager()->persist($user);
        $this->getEntityManager()->flush();
    }

    /**
     * @return User[]
     */
    public function getInactiveUsersToDisable(int $intervalDays): array
    {
        return $this->createQueryBuilder('u')
            ->where('u.enabled = :enabled')
            ->andWhere('u.role NOT IN (:userRole)')
            ->andWhere('u.disableAutomaticallyDueToInactivity = :disableAutomaticallyDueToInactivity')
            ->andWhere('u.lastLoginTime <= DATE_SUB(CURRENT_TIMESTAMP(), :interval, \'DAY\')')
            ->andWhere('u.timeLastAction <= DATE_SUB(CURRENT_TIMESTAMP(), :interval, \'DAY\')')
            ->setParameter('enabled', true)
            ->setParameter('userRole', [UserRole::SUPER_ADMIN, UserRole::TENANT])
            ->setParameter('disableAutomaticallyDueToInactivity', true)
            ->setParameter('interval', $intervalDays)
            ->getQuery()
            ->getResult();
    }

    public function resetTimeTosAcceptedForAllUser(): void
    {
        $qb = $this->createQueryBuilder('u')
            ->update()
            ->set('u.timeTosAccepted', 'NULL')
            ->getQuery();

        $qb->execute();
    }
}
