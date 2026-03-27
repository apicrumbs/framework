<?php

namespace ApiCrumbs\Framework\Contracts;

use GuzzleHttp\Client;
use GuzzleHttp\Exception\GuzzleException;

/**
 * BaseCrumb - The Hardened Foundation
 * Features: 2FA Handshake, PII Redaction, Exponential Backoff, XAMPP SSL Fix.
 */
abstract class BaseCrumb implements CrumbInterface
{
    protected Client $client;

    /** @var masterContext[] */
    protected array $masterContext = [];
    
    // --- Enterprise & Security Settings ---
    public bool $requires2FA = false; 
    protected string $piiLevel = 'STRICT'; // Options: NONE, STRICT
    
    // --- Resilience & Throttling ---
    protected int $maxRetries = 3;
    protected int $baseDelayMicros = 500000; // 0.5s starting backoff
    protected int $throttleMicros = 1000;    // Minimum heartbeat between calls

    public function __construct(array $guzzleConfig = [], array $context = [])
    {
        $isWindows = strncasecmp(PHP_OS, 'WIN', 3) === 0;

        $defaultConfig = [
            'timeout' => 30,
            'headers' => [
                'User-Agent' => 'ApiCrumbs-Foundry/2.1',
                'Accept'     => 'application/json',
            ],
            // 🛡️ The XAMPP Fix: Native CA Store for Windows handshakes
            'curl' => $isWindows && defined('CURLSSLOPT_NATIVE_CA') 
                ? [CURLOPT_SSL_OPTIONS => CURLSSLOPT_NATIVE_CA] 
                : []
        ];

        $this->client = new Client(array_merge($defaultConfig, $guzzleConfig));

        $this->piiLevel = getenv('PII_REDACTION_LEVEL');

        $this->masterContext = $context;        
    }

    /**
     * 🚀 Exponential Backoff + Throttled Fetch
     */
    protected function safeFetch(string $url, array $options = []): array
    {
        $retries = 0;

        while ($retries <= $this->maxRetries) {
            try {
                // 1. Mandatory Heartbeat
                usleep($this->throttleMicros);
                
                $response = $this->client->get($url, $options);
                return json_decode($response->getBody()->getContents(), true) ?? [];
                
            } catch (GuzzleException $e) {
                $code = $e->getCode();

                // 2. Exponential Backoff on Rate Limits (429) or Server Errors (503)
                if ($code === 429 || $code === 503) {
                    $retries++;
                    if ($retries > $this->maxRetries) break;

                    // Formula: (Base * 2^retries) + Jitter
                    $delay = ($this->baseDelayMicros * pow(2, $retries)) + rand(0, 100000);
                    $this->logError("BACKOFF: Retrying in " . ($delay/1000000) . "s due to 429/503.");
                    usleep($delay);
                    continue;
                }

                $this->logError($e->getMessage());
                return [];
            }
        }
        return [];
    }

    /**
     * 🛡️ 2FA Handshake Logic
     */
    protected function authenticate2FA(string $endpoint, string $otp): bool
    {
        try {
            $response = $this->client->post($endpoint, [
                'json' => ['otp_token' => $otp]
            ]);
            return $response->getStatusCode() === 200;
        } catch (GuzzleException $e) {
            $this->logError("2FA_FAIL: " . $e->getMessage());
            return false;
        }
    }

    /**
     * 🧹 PII Redactor: Scrubs sensitive data using regex
     */
    protected function redact(array $data): array
    {
        if ($this->piiLevel <> 'STRICT') return $data;

        $patterns = [
            'emails' => '/[a-z0-9._%+-]+@[a-z0-9.-]+\.[a-z]{2,4}/i',
            'phones' => '/(\+44\s?7\d{3}|\(?07\d{3}\)?)\s?\d{3}\s?\d{3}/',
            'phone_std' => '/\b\d{7,11}\b/',
            'phone_uk' => '/\+44\s?\(?0?\)?(?:\s?\d){10}\b/',
            'phone_us' => '/\(?\b[2-9]\d{2}[-.)\s]*\d{3}[-.\s]*\d{4}\b/',
            'phone_us_general' => '/(?:\+\d{1,2}\s?)?\(?\d{3}\)?[\s.-]?\d{3}[\s.-]?\d{4}/',
            'ni_nums' => '/[A-CEGHJ-PR-TW-Z][A-CEGHJ-NPR-TW-Z]\s*\d{2}\s*\d{2}\s*\d{2}\s*[A-D]/i',
            'ni_general' => '/[A-Z]{2}\s?\d{2}\s?\d{2}\s?\d{2}\s?[A-D]/',
        ];

        foreach ($data as $key => &$row) {
            foreach ($patterns as $type => $pattern) {
                $row = preg_replace($pattern, "[REDACTED_{$type}]", $row);
            }
        }

        return $data;
    }

    /**
     * 🧩 Standardised Grounding Wrapper
     */
    protected function wrap(string $refinedData, array $meta = []): string
    {
        $scrubbed = ($this->piiLevel !== 'NONE') ? " [PII_CLEAN]" : " [RAW]";
        $id = $meta['id'] ?? 'N/A';
        
        return "### " . strtoupper($this->getName()) . " (ID: $id)" . PHP_EOL . 
               trim($refinedData) . PHP_EOL . 
               "---" . PHP_EOL .
               "[SOURCE: " . ($meta['source'] ?? 'Registry') . " | REF: $id]" . PHP_EOL .
               "[SECURITY: {$this->piiLevel}$scrubbed | ENGINE=ApiCrumbs_v1]" . PHP_EOL;
    }

    /**
     * Default Batch Engine: The Safe Loop
     */
    public function fetchBatch(array $ids, array $context = []): array 
    {
        $results = [];
        foreach ($ids as $id) {
            $results[$id] = $this->fetchData($id, $context);
        }
        return $results;
    }

    /**
     * Default Dependencies (Override in specific crumbs)
     */
    public function getDependencies(): array { return []; }

    /**
     * Centralised Logging
     */
    protected function logError(string $message): void
    {
        $log = getcwd() . '/apicrumbs.log';
        $entry = "[" . date('Y-m-d H:i:s') . "] 🍪 Crumb [{$this->getName()}]: $message" . PHP_EOL;
        file_put_contents($log, $entry, FILE_APPEND);
    }

    /**
     * AUTO-TRANSFORM: The Standardised Context Engine
     * Automatically generates high-signal Markdown from a key-value map.
     * 
     * @param array $dataPoints Simple associative array [Label => Value]
     * @param array $meta [id, source, ttl]
     */
    protected function autoTransform(array $dataPoints, array $meta = []): string
    {
        // 1. Scrub PII before any formatting
        $safeData = $this->redact($dataPoints);
        
        $markdown = "";
        foreach ($safeData as $label => $value) {
            // Format the label: "net_income" -> "Net Income"
            $formattedLabel = ucwords(str_replace(['_', '-'], ' ', $label));
            
            if (str_starts_with($formattedLabel, '#')) {
                // Handle nested arrays or empty values gracefully
                $markdown .= "{$formattedLabel}" . PHP_EOL;
                continue;
            }

            if ($formattedLabel && !is_numeric($formattedLabel)) {
                // Handle nested arrays or empty values gracefully
                $displayValue = is_array($value) ? implode(', ', $value) : ($value ?: 'N/A');
                $markdown .= "- {$formattedLabel}: {$displayValue}" . PHP_EOL;
                continue;
            }

            // Handle nested arrays or empty values gracefully
            $markdown .= "- {$value}" . PHP_EOL;
        
        }

        // 2. Wrap in the Global Grounding Standard
        return $this->wrap($markdown, [
            'id'     => $meta['id'] ?? 'N/A',
            'source' => $meta['source'] ?? 'Registry Provider',
            'ttl'    => $meta['ttl'] ?? '30d'
        ]);
    }

    /**
     * GET CONTEXT: 
     * Return the current master context memory
     */
    public function getContext(): array
    {
        return $this->masterContext;
    }

}