<?php

declare(strict_types=1);

// ---------------------------------------------------------------------
// Metadata
// ---------------------------------------------------------------------

// (1). Application
// -----------------------------
define('APP_NAME', 'URL-Shortener');
define('APP_VERSION', '0.6.1');

// (2). APP_STAGE
// -----------------------------
// We can use different configurations at different stages.
// Supported stages: PRODUCTION, TESTING, STAGING, DEVELOPMENT
$_stage = getenv('APP_STAGE') ?: 'PRODUCTION';
if (!in_array($_stage, ['DEVELOPMENT', 'TESTING', 'STAGING', 'PRODUCTION'])) {
    exit('Unsupported APP_STAGE, program halt.');
}
define('APP_STAGE', $_stage);
unset($_stage);
