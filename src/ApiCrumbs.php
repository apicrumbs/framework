<?php

namespace ApiCrumbs\Framework;

use ApiCrumbs\Framework\Contracts\CrumbInterface;
use ApiCrumbs\Framework\EnvLoader;
use ApiCrumbs\Framework\ManifestLoader;

// 🔥 The Spark: Boot the Environment before anything else runs
EnvLoader::load(dirname(__DIR__) . '/.env');


class ApiCrumbs
{
    /** @var CrumbInterface[] */
    protected array $crumbs = [];

    /** @var masterContext[] */
    protected array $masterContext = [];

    /** @var manifest[] */
    protected array $manifest = [];

    public function __construct()
    {
        $this->manifest['crumbs'] = ManifestLoader::scanLocalCrumbs();
        $this->manifest['recipes'] = ManifestLoader::scanLocalRecipes();
    }

    public function getCrumbs(): array
    {
        return $this->crumbs;
    }


    /**
     * Prime the engine with a pre-defined Industry Pack
     */
    public function withRecipe(string $recipeId): self
    {
        // 1. Find the recipe in the manifest
        $recipe = null;
        $recipeParts = explode('/', $recipeId);
        //print_r($recipeParts);
        //$this->manifest['recipes' ] = $this->scanLocalRecipes();
        //print_r($this->manifest['recipes']);
        
        if (!$this->manifest['recipes']) {
            throw new \Exception("No Recipes are installed. So [{$recipeId}] not found in Recipes Registry.");
        }

        foreach ($this->manifest['recipes'] as $r) {
        
            if ($r->getName() === $recipeId) {
                $recipe = $r;
                break;
            }
        }

        if (!$recipe) {
            throw new \Exception("Recipe [{$recipeId}] not found in Foundry Registry.");
        }

        $this->currentRecipe = $recipe;

        // 2. Automatically load the required Crumbs for this recipe
        foreach ($recipe->getCrumbSchema() as $crumbId) {
            $this->loadCrumbById($crumbId);
        }

        return $this;
    }

    /**
     * Internal helper to find and instantiate a Crumb from the manifest
     */
    protected function loadCrumbById(string $crumbId): void
    {
        //$this->manifest['crumbs' ] = $this->scanLocalCrumbs();
        //print_r($this->manifest['crumbs']);
        //die();

        foreach ($this->manifest['crumbs'] as $c) {
            //print_r($c->getName());
            //print_r($crumbId);
            //print_r($c);
            
            
            if ($c->getName() === $crumbId) {
                $this->crumbs[$crumbId] = $c;
                break;
            }
        }
    }

     /**
     * Fluent Registration: Allows array provider registration like:
     * $api->withCrumbs(new PostcodeIoCrumb(),new OpenMeteoCrumb($customGuzzle));
     */
    public function withCrumbs(array $crumbs): self 
    {
        foreach ($crumbs as $p) {
            $this->crumbs[$p->getName()] = $p;
        }
        return $this;
    }

    /**
     * Fluent Registration: Allows chaining like:
     * $api->registerCrumb(new PostcodeCrumb())
     *     ->registerCrumb(new OpenMeteoCrumb($customGuzzle));
     */
    public function registerCrumb(CrumbInterface $crumb): self
    {
        // Use the crumb's internal name as the unique key
        $this->crumbs[$crumb->getName()] = $crumb;
        return $this;
    }

    /**
     * The Magic Method: Orchestrates all registered crumbs into 
     * a single, token-efficient Markdown string for LLM injection.
     */
    public function stitch(string|array $targetIds): string 
    {
        $targets = is_array($targetIds) ? $targetIds : [$targetIds];
        $fullMarkdown = "";
        $executionStack = Resolver::sort($this->crumbs);

        foreach ($executionStack as $crumb) {

            // 🚀 THE SPEED BOOST: Fetch all targets for this Crumb at once
            $batchData = $crumb->fetchBatch($targets, $this->masterContext);
            
            foreach ($targets as $id) {
                $rawData = $batchData[$id] ?? [];
                $this->masterContext[$id][$crumb->getName()] = $rawData;
                
                // Add Header only on the first Crumb of each target section
                if ($crumb === reset($executionStack)) {
                    $fullMarkdown .= "## TRAIL: " . strtoupper($id) . PHP_EOL;
                }
                
                $fullMarkdown .= $crumb->transform($rawData) . PHP_EOL;
            
            }
        }

        return trim($fullMarkdown);
    }


    /**
     * Internal logging logic (Sponsoware Grade)
     */
    private function logError(string $crumbName, string $message): void
    {
        $logPath = getcwd() . '/apicrumbs.log';
        $timestamp = date('Y-m-d H:i:s');
        $logEntry = "[{$timestamp}] CRUMB_FAIL: Crumb [{$crumbName}] -> {$message}" . PHP_EOL;
        
        file_put_contents($logPath, $logEntry, FILE_APPEND);
    }
}