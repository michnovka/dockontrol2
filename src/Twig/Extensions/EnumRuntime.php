<?php

declare(strict_types=1);

namespace App\Twig\Extensions;

use BadMethodCallException;
use Twig\Extension\RuntimeExtensionInterface;

readonly class EnumRuntime implements RuntimeExtensionInterface
{
    /**
     * @param class-string $enumFQN
     */
    public function createProxy(string $enumFQN): object
    {
        return new class($enumFQN) {
            public function __construct(
                /** @var class-string $enum */
                private readonly string $enum,
            ) {
            }

            /**
             * @param array<mixed> $arguments
             */
            public function __call(string $name, array $arguments): mixed
            {
                $enumFQN = sprintf('%s::%s', $this->enum, $name);

                if (defined($enumFQN)) {
                    return constant($enumFQN);
                }

                if (method_exists($this->enum, $name)) {
                    return $this->enum::$name(...$arguments);
                }

                throw new BadMethodCallException('Neither "' . $enumFQN . '" or "' . $enumFQN . '::' . $name . '()" exist in this runtime.');
            }
        };
    }
}
