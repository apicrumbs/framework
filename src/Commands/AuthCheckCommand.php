<?php

namespace ApiCrumbs\Framework\Commands;

use GuzzleHttp\Client;

/**
 * AuthCheckCommand - Contributor Validator
 */
class AuthCheckCommand
{
    public function handle(): void
    {
        echo "🔐 \e[1;36mApiCrumbs Authenticator\e[0m\n";
        echo "---------------------------\n";

        $this->checkGithubStatus();
        
        echo "\n\e[36m💡 Tip: To submit Crumbs, ensure your PAT has 'public_repo' scope.\e[0m\n";
    }

    private function checkGithubStatus(): void
    {
        $token = getenv('GITHUB_PAT');
        echo "🐙 GitHub Status: ";

        if (!$token) {
            echo "\e[31mNOT CONFIGURED\e[0m (Missing GITHUB_PAT)\n";
            return;
        }

        $client = new Client([
            'base_uri' => 'https://api.github.com',
            'timeout'  => 5.0,
            'curl' => strncasecmp(PHP_OS, 'WIN', 3) === 0 && defined('CURLSSLOPT_NATIVE_CA') 
                      ? [CURLOPT_SSL_OPTIONS => CURLSSLOPT_NATIVE_CA] : []
        ]);

        try {
            $response = $client->get('user', [
                'headers' => [
                    'Authorization' => 'token ' . $token,
                    'User-Agent'    => 'ApiCrumbs-CLI'
                ]
            ]);

            $user = json_decode($response->getBody(), true);
            $scopes = explode(',', $response->getHeaderLine('X-OAuth-Scopes'));
            
            // Check for required contribution scope
            if (in_array('public_repo', array_map('trim', $scopes)) || in_array('repo', array_map('trim', $scopes))) {
                echo "\e[32mREADY\e[0m (@" . $user['login'] . " | Scope: public_repo)\n";
            } else {
                echo "\e[33mLIMITED\e[0m (@" . $user['login'] . " | Missing 'public_repo' scope)\n";
                echo "   👉 \e[2mSuggest/Submit will fail until scope is added.\e[0m\n";
            }

        } catch (\Exception $e) {
            echo "\e[31mAUTH FAILED\e[0m (Invalid or Expired Token)\n";
        }
    }

}