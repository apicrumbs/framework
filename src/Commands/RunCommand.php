<?php

namespace ApiCrumbs\Framework\Commands;

class RunCommand
{
    private string $registryUrl = 'https://raw.githubusercontent.com/apicrumbs/registry/refs/heads/main/manifest.json';

    public function handle(array $args): void
    {
        $name = $args[2] ?? null;
        $id = $args[3] ?? 'SW1A1AA';
        $showJson = in_array('--json', $args);

        if (!$name) {
            echo "\e[31m❌ Error: Specify a crumb (e.g. php crumb run PostcodeId)\e[0m\n";
            return;
        }

        $className = $this->resolveCrumbClass($name);
        if (!$className || !class_exists($className)) {
            echo "\e[31m❌ Error: Crumb '{$name}' not found.\e[0m\n";
            return;
        }

        $crumb = new $className();

        echo "\e[1;36m🍪 ApiCrumbs Preview: {$name}Crumb\e[0m\n";
        echo "\e[34mTarget ID: {$id}\e[0m\n";
        echo str_repeat("-", 40) . "\n";

        try {
            $data = $crumb->fetchData($id, []);
            
            if (empty($data)) {
                echo "\e[33m⚠️  Warning: Crumb returned empty data.\e[0m\n";
                return;
            }

            // --- NEW: RAW JSON OUTPUT ---
            if ($showJson) {
                echo "\e[1;35m[RAW API RESPONSE]\e[0m\n";
                echo json_encode($data, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES) . "\n";
                echo str_repeat("-", 40) . "\n";
            }

            // --- TRANSFORMED MARKDOWN ---
            echo "\e[1;32m[LLM TRANSFORMED MARKDOWN]\e[0m\n";
            echo $crumb->transform($data);

        } catch (\Exception $e) {
            echo "\e[31m❌ Execution Failed: {$e->getMessage()}\e[0m\n";
        }
    }

    private function resolveCrumbClass(string $name): ?string
    {
        $manifestPath = $this->registryUrl;
        $manifest = json_decode(@file_get_contents($manifestPath), true);
        $id = strtolower($name);
        foreach ($manifest['crumbs'] as $crumb) {
            if ($crumb['id'] == $id) {
                return $crumb['class'];
            }
        }
        return null;
    }
}
