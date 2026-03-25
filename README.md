🍪 ApiCrumbs Framework


The Wikipedia of Context for the PHP Ecosystem.

ApiCrumbs is a community-driven Data Logistics Layer. It solves the "Dirty Data" problem by refining messy APIs, 500MB+ CSVs, and web scrapes into High-Signal Markdown "Crumbs" designed specifically for LLM context windows.

Our mission: 10,000 community-maintained Crumbs to ground AI reasoning in reality.

* * * * *

🚀 Quick Start (XAMPP 8.2 Optimized)

ApiCrumbs is built for local-first development. It natively solves the "cURL Error 60" SSL handshake issue on Windows using the Native CA Store.

1\. Installation

bash

```
composer require apicrumbs/core

```



2\. Initialize the Framework

php

```
require 'vendor/autoload.php';

use ApiCrumbs\Framework\ApiCrumbs;
use ApiCrumbs\Crumbs\Geo\PostcodeCrumb;
use ApiCrumbs\Crumbs\Finance\HmrcCrumb;

$engine = new ApiCrumbs();

// Register your "Senses"
$engine->withCrumbs([
    new PostcodeCrumb(),
    new HmrcCrumb()
]);

// Stitch a multi-source data trail (The "Grand Stitch")
$context = $engine->stitch("SW1A1AA");

echo $context; // Token-optimized Markdown ready for your LLM

```



* * * * *

🛠️ The Foundry CLI (`php crumb`)

The `crumb` binary is your cockpit for managing the data supply chain.

🔍 Diagnose & Verify

Check your SSL health, PHP environment, and local Crumb code quality.

bash

```
php crumb doctor

```



📦 Install from the Global Registry

Download any connector from the 10,000-strong Wikipedia of Context.

bash

```
php crumb install finance/hmrc-spending

```



🛠️ Scaffold a New Crumb

Create a new API or high-volume CSV connector instantly.

bash

```
# Standard API Crumb
php crumb make VatCheck finance

# Memory-lean CSV Streamer (XAMPP Safe)
php crumb make GovtSpending finance --csv

```



📊 Bench & Trace

Prove your Token ROI and visualize the data lineage of a stitch.

bash

```
# See how much money you save on LLM tokens
php crumb bench finance/hmrc "01234567"

# Trace the "Silent Anchors" (Source/Info tags)
php crumb trace "geo/postcode,weather/meteo" "SW1A1AA"

```



* * * * *

🤝 Join the 10,000 Crumb Mission

We don't just want users; we want Librarians of Context.

💡 Suggest a New Crumb

Got an idea for a connector? Push it directly to the Global Roadmap from your terminal.

bash

```
php crumb suggest "Companies House Officers" finance "Need to trace director relationships"

```



🚀 Submit Your Code

Finished a local Crumb? Submit it to the public registry and see your GitHub Avatar in the Hall of Senses.

bash

```
php crumb submit MyNewCrumb finance

```



* * * * *

💎 Sponsoware Model

The code is Free. The Roadmap is Sponsored.

-   Community Sponsor ($5/mo): Maintains the global registry infrastructure.
-   Roadmap Sponsor ($25/mo): Grants 5x Voting Power (🚀) on GitHub Issues to decide which Expert Crumbs are developed next.
-   Enterprise Sponsor ($250+/mo): Unlocks Hardened Drivers (PII redaction, 2FA, OAuth2 Refresh) and private audit logs.

Sponsor the Mission on GitHub →

* * * * *

🏗️ Technical Pillars

-   Token Refinement: Reduces raw JSON noise by ~90% using the `transform()` logic.
-   Silent Grounding: Injects hidden `<!-- Source -->` tags to reduce LLM hallucinations.
-   Memory Safety: Uses PHP Generators for "Big Data" CSV processing on standard laptops.
-   Native SSL: Zero-config cURL verification for Windows/XAMPP environments.

Ground your AI. Build the trail. Follow the crumbs.

* * * * *