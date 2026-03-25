<?php

namespace ApiCrumbs\Framework\Contracts;

abstract class BaseRecipe 
{
    protected array $crumbs = [];

    /** The unique key for the context block (e.g., 'companyprofile') */
    abstract public function getName(): string;

    /** Define which Crumbs this recipe needs */
    abstract public function getCrumbSchema(): array;

    /** Define the LLM Instruction for this recipe */
    abstract public function getStitchPattern(): string;

    public function stitch(array $dataByCrumb): string {
        $output = "### INSTRUCTION: " . $this->getStitchPattern() . "\n\n";
        foreach ($dataByCrumb as $crumbName => $content) {
            $output .= $content . "\n";
        }
        return $output;
    }
}