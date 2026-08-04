<?php

declare(strict_types=1);

if (!defined('WHMCS')) {
    exit('This file cannot be accessed directly.');
}

require_once __DIR__ . '/lib/GatewayFees.php';

use GatewayFees\FeeManager;

add_hook('InvoiceCreation', 100, static function (array $vars): void {
    FeeManager::applyToInvoice((int) ($vars['invoiceid'] ?? 0));
});

add_hook('InvoiceChangeGateway', 100, static function (array $vars): void {
    FeeManager::applyToInvoice((int) ($vars['invoiceid'] ?? 0));
});
