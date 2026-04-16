<?php

namespace ApiCrumbs\Framework\Commands;

use ApiCrumbs\Framework\FileLoader;

class UpdateCommand
{
    private string $archiveUrl = 'https://raw.githubusercontent.com/apicrumbs/archive/refs/heads/main/manifest.json';

    public function handle(array $args): void
    {
        $isDryRun = in_array('--dry-run', $args);
        echo $isDryRun ? "🔍 [DRY RUN] Comparing Registry...\n" : "📡 Syncing Registry...\n";

        // 1. Fetch Remote Manifest
        $remoteJson = FileLoader::getFileContents($this->archiveUrl);
        if (!$remoteJson) {
            echo "\e[31m❌ Error: Cannot reach remote archive manifest.\e[0m\n";
            return;
        }
        $remoteManifest = json_decode($remoteJson, true);

        // 2. Scan Local Crumbs
        $localCrumbs = $this->scanLocalCrumbs();

        foreach ($remoteManifest['crumbs'] as $meta) {

            $id = $meta['id'];
            $remoteVer = $meta['version'];
            $localVer = $localCrumbs[$id]['version'] ?? '';

            if (!$localVer) {
                continue;
            }

            $targetPath = $this->resolvePath($meta['class']);

            // 3. Version Comparison
            if (version_compare($remoteVer, $localVer, '>')) {
                echo "📦 Update available for [\e[1m{$meta['id']}\e[0m]: {$localVer} -> \e[32m{$remoteVer}\e[0m\n";

                if ($isDryRun) continue;

                $this->performAtomicUpdate($id, $meta['download_url'], $targetPath, $remoteVer);
            }
        }

        echo "\e[32m✨ Sync complete.\e[0m\n";
    }

    private function performAtomicUpdate($id, $url, $path, $version): void
    {
        $backup = $path . '.bak';
        if (file_exists($path)) copy($path, $backup);

        echo "   📥 Downloading {$version}... " . PHP_EOL;
        
        $sourceFile = FileLoader::getFileContents($url);

        if (file_put_contents($path, $sourceFile)) {
            echo "\e[32mDone\e[0m\n";
            if (file_exists($backup)) unlink($backup);
        } else {
            echo "\e[31mFailed\e[0m\n";
            if (file_exists($backup)) {
                rename($backup, $path);
                echo "   \e[33mRestored from backup.\e[0m\n";
            }
        }
    }

    private function scanLocalCrumbs(): array
    {
        $found = [];

        $files = glob(getcwd() . '/src/Crumbs/**/*');

        foreach ($files as $filePath) {
            $fileName = basename($filePath);

            // Extract Category (Parent folder name)
            $pathParts = explode(DIRECTORY_SEPARATOR, dirname($filePath));
            $fileCategory = strtolower(end($pathParts));
            $fileCategoryParts = explode('/', $fileCategory);
            $fileCategory = end($fileCategoryParts);

            $className = self::resolveCrumbsNamespace($filePath);
            $crumb = new $className();

            $id = $fileCategory .'/'. strtolower(str_replace('Crumb.php', '', $fileName));
            $found[$id] = ['version' => $crumb->getVersion()];
        }

        return $found;
    }

    private static function resolveCrumbsNamespace(string $path): string 
    {
        // 1. Standardise separators for Windows/XAMPP compatibility
        $path = str_replace('\\', '/', $path);
        $root = str_replace('\\', '/', getcwd() . '/src/');

        // 2. Strip the root path and the .php extension
        $relative = str_replace([$root, '.php'], ['', ''], $path);

        // 3. Convert folder separators to Namespace backslashes
        $ns = 'ApiCrumbs\\' . str_replace('/', '\\', $relative);

        return $ns;
    }

    private function resolvePath(string $class): string
    {
        return str_replace(['ApiCrumbs\\', '\\'], ['src/', '/'], $class) . '.php';
    }
}