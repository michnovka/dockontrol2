<?php

declare(strict_types=1);

namespace App\Helper;

use App\Entity\ActionInterface;
use App\Entity\CameraInterface;
use App\Entity\DockontrolNode;
use App\Entity\User;
use Doctrine\ORM\EntityManagerInterface;
use Exception;
use RuntimeException;
use SodiumException;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\Uid\Uuid;
use Symfony\Component\Validator\Validator\ValidatorInterface;
use Symfony\Contracts\HttpClient\Exception\TransportExceptionInterface;
use Symfony\Contracts\HttpClient\HttpClientInterface;
use Throwable;

/**
 * @psalm-type DockontrolNodeAPIReturn = array{jsonData?: array<mixed>, rawData: string, httpCode: int<200,499>}
 */
readonly class DockontrolNodeHelper
{
    /** @var int TIMEOUT in seconds */
    private const int TIMEOUT = 10;
    private const string API_ENDPOINT = '/api.php';

    public function __construct(
        private HttpClientInterface $client,
        private EntityManagerInterface $entityManager,
        private WireguardHelper $wireguardHelper,
        private ValidatorInterface $validator,
        private UserActionLogHelper $userActionLogHelper,
    ) {
    }

    /**
     * @param array<mixed> $data
     */
    public function createDockontrolNodeAPIResponse(
        DockontrolNode $dockontrolNode,
        int $timestampFromRequest,
        string $signatureFromRequest,
        array $data,
    ): JsonResponse {

        $apiPrivKey = $dockontrolNode->getApiSecretKey()->toString();

        /** @var string $jsonData*/
        $jsonData = json_encode($data);

        // Create the data string for signature
        $dataString = $timestampFromRequest . $signatureFromRequest . $jsonData;

        // Compute the signature
        $signature = hash_hmac('sha256', $dataString, $apiPrivKey);

        return new JsonResponse($data, 200, [
            'X-API-SIGNATURE' => $signature,
        ]);
    }

    public function verifyDockontrolNodeAPIReply(
        DockontrolNode $dockontrolNode,
        int $timestampFromRequest,
        string $signatureFromRequest,
        string $signatureFromResponse,
        string $jsonData,
    ): bool {
        $apiPrivKey = $dockontrolNode->getApiSecretKey()->toString();

        // Create the data string for signature
        $dataString = $timestampFromRequest . $signatureFromRequest . $jsonData;

        // Compute the signature
        $signature = hash_hmac('sha256', $dataString, $apiPrivKey);

        return $signature === $signatureFromResponse;
    }

    /**
     * @psalm-return DockontrolNodeAPIReturn
     * @throws Exception
     * @throws TransportExceptionInterface
     */
    public function callDockontrolNodeAPICamera(CameraInterface $camera): array
    {
        return $this->callDockontrolNodeAPI($camera->getDockontrolNode(), 'camera', $camera->getDockontrolNodePayload() + ['return_raw' => 1], true);
    }

    /**
     * @psalm-return DockontrolNodeAPIReturn
     * @throws Exception
     * @throws TransportExceptionInterface
     */
    public function callDockontrolNodeAPIAction(ActionInterface $action): array
    {
        /** @var DockontrolNode $dockontrolNode */
        $dockontrolNode = $action->getDockontrolNode();
        return $this->callDockontrolNodeAPI($dockontrolNode, 'action', $action->getActionPayload() ?? []);
    }

    /**
     * @psalm-return DockontrolNodeAPIReturn
     * @throws Exception
     * @throws TransportExceptionInterface
     */
    public function callDockontrolNodeAPIVersion(DockontrolNode $dockontrolNode): array
    {
        return $this->callDockontrolNodeAPI($dockontrolNode, 'version');
    }

    /**
     * @param array<string, int> $params
     */
    public function callDockontrolNodeAPILegacy(
        string $remoteHost,
        string $apiSecret,
        string $action,
        array $params = [],
    ): string {
        $params['action'] = $action;
        $params['secret'] = $apiSecret;
        $url = 'https://' . $remoteHost . self::API_ENDPOINT;

        try {
            $response = $this->client->request('GET', $url, [
                'query' => $params,
                'timeout' => self::TIMEOUT,
                'verify_peer' => false,
                'verify_host' => false,
            ]);
            return $response->getContent();
        } catch (Throwable $e) {
            throw new RuntimeException(sprintf('Failed to fetch API data: %s', $e->getMessage()), 0, $e);
        }
    }

    /**
     * @throws RuntimeException
     */
    public function populateNewWireguardKeyPair(DockontrolNode $dockontrolNode): void
    {
        try {
            $wireguardKeys = $this->wireguardHelper->generateKeypair();
            $dockontrolNode->setWireguardPublicKey($wireguardKeys['publicKey']);
            $dockontrolNode->setWireguardPrivateKey($wireguardKeys['privateKey']);
        } catch (SodiumException $exception) {
            throw new RuntimeException('Failed to create new wireguard key pair. ' . $exception->getMessage());
        } catch (Throwable $exception) {
            throw new RuntimeException('Something went wrong when generating new wireguard key pair. ' . $exception->getMessage());
        }
    }

    /**
     * @throws RuntimeException
     */
    public function saveDockontrolNode(DockontrolNode $dockontrolNode, bool $validate = true): void
    {
        if ($validate) {
            $validation = $this->validator->validate($dockontrolNode);

            if (count($validation) > 0) {
                $errorMsg = '';
                foreach ($validation as $item) {
                    $errorMsg .= (string) $item->getMessage();
                }
                throw new RuntimeException($errorMsg);
            }
        }

        $this->entityManager->persist($dockontrolNode);
        $this->entityManager->flush();
    }

    public function removeDockontrolNode(DockontrolNode $dockontrolNode): void
    {
        $this->entityManager->remove($dockontrolNode);
        $this->entityManager->flush();
    }

    public function regenerateAPIKeys(DockontrolNode $dockontrolNode): void
    {
        $dockontrolNode->setApiPublicKey(Uuid::v4());
        $dockontrolNode->setApiSecretKey(Uuid::v4());
        $this->saveDockontrolNode($dockontrolNode);
    }

    /**
     * @param User[] $usersToNotifyWhenStatusChanges
     * @param User[] $updatedUsers
     */
    public function updateUsersToNotifyWhenStatusChange(
        DockontrolNode $dockontrolNode,
        array $usersToNotifyWhenStatusChanges,
        array $updatedUsers,
        User $adminForActionLog,
    ): void {
        $userDiff = function (User $user1, User $user2) {
            return $user1 <=> $user2;
        };

        $addedUsers = array_udiff($updatedUsers, $usersToNotifyWhenStatusChanges, $userDiff);
        $removedUsers = array_udiff($usersToNotifyWhenStatusChanges, $updatedUsers, $userDiff);
        $addedUserNames = [];
        $removedUserNames = [];

        if (!empty($addedUsers)) {
            foreach ($addedUsers as $user) {
                $dockontrolNode->addUserToNotifyWhenStatusChanges($user);
                $addedUserNames[] = $user->getName();
            }
        }

        if (!empty($removedUsers)) {
            foreach ($removedUsers as $user) {
                $dockontrolNode->removeUserToNotifyWhenStatusChange($user);
                $removedUserNames[] = $user->getName();
            }
        }

        $description = 'Updated DOCKontrol Node: ' . $dockontrolNode->getName();

        if (!empty($addedUserNames)) {
            $description .= ', added users: ' . implode(',', $addedUserNames);
        }

        if (!empty($removedUserNames)) {
            $description .= ', removed users: ' . implode(',', $removedUserNames);
        }

        $this->saveDockontrolNode($dockontrolNode);
        $this->userActionLogHelper->addUserActionLog($description, $adminForActionLog);
    }

    /**
     * @param array<string,mixed> $data
     * @psalm-return DockontrolNodeAPIReturn
     * @throws Exception
     * @throws TransportExceptionInterface
     */
    private function callDockontrolNodeAPI(
        DockontrolNode $dockontrolNode,
        string $action,
        array $data = [],
        bool $rawResponseOnly = false,
    ): array {
        $timestamp = time();
        $apiPrivKey = $dockontrolNode->getApiSecretKey()->toString();
        $apiPubKey = $dockontrolNode->getApiPublicKey()->toString();
        $data['action'] = $action;

        $url = 'http://' . $dockontrolNode->getIp() . '/api2.php';

        // Parse the URL to get the path
        $parsedUrl = parse_url($url);
        if (!isset($parsedUrl['path'])) {
            throw new Exception('Invalid URL: Path is missing.');
        }

        $path = $parsedUrl['path'];

        $body = http_build_query($data);

        // Create the data string for signature
        $dataString = $timestamp . 'POST' . $path . $body;

        // Compute the signature
        $signature = hash_hmac('sha256', $dataString, $apiPrivKey);

        // Set headers
        $headers = [
            'X-API-KEY'       => $apiPubKey,
            'X-API-SIGNATURE' => $signature,
            'X-API-TIMESTAMP' => $timestamp,
        ];

        // Set Content-Type header for POST requests
        $headers['Content-Type'] = 'application/x-www-form-urlencoded';

        // Prepare options for the request
        $options = [
            'headers' => $headers,
            'timeout' => self::TIMEOUT, // Timeout in seconds
            'verify_peer' => false, // Disable SSL peer verification
            'verify_host' => false, // Disable SSL host verification
        ];

        $options['body'] = $body;

        // Perform the request
        $response = $this->client->request('POST', $url, $options);

        // Get HTTP status code
        $httpCode = $response->getStatusCode();

        // Get response content without throwing exception
        $content = $response->getContent(false);

        // Check for HTTP errors
        if ($httpCode < 200 || $httpCode >= 500) {
            throw new Exception('HTTP error code: ' . $httpCode . ', response: ' . $content);
        }

        // Return the decoded JSON data
        $responseData = [
            'httpCode' => $httpCode,
            'rawData' => $content,
        ];


        if (!$rawResponseOnly) {
            // Decode JSON response
            $jsonData = json_decode($content, true);

            // Check for JSON decoding errors
            if (json_last_error() !== JSON_ERROR_NONE) {
                throw new Exception('JSON decode error: ' . json_last_error_msg());
            }

            $responseData['jsonData'] = $jsonData;
        }

        return $responseData;
    }
}
