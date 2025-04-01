<?php

declare(strict_types=1);

namespace App\Repository;

use App\Entity\APIKey;
use App\Entity\Enum\UserRole;
use App\Entity\User;
use App\Extension\Type\DateRange;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\ORM\Query;
use Doctrine\Persistence\ManagerRegistry;
use InvalidArgumentException;

/**
 * @extends ServiceEntityRepository<APIKey>
 *
 *  @psalm-type ApiKeyFilterArray = array{
 *         user: ?User,
 *         timeCreated: ?DateRange,
 *         timeLastUsed: ?DateRange,
 *   }
 */
class APIKeyRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, APIKey::class);
    }

    /**
     * @psalm-param ApiKeyFilterArray $filter
     */
    public function getQueryBuilder(User $adminUser, array $filter): Query
    {
        $qb = $this->createQueryBuilder('ak')
        ->innerJoin('ak.user', 'u')
        ->addSelect('u');

        if (!empty($filter['user'])) {
            $qb->andWhere('u = :user')
                ->setParameter('user', $filter['user']);
        }

        if (!empty($filter['timeCreated'])) {
            $qb->andWhere('ak.timeCreated BETWEEN :timeCreatedFrom AND :timeCreatedTo')
                ->setParameter('timeCreatedFrom', $filter['timeCreated']->getStartDate()->format('Y-m-d H:i:s'))
                ->setParameter('timeCreatedTo', $filter['timeCreated']->getEndDate()->format('Y-m-d H:i:s'));
        }

        if (!empty($filter['timeLastUsed'])) {
            $qb->andWhere('ak.timeLastUsed BETWEEN :timeLastUsedFrom AND :timeLastUsedTo')
                ->setParameter('timeLastUsedFrom', $filter['timeLastUsed']->getStartDate()->format('Y-m-d H:i:s'))
                ->setParameter('timeLastUsedTo', $filter['timeLastUsed']->getEndDate()->format('Y-m-d H:i:s'));
        }

        if ($adminUser->getRole() === UserRole::SUPER_ADMIN) {
        } elseif ($adminUser->getRole() === UserRole::ADMIN) {
            $qb
                ->innerJoin('u.apartment', 'a')
                ->andWhere('a.building IN (:adminBuildings)')
                ->setParameter('adminBuildings', [$adminUser->getAdminBuildings()]);
        } else {
            throw new InvalidArgumentException('Invalid user role.');
        }

        return $qb->getQuery();
    }

    public function hasReachedApiKeyLimit(User $adminUser): bool
    {
        $totalAPIKeys = $this->createQueryBuilder('ak')
            ->select('COUNT(ak.publicKey) as total_api_keys')
            ->where('ak.user = :user')
            ->setParameter('user', $adminUser)
            ->setMaxResults(1)
            ->getQuery()
            ->getSingleScalarResult();

        return $totalAPIKeys >= APIKey::MAX_API_KEYS_PER_USER;
    }
}
