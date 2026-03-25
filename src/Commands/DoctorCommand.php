<?php

namespace ApiCrumbs\Framework\Commands;

use RecursiveDirectoryIterator;
use RecursiveIteratorIterator;
use ReflectionClass;

class DoctorCommand
{
    public function handle(): void
    {
        echo "🩺 \e[1;36mApiCrumbs System & Crumb Audit\e[0m\n";
        echo "------------------------------------\n";

        $this->checkEnvironment(); // SSL/PHP Checks
        $this->checkLocalCrumbs(); // 🚀 NEW: The Crumb Linter

        echo "\n\e[36m💡 Tip: Fix 'STITCH_FAIL' marks before running 'crumb submit'.\e[0m\n";
    }

    private function checkLocalCrumbs(): void
    {
        echo "\e[1mStep 3: Local Crumb Integrity (The Linter)\e[0m\n";
        $crumbDir = getcwd() . '/src/Crumbs';
        
        if (!is_dir($crumbDir)) {
            echo "⚠️  No local crumbs detected in src/Crumbs.\n";
            return;
        }

        $files = new RecursiveIteratorIterator(new RecursiveDirectoryIterator($crumbDir));
        foreach ($files as $file) {
            if ($file->isDir() || $file->getExtension() !== 'php') continue;

            $this->auditFile($file->getRealPath());
        }
    }

    private function auditFile(string $path): void
    {
        $content = file_get_contents($path);
        $fileName = basename($path);
        $errors = [];

        // 1. Check for Interface implementation
        if (
            !str_contains($content, 'extends BaseCrumb') &&
            !str_contains($content, 'extends CsvStreamCrumb') 
        ) {
            $errors[] = "MISSING_CONTRACT (Must extend BaseCrumb or extend CsvStreamCrumb)";
        }

        // 2. Check for Memory Leaks in CSVs
        if (str_contains($content, 'file_get_contents') && str_contains($content, '.csv')) {
            $errors[] = "MEMORY_RISK (Use fopen/Generator for CSVs)";
        }

        // 3. Check for Hardcoded Keys (Security Audit)
        if (preg_match('/["\'][a-zA-Z0-9]{32,}["\']/', $content)) {
            $errors[] = "SECURITY_FLAW (Hardcoded API Key detected)";
        }

        $status = empty($errors) ? "✅" : "❌";
        echo " {$status} {$fileName} " . (empty($errors) ? "" : "\e[31m(" . implode(', ', $errors) . ")\e[0m") . "\n";
    }

    private function checkEnvironment(): void { /* ... SSL/PHP logic ... */ }
}