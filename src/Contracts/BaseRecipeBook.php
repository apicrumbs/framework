<?php

namespace ApiCrumbs\Framework\Contracts;

/**
 * BaseRecipeBook - The Industrial Publisher
 * Standardises the "Book" format for LLM Agents and Search Indexers.
 */
abstract class BaseRecipeBook
{
    public function __construct()
    {
        
    }

    /**
     * Compose the final Markdown "Book"
     * Combines YAML Front-Matter with the Stitched Recipe content.
     */
    public function compose(string $id, string $stitchedContent, array $extraMeta = []): string
    {
        $yaml = $this->buildFrontMatter($id, $extraMeta);
        $body = $this->formatBody($stitchedContent);
        $footer = $this->getFooter();

        return $yaml . PHP_EOL . $body . PHP_EOL . $footer;
    }

    /**
     * Generates Gemini-ready YAML Metadata
     */
    protected function buildFrontMatter(string $id, array $extraMeta): string
    {
        $meta = array_merge([
            'title'             => "{$this->getPrefix()}: {$id}",
            'entity_id'         => $id,
            'category'          => $this->getCategory(),
            'recipe_id'         => $this->getRecipeId(),
            'recipe_version'    => $this->getVersion(),
            'generated_by'      => 'ApiCrumbs_Foundry_v2',
            'last_updated'      => date('c'),
            'status'            => 'Grounded_Truth',
            'schema'            => $this->getSchemaVersion()
        ], $extraMeta);

        $output = "---" . PHP_EOL;
        foreach ($meta as $key => $value) {
            // Standard YAML string escaping for safety
            $val = is_string($value) ? "\"$value\"" : $value;
            $output .= "- {$key}: {$val}" . PHP_EOL;
        }
        $output .= "---" . PHP_EOL;

        return $output;
    }

    /**
     * Injects the standard AI grounding structure into the body
     */
    protected function formatBody(string $content): string
    {
        return "# " . strtoupper($this->getCategory()) . " REGISTRY" . PHP_EOL . $content;
    }

    /**
     * Standard branding and sponsorship hook
     */
    protected function getFooter(): string
    {
        return PHP_EOL . "---" . PHP_EOL . 
               "Generated via ApiCrumbs Foundry. " .
               "Visit https://apicrumbs.com for real-time updates.";
    }

    abstract protected function getPrefix(): string;        // e.g. "Safety Profile"
    abstract protected function getCategory(): string;      // e.g. "Geospatial"
    abstract protected function getSchemaVersion(): string; // e.g. "v1.2"
    abstract public function getRecipeId(): string;      // e.g. "transparency/expenses"
    abstract public function getVersion(): string; // e.g. "1.0.0"
}