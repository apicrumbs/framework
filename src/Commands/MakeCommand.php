<?php

namespace ApiCrumbs\Framework\Commands;

/**
 * MakeCommand - Interactive Crumb Scaffolder
 * Generates API or CSV based crumbs with tier-specific namespaces.
 */
class MakeCommand
{
    public function handle(array $args): void
    {
        $name = $args[2] ?? null;
        $category = $args[3] ?? null;
        $isCsv = in_array('--csv', $args);

        if (!$name || !$category) {
            echo "❌ \e[31mUsage: php vendor/bin/crumb make [Name] [Category] [--csv]\e[0m\n";
            return;
        }

        $name = ucwords($name);
        $name = str_replace(" ", "", $name);
        
        // 1. Detect Flags
        $isCsv = in_array('--csv', $args);
        $tier = $category;
        $className = ucfirst($name) . "Crumb";
        $namespace = "ApiCrumbs\\Crumbs\\" . ucfirst($tier);
        $directory = getcwd() . "/src/Crumbs/" . ucfirst($tier);
        
        $filePath = "{$directory}/{$className}.php";

        if (file_exists($filePath)) {
            echo "\e[33m⚠️  Warning: {$className} already exists at {$filePath}\e[0m\n";
            exit(1);
        }
        
        // 2. Interactive Wizard for Metadata       
        $name = str_replace('_', '', $name);
        $name = str_replace('-', '', $name);
        $name = str_replace('/', '', $name);
        $id = strtolower($name);
        
        echo "Enter Dependencies (comma-separated, or leave blank, ie geo/postcodesio, weather/openmeteo):\n> ";
        $depsInput = trim(fgets(STDIN));
        $deps = !empty($depsInput) ? array_map('trim', explode(',', $depsInput)) : [];
        $formattedDeps = "['" . implode("', '", $deps) . "']";

        // 3. Generate Content
        if (!is_dir($directory)) mkdir($directory, 0755, true);

        $categoryAndId = strtolower($tier . '/' . $id);

        $stub = $isCsv 
            ? $this->getCsvStub($namespace, $className, $categoryAndId, $formattedDeps) 
            : $this->getApiStub($namespace, $className, $categoryAndId, $formattedDeps);

        if (file_put_contents($filePath, $stub)) {
            echo "\e[32m✨ Success! Created at: {$filePath}\e[0m\n";
            echo "💡 Next: Define your " . ($isCsv ? 'mapping' : 'fetchData') . " logic.\n";
        }
    }

    private function resolveTier(array $args): string
    {
        if (in_array('--global', $args)) return 'global';
        if (in_array('--pro', $args)) return 'pro';
        return 'free';
    }

    private function getApiStub($ns, $class, $id, $deps): string
    {
        return <<<PHP
<?php

namespace {$ns};

use ApiCrumbs\Framework\Contracts\BaseCrumb;

class {$class} extends BaseCrumb
{
    public function getName(): string { return '{$id}'; }
    public function getVersion(): string { return '1.0.0'; }
    public function getDependencies(): array { return {$deps}; }

    public function fetchData(string \$id, array \$context = []): array
    {
        // TODO: Implement Guzzle fetch logic
        return [];
    }

    /**
     * Default Batch: The "Safe Loop"
     */
    public function fetchBatch(array \$ids, array \$context = []): array 
    {
        \$results = [];
        foreach (\$ids as \$id) {
            \$results[\$id] = \$this->fetchData(\$id, \$context[\$id] ?? []);
        }
        return \$results;
    }

    public function transform(array \$data): string
    {
        if (empty(\$data)) return "";
        return "### 🍪 " . strtoupper(\$this->getName()) . PHP_EOL . "---" . PHP_EOL;
    }
}
PHP;
    }

    private function getCsvStub($ns, $class, $id, $deps): string
    {
        return <<<PHP
<?php

namespace {$ns};

use ApiCrumbs\Framework\Contracts\CsvStreamCrumb;

class {$class} extends CsvStreamCrumb
{
    public function getName(): string { return '{$id}'; }
    public function getVersion(): string { return '1.0.0'; }
    public function getDependencies(): array { return {$deps}; }

    public function getSourceUrl(): string 
    {
        return "https://example.com";
    }

    public function getMapping(): array
    {
        return [
            'id_column' => 'ID',
            'val_column' => 'Value'
        ];
    }

    /**
     * Default Batch: The "Safe Loop"
     */
    public function fetchBatch(array \$ids, array \$context = []): array 
    {
        \$results = [];
        foreach (\$ids as \$id) {
            \$results[\$id] = \$this->fetchData(\$id, \$context[\$id] ?? []);
        }
        return \$results;
    }

    public function transform(array \$data): string
    {
        if (empty(\$data)) return "";
        return "### 🍪 " . strtoupper(\$this->getName()) . PHP_EOL . "---" . PHP_EOL;
    }
}
PHP;
    }
}