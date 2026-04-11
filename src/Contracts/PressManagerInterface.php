<?php

namespace ApiCrumbs\Framework\Contracts;

interface PressManagerInterface
{
    /** Find the next script using the Batch Progress cursor */
    public function getNextScript(): ?string;

    protected function loadBatchProgress(): array;

    public function saveProgress(string $currentPath): void;
    
}