<?php

declare(strict_types=1);

namespace App\Repository;

use App\Entity\Apartment;
use App\Entity\Building;
use App\Entity\Enum\UserRole;
use App\Entity\SignupCode;
use App\Entity\User;
use App\Extension\Type\DateTimeRange;
use Carbon\CarbonImmutable;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\ORM\Query;
use Doctrine\Persistence\ManagerRegistry;
use InvalidArgumentException;
use Symfony\Bridge\Doctrine\Types\UlidType;

/**
 * @extends ServiceEntityRepository<SignupCode>
 *
 * @psalm-type signupCodeFilterArray = array{
 *     hash: ?string,
 *     admin: ?User,
 *     building: ?Building,
 *     apartment: ?Apartment,
 *     status: ?array<string>,
 *     timeCreated: ?DateTimeRange,
 *     timeExpires: ?DateTimeRange,
 *     timeUsed: ?DateTimeRange
 * }
 */
class SignupCodeRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, SignupCode::class);
    }

    public function cleanupSignupCodes(): void
    {
        $this->createQueryBuilder('sc')
            ->delete(SignupCode::class, 'sc')
            ->where('sc.expires < NOW()')
            ->andWhere('sc.usedTime IS NULL')
            ->getQuery()
            ->execute()
        ;
    }

    /**
     * @psalm-param signupCodeFilterArray $filter
     */
    public function getQueryBuilderForAdmin(User $admin, array $filter = []): Query
    {
        $qb = $this->createQueryBuilder('sc')
            ->innerJoin('sc.apartment', 'a')
            ->innerJoin('a.building', 'b')
            ->leftJoin('sc.newUser', 'u')
            ->addSelect('a')
            ->addSelect('b')
            ->addSelect('u');

        $currentDateTime = CarbonImmutable::now();

        if (!empty($filter['hash'])) {
            $qb->andWhere('sc.hash = :hash')
                ->setParameter('hash', $filter['hash'], UlidType::NAME);
        }

        if (!empty($filter['status'])) {
            $orX = $qb->expr()->orX();

            foreach ($filter['status'] as $status) {
                switch ($status) {
                    case 'Unused':
                        $orX->add(
                            $qb->expr()->andX(
                                $qb->expr()->isNull('sc.usedTime'),
                                $qb->expr()->isNull('sc.newUser')
                            )
                        );
                        break;

                    case 'Used':
                        $orX->add(
                            $qb->expr()->andX(
                                $qb->expr()->isNotNull('sc.usedTime'),
                                $qb->expr()->isNotNull('sc.newUser')
                            )
                        );
                        break;

                    case 'Expired':
                        $orX->add($qb->expr()->lt('sc.expires', ':now'));
                        $qb->setParameter('now', $currentDateTime);
                        break;

                    default:
                        throw new InvalidArgumentException('Invalid status.');
                }
            }

            $qb->andWhere($orX);
        }

        if (!empty($filter['timeCreated'])) {
            $qb->andWhere('sc.createdTime BETWEEN :start AND :end')
                ->setParameter('start', $filter['timeCreated']->getStartDate()->format('Y-m-d H:i:s'))
                ->setParameter('end', $filter['timeCreated']->getEndDate()->format('Y-m-d H:i:s'));
        }

        if (!empty($filter['timeExpired'])) {
            $qb->andWhere('sc.expires BETWEEN :start AND :end')
                ->setParameter('start', $filter['timeExpired']->getStartDate()->format('Y-m-d H:i:s'))
                ->setParameter('end', $filter['timeExpired']->getEndDate()->format('Y-m-d H:i:s'));
        }

        if (!empty($filter['timeUsed'])) {
            $qb->andWhere('sc.usedTime BETWEEN :start AND :end')
                ->setParameter('start', $filter['timeUsed']->getStartDate()->format('Y-m-d H:i:s'))
                ->setParameter('end', $filter['timeUsed']->getEndDate()->format('Y-m-d H:i:s'));
        }

        if ($admin->getRole() == UserRole::SUPER_ADMIN) {
            if (!empty($filter['admin'])) {
                $qb->andWhere('sc.adminUser = :admin')
                    ->setParameter('admin', $filter['admin']);
            }

            if (!empty($filter['building'])) {
                $qb->andWhere('b = :building')
                    ->setParameter('building', $filter['building']);
            }

            if (!empty($filter['apartment'])) {
                $qb->andWhere('a = :apartment')
                    ->setParameter('apartment', $filter['apartment']);
            }
        } elseif ($admin->getRole() === UserRole::ADMIN) {
            $qb->andWhere('a.building IN (:buildings)')
                ->setParameter('buildings', $admin->getAdminBuildings());
        } else {
            // for non-admins we throw error
            throw new InvalidArgumentException('User provided is not an admin.');
        }

        return $qb->getQuery();
    }
}
