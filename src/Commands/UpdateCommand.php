<?php

namespace ApiCrumbs\Framework\Commands;

class UpdateCommand
{
    private string $registryUrl = 'https://raw.githubusercontent.com/apicrumbs/registry/refs/heads/main/manifest.json';

    public function handle(array $args): void
    {
        $isDryRun = in_array('--dry-run', $args);
        echo $isDryRun ? "🔍 [DRY RUN] Comparing Registry...\n" : "📡 Syncing Registry...\n";

        // 1. Fetch Remote Manifest
        $remoteJson = @file_get_contents($this->registryUrl);
        if (!$remoteJson) {
            echo "\e[31m❌ Error: Cannot reach remote registry manifest.\e[0m\n";
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

        echo "   📥 Downloading {$version}... ";

        if (@copy($url, $path)) {
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
        $dir = new \RecursiveIteratorIterator(new \RecursiveDirectoryIterator(getcwd() . '/src/Crumbs'));
        
        foreach ($dir as $file) {
            if (!$file->isFile() || !str_ends_with($file->getFilename(), 'Crumb.php')) continue;
            $fileParts = explode('\\', $file->getPathname());
            $fileCategory = strtolower($fileParts[count($fileParts) - 2]);
            // Reflect to get version without executing full API logic
            $content = file_get_contents($file->getPathname());
            if (preg_match("/public function getVersion\(\): string\s*{\s*return ['\"](.*?)['\"];\s*}/", $content, $matches)) {
                $id = $fileCategory .'/'. strtolower(str_replace('Crumb.php', '', $file->getFilename()));
                echo $id . PHP_EOL;
                $found[$id] = ['version' => $matches[1]];
            }
        }
        return $found;
    }

    private function resolvePath(string $class): string
    {
        return str_replace(['ApiCrumbs\\', '\\'], ['src/', '/'], $class) . '.php';
    }
}