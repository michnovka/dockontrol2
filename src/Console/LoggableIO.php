<?php

declare(strict_types=1);

namespace App\Console;

use Carbon\CarbonImmutable;
use Override;
use Symfony\Component\Console\Style\SymfonyStyle;

/** @psalm-suppress ParamNameMismatch */
class LoggableIO extends SymfonyStyle
{
    private string $buffer = '';

    public function getOutput(): string
    {
        return $this->buffer;
    }

    public function clear(): void
    {
        $this->buffer = '';
    }

    /** @inheritDoc */
    #[Override]
    public function info(string|array $message): void
    {
        $this->bufferAppend($message, 'INFO');
        parent::info($message);
    }

    /** @inheritDoc */
    #[Override]
    public function error($message): void
    {
        parent::error($message);
        $this->bufferAppend($message, 'ERROR');
    }

    /** @inheritDoc */
    #[Override]
    public function title(string $message): void
    {
        parent::title($message);
        $this->bufferAppend($message, 'TITLE');
    }

    /** @inheritDoc */
    #[Override]
    public function text($message): void
    {
        parent::text($message);
        $this->bufferAppend($message);
    }

    /** @inheritDoc */
    #[Override]
    public function success($message): void
    {
        parent::success($message);
        $this->bufferAppend($message, 'OK');
    }

    private function bufferAppend(
        string|iterable $message,
        ?string $messageType = null,
        bool $timeInfo = true,
        bool $newline = true,
    ): void {
        if (!is_iterable($message)) {
            $message = [$message];
        }

        foreach ($message as $line) {
            $this->buffer .= ($messageType ? '[' . $messageType . '] ' : '') . ($timeInfo ? ' ' . CarbonImmutable::now()->format('Y-m-d H:i:s') . ' | ' : '') . $line . ($newline ? PHP_EOL : '');
        }
    }
}
