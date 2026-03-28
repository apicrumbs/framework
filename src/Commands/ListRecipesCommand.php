<?php

namespace ApiCrumbs\Framework\Commands;

class ListRecipesCommand
{
    private string $manifestUrl = "https://raw.githubusercontent.com/apicrumbs/archive/refs/heads/main/manifest.json";

    public function handle(array $args): void
    {
        echo "\e[1;34m🔍 Fetching Recipe Registry...\e[0m\n\n";

        $json = @file_get_contents($this->manifestUrl);
        if (!$json) {
            echo "\e[31m❌ Error: Could not connect to the Registry.\e[0m\n";
            exit(1);
        }

        $manifest = json_decode($json, true);
        
        // Table Header
        echo str_pad("TIER", 8) . str_pad("ID", 25) . str_pad("NAME", 20) . "CAPABILITIES\n";
        echo str_repeat("-", 80) . "\n";

        foreach ($manifest['recipes'] as $p) {
            
            // Color Logic
            $tierTag = "\e[36m[FREE]\e[0m ";
            $idColor = "\e[32m"; // Grey out Pro IDs, Green for Free
            
            echo str_pad($tierTag, 17) . 
                 str_pad($idColor . $p['id'] . "\e[0m", 34) . 
                 str_pad($p['name'], 20) . 
                 "\e[2m" . $p['difficulty'] . "\e[0m\n";
        }

        echo "\n\e[1mUsage:\e[0m  php crumb install:recipe transparency/suffolkcountycouncilexpenses\n";
        echo "\e[1mPro:\e[0m    https://github.com\n\n";
    }
}