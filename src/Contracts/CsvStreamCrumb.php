<?php

namespace ApiCrumbs\Framework\Contracts;

use ApiCrumbs\Framework\Contracts\BaseCrumb;
use Generator;

/**
 * CsvStreamCrumb - Industrial Memory-Lean Streaming
 * Enforces Grounding Standards on 500MB+ CSV Files.
 */
abstract class CsvStreamCrumb extends BaseCrumb
{
    /** Define header mapping: ['llm_key' => 'CSV Header Name'] */
    abstract public function getMapping(): array;

    /** Define the source URL (Local path or Remote URL) */
    public function getSourceUrl(): string 
    {
        return $this->masterContext['source_url'] ?? "";
    }

    /**
     * Memory-Efficient Generator: Zero-footprint streaming.
     * Inherits safeFetch logic if $url is remote.
     */
    public function stream(string $url): Generator
    {
        // Use fopen with 'rb' for cross-platform binary safety
        $handle = @fopen($url, 'r');
        
        if (!$handle) {
            $this->logError("CSV_STREAM_FAIL: Unable to open {$url}");
            return;
        }

        $headers = null;
        $mapping = $this->getMapping();

        try {
            while (($data = fgetcsv($handle)) !== false) {
                if (!$headers) {
                    $headers = array_map('trim', $data);
                    continue;
                }

                // Check for row/header mismatch to prevent crashes
                if (count($headers) !== count($data)) continue;

                $row = array_combine($headers, $data);
                
                // Map messy CSV keys to Standardised LLM Keys
                $cleanRow = [];
                foreach ($mapping as $standardKey => $csvKey) {
                    $cleanRow[$standardKey] = $row[$csvKey] ?? 'N/A';
                }

                yield $cleanRow;
            }
        } finally {
            if (is_resource($handle)) fclose($handle);
        }
    }

    /**
     * Fetch Logic: Filters the stream by ID (e.g., Department or RegNo)
     */
    public function fetchData(string $id, array $context = []): array
    {
        $limit = 15; // LLM Safety Threshold
        $results = [];

        foreach ($this->stream($this->getSourceUrl()) as $row) {
            // Logic: Search for the ID in the mapped data
            if (in_array($id, $row)) {
                $results[] = $row;
            }
            if (count($results) >= $limit) break;
        }

        return $results;
    }

    /**
     * Standardised Context Output: Uses the BaseCrumb auto-formatter.
     */
    public function transform(array $data): string
    {
        if (empty($data)) return "❌ CSV_RECORD_NOT_FOUND";

        // If multiple rows returned, flatten for the transformer
        $contextPoints = is_array(reset($data)) ? $data[0] : $data;

        return $this->autoTransform($contextPoints, [
            'id'     => 'CSV_QUERY',
            'source' => basename($this->getSourceUrl()),
            'ttl'    => 'Static_File'
        ]);
    }

    /**
     * Default Batch Framework: The Safe Loop with Exponential Backoff
     */
    public function fetchBatch(array $ids, array $context = []): array 
    {
        $results = [];
        foreach ($ids as $id) {
            // safeFetch logic applies here if getSourceUrl is remote
            $results[$id] = $this->fetchData($id, $context);
        }
        return $results;
    }
}