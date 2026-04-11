<?php
namespace ApiCrumbs\Framework\Contracts;

class BasePressManager implements PressManagerInterface
{
    public $name;
    protected $scripts = [];
    protected $outputDir;

    public function __construct($name, array $scripts, string $outputDir) 
    {
        $this->name = $name;
        $this->scripts = $scripts;
        $this->outputDir = $outputDir;
    }

    /** Find the next script using the Batch Progress cursor */
    public function getNextScript(): ?string 
    {
        $progress = $this->loadBatchProgress();
        $lastId = $progress['last_id'];

        // If no progress, start at the beginning
        if ($lastId === null) {
            return $this->scripts[0] ?? null;
        }

        // Find the index of the last run script and return the next one
        foreach ($this->scripts as $index => $path) {
            if ($path === $lastId) {
                return $this->scripts[$index + 1] ?? $this->scripts[0];
            }
        }

        return $this->scripts[0] ?? null; // End of list
    }

    public function loadBatchProgress(): array 
    {
        $path = $this->outputDir . DIRECTORY_SEPARATOR . 'batch_progress.json';
        return file_exists($path) 
            ? json_decode(file_get_contents($path), true) 
            : ['last_id' => null, 'stats' => ['printed' => 0]];
    }

    public function saveProgress(string $currentPath): void 
    {
        $progress = $this->loadBatchProgress();
        $progress['last_id'] = $currentPath;
        $progress['stats']['printed']++;
        $progress['timestamp'] = date('Y-m-d H:i:s');

        if (!is_dir($this->outputDir)) mkdir($this->outputDir, 0777, true);
        file_put_contents(
            $this->outputDir . DIRECTORY_SEPARATOR . 'batch_progress.json', 
            json_encode($progress, JSON_PRETTY_PRINT)
        );
    }
}