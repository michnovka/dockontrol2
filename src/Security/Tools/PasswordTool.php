<?php

declare(strict_types=1);

namespace App\Security\Tools;

use Override;
use RuntimeException;
use SensitiveParameter;
use Symfony\Component\PasswordHasher\Exception\InvalidPasswordException;
use Symfony\Component\PasswordHasher\PasswordHasherInterface;

class PasswordTool implements PasswordHasherInterface
{
    public static function generateRandomHash(
        int $length = 0,
        string|int $alphabet = 'abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ0123456789!@#%^&*()-_+=;[]<>?,.',
    ): string {

        if ($length <= 0) {
            $length = 8;
        }

        if (is_numeric($alphabet)) {
            if ($alphabet == 10) {
                $alphabet = '0123456789';
            } elseif ($alphabet == 2) {
                $alphabet = '01';
            } else {
                $alphabet = '0123456789abcdef';
            }
        }

        $alphabetLength = strlen($alphabet);

        $hash = '';

        for ($i = 0; $i < $length; $i++) {
            $hash .= $alphabet[rand(0, $alphabetLength - 1)];
        }

        return $hash;
    }

    public static function getHashedPassword(
        string $password,
        string $hashAlgorithm = 'md5',
        ?string $salt = null,
        string|int|null $saltAlphabet = null,
        ?string $saltSeparator = ':',
    ): string {
        if (!$salt || is_numeric($salt)) {
            $salt = $saltAlphabet ? self::generateRandomHash(intval($salt), $saltAlphabet) : self::generateRandomHash(intval($salt));
        }

        if (!in_array($hashAlgorithm, ['md5', 'sha256'])) {
            $hashAlgorithm = 'md5';
        }

        return hash($hashAlgorithm, $salt . $password) . $saltSeparator . $salt;
    }

    /**
     * @param non-empty-string $saltSeparator
     */
    public static function checkPassword(
        string $password,
        string $hashedPasswordWithSalt,
        string $hashAlgorithm = 'md5',
        string $saltSeparator = ':',
    ): bool {
        if (!in_array($hashAlgorithm, ['md5', 'sha256'])) {
            return false;
        }

        $parts = explode($saltSeparator, $hashedPasswordWithSalt, 2);
        if (count($parts) === 2) {
            list($hashedPassword, $salt) = $parts;
        } else {
            throw new RuntimeException("Invalid hashed password format.");
        }

        return hash($hashAlgorithm, $salt . $password) == $hashedPassword;
    }
    public static function checkPasswordStrengthLevel(string $password): float
    {
        $score = 0;

        // check if contains lowercase
        if (preg_match('/^\S*(?=\S*[a-z])\S*$/', $password)) {
            $score++;
        }

        // check if contains upper
        if (preg_match('/^\S*(?=\S*[A-Z])\S*$/', $password)) {
            $score++;
        }

        //numbers
        if (preg_match('/^\S*(?=\S*[\d])\S*$/', $password)) {
            $score++;
        }

        //special chars
        if (preg_match('/^\S*(?=\S*[\W])\S*$/', $password)) {
            $score++;
        }

        $score += (int) floor((float) strlen($password) / 3.0);

        return min($score, 10);
    }
    public static function checkPasswordStrength(string $password): bool
    {
        return self::checkPasswordStrengthLevel($password) >= 5;
    }

    #[Override]
    public function hash(#[SensitiveParameter] string $plainPassword): string
    {
        if ($this->checkPasswordStrength($plainPassword)) {
            return $this->getHashedPassword($plainPassword);
        }
        throw new InvalidPasswordException('Password is too weak.');
    }

    #[Override]
    public function verify(string $hashedPassword, #[SensitiveParameter] string $plainPassword): bool
    {
        if ('' === $plainPassword) {
            return false;
        }

        return $this->checkPassword($plainPassword, $hashedPassword);
    }

    #[Override]
    public function needsRehash(string $hashedPassword): bool
    {
        return strlen($hashedPassword) === 41;
    }
}
