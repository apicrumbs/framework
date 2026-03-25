<?php

namespace ApiCrumbs\Framework\Commands;

class LogsCommand
{
    private string $logFile = 'apicrumbs.log';

    public function handle(array $args): void
    {
        // Check for --clear flag
        if (in_array('--clear', $args)) {
            $this->clear();
            return;
        }

        if (!file_exists($this->logFile)) {
            echo "✨ \e[32mNo logs found. All systems 100% healthy!\e[0m\n";
            return;
        }

        $logLines = file($this->logFile, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
        $errorBreakdown = [];

        foreach ($logLines as $line) {
            // Regex to extract: [timestamp] 🍪 CRUMB_FAIL: Crumb [name] -> message
            if (preg_match('/Crumb \[(.*?)\] -> (.*)/', $line, $matches)) {
                $name = $matches[1];
                $msg  = $matches[2];
                $errorBreakdown[$name]['count'] = ($errorBreakdown[$name]['count'] ?? 0) + 1;
                $errorBreakdown[$name]['last_msg'] = $msg;
            }
        }

        $this->renderDashboard(count($logLines), $errorBreakdown);
    }

    private function clear(): void
    {
        if (file_exists($this->logFile)) {
            unlink($this->logFile);
            echo "🧹 \e[32mLog file cleared successfully.\e[0m\n";
        } else {
            echo "ℹ️  No log file found to clear.\e[0m\n";
        }
    }

    private function renderDashboard(int $total, array $breakdown): void
    {
        echo "\e[1m🍪 ApiCrumbs Health Dashboard\e[0m\n";
        echo "-------------------------------\n";
        echo "Total Failed Executions: \e[31m{$total}\e[0m\n\n";

        foreach ($breakdown as $name => $stats) {
            echo "- \e[33m{$name}\e[0m: {$stats['count']} failures (Last: {$stats['last_msg']})\n";
        }

        echo "\n\e[36m💡 Tip: Run 'php foundry logs --clear' to reset this list.\e[0m\n";
    }
}
