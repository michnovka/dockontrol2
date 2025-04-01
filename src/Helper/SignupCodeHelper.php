<?php

declare(strict_types=1);

namespace App\Helper;

use App\Entity\Enum\UserRole;
use App\Entity\Group;
use App\Entity\SignupCode;
use App\Entity\User;
use App\Security\Tools\PasswordTool;
use Carbon\CarbonImmutable;
use Doctrine\DBAL\LockMode;
use Doctrine\ORM\EntityManagerInterface;
use RuntimeException;
use SensitiveParameter;
use Symfony\Component\Validator\Validator\ValidatorInterface;
use Throwable;

readonly class SignupCodeHelper
{
    public function __construct(
        private EntityManagerInterface $entityManager,
        private ValidatorInterface $validator,
        private PasswordTool $passwordTool,
    ) {
    }

    public function saveSignupCode(SignupCode $signupCode): void
    {
        $this->entityManager->persist($signupCode);
        $this->entityManager->flush();
    }

    public function deleteSignupCode(SignupCode $signupCode): void
    {
        $this->entityManager->beginTransaction();
        try {
            $this->entityManager->lock($signupCode, LockMode::PESSIMISTIC_WRITE);
            if ($signupCode->getNewUser() === null) {
                $this->entityManager->remove($signupCode);
                $this->entityManager->flush();
                $this->entityManager->commit();
            } else {
                throw new RuntimeException('Could not delete the signup code because it has already been used.');
            }
        } catch (Throwable $e) {
            $this->entityManager->rollback();
            throw new RuntimeException('Could not delete signup code.', 0, $e);
        }
    }

    public function createUserUsingSignupCode(
        SignupCode $signupCode,
        User $user,
        #[SensitiveParameter] string $password,
        string $phone,
    ): void {
        /** @var string $phone*/
        $phone = preg_replace('/[+\s]/', '', $phone);
        $phone = ltrim($phone, '0');
        $user->setPhone($phone);
        $user->setApartment($signupCode->getApartment());
        $user->setRole(UserRole::LANDLORD);
        $user->setCreatedBy($signupCode->getAdminUser());
        $defaultGroupOfBuilding = $signupCode->getApartment()->getBuilding()->getDefaultGroup();
        $defaultGroupOfApartment = $signupCode->getApartment()->getDefaultGroup();
        if ($defaultGroupOfBuilding instanceof Group) {
            $user->addGroup($defaultGroupOfBuilding);
        }
        if ($defaultGroupOfApartment instanceof Group) {
            $user->addGroup($defaultGroupOfApartment);
        }
        $isValidData = $this->validator->validate($user);
        if ($isValidData->count() > 0) {
            $errorMsg = '';
            foreach ($isValidData as $item) {
                $errorMsg .= (string) $item->getMessage();
            }
            throw new RuntimeException($errorMsg);
        } else {
            $this->entityManager->beginTransaction();
            try {
                $this->entityManager->lock($signupCode, LockMode::PESSIMISTIC_WRITE);
                if ($signupCode->getNewUser() === null) {
                    $user->setPassword($this->passwordTool->hash($password));
                    $user->setPasswordSetTime(CarbonImmutable::now());
                    $signupCode->setNewUser($user);
                    $signupCode->setUsedTime(CarbonImmutable::now());
                    $this->entityManager->persist($user);
                    $this->entityManager->persist($signupCode);
                    $this->entityManager->flush();
                    $this->entityManager->commit();
                }
            } catch (Throwable $e) {
                $this->entityManager->rollback();
                throw new RuntimeException($e->getMessage());
            }
        }
    }
}
