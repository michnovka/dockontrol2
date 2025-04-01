<?php

declare(strict_types=1);

namespace App\Repository;

use App\Entity\User;
use App\Entity\WebauthnRegistration;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<WebauthnRegistration>
 */
class WebauthnRegistrationRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, WebauthnRegistration::class);
    }

    public function cleanupRegistrations(int $intervalDays): void
    {
        $this->createQueryBuilder('wr')
            ->delete(WebauthnRegistration::class, 'wr')
            ->where('wr.lastUsedTime < DATE_SUB(now(), :interval, \'DAY\')')
            ->setParameter('interval', $intervalDays)
            ->getQuery()
            ->execute()
        ;
    }

    /**
     * @return string[]
     */
    public function getCredentialsForUser(User $user): array
    {
        return $this->createQueryBuilder('wr')
            ->select('wr.credentialId')
            ->where('wr.user = :user')
            ->setParameter('user', $user)
            ->getQuery()
            ->getSingleColumnResult();
    }
}
