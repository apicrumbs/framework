<?php

namespace ApiCrumbs\Framework\Contracts;

interface CrumbInterface
{
    /** The unique key for the context block (e.g., 'companyprofile') */
    public function getName(): string;

    /** 
     * Returns an array of crumb names this crumb depends on.
     * e.g., ['geo_context'] 
     */
    public function getDependencies(): array;
    
    /** The current version of the crumb (e.g., '1.0.0') */
    public function getVersion(): string;

    /**
     * @param string $id The primary search term
     * @param array $context Data already collected by previous crumbs in the stack
     */
    public function fetchData(string $id, array $context = []): array;

    /**
     * THE BATCH ENGINE: Fetch multiple entities in one optimized call.
     * Default implementation in BaseCrumb will just loop fetchData().
     */
    public function fetchBatch(array $ids, array $context = []): array;

    /** 
     * Converts raw array data into LLM-optimized strings.
     * Use this to prune tokens, rename keys, or add system hints.
     */
    public function transform(array $data): string;

    
}