<?php

declare(strict_types=1);

namespace GatewayFees;

use WHMCS\Database\Capsule;

final class Module
{
    public const NAME = 'gateway_fees';
    public const VERSION = '2.0.0';

    public static function config(): array
    {
        return [
            'name' => 'Gateway Fees',
            'description' => 'Adds a configurable payment gateway fee to newly created invoices.',
            'version' => self::VERSION,
            'author' => 'Khalequzzaman',
            'language' => 'english',
            'fields' => [
                'enabled' => [
                    'FriendlyName' => 'Enabled',
                    'Type' => 'yesno',
                    'Description' => 'Apply fees automatically when invoices are created or their gateway changes.',
                    'Default' => 'on',
                ],
                'fee_type' => [
                    'FriendlyName' => 'Fee type',
                    'Type' => 'dropdown',
                    'Options' => 'percentage,fixed',
                    'Description' => 'Percentage is calculated from the invoice subtotal before the fee.',
                    'Default' => 'percentage',
                ],
                'percentage' => [
                    'FriendlyName' => 'Percentage',
                    'Type' => 'text',
                    'Size' => '10',
                    'Description' => 'For example, 2.90 means 2.90%. Used when fee type is percentage.',
                    'Default' => '0',
                ],
                'fixed' => [
                    'FriendlyName' => 'Fixed amount',
                    'Type' => 'text',
                    'Size' => '10',
                    'Description' => 'Amount in the invoice currency. Used when fee type is fixed.',
                    'Default' => '0',
                ],
                'gateways' => [
                    'FriendlyName' => 'Payment gateways',
                    'Type' => 'text',
                    'Size' => '50',
                    'Description' => 'Comma-separated gateway system names. Leave blank to apply to every gateway.',
                    'Default' => '',
                ],
                'description' => [
                    'FriendlyName' => 'Invoice description',
                    'Type' => 'text',
                    'Size' => '50',
                    'Description' => 'Description shown on the invoice.',
                    'Default' => 'Payment gateway fee',
                ],
                'taxed' => [
                    'FriendlyName' => 'Tax the fee',
                    'Type' => 'yesno',
                    'Description' => 'Mark the fee invoice item as taxable.',
                    'Default' => '',
                ],
            ],
        ];
    }

    public static function activate(): array
    {
        return ['status' => 'success', 'description' => 'Gateway Fees has been activated.'];
    }

    public static function deactivate(): array
    {
        return ['status' => 'success', 'description' => 'Gateway Fees has been deactivated. Existing invoice items were not changed.'];
    }

    public static function output(array $vars): void
    {
        $settings = Settings::all();

        if (($_SERVER['REQUEST_METHOD'] ?? 'GET') === 'POST' && ($_POST['gateway_fees_action'] ?? '') === 'save') {
            if (function_exists('check_token') && !check_token('WHMCS.admin.default')) {
                echo '<div class="alert alert-danger">Invalid security token.</div>';
            } else {
                Settings::save([
                    'enabled' => isset($_POST['enabled']) ? 'on' : '',
                    'fee_type' => in_array($_POST['fee_type'] ?? '', ['percentage', 'fixed'], true) ? $_POST['fee_type'] : 'percentage',
                    'percentage' => self::decimal($_POST['percentage'] ?? '0'),
                    'fixed' => self::decimal($_POST['fixed'] ?? '0'),
                    'gateways' => trim((string) ($_POST['gateways'] ?? '')),
                    'description' => trim((string) ($_POST['description'] ?? 'Payment gateway fee')) ?: 'Payment gateway fee',
                    'taxed' => isset($_POST['taxed']) ? 'on' : '',
                ]);
                $settings = Settings::all();
                echo '<div class="alert alert-success">Settings saved.</div>';
            }
        }

        $token = function_exists('generate_token') ? generate_token('form') : '';
        $e = static fn (string $key): string => htmlspecialchars((string) ($settings[$key] ?? ''), ENT_QUOTES, 'UTF-8');
        echo '<form method="post" action="' . htmlspecialchars((string) ($vars['modulelink'] ?? ''), ENT_QUOTES, 'UTF-8') . '">';
        echo '<input type="hidden" name="gateway_fees_action" value="save"><input type="hidden" name="token" value="' . $token . '">';
        echo '<div class="panel panel-default"><div class="panel-heading"><strong>Gateway Fees ' . self::VERSION . '</strong></div><div class="panel-body">';
        echo '<p>Fees are added as a separate invoice line item. Existing invoices are never modified by activation or deactivation.</p>';
        self::checkbox('enabled', 'Enabled', $settings, $e);
        echo '<div class="form-group"><label>Fee type</label><select name="fee_type" class="form-control"><option value="percentage"' . ($settings['fee_type'] === 'percentage' ? ' selected' : '') . '>Percentage</option><option value="fixed"' . ($settings['fee_type'] === 'fixed' ? ' selected' : '') . '>Fixed amount</option></select></div>';
        self::input('percentage', 'Percentage', $settings, $e, 'Example: 2.90');
        self::input('fixed', 'Fixed amount', $settings, $e, 'Example: 0.30');
        self::input('gateways', 'Payment gateways', $settings, $e, 'Example: paypal, stripe, banktransfer');
        self::input('description', 'Invoice description', $settings, $e, 'Text displayed to the client');
        self::checkbox('taxed', 'Tax the fee', $settings, $e);
        echo '</div><div class="panel-footer"><button type="submit" class="btn btn-primary">Save settings</button></div></div></form>';
    }

    private static function input(string $name, string $label, array $settings, callable $escape, string $help): void
    {
        echo '<div class="form-group"><label for="gateway-fees-' . $name . '">' . $label . '</label><input id="gateway-fees-' . $name . '" class="form-control" name="' . $name . '" value="' . $escape($name) . '"><span class="help-block">' . $help . '</span></div>';
    }

    private static function checkbox(string $name, string $label, array $settings, callable $escape): void
    {
        echo '<div class="checkbox"><label><input type="checkbox" name="' . $name . '" ' . (!empty($settings[$name]) ? 'checked' : '') . '> ' . $label . '</label></div>';
    }

    private static function decimal(mixed $value): string
    {
        $value = str_replace(',', '.', trim((string) $value));
        return is_numeric($value) ? number_format(max(0, (float) $value), 6, '.', '') : '0';
    }
}

final class Settings
{
    public static function all(): array
    {
        $values = [];
        foreach (Capsule::table('tbladdonmodules')->where('module', Module::NAME)->get(['setting', 'value']) as $row) {
            $values[(string) $row->setting] = (string) $row->value;
        }
        return array_merge(['enabled' => 'on', 'fee_type' => 'percentage', 'percentage' => '0', 'fixed' => '0', 'gateways' => '', 'description' => 'Payment gateway fee', 'taxed' => ''], $values);
    }

    public static function save(array $values): void
    {
        foreach ($values as $setting => $value) {
            Capsule::table('tbladdonmodules')->updateOrInsert(['module' => Module::NAME, 'setting' => $setting], ['value' => (string) $value]);
        }
    }
}

final class FeeManager
{
    private const MARKER = '[gateway_fees]';

    public static function applyToInvoice(int $invoiceId): void
    {
        if ($invoiceId < 1 || !function_exists('localAPI')) {
            return;
        }
        $settings = Settings::all();
        $invoice = localAPI('GetInvoice', ['invoiceid' => $invoiceId]);
        if (($invoice['result'] ?? '') !== 'success') {
            return;
        }

        $items = $invoice['items']['item'] ?? [];
        if (isset($items['id'])) {
            $items = [$items];
        }
        $feeIds = [];
        $base = 0.0;
        foreach ($items as $item) {
            if (str_contains((string) ($item['description'] ?? ''), self::MARKER)) {
                $feeIds[] = (int) ($item['id'] ?? 0);
            } else {
                $base += (float) ($item['amount'] ?? 0);
            }
        }
        if ($feeIds) {
            self::update($invoiceId, ['deletelineids' => array_values(array_filter($feeIds))]);
        }
        if (empty($settings['enabled']) || !self::gatewayAllowed((string) ($invoice['paymentmethod'] ?? ''), (string) $settings['gateways'])) {
            return;
        }
        $amount = $settings['fee_type'] === 'fixed' ? (float) $settings['fixed'] : $base * ((float) $settings['percentage'] / 100);
        $amount = round(max(0, $amount), 2);
        if ($amount <= 0) {
            return;
        }
        self::update($invoiceId, ['newitemdescription' => [self::description((string) $settings['description'])], 'newitemamount' => [$amount], 'newitemtaxed' => [empty($settings['taxed']) ? 0 : 1]]);
    }

    private static function update(int $invoiceId, array $data): void
    {
        $data['invoiceid'] = $invoiceId;
        $result = localAPI('UpdateInvoice', $data);
        if (($result['result'] ?? '') !== 'success' && function_exists('logModuleCall')) {
            logModuleCall(Module::NAME, 'UpdateInvoice', $data, $result);
        }
    }

    private static function description(string $description): string
    {
        return self::MARKER . ' ' . trim($description ?: 'Payment gateway fee');
    }

    private static function gatewayAllowed(string $gateway, string $allowed): bool
    {
        $allowed = array_filter(array_map(static fn (string $value): string => strtolower(trim($value)), explode(',', $allowed)));
        return !$allowed || in_array(strtolower(trim($gateway)), $allowed, true);
    }
}
