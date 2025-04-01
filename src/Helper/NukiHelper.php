<?php

declare(strict_types=1);

namespace App\Helper;

use App\Entity\Enum\NukiAction;
use App\Entity\Enum\NukiStatus;
use App\Entity\Log\NukiLog;
use App\Entity\Nuki;
use App\Exception\Nuki\APICallFailed;
use App\Exception\Nuki\LockNotAvailable;
use App\Exception\Nuki\Password1Mismatch;
use App\Exception\Nuki\PINMismatch;
use App\Exception\Nuki\PINRequiredException;
use App\Exception\Nuki\TooManyTries;
use App\Repository\Log\NukiLogRepository;
use Carbon\CarbonImmutable;
use Doctrine\ORM\EntityManagerInterface;
use OTPHP\TOTP;
use SensitiveParameter;
use Symfony\Component\HttpClient\Exception\TimeoutException;
use Symfony\Contracts\HttpClient\Exception\ClientExceptionInterface;
use Symfony\Contracts\HttpClient\Exception\DecodingExceptionInterface;
use Symfony\Contracts\HttpClient\Exception\RedirectionExceptionInterface;
use Symfony\Contracts\HttpClient\Exception\ServerExceptionInterface;
use Symfony\Contracts\HttpClient\Exception\TransportExceptionInterface;
use Symfony\Contracts\HttpClient\HttpClientInterface;

readonly class NukiHelper
{
    public function __construct(
        private EntityManagerInterface $entityManager,
        private HttpClientInterface $client,
        private NukiLogRepository $nukiLogRepository,
    ) {
    }

    public function saveNuki(
        Nuki $nuki,
        #[SensitiveParameter] ?string $password = null,
        #[SensitiveParameter] ?string $pin = null,
    ): void {
        if (!is_null($password)) {
            $nuki->setPassword1($password);
        }

        if (!is_null($pin)) {
            $nuki->setPin($pin);
        }

        $this->entityManager->persist($nuki);
        $this->entityManager->flush();
    }

    public function deleteNuki(Nuki $nuki): void
    {
        $this->entityManager->remove($nuki);
        $this->entityManager->flush();
    }

    public function removeNukiPin(Nuki $nuki): void
    {
        $nuki->setPin(null);

        $this->entityManager->persist($nuki);
        $this->entityManager->flush();
    }

    /**
     * @return array{status: ?string, message: ?string}
     * @throws PINMismatch
     * @throws LockNotAvailable
     * @throws PINRequiredException
     * @throws APICallFailed
     * @throws TooManyTries
     */
    public function engage(Nuki $nuki, bool $isLock, string $totp2, string $totpNonce, ?string $pin = null): array
    {
        if ($nuki->getPin() !== null && is_null($pin)) {
            throw new PINRequiredException();
        }

        $nukiAction = $isLock ? NukiAction::LOCK : NukiAction::UNLOCK;
        $incorrectPinTries = $this->nukiLogRepository->getIncorrectStatusTriesCountForNukiForPastOneMinute($nuki, NukiStatus::INCORRECT_PIN);
        if ($incorrectPinTries >= 5) {
            throw new TooManyTries();
        }

        if ($nuki->getPin() !== null && $pin !== $nuki->getPin()) {
            $this->addNukiLog($nuki, NukiStatus::INCORRECT_PIN, $nukiAction);
            throw new PINMismatch();
        }

        // check if lock and can lock
        if ($isLock && !$nuki->isCanLock()) {
            throw new LockNotAvailable();
        }

        $secret1 = str_pad($this->hex2base32(substr(hash('sha256', $nuki->getPassword1()), 0, 20)), 16, 'A', STR_PAD_LEFT) .
            str_pad($this->hex2base32(substr(hash('sha256', $totpNonce), 0, 10)), 8, 'A', STR_PAD_LEFT);

        $totp1 = TOTP::create($secret1);

        $action = $isLock ? 'lock' : 'unlock';

        $queryData = [
            'username' => $nuki->getUsername(),
            'totp1'   => $totp1->now(),
            'totp2'   => $totp2,
            'nonce'   => $totpNonce,
            'action'  => $action,
        ];

        $url = $nuki->getDockontrolNukiApiServer();

        try {
            $response = $this->client->request('GET', $url, [
                'query' => $queryData,
                'timeout' => 3,
                'headers' => [
                    'Accept' => 'application/json',
                    'Content-Type' => 'application/json',
                ],
            ]);

            // The toArray() method automatically decodes the response and throws exceptions for non-JSON responses.
            $data = $response->toArray();

            // Step 5: Extract needed fields
            $status = $data['status'] ?? null;
            $message = $data['message'] ?? null;

            $this->addNukiLog($nuki, NukiStatus::OK, $nukiAction);

            return [
                'status' => $status,
                'message' => $message,
            ];
        } catch (TimeoutException | ClientExceptionInterface | DecodingExceptionInterface | RedirectionExceptionInterface | ServerExceptionInterface | TransportExceptionInterface $e) {
            $this->addNukiLog($nuki, NukiStatus::ERROR, $nukiAction);
            throw new APICallFailed("DOCKontrol Nuki API call failed", previous: $e);
        }
    }

    /**
     * @throws TooManyTries
     * @throws PINMismatch
     */
    public function checkEnteredPinIsValid(Nuki $nuki, ?string $pin = null): void
    {
        $incorrectPinTries = $this->nukiLogRepository->getIncorrectStatusTriesCountForNukiForPastOneMinute($nuki, NukiStatus::INCORRECT_PIN);
        if ($incorrectPinTries >= 5) {
            throw new TooManyTries();
        }

        if ($nuki->getPin() !== null && $pin !== $nuki->getPin()) {
            $this->addNukiLog($nuki, NukiStatus::INCORRECT_PIN, NukiAction::PIN_CHECK);
            throw new PINMismatch();
        }

        $this->addNukiLog($nuki, NukiStatus::OK, NukiAction::PIN_CHECK);
    }
    public function setPin(
        Nuki $nuki,
        ?string $pin = null,
    ): void {
        $nuki->setPin($pin);
        $this->saveNuki($nuki);
    }

    /**
     * @throws Password1Mismatch
     * @throws TooManyTries
     */
    public function checkPassword1(
        Nuki $nuki,
        #[SensitiveParameter] string $password1,
    ): void {
        $incorrectPasswordTries = $this->nukiLogRepository->getIncorrectStatusTriesCountForNukiForPastOneMinute($nuki, NukiStatus::INCORRECT_PASSWORD1);
        if ($incorrectPasswordTries >= 5) {
            throw new TooManyTries();
        }

        if ($nuki->getPassword1() !== $password1) {
            $this->addNukiLog($nuki, NukiStatus::INCORRECT_PASSWORD1, NukiAction::PASSWORD1_CHECK);
            throw new Password1Mismatch();
        }

        $this->addNukiLog($nuki, NukiStatus::OK, NukiAction::PASSWORD1_CHECK);
    }

    private function hex2base32(string $hex): string
    {
        // Convert the hex string to binary data.
        $data = pack('H*', $hex);

        // Define the Base32 alphabet (RFC 4648)
        $alphabet = 'ABCDEFGHIJKLMNOPQRSTUVWXYZ234567';
        $binaryString = '';

        // Convert each byte of the binary data to an 8-bit binary string.
        for ($i = 0, $length = strlen($data); $i < $length; $i++) {
            $binaryString .= str_pad(decbin(ord($data[$i])), 8, '0', STR_PAD_LEFT);
        }

        $base32 = '';
        // Split the binary string into 5-bit groups.
        $chunks = str_split($binaryString, 5);
        foreach ($chunks as $chunk) {
            // Pad the chunk with zeros if necessary.
            $chunk = str_pad($chunk, 5, '0', STR_PAD_RIGHT);
            // Convert the 5-bit chunk to its corresponding index.
            $index = (int) bindec($chunk);
            $base32 .= $alphabet[$index];
        }

        // Add padding with "=" characters if necessary.
        $paddingNeeded = (8 - (strlen($base32) % 8)) % 8;
        if ($paddingNeeded > 0) {
            $base32 .= str_repeat('=', $paddingNeeded);
        }

        return $base32;
    }

    private function addNukiLog(Nuki $nuki, NukiStatus $status, NukiAction $action): void
    {
        $nukiLog = new NukiLog();
        $nukiLog->setNuki($nuki);
        $nukiLog->setTime(CarbonImmutable::now());
        $nukiLog->setStatus($status);
        $nukiLog->setAction($action);

        $this->entityManager->persist($nukiLog);
        $this->entityManager->flush();
    }
}
