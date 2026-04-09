<?php

namespace ApiCrumbs\Framework\Commands;

/**
 * BenchCommand - Performance & Token ROI Auditor
 * Scans local folders to build a live archive, then benchmarks the target.
 */
class BenchCommand
{
    private array $archive = [];

    public function handle(array $args): void
    {
        $crumbId = $args[2] ?? null;
        $targetId = $args[3] ?? 'SW1A1AA';

        if (!$crumbId) {
            echo "❌ \e[31mUsage: php vendor/bin/crumb bench [crumb_id] [target_id]\e[0m\n";
            return;
        }

        // 1. Build Local Registry on the fly
        $this->archive = $this->scanLocalCrumbs();

        $crumb = $this->archive[$crumbId] ?? null;
        
        if (!$crumb) {
            echo "❌ \e[31mCrumb '{$crumbId}' not found in src/Crumbs/.\e[0m\n";
            return;
        }

        echo "📊 \e[1;36mBenchmarking Crumb:\e[0m {$crumbId}\n";
        echo "--------------------------------------------------\n";

        // 2. Measure Raw Fetch (Network/Disk)
        $start = microtime(true);
        $context = [];
        $rawData = $crumb->fetchData($targetId, $context);
        $fetchTime = round((microtime(true) - $start) * 1000, 2);

        // 3. Measure Refinement (Stitch Logic)
        $rawWeight = strlen(json_encode($rawData));
        
        $startRefine = microtime(true);
        $refinedMarkdown = $crumb->transform($rawData);
        $refineTime = round((microtime(true) - $startRefine) * 1000, 2);
        
        $refinedWeight = strlen($refinedMarkdown);

        // 4. Calculate ROI
        $compression = $rawWeight > 0 ? round((1 - ($refinedWeight / $rawWeight)) * 100, 1) : 0;
        $tokensSaved = ceil(($rawWeight - $refinedWeight) / 4);

        // 5. High-Contrast Output
        echo "📡 \e[1mFetch Latency:\e[0m   {$fetchTime}ms\n";
        echo "🧠 \e[1mRefine Latency:\e[0m  {$refineTime}ms\n\n";

        echo "📦 \e[1;31mRAW JSON WEIGHT:\e[0m   " . number_format($rawWeight) . " bytes\n";
        echo "✨ \e[1;32mREFINED WEIGHT:\e[0m    " . number_format($refinedWeight) . " bytes\n";
        echo "🚀 \e[1;35mCONTEXT SAVED:\e[0m     {$compression}% (Approx. {$tokensSaved} tokens)\n";
        
        echo "\n\e[36m💡 Refined Markdown Preview:\e[0m\n";
        echo "--------------------------------------------------\n";
        echo $refinedMarkdown . "\n";
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

            $fileName = $file->getPathname();
            $className = $this->resolveNamespace($fileName);
           
            if (class_exists($className)) {
                $id = $fileCategory .'/'. strtolower(str_replace('Crumb.php', '', $file->getFilename()));
                //$id = $this->toSnake(basename($fileName, '.php'));
                $found[$id] = new $className();
            }
           
        }
        return $found;
    }

    private function resolveNamespace(string $path): string {
        $ns = str_replace([getcwd().'/src/', '.php', '/'], ['ApiCrumbs', '', '\\'], $path);
        return str_replace('ApiCrumbsCrumbs', 'ApiCrumbs\\Crumbs', $ns);
    }

    private function toSnake(string $name): string {
        $name = str_replace('Crumb', '', $name);
        return strtolower(preg_replace('/(?<!^)[A-Z]/', '_$0', $name));
    }
}