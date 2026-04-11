<?php
namespace ApiCrumbs\Framework\Contracts;

use ApiCrumbs\Framework\Contracts\PressManagerInterface;

class BasePressFleetManager 
{
    protected $managers = [];

    public function addManager(PressManagerInterface $manager) 
    {
        $this->managers[] = $manager;
    }

    public function runNextInQueue(): void 
    {
        foreach ($this->managers as $manager) {
            $scriptPath = $manager->getNextScript();

            if ($scriptPath) {
                echo "🚀 Dispatching [{$manager->name}] -> " . basename($scriptPath) . "\n";
                
                $resultCode = 0;
                passthru("php $scriptPath", $resultCode);

                if ($resultCode === 0) {
                    $manager->saveProgress($scriptPath);
                    echo "✅ Progress Saved. Batch Advanced.\n";
                } else {
                    echo "❌ Script Failed. Cursor remains at last successful position.\n";
                }
                return; // Exit: One script per call
            }
        }
        echo "🏁 Fleet Idle: All managed scripts are fully inked.\n";
    }
}