<?php

declare(strict_types=1);

namespace App\DataFixtures;

use App\Entity\APIKey;
use App\Entity\Building;
use App\Entity\DockontrolNode;
use App\Entity\Enum\DockontrolNodeStatus;
use App\Entity\Enum\UserRole;
use App\Entity\User;
use App\Helper\DockontrolNodeHelper;
use Doctrine\Bundle\FixturesBundle\Fixture;
use Doctrine\Bundle\FixturesBundle\FixtureGroupInterface;
use Doctrine\Persistence\ObjectManager;
use Override;
use RuntimeException;
use Symfony\Component\DependencyInjection\ParameterBag\ParameterBagInterface;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;
use Symfony\Component\Uid\Uuid;

class TestFixtures extends Fixture implements FixtureGroupInterface
{
    public function __construct(
        private readonly UserPasswordHasherInterface $passwordHasher,
        private readonly ParameterBagInterface $parameterBag,
        private readonly DockontrolNodeHelper $dockControlNodeHelper,
    ) {
    }

    #[Override]
    public function load(ObjectManager $manager): void
    {
        $user = $this->createUser();
        $manager->persist($user);
        $user->setCreatedBy($user);

        $apiKey = $this->generateAPIKeyForUser($user);
        $manager->persist($apiKey);

        $building = $this->generateBuilding();
        $manager->persist($building);

        $dockontrolNode = $this->generateDockontrolNode();
        $dockontrolNode->setBuilding($building);
        $manager->persist($dockontrolNode);

        $manager->flush();
    }

    /**
     * @return array<string>
     */
    #[Override]
    public static function getGroups(): array
    {
        return ['test'];
    }

    private function createUser(): User
    {
        $user = new User();

        /** @var string $email*/
        $email = $this->parameterBag->get('email_for_legacy_api_test');

        if (empty($email)) {
            throw new RuntimeException('The username for legacy API test should not be empty.');
        }

        /** @var string $plainPassword*/
        $plainPassword = $this->parameterBag->get('password_for_legacy_api_test');

        $user->setName('Dock Admin');
        $user->setPhone('123456789');
        $user->setPhoneCountryPrefix(420);
        $user->setEmail($email);
        $password = $this->passwordHasher->hashPassword($user, $plainPassword);
        $user->setPassword($password);
        $user->setRole(UserRole::ADMIN);
        $user->setEnabled(true);

        return $user;
    }

    private function generateAPIKeyForUser(User $user): APIKey
    {
        $apiKey = new APIKey();

        /** @var string $apiPublicKey*/
        $apiPublicKey = $this->parameterBag->get('public_key_for_test');

        /** @var string $apiPrivateKey*/
        $apiPrivateKey = $this->parameterBag->get('private_key_for_test');

        $publicKey = Uuid::fromString($apiPublicKey);
        $privateKey = Uuid::fromString($apiPrivateKey);

        $apiKey->setName('Test Key');
        $apiKey->setPublicKey($publicKey);
        $apiKey->setPrivateKey($privateKey);
        $apiKey->setUser($user);
        return $apiKey;
    }

    private function generateDockontrolNode(): DockontrolNode
    {
        $dockontrolNode = new DockontrolNode();

        /** @var string $apiPublicKey*/
        $apiPublicKey = $this->parameterBag->get('dockontrol_node_public_key_for_test');

        /** @var string $apiPrivateKey*/
        $apiPrivateKey = $this->parameterBag->get('dockontrol_node_private_key_for_test');

        $publicKey = Uuid::fromString($apiPublicKey);
        $privateKey = Uuid::fromString($apiPrivateKey);

        $this->dockControlNodeHelper->populateNewWireguardKeyPair($dockontrolNode);

        $dockontrolNode->setName('Test Node')
            ->setIp('127.0.0.1')
            ->setStatus(DockontrolNodeStatus::OFFLINE)
            ->setDockontrolNodeVersion('v1')
            ->setComment('Created From Fixture')
            ->setDevice('Test device')
            ->setApiPublicKey($publicKey)
            ->setApiSecretKey($privateKey);

        return $dockontrolNode;
    }

    private function generateBuilding(): Building
    {
        $building = new Building();
        $building->setName('Test Building');

        return $building;
    }
}
