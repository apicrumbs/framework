<?php

namespace ApiCrumbs\Framework\Contracts;

use ApiCrumbs\Framework\ApiCrumbs;

abstract class BasePrintingPress 
{
    protected CsvStreamCrumb $source;
    protected ApiCrumbs $engine;
    protected string $recipeId;
    protected BaseRecipeBook $recipeBook;
    protected int $defaultLimit = 5;
    protected array $index = []; // Stores: ['Name' => ['slug' => 'url', 'meta' => '...']]
    protected array $masterContext = [];
    protected array $batchProgress = [];

    /**
     * @param CsvStreamCrumb $source The CSV Streamer (The Ink)
     * @param string $recipeId The Recipe ID (The Template)
     */
    public function __construct(CsvStreamCrumb $source, string $recipeId, BaseRecipeBook $recipeBook, array $context) 
    {
        $this->source = $source;
        $this->recipeId = $recipeId;
        $this->recipeBook = $recipeBook;
        $this->masterContext = $context;
        $this->engine = new ApiCrumbs();
        
        // 📂 Set the LOCAL path for the XAMPP Printing Press
        $this->engine->withContext($context);

        // Prime the engine with the required logical crumbs
        $this->engine->withRecipe($this->recipeId);
    }

    /** Load progress to find the starting point */
    protected function loadBatchProgress(string $outputDir): void {
        $path = $outputDir . DIRECTORY_SEPARATOR . 'batch_progress.json';
        $this->batchProgress = file_exists($path) 
            ? json_decode(file_get_contents($path), true) 
            : ['last_id' => null, 'stats' => ['printed' => 0, 'skipped' => 0]];
    }

    /** Save current progress for the next batch */
    protected function saveBatchProgress(string $outputDir, string $lastId, array $stats): void {
        $data = [
            'last_id' => $lastId,
            'stats'   => $stats,
            'timestamp' => date('Y-m-d H:i:s')
        ];
        file_put_contents($outputDir . DIRECTORY_SEPARATOR . 'batch_progress.json', json_encode($data, JSON_PRETTY_PRINT));
    }

    public function initAndSeedShard(string $outputDir, string $repoName): void 
{
    $git = '"C:\Program Files\Git\bin\git.exe"';
    $org = getenv('GITHUB_ORG');
    $url = "https://github.com/{$org}/{$repoName}.git";

    if (!is_dir($outputDir)) mkdir($outputDir, 0755, true);

    if (!is_dir($outputDir . "/.git")) {
        $nativePath = str_replace('/', DIRECTORY_SEPARATOR, $outputDir);
        echo "📦 Seeding Shard: {$nativePath}\n";

        // Chain: Init -> Remote -> Placeholder -> Commit -> Push
        // Note: We use -u origin master:main to ensure the first push creates 'main'
        $cmd = "cd /D " . escapeshellarg($nativePath) . " && " .
               "{$git} init && " .
               "{$git} remote add origin {$url} && " .
               "echo # {$repoName} > seed.md && " .
               "{$git} add seed.md && " .
               "{$git} commit -m \"Foundry: Initializing Shard\" && " .
               "{$git} push -u origin master:main";

        $output = shell_exec($cmd . " 2>&1");
        echo "💻 Seed Output: " . $output . "\n";
    }
}


    /**
     * Executes Industrial Git Sync
     * @param string $outputDir The local repository path
     * @param string $message Custom commit message
     */
        protected function syncToGitHub(string $outputDir, string $message = "Foundry Print Run"): void 
    {
        $git = '"C:\Program Files\Git\bin\git.exe"'; 
        $nativePath = str_replace('/', DIRECTORY_SEPARATOR, $outputDir);
        $safePath = escapeshellarg($nativePath);

        echo "🔄 Reconciling local and remote shards...\n";

        // 🚀 THE INDUSTRIAL CHAIN:
        // 1. Force stay on 'main' branch
        // 2. Stage and commit all changes (Metadata + Books)
        // 3. Pull from GitHub, allowing unrelated histories (merges the auto-init README)
        // 4. Final push to the remote repository
        $cmd = "cd /D {$safePath} && " .
               "{$git} config core.autocrlf false && " .
               "{$git} checkout -B main && " . 
               "{$git} add . && " .
               "{$git} commit -m " . escapeshellarg($message) . " && " .
               "{$git} pull origin main --rebase --allow-unrelated-histories && " .
               "{$git} push -u origin main";

        $output = shell_exec($cmd . " 2>&1");
        echo "💻 Sync Output: " . $output . "\n";
    }


    /** 
     * The Main Loop with Version-Check Bypass and Force-Print 
     * @param string $outputDir Destination folder
     * @param bool $force If true, ignores version checks and re-prints everything
     */
    public function run(string $outputDir, bool $resume = true): void
    {
        if (!is_dir($outputDir)) mkdir($outputDir, 0755, true);
        
        $originalOuputDir = $outputDir;

        $this->loadBatchProgress($outputDir);
        $lastId = $this->batchProgress['last_id'];
        $foundStart = ($lastId === null || !$resume); // If no last_id, start immediately

        $stats = $resume ? $this->batchProgress['stats'] : ['printed' => 0, 'skipped' => 0];
        $printedInBatch = 0;
        $processedIds = [];
        $totalIds = [];
        $batchLimit = $this->getLimit();

        echo "🔥 RESUME MODE...\n";

        // Stream the CSV row-by-row (Industrial Memory Efficiency)
        foreach ($this->source->stream($this->source->getSourceUrl()) as $row) {
            $id = trim($row[$this->getIdentityKey()]);

            // Skip empty or already printed IDs in this run
            if (empty($id) || in_array($id, $processedIds)) continue;
        
            $firstLetter = strtoupper(substr($id, 0, 1));
            $outputDir = $originalOuputDir . '/' . $firstLetter;

            if (!is_dir($outputDir)) mkdir($outputDir, 0755, true);

            // Save to Sharded Directory (The Warehouse)
            $filename = $this->slugify($id) . ".md";
            $slug = $firstLetter . '/' . $filename;
            $filePath = $outputDir . DIRECTORY_SEPARATOR . $filename;

            // Add to Index (Store high-level stats for the README)
            $this->index[$id] = [
                'slug' => $slug,
                'summary' => $this->getSummaryLine($row) 
            ];

            // Skip everything until we find the last processed ID
            if (!$foundStart) {
                if ($id === $lastId) {
                    $foundStart = true;
                    echo "⏮️ Resuming from: {$id}\n";
                }
                $processedIds[] = $id;
                $stats['skipped']++;
                continue;
            }

            // Stitch the Book via the Recipe Logic
            // This triggers all functional crumbs (Total, Avg, Outliers)
            $stitchedContent = $this->engine->stitch($id);        
            $markdownBook = $this->recipeBook->compose($id, $stitchedContent);
            $markdownBook = $this->cleanMarkdownBook($markdownBook);

            $bom = "\xEF\xBB\xBF";
            file_put_contents($outputDir . DIRECTORY_SEPARATOR . $filename, $bom . $markdownBook);

            $stats['printed']++;
            $processedIds[] = $id;
            $totalIds[] = $id;
            $printedInBatch++;
            
            echo "✅ Printed: {$filename}\n";
            
            if ($printedInBatch >= $batchLimit) {
                $this->saveBatchProgress($originalOuputDir, $id, $stats);
                echo "💾 Batch Checkpoint saved at ID: {$id}\n";
                break;
            }

            //break;
        }

        $stats['total'] = count($processedIds);

        // THE FINALE: Generate the Table of Contents
        $this->generateReadme($originalOuputDir, $stats);

        // 2. 🚀 THE SYNC: Ship the entire folder to the cloud
        // We pass a descriptive message so your GitHub history looks professional
        $commitMsg = "Foundry Print: " . $stats['printed'] . " books updated";
        $this->syncToGitHub($originalOuputDir, $commitMsg);
        
        echo "🏁 Shard Synchronised: https://github.com/" . getenv('GITHUB_ORG') . "/";
    }

    /** Override this in your Specific Press to define the README summary column */
    abstract protected function getSummaryLine(array $row): string;

   /** Generates the README with Delta Activity Signals */
    protected function generateReadme(string $outputDir, array $stats): void 
    {
        $sector = strtoupper(basename($outputDir));
        $isSponsorRun = getenv('FOUNDRY_MODE') === 'SPONSOR';
        
        $md = "# 🏛️ ApiCrumbs Archive: {$sector}\n";
        
        // 🚀 THE DELTA SUMMARY: High-visibility activity indicator
        $isSponsorRun = getenv('FOUNDRY_MODE') === 'SPONSOR';
        $statusColor = $isSponsorRun ? 'emerald' : 'orange';
        $statusLabel = $isSponsorRun ? 'VERIFIED_FRESH' : 'STATIC_SNAPSHOT';
        $rawDate = date('Y-m-d');
        $safeDate = str_replace('-', '--', $rawDate);

        $md .= "![Last Sync](https://shields.io/badge/Synchronised-" . $safeDate . "-blue?style=for-the-badge) ";
        $md .= "![Status](https://shields.io/badge/{$statusLabel}-{$statusColor}?style=for-the-badge) ";
        $md .= "![Delta](https://img.shields.io/badge/" . $stats['printed'] . "_UPDATED-emerald?style=for-the-badge)\n\n";

        $md .= "📦 **Books in Shard:** {$stats['total']} | 🛡️ **Tier:** " . ($isSponsorRun ? 'PREMIUM_FRESH' : 'PUBLIC_SNAPSHOT') . "\n";
        $md .= "⚡ **Latest Press Run:** {$stats['printed']} printed / {$stats['skipped']} skipped (up-to-date).\n\n";
        
        $md .= "## 📚 Table of Contents\n";
        $md .= "| Status | Last Updated | Entity / ID | Summary Insight | Access Book |\n";
        $md .= "| :--- | :--- | :--- | :--- | :--- |\n";

        ksort($this->index);
        foreach ($this->index as $id => $data) {
            //$date = date('Y-m-d');
            $timestamp = $this->batchProgress['timestamp'] ?? 'Never';
            //$md .= "| 🟢 | `$date` | **$id** | {$data['summary']} | [View Context ↗](./{$data['slug']}) |\n";
            $md .= "| 🟢 | `{$timestamp}` | **$id** | {$data['summary']} | [View ↗](./{$data['slug']}) |\n";
        }

        $md .= "\n\n---\n";
        $md .= "🚀 **STALE DATA?** Public snapshots are monthly. [Sponsor apicrumbs.com](https://apicrumbs.com) for the **Daily Delta** (Hourly updates).";
        
        file_put_contents($outputDir . "/README.md", $md);
        echo "   📄 README.md Updated: {$stats['printed']} changes recorded.\n";
    }
    
    private function cleanMarkdownBook($markdownBook)
    {
        // 1. Manually replace the broken sequences with a placeholder
        // We use a placeholder to protect the Pound sign from the cleaner
        $markdownBook = str_replace(['£', 'Â£', "\xc2\xa3"], '___POUND___', $markdownBook);

        // 2. Remove all non-printable/junk bytes (0-31 and 127-255)
        // BUT we keep Newlines (\x0A) and Carriage Returns (\x0D)
        $markdownBook = preg_replace('/[\x00-\x09\x0B\x0C\x0E-\x1F\x7F-\xFF]/', '', $markdownBook);

        // 3. Swap the placeholder back for a clean, properly formatted UTF-8 Pound sign
        $markdownBook = str_replace('___POUND___', "\xc2\xa3", $markdownBook);

        return $markdownBook;
    }
    /** Clean filenames for GitHub/Web compatibility */
    protected function slugify(string $text): string {
        return strtolower(preg_replace('/[^a-z0-9]/i', '-', $text));
    }

    abstract protected function getIdentityKey(): string;

    public function getLimit(): string
    {
        return $this->masterContext['limit'] ?? $this->defaultLimit;
    }

    /**
     * Enforces branch protection on the main branch via GitHub REST API
     * @param string $repoName The name of the shard repository
     */
    public function protectMainBranch(string $repoName): void 
    {
        $githubToken = getenv('GITHUB_TOKEN');
        $orgName = getenv('GITHUB_ORG');
        
        $isWindows = strncasecmp(PHP_OS, 'WIN', 3) === 0;

        $defaultConfig = [
            'base_uri' => 'https://api.github.com',
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

        $client = new \GuzzleHttp\Client($defaultConfig);

        //$client = new \GuzzleHttp\Client(['base_uri' => 'https://api.github.com/']);

        echo "🛡️ Enforcing Branch Protection on {$repoName}/main...\n";

        try {
            $client->put("repos/{$orgName}/{$repoName}/branches/main/protection", [
                'headers' => [
                    'Authorization' => "token {$githubToken}",
                    'Accept'        => 'application/vnd.github+json',
                ],
                'json' => [
                    'required_status_checks' => null,
                    'enforce_admins'         => false, // 🔓 Allow the Admin (your Token) to push directly
                    'required_pull_request_reviews' => [
                        'dismiss_stale_reviews'      => true,
                        'required_approving_review_count' => 0 // 🚀 Set to 0 to allow direct pushes
                    ],
                    'restrictions'           => null,
                    'required_linear_history'=> true,
                    'allow_force_pushes'     => false,
                    'allow_deletions'        => false,
                    'required_conversation_resolution' => true,
                    'required_signatures'    => true 
                ]

            ]);
            echo "✅ Main branch protection enabled.\n";
        } catch (\Exception $e) {
            echo "⚠️ Could not set branch protection: " . $e->getMessage() . "\n";
        }
    }

    /**
     * Detects and ensures a GitHub Shard exists for the current month
     * @param string $repoName e.g., "data-cambs-2026-04"
     */
    public function ensureRemoteShard(string $repoName, string $description): void 
    {
        $githubToken = getenv('GITHUB_TOKEN');
        $orgName = getenv('GITHUB_ORG'); // e.g., "apicrumbs-foundry"

        echo "🔍 Checking Shard Registry for: {$repoName}...\n";

        $isWindows = strncasecmp(PHP_OS, 'WIN', 3) === 0;

        $defaultConfig = [
            'base_uri' => 'https://api.github.com',
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

        $client = new \GuzzleHttp\Client($defaultConfig);
        
        try {
            // 1. Check if repo exists
            $client->get("repos/{$orgName}/{$repoName}", [
                'headers' => ['Authorization' => "token {$githubToken}"]
            ]);
            echo "✅ Shard found. Proceeding to Print.\n";
        } catch (\GuzzleHttp\Exception\ClientException $e) {
            if ($e->getResponse()->getStatusCode() === 404) {
                // 2. 🚀 AUTO-CREATE: Shard not found, let's build it
                echo "🏗️  NEW SHARD DETECTED: Creating {$repoName} on GitHub...\n";
                
                $client->post("orgs/{$orgName}/repos", [
                    'headers' => ['Authorization' => "token {$githubToken}"],
                    'json' => [
                        'name' => $repoName,
                        'description' => $description,
                        'private' => false,
                        'has_issues' => false,
                        'has_projects' => false,
                        'auto_init' => false // Creates the first commit + README
                    ]
                ]);
                
                echo "🚀 Shard Provisioned. Waiting for GitHub propagation (5s)...\n";
                sleep(5);
            }
        }
    }
    public function initLocalGit(string $outputDir, string $repoName): void 
    {
        $git = '"C:\Program Files\Git\bin\git.exe"'; 
        $orgName = getenv('GITHUB_ORG');
        $gitUrl = "https://github.com/{$orgName}/{$repoName}.git";

        // 🚀 THE FIX 1: Ensure the folder exists BEFORE we try to enter it
        if (!is_dir($outputDir)) {
            mkdir($outputDir, 0755, true);
        }

        if (!is_dir($outputDir . DIRECTORY_SEPARATOR . ".git")) {
            
            // 🚀 THE FIX 2: Get the absolute real path with Windows backslashes
            $nativePath = realpath($outputDir);
            if (!$nativePath) {
                // If realpath fails, it's a major permission or path length issue
                $nativePath = str_replace('/', DIRECTORY_SEPARATOR, $outputDir);
            }
            
            echo "📦 Initialising local Git at {$nativePath}...\n";

            // 🚀 THE FIX 3: Use /D and CD instead of pushd for XAMPP stability
            $cmd = "cd /D " . escapeshellarg($nativePath) . " && " .
                   "{$git} init && " .
                   "{$git} remote add origin {$gitUrl} && " .
                   "{$git} checkout -b main";

            $output = shell_exec($cmd . " 2>&1");
            echo "💻 OS Output: " . $output . "\n";
            
            if (is_dir($outputDir . DIRECTORY_SEPARATOR . ".git")) {
                echo "✅ Git repository created successfully.\n";
            } else {
                echo "❌ STILL FAILED. Path exists? " . (is_dir($outputDir) ? 'YES' : 'NO') . "\n";
            }
        }
    }




}
