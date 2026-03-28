<?php

namespace ApiCrumbs\Framework\Commands;

use GuzzleHttp\Client;
use GuzzleHttp\Exception\GuzzleException;

/**
 * SuggestCommand - Direct GitHub Issue Injector
 * Usage: php crumb suggest "HMRC VAT" finance "Need to verify UK VAT status"
 */
class SuggestCommand
{
    public function handle(array $args): void
    {
        $name     = $args[2] ?? null;
        $category = $args[3] ?? 'general';
        $reason   = $args[4] ?? 'No description provided.';
        $token    = getenv('GITHUB_PAT'); // User's GitHub PAT

        if (!$name) {
            echo "❌ \e[31mUsage: php crumb suggest \"[Name]\" [Category] \"[Reason]\"\e[0m\n";
            return;
        }

        if (!$token) {
            echo "❌ \e[31mGitHub Token Missing:\e[0m Add CRUMB_PRO_TOKEN to your .env\n";
            echo "👉 Create one at: https://github.com\n";
            return;
        }

        echo "🚀 \e[1;36mSubmitting Proposal to Global Registry...\e[0m\n";

        $client = new Client([
            'base_uri' => 'https://api.github.com',
            'timeout'  => 10.0,
            // 🛡️ XAMPP SSL Fix
            'curl' => strncasecmp(PHP_OS, 'WIN', 3) === 0 && defined('CURLSSLOPT_NATIVE_CA') 
                      ? [CURLOPT_SSL_OPTIONS => CURLSSLOPT_NATIVE_CA] : []
        ]);

        try {
            $response = $client->post('repos/apicrumbs/archive/issues', [
                'headers' => [
                    'Authorization' => 'token ' . $token,
                    'Accept'        => 'application/vnd.github.v3+json',
                    'User-Agent'    => 'ApiCrumbs-CLI'
                ],
                'json' => [
                    'title'  => "[REQUEST] New Crumb: {$name}",
                    'body'   => "### 🍪 Crumb Suggestion\n**Category:** {$category}\n**Goal:** {$reason}\n\n_Submitted via ApiCrumbs CLI_",
                    'labels' => ['voting-pool', 'proposed', $category]
                ]
            ]);

            $data = json_decode($response->getBody(), true);
            
            echo "✅ \e[32mSuccess!\e[0m Your suggestion is live.\n";
            echo "🔗 \e[34mView Issue:\e[0m {$data['html_url']}\n";
            echo "🗳️  \e[2mRoadmap Sponsors can now vote on this request.\e[0m\n";

        } catch (GuzzleException $e) {
            echo "❌ \e[31mGitHub API Error:\e[0m " . $e->getMessage() . "\n";
        }
    }
}