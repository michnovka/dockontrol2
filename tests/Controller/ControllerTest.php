<?php

declare(strict_types=1);

namespace App\Tests\Controller;

use App\Controller\API\AbstractAPIController;
use App\Controller\CP\AbstractCPController;
use App\Controller\Guest\AbstractGuestController;
use App\Controller\PZ\AbstractPZController;
use FilesystemIterator;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;
use RecursiveIteratorIterator;
use ReflectionClass;
use ReflectionMethod;
use Symfony\Component\Finder\Iterator\RecursiveDirectoryIterator;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

class ControllerTest extends TestCase
{
    #[DataProvider('controllerClassProvider')]
    public function testControllerInheritanceAndIsGranted(string $controllerClass, string $abstractController): void
    {
        $this->assertTrue(class_exists($controllerClass), sprintf('Class %s does not exist', $controllerClass));
        $this->assertTrue(class_exists($abstractController), sprintf('Class %s does not exist', $abstractController));

        $reflectionClass = new ReflectionClass($controllerClass);

        // Assert that the controller inherits from the abstract controller
        $this->assertTrue(
            $reflectionClass->isSubclassOf($abstractController),
            sprintf('%s does not inherit from %s', $controllerClass, $abstractController)
        );

        // Check if the class has the #[IsGranted] attribute
        $hasIsGrantedAttribute = $this->classContainsAttribute($reflectionClass, IsGranted::class);

        if ($hasIsGrantedAttribute) {
            return; // All good, class has IsGranted attribute
        }

        // If no IsGranted attribute on the class, check public #[Route] methods for #[IsGranted]
        $publicMethods = $reflectionClass->getMethods(ReflectionMethod::IS_PUBLIC);

        foreach ($publicMethods as $method) {
            if ($this->methodHasAttribute($method, Route::class)) {
                $this->assertTrue(
                    $this->methodHasAttribute($method, IsGranted::class),
                    sprintf('%s::%s does not have the #[IsGranted] attribute', $controllerClass, $method->getName())
                );
            }
        }
    }

    /**
     * Provides a list of controller classes and their respective abstract controllers.
     *
     * @return array<int, array{string, string}>
     */
    public static function controllerClassProvider(): array
    {
        $controllerModules = [
            'CP' => AbstractCPController::class,
            'PZ' => AbstractPZController::class,
            'Guest' => AbstractGuestController::class,
            'API' => AbstractAPIController::class,
        ];

        $controllerClasses = [];

        foreach ($controllerModules as $moduleName => $abstractController) {
            $srcDir = realpath(__DIR__ . "/../../src/Controller/$moduleName");

            if (!$srcDir) {
                continue; // Skip if the directory doesn't exist
            }

            $iterator = new RecursiveIteratorIterator(
                new RecursiveDirectoryIterator($srcDir, FilesystemIterator::SKIP_DOTS),
                RecursiveIteratorIterator::SELF_FIRST
            );

            foreach ($iterator as $file) {
                if ($file->isFile() && $file->getExtension() === 'php') {
                    $controllerClass = self::getClassNameFromPath($file->getRealPath(), $srcDir, $moduleName);

                    if ($controllerClass === $abstractController) {
                        continue; // Skip the abstract controller itself
                    }

                    if (class_exists($controllerClass)) {
                        $controllerClasses[] = [$controllerClass, $abstractController];
                    }
                }
            }
        }

        return $controllerClasses;
    }

    private static function getClassNameFromPath(string $fullPath, string $srcDir, string $moduleName): string
    {
        $relativePath = substr($fullPath, strlen($srcDir) + 1);
        $relativeClassPath = str_replace([DIRECTORY_SEPARATOR, '.php'], ['\\', ''], $relativePath);

        return "App\\Controller\\$moduleName\\$relativeClassPath";
    }

    private function classContainsAttribute(ReflectionClass $class, string $attributeClass): bool
    {
        $attributes = $class->getAttributes();

        return array_any($attributes, fn ($attribute) => $attribute->getName() === $attributeClass);
    }

    private function methodHasAttribute(ReflectionMethod $method, string $attributeClass): bool
    {
        $attributes = $method->getAttributes();

        return array_any($attributes, fn ($attribute) => $attribute->getName() === $attributeClass);
    }
}
