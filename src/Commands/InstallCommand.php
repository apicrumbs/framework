<?php

namespace ApiCrumbs\Framework\Commands;

use GuzzleHttp\Client;

/**
 * InstallCommand - The Unified Registry Installer
 * Handles atomic downloads for Crumbs, Agents, and Drivers.
 */
class InstallCommand
{
    private string $manifestUrl = 'https://raw.githubusercontent.com/apicrumbs/registry/refs/heads/main/manifest.json';
    private string $registryBase = "https://raw.githubusercontent.com/apicrumbs/registry/refs/heads/main/";
    private array $manifest = [];
    
    public function handle(array $args): void
    {
        $id   = $args[2] ?? null;

        if (!$id) {
            echo "❌ \e[31mUsage: php foundry install [id]\e[0m\n";
            echo "Example: php foundry install postcodeio\n";
            return;
        }

        $this->manifest = $this->fetchManifest();
        
        $this->install($id);
    }

    private function install(string $id, bool $isDependency = false): void
    {
        // 1. Resolve Category (crumbs, agents, drivers)
        $items = $this->manifest['crumbs'] ?? null; 

        if (!$items) {
            echo "❌ \e[31mError: '{$id}' not found in registry.\e[0m\n";
            return;
        }

        $manifestItem = false;
        foreach ($items as $item) {
            if ($id == $item['id']) {
                $manifestItem = $item;
                break;
            }
        } 

        $item = $manifestItem;

        if (!$item) {
            echo "❌ \e[31mError: '{$id}' not found in registry.\e[0m\n";
            return;
        }

        // 2. Sponsoware Tier Validation
        if ($item['tier'] !== 'free' && 
            (empty(getenv('APICRUMBS_PRO_TOKEN')) || getenv('APICRUMBS_PRO_TOKEN') == 'your_sponsor_token_here')) {
            echo "🔐 \e[33mAccess Denied:\e[0m '{$id}' is a {$item['tier']} asset.\n  Please update the APICRUMBS_PRO_TOKEN in the .env file.\n";
            echo "👉 Sponsor at https://github.com to unlock.\n";
            return;
        }

        echo ($isDependency ? "  📦 " : "📥 ") . "Installing \e[1m{$item['name']}\e[0m (v{$item['version']})...\n";

        // 3. Atomic Download & Save
        $this->downloadFile($item);
        
        if (!$isDependency) {            
            echo "✨ \e[32mInstallation Complete!\e[0m\n";
            echo "💡 Try it: \e[2mphp crumb run " . $item['id'] . " \"{$item['example_id']}\"\e[0m\n";
        }
    }

    private function fetchManifest(): array
    {
        $manifestJson = @file_get_contents("{$this->manifestUrl}");
        return json_decode($manifestJson, true);
    }

    private function downloadFile(array $item): void
    {
        $targetPath = getcwd() . DIRECTORY_SEPARATOR . str_replace(['/', '\\'], DIRECTORY_SEPARATOR, $item['install_path']);
        $dir = dirname($targetPath);

        if (!is_dir($dir)) mkdir($dir, 0755, true);

        // In a real Sponsoware setup, the Pro files would be fetched via a secure 
        // proxy that validates the APICRUMBS_PRO_TOKEN header.        
        $sourceUrl = $this->registryBase . $item['install_path'];
        
        $code = @file_get_contents($sourceUrl);

        if (!$code) {
            echo "\e[31m❌ Error: Failed to download source code.\e[0m\n";
            exit(1);
        }

        file_put_contents($targetPath, $code);
    }

}
