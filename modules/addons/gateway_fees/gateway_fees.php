<?php

declare(strict_types=1);

if (!defined('WHMCS')) {
    exit('This file cannot be accessed directly.');
}

require_once __DIR__ . '/lib/GatewayFees.php';

use GatewayFees\Module;

function gateway_fees_config(): array
{
    return Module::config();
}

function gateway_fees_activate(): array
{
    return Module::activate();
}

function gateway_fees_deactivate(): array
{
    return Module::deactivate();
}

function gateway_fees_output(array $vars): void
{
    Module::output($vars);
}
