<?php
require 'vendor/autoload.php';

//use ApiCrumbs\Framework\ApiCrumbs;
//use ApiCrumbs\Crumbs\Geo\PostcodesIoCrumb;
//use ApiCrumbs\Crumbs\Weather\OpenMeteoCrumb;
//use ApiCrumbs\Crumbs\Finance\CoinGeckoCrumb;
//use ApiCrumbs\Crumbs\Business\CompaniesHouseCrumb;
//use ApiCrumbs\Crumbs\Business\CompaniesHouseSicCrumb;
//use ApiCrumbs\Crumbs\Business\CompaniesHouseFilingHistoryCrumb;
//use ApiCrumbs\Crumbs\Free\CambsExpensesCrumb;
use ApiCrumbs\Recipes\Transparency\TransparencyExpensesRecipe;
use ApiCrumbs\RecipeBooks\Transparency\SuffolkCountyCouncilExpenses202501SupplierDossierRecipeBook;
use ApiCrumbs\PrintingPresses\Transparency\TransparencyExpensesPrintingPress;
use ApiCrumbs\Crumbs\Free\CsvIdsCrumb;


$recipeBook = new SuffolkCountyCouncilExpenses202501SupplierDossierRecipeBook();
$printingPress = new TransparencyExpensesPrintingPress(
    (new CsvIdsCrumb(
        [], 
        [
            'source_url' => 'C:/xampp8.2/htdocs/ApiCrumbs/Data/transparency/county-council/suffolk-county-council/expenses/scc-spend-jan-2025.csv',
        ])
    ), 
    'transparency/expenses', 
    $recipeBook, 
    [
        'original_source_url' => 'https://www.suffolk.gov.uk/asset-library/scc-spend-jan-2025.csv', 
        'source_url'   => 'C:/xampp8.2/htdocs/ApiCrumbs/Data/transparency/county-council/suffolk-county-council/expenses/scc-spend-jan-2025.csv', 
        'referenceId'  => 'Suffolk Council Council January 2025 Expenses', 
        'limit'        => 1, 
        'ledger_limit' => 10, 
        'supplier_ledger_mapping' => [
            'supplier'     => 'Supplier Name',
            'amount'       => 'Sub Amount',
            'department'   => 'Service Area',
            'expense_type' => 'Sub-description'
        ],
        'supplier_sector_mix_mapping' => [
            'supplier' => 'Supplier Name',
            'amount'   => 'Sub Amount',
            'dept'     => 'Service Area' // The Council Department
        ],
        'supplier_total_spend_mapping' => [
            'supplier' => 'Supplier Name',
            'amount'   => 'Sub Amount',
            'dept'     => 'Service Area'
        ],
        'supplier_financial_pulse_mapping' => [
            'supplier' => 'Supplier Name',
            'amount'   => 'Sub Amount'
        ],
    ]
);

// 1. Identify the Period from the CSV
$period = "2025-01"; 
$shardName = "suffolk-county-council-expenses-{$period}-supplier-dossier";
$outputDir = "C:/xampp8.2/htdocs/ApiCrumbs/Books/transparency/county-council/suffolk-county-council/{$shardName}";

// 2. Automate the Infrastructure
$printingPress->ensureRemoteShard($shardName, "Suffolk County Council Expenses Ledger: {$period} Snapshot");

$printingPress->initAndSeedShard($outputDir, $shardName);

// 3. Set Protection Rules
$printingPress->protectMainBranch($shardName);

// 4. Run Printing Press
$printingPress->run($outputDir, true);
