#!/usr/bin/env php
<?php

/**
 * list_files.php
 *
 * High-Level Description:
 * This script recursively gathers all necessary PHP files for a base class
 * (including same-namespace references, "use" statements, typed properties,
 * references from T_NEW or T_DOUBLE_COLON). It can also gather referenced
 * twig templates, optionally print composer.json, exclude certain files by
 * class name or entire "Repository" directory, and can produce a dry-run list
 * or detailed file contents.
 *
 * Usage:
 *   php list_files.php <FQCN> [options]
 *
 * Options:
 *   --twig           Include .twig references
 *   --composer       Include contents of composer.json
 *   --dry-run        Only list discovered files, do not print their contents
 *   --no-repository  Exclude scanning/including files from 'Repository/' folder
 *   --exclude XXX    Exclude class 'XXX' (you can repeat this argument multiple times)
 *   --debug          Print debug info
 *   --help           Show this help message
 *
 * Example:
 *   php list_files.php "App\\Security\\Voter\\CameraVoter" --twig --dry-run --debug --exclude App\\Security\\SomeClass
 */

declare(strict_types=1);

const BASE_NAMESPACE = 'App'; // Adjust if your base namespace is different

// ----------------------------------------------------------------
// 1. Parse Command-Line Arguments
// ----------------------------------------------------------------
if ($argc < 2) {
    showHelpAndExit();
}

$classFqcn = $argv[1];
if (str_starts_with($classFqcn, '--')) {
    // e.g., "php list_files.php --help"
    showHelpAndExit();
}

$flags = array_slice($argv, 2);

// Determine which flags/switches are present
$includeTwig     = in_array('--twig', $flags, true);
$includeComposer = in_array('--composer', $flags, true);
$dryRun          = in_array('--dry-run', $flags, true);
$noRepository    = in_array('--no-repository', $flags, true);
$debug           = in_array('--debug', $flags, true);
$helpRequested   = in_array('--help', $flags, true);

// Gather any --exclude arguments (can appear multiple times)
$excludedClasses = [];
for ($i = 2; $i < $argc; $i++) {
    if ($argv[$i] === '--exclude' && isset($argv[$i + 1])) {
        $excludedClasses[] = $argv[$i + 1];
    }
}

// If help was requested at any point
if ($helpRequested) {
    showHelpAndExit();
}

// Derive paths
$rootDir      = realpath(__DIR__ . '/..');
$composerFile = $rootDir . '/composer.json';

// ----------------------------------------------------------------
// 2. Helper: showHelpAndExit
// ----------------------------------------------------------------
function showHelpAndExit(): never
{
    echo "Usage: php list_files.php <FQCN> [options]\n\n";
    echo "Options:\n";
    echo "  --twig           Include .twig references\n";
    echo "  --composer       Include composer.json contents at the top\n";
    echo "  --dry-run        Only list discovered files, don't print file contents\n";
    echo "  --no-repository  Exclude scanning & including files from 'Repository/' folder\n";
    echo "  --exclude XXX    Exclude a specific class 'XXX'. Can appear multiple times.\n";
    echo "  --debug          Print debug info\n";
    echo "  --help           Show this help\n\n";
    exit(0);
}

// ----------------------------------------------------------------
// 3. Validate that the initial file for the given class exists
// ----------------------------------------------------------------
$basePhpFile = fqcnToFilePath($classFqcn, $rootDir);
if (!file_exists($basePhpFile)) {
    echo "Error: file for class $classFqcn not found at $basePhpFile\n";
    exit(1);
}

// ----------------------------------------------------------------
// 4. Utility Functions
// ----------------------------------------------------------------

/**
 * Convert an FQCN (e.g. "App\\Helper\\MyHelper") to a file path
 * (e.g. "src/Helper/MyHelper.php"), respecting BASE_NAMESPACE.
 */
function fqcnToFilePath(string $fqcn, string $rootDir): string
{
    $namespacePrefix = BASE_NAMESPACE . '\\';
    if (str_starts_with($fqcn, $namespacePrefix)) {
        $fqcn = substr($fqcn, strlen($namespacePrefix));
    }
    return $rootDir . '/src/' . str_replace('\\', '/', $fqcn) . '.php';
}

/**
 * Check if a class is excluded by name.
 * @param string[] $excludedClasses
 */
function isClassExcluded(string $fqcn, array $excludedClasses): bool
{
    return in_array($fqcn, $excludedClasses, true);
}

/**
 * Decide if a path should be excluded based on --no-repository or --exclude
 * @param string[] $excludedClasses
 */
function shouldExcludePath(
    string $filePath,
    ?string $fqcn,
    array $excludedClasses,
    bool $noRepository,
): bool {
    // If --no-repository, skip any file in /Repository/ folder
    if ($noRepository && str_contains($filePath, '/Repository/')) {
        return true;
    }
    // If we have a known FQCN, check if it's excluded
    if ($fqcn && isClassExcluded($fqcn, $excludedClasses)) {
        return true;
    }
    return false;
}

/**
 * Return true if type name is built-in or special (self, static, parent, magic).
 */
function isBuiltinOrSpecialName(?string $typeName): bool
{
    static $builtinTypes = [
        'bool', 'int', 'float', 'string', 'array', 'object',
        'callable', 'iterable', 'mixed', 'void', 'null', 'false', 'true', 'never',
    ];

    if (!$typeName) {
        return false;
    }

    $lower = strtolower($typeName);
    if (in_array($lower, $builtinTypes, true)) {
        return true;
    }
    if (in_array($lower, ['self', 'static', 'parent'], true)) {
        return true;
    }
    if (str_starts_with($typeName, '__')) {
        return true;
    }

    return false;
}

// ----------------------------------------------------------------
// 5. parsePhpFile - identifies references in a single PHP file
// ----------------------------------------------------------------
/**
 * @return array{namespace:string,useClasses:string[],sameNamespaceRefs:string[],twigTemplates:string[]}
 */
function parsePhpFile(string $phpFile, bool $debug): array
{
    if ($debug) {
        echo "[DEBUG] Parsing PHP file: $phpFile\n";
    }

    $content = file_get_contents($phpFile);
    $tokens  = token_get_all($content);

    $namespace         = '';
    $useClasses        = [];
    $sameNamespaceRefs = [];
    $twigTemplates     = [];

    // Helper to build up class strings from T_STRING, T_NS_SEPARATOR, etc.
    $buildClassString = function (array &$tokens, int &$i): string {
        $validNameTokens = [T_STRING, T_NS_SEPARATOR];
        if (defined('T_NAME_FULLY_QUALIFIED')) {
            $validNameTokens[] = T_NAME_FULLY_QUALIFIED;
        }
        if (defined('T_NAME_QUALIFIED')) {
            $validNameTokens[] = T_NAME_QUALIFIED;
        }
        if (defined('T_NAME_RELATIVE')) {
            $validNameTokens[] = T_NAME_RELATIVE;
        }

        $tokenCount = count($tokens);
        $result = '';
        while ($i < $tokenCount) {
            $t = $tokens[$i];
            if (!is_array($t)) {
                if (in_array($t, [';', '{', '(', ')', ',', '='], true)) {
                    break;
                }
                break;
            }

            [$tid, $ttext] = $t;
            if (in_array($tid, [T_WHITESPACE, T_COMMENT, T_DOC_COMMENT], true)) {
                $i++;
                continue;
            }
            if (in_array($tid, $validNameTokens, true)) {
                $result .= $ttext;
                $i++;
                continue;
            }
            break;
        }
        return trim($result);
    };

    // Pass #1: find namespace, use statements, twig references
    $tokenCount = count($tokens);
    $i = 0;
    while ($i < $tokenCount) {
        $t = $tokens[$i];
        if (!is_array($t)) {
            $i++;
            continue;
        }
        switch ($t[0]) {
            case T_NAMESPACE:
                $i++;
                $namespace = $buildClassString($tokens, $i);
                if ($debug) {
                    echo "[DEBUG] => Found namespace: $namespace\n";
                }
                break;

            case T_USE:
                $i++;
                $fullClass = $buildClassString($tokens, $i);
                if (!empty($fullClass)) {
                    $useClasses[] = $fullClass;
                }
                // skip until semicolon
                while ($i < $tokenCount) {
                    if ($tokens[$i] === ';') {
                        $i++;
                        break;
                    }
                    $i++;
                }
                break;

            case T_CONSTANT_ENCAPSED_STRING:
                // detect .twig
                $strVal = trim($t[1], "'\"");
                if (str_ends_with($strVal, '.twig')) {
                    $twigTemplates[] = $strVal;
                }
                break;
        }
        $i++;
    }

    // Pass #2: typed properties, T_NEW, T_DOUBLE_COLON
    $i = 0;
    while ($i < $tokenCount) {
        $t = $tokens[$i];
        if (!is_array($t)) {
            $i++;
            continue;
        }
        $tokenId = $t[0];

        // typed property => e.g. private MyHelper $obj
        if (in_array($tokenId, [T_PRIVATE, T_PROTECTED, T_PUBLIC], true)) {
            $i++;
            $typeCandidate = $buildClassString($tokens, $i);
            if ($typeCandidate && !isBuiltinOrSpecialName($typeCandidate) && !str_contains($typeCandidate, '\\')) {
                $sameNamespaceRefs[] = $typeCandidate;
                if ($debug) {
                    echo "[DEBUG] => Found typed reference: $typeCandidate\n";
                }
            }
            continue;
        }

        // T_FUNCTION => skip next T_STRING (the method name)
        if ($tokenId === T_FUNCTION) {
            $i++;
            if (isset($tokens[$i]) && is_array($tokens[$i]) && $tokens[$i][0] === T_STRING) {
                $fnName = $tokens[$i][1];
                if ($debug) {
                    echo "[DEBUG] => Found function: $fnName (skipping)\n";
                }
                $i++;
            }
            continue;
        }

        // T_NEW => parse forward for class name
        if ($tokenId === T_NEW) {
            $i++;
            $possibleClass = $buildClassString($tokens, $i);
            if ($possibleClass && !isBuiltinOrSpecialName($possibleClass) && !str_starts_with($possibleClass, '\\')) {
                $sameNamespaceRefs[] = $possibleClass;
                if ($debug) {
                    echo "[DEBUG] => Found reference from T_NEW: $possibleClass\n";
                }
            }
            continue;
        }

        // T_DOUBLE_COLON => parse backward for class name
        if ($tokenId === T_DOUBLE_COLON) {
            $className = '';
            $revIndex = $i - 1;
            while ($revIndex >= 0 && is_array($tokens[$revIndex])) {
                [$tid, $ttext] = $tokens[$revIndex];
                if (in_array($tid, [T_WHITESPACE, T_COMMENT, T_DOC_COMMENT], true)) {
                    $revIndex--;
                    continue;
                }
                $validNameTokens = [T_STRING, T_NS_SEPARATOR];
                if (defined('T_NAME_FULLY_QUALIFIED')) {
                    $validNameTokens[] = T_NAME_FULLY_QUALIFIED;
                }
                if (defined('T_NAME_QUALIFIED')) {
                    $validNameTokens[] = T_NAME_QUALIFIED;
                }
                if (defined('T_NAME_RELATIVE')) {
                    $validNameTokens[] = T_NAME_RELATIVE;
                }
                if (in_array($tid, $validNameTokens, true)) {
                    $className = $ttext . $className;
                    $revIndex--;
                } else {
                    break;
                }
            }
            if ($className && !isBuiltinOrSpecialName($className) && !str_starts_with($className, '\\') && !str_contains($className, '\\')) {
                $sameNamespaceRefs[] = $className;
                if ($debug) {
                    echo "[DEBUG] => Found reference before T_DOUBLE_COLON: $className\n";
                }
            }
            $i++;
            continue;
        }

        $i++;
    }

    return [
        'namespace'         => $namespace,
        'useClasses'        => $useClasses,
        'sameNamespaceRefs' => $sameNamespaceRefs,
        'twigTemplates'     => $twigTemplates,
    ];
}

// ----------------------------------------------------------------
// 6. gatherPhpDependencies: Recursively gather needed .php files
// ----------------------------------------------------------------
/**
 * @param string[] $visitedClasses
 * @param string[] $collectedPhpFiles
 * @param string[] $excludedClasses
 * @param string[] $excludedFiles
 * @return string[]
 */
function gatherPhpDependencies(
    string $phpFile,
    array &$visitedClasses,
    array &$collectedPhpFiles,
    bool $debug,
    array $excludedClasses,
    bool $noRepository,
    array &$excludedFiles,
): array {
    $info = parsePhpFile($phpFile, $debug);

    if (!in_array($phpFile, $collectedPhpFiles, true)) {
        $collectedPhpFiles[] = $phpFile;
    }

    $namespace    = $info['namespace'] ?? '';
    $twigTemplates = $info['twigTemplates'] ?? [];

    // Process use statements
    foreach ($info['useClasses'] as $rawUseClass) {
        $fqcn = $rawUseClass;
        if (!str_starts_with($fqcn, '\\') && !str_starts_with($fqcn, BASE_NAMESPACE . '\\')) {
            $fqcn = BASE_NAMESPACE . '\\' . $fqcn;
        }
        if ($debug) {
            echo "[DEBUG] 'use' => raw: {$rawUseClass}, resolved: {$fqcn}\n";
        }

        $filePath = fqcnToFilePath(ltrim($fqcn, '\\'), $GLOBALS['rootDir']);

        // Check for exclusion
        if (shouldExcludePath($filePath, $fqcn, $excludedClasses, $noRepository)) {
            if (!in_array($filePath, $excludedFiles, true)) {
                $excludedFiles[] = $filePath;
            }
            continue;
        }

        if (file_exists($filePath)) {
            if (!isset($visitedClasses[$fqcn])) {
                $visitedClasses[$fqcn] = $filePath;
                $subTwig = gatherPhpDependencies($filePath, $visitedClasses, $collectedPhpFiles, $debug, $excludedClasses, $noRepository, $excludedFiles);
                $twigTemplates = array_merge($twigTemplates, $subTwig);
            }
        } else {
            if ($debug) {
                echo "[DEBUG] => File not found: $filePath\n";
            }
        }
    }

    // Process same-namespace references
    foreach ($info['sameNamespaceRefs'] as $className) {
        $fqcn = $namespace ? $namespace . '\\' . $className : $className;
        if (!str_starts_with($fqcn, BASE_NAMESPACE . '\\')) {
            $fqcn = BASE_NAMESPACE . '\\' . $fqcn;
        }
        if ($debug) {
            echo "[DEBUG] => same-namespace ref: {$className}, resolved: {$fqcn}\n";
        }

        $filePath = fqcnToFilePath(ltrim($fqcn, '\\'), $GLOBALS['rootDir']);

        if (shouldExcludePath($filePath, $fqcn, $excludedClasses, $noRepository)) {
            if (!in_array($filePath, $excludedFiles, true)) {
                $excludedFiles[] = $filePath;
            }
            continue;
        }

        if (file_exists($filePath)) {
            if (!isset($visitedClasses[$fqcn])) {
                $visitedClasses[$fqcn] = $filePath;
                $subTwig = gatherPhpDependencies($filePath, $visitedClasses, $collectedPhpFiles, $debug, $excludedClasses, $noRepository, $excludedFiles);
                $twigTemplates = array_merge($twigTemplates, $subTwig);
            }
        } else {
            if ($debug) {
                echo "[DEBUG] => File not found: $filePath\n";
            }
        }
    }

    return $twigTemplates;
}

// ----------------------------------------------------------------
// 7. Helpers for twig references
// ----------------------------------------------------------------
function findTwigFilePath(string $templateName): ?string
{
    $rootDir = $GLOBALS['rootDir'];
    $candidate = $rootDir . '/templates/' . $templateName;
    return file_exists($candidate) ? $candidate : null;
}

/**
 * @return string[]
 */
function parseTwigFileForReferences(string $filePath, bool $debug): array
{
    if ($debug) {
        echo "[DEBUG] Parsing twig file: $filePath\n";
    }
    $content = file_get_contents($filePath);
    $pattern = '/\{%\s*(extends|include|embed)\s+[\'"]([^\'"]+\.twig)[\'"]\s*%\}/';
    $matches = [];
    $refs    = [];

    if (preg_match_all($pattern, $content, $matches, PREG_SET_ORDER)) {
        foreach ($matches as $m) {
            $refs[] = $m[2];
        }
    }
    if ($debug && $refs) {
        echo "[DEBUG] => Found references in twig:\n";
        foreach ($refs as $r) {
            echo "    * $r\n";
        }
    }
    return $refs;
}

/**
 * @param string[] $visitedTwig
 * @param string[] $collectedTwigFiles
 */
function gatherTwigDependencies(
    string $twigName,
    array &$visitedTwig,
    array &$collectedTwigFiles,
    bool $debug,
): void {
    if ($debug) {
        echo "[DEBUG] Attempting to gather twig dependencies for '$twigName'\n";
    }
    $path = findTwigFilePath($twigName);
    if (!$path) {
        if ($debug) {
            echo "[DEBUG] => No twig file found for '$twigName'\n";
        }
        return;
    }
    if (isset($visitedTwig[$twigName])) {
        if ($debug) {
            echo "[DEBUG] => Already visited twig '$twigName'\n";
        }
        return;
    }
    $visitedTwig[$twigName] = $path;
    if (!in_array($path, $collectedTwigFiles, true)) {
        $collectedTwigFiles[] = $path;
    }

    $refs = parseTwigFileForReferences($path, $debug);
    foreach ($refs as $r) {
        gatherTwigDependencies($r, $visitedTwig, $collectedTwigFiles, $debug);
    }
}

// ----------------------------------------------------------------
// 8. Program Execution
// ----------------------------------------------------------------
$excludedFiles = [];
$visitedClasses = [];
$collectedPhpFiles = [];
$visitedTwig = [];
$collectedTwigFiles = [];


if ($debug) {
    echo "[DEBUG] Root dir: $rootDir\n";
    echo "[DEBUG] Base file: $basePhpFile\n";
    echo "[DEBUG] Starting gatherPhpDependencies...\n";
}

// Gather all the PHP dependencies from the base file
$twigFromPhp = gatherPhpDependencies(
    $basePhpFile,
    $visitedClasses,
    $collectedPhpFiles,
    $debug,
    $excludedClasses,
    $noRepository,
    $excludedFiles
);

// If user wants twig references
if ($includeTwig) {
    // Recursively gather twig dependencies
    foreach ($twigFromPhp as $twigName) {
        gatherTwigDependencies($twigName, $visitedTwig, $collectedTwigFiles, $debug);
    }
}

// Sort the discovered files
sort($collectedPhpFiles, SORT_STRING);
sort($collectedTwigFiles, SORT_STRING);
sort($excludedFiles, SORT_STRING);

// If user wants composer.json at top
if ($includeComposer && file_exists($composerFile)) {
    if ($dryRun) {
        echo "composer.json\n";
    } else {
        echo "Filename: composer.json\n";
        echo "Contents:\n```json\n";
        echo file_get_contents($composerFile);
        echo "\n```\n---\n";
    }
}

// Print discovered PHP files
foreach ($collectedPhpFiles as $phpFile) {
    $relPath = str_replace($rootDir . '/', '', $phpFile);

    if ($dryRun) {
        echo "$relPath\n";
    } else {
        echo "Filename: $relPath\n";
        echo "Contents:\n```php\n";
        echo file_get_contents($phpFile);
        echo "\n```\n---\n";
    }
}

// Print discovered twig files
if ($includeTwig) {
    foreach ($collectedTwigFiles as $twigFile) {
        $relPath = str_replace($rootDir . '/', '', $twigFile);

        if ($dryRun) {
            echo "$relPath\n";
        } else {
            echo "Filename: $relPath\n";
            echo "Contents:\n```twig\n";
            echo file_get_contents($twigFile);
            echo "\n```\n---\n";
        }
    }
}

// If there are excluded files
if ($excludedFiles) {
    if ($dryRun) {
        echo "\nExcluded files:\n";
        foreach ($excludedFiles as $exPath) {
            echo str_replace($rootDir . '/', '', $exPath) . "\n";
        }
    } else {
        echo "\nThe following files were excluded:\n";
        foreach ($excludedFiles as $exPath) {
            echo "- " . str_replace($rootDir . '/', '', $exPath) . "\n";
        }
        echo "If you need them to understand code, request them and I shall provide them.\n";
    }
}
