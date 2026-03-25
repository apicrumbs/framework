<?php

namespace ApiCrumbs\Framework\Commands;

use ReflectionClass;
use ApiCrumbs\Framework\Contracts\CrumbInterface;
use ApiCrumbs\Framework\Contracts\BaseCrumb;
use ApiCrumbs\Framework\Contracts\CsvStreamCrumb;

class LinterCommand
{
    public function handle(): void
    {
        echo "🛡️  ApiCrumbs Registry Linter: Validating Architectures...\n";
        
        $crumbsDir = getcwd() . '/src/Crumbs';
        $iterator = new \RecursiveIteratorIterator(new \RecursiveDirectoryIterator($crumbsDir));
        $errors = 0;

        foreach ($iterator as $file) {
            if (!$file->isFile() || !str_ends_with($file->getFilename(), 'Crumb.php')) continue;

            $className = $this->resolveClassName($file->getPathname());
            if (!class_exists($className)) require_once $file->getPathname();

            $issues = $this->lintCrumb($className, $file->getPathname());
            
            if (!empty($issues)) {
                echo "\n❌ \e[31m{$className}\e[0m\n";
                foreach ($issues as $err) echo "  - {$err}\n";
                $errors++;
            }
        }

        if ($errors === 0) {
            echo "\e[32m✨ All systems green. Registry is ready for release.\e[0m\n";
        } else {
            exit(1); // Non-zero exit for CI/CD failure
        }
    }

    private function lintCrumb(string $class, string $path): array
    {
        $issues = [];
        $reflection = new ReflectionClass($class);
        $code = file_get_contents($path);

        // 1. Structural Checks
        if (!$reflection->implementsInterface(CrumbInterface::class)) {
            $issues[] = "Critical: Missing CrumbInterface implementation.";
        }

        // 2. MetadataTransformer Requirement
        if (!$reflection->hasMethod('transform')) {
            $issues[] = "Missing transform() method. All 'Crumbs' must optimize data for LLMs.";
        }

        // 3. Inheritance & Security Logic
        if (str_contains($class, '\\Pro\\') || str_contains($class, '\\Global\\')) {
            if (class_exists(BaseCrumb::class) && !$reflection->isSubclassOf(BaseCrumb::class) && !$reflection->isSubclassOf(CsvStreamCrumb::class)) {
                $issues[] = "Tier Violation: Pro/Global crumbs must extend BaseCrumb or CsvStreamCrumb.";
            }
        }

        // 4. Static Analysis: Forbidden Functions (Force Guzzle/safeFetch)
        if (preg_match('/(curl_init|file_get_contents\(["\']http)/', $code)) {
            $issues[] = "Security: Bypassing Guzzle! Use \$this->safeFetch() to respect throttling.";
        }

        // 5. Versioning Check
        if (!$reflection->hasMethod('getVersion') || empty((new $class())->getVersion())) {
            $issues[] = "Registry Error: getVersion() must return a semantic version string.";
        }

        return $issues;
    }

    private function resolveClassName(string $path): string {
        $relPath = str_replace([getcwd().'/src/', '.php', '/'], ['', '', '\\'], $path);
        return "ApiCrumbs\\" . $relPath;
    }
}
