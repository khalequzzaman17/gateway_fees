# WHMCS Gateway Fees

An open, maintainable WHMCS addon module that adds a payment gateway fee as a separate invoice line item.

## Compatibility

- WHMCS 8.x and current WHMCS releases using the documented addon-module and hook APIs
- PHP 8.3 and PHP 8.4
- No Composer dependency and no encoded/obfuscated source

## Installation

1. Copy `modules/addons/gateway_fees` to the WHMCS `modules/addons` directory.
2. In WHMCS, open **Configuration > System Settings > Addon Modules**.
3. Activate **Gateway Fees** and grant access to the required administrator roles.
4. Open the module and configure the fee.

The module stores only its settings in WHMCS's standard `tbladdonmodules` table. It does not create a custom table. Deactivation does not remove or alter existing invoice items.

## Configuration

- **Enabled**: enable or disable automatic fees.
- **Fee type**: percentage of the invoice subtotal before the fee, or a fixed amount.
- **Payment gateways**: comma-separated WHMCS gateway system names, such as `paypal,stripe`. Leave blank for all gateways.
- **Tax the fee**: controls the taxable flag on the new invoice item.

The module listens to `InvoiceCreation` and `InvoiceChangeGateway`. It removes and recreates its own marked line item before applying the current rule, making repeated hook execution idempotent. It does not charge fees retroactively to old invoices unless a gateway is changed on that invoice.

## File structure

```text
modules/addons/gateway_fees/
├── gateway_fees.php       # WHMCS addon entry points and configuration
├── hooks.php              # Invoice lifecycle hooks
└── lib/
    └── GatewayFees.php    # Settings, admin output, and fee calculation
```

## Security and maintenance

The previous distribution contained encoded payloads and self-integrity checks. This version contains ordinary PHP source so it can be reviewed, scanned, and maintained. It uses WHMCS's `localAPI('GetInvoice')` and `localAPI('UpdateInvoice')` for invoice changes and does not accept client-supplied invoice IDs.

Before production use, test invoice creation, partial payments, refunds, gateway changes, taxes, multi-currency invoices, and all enabled gateways in a staging WHMCS installation. Keep WHMCS and PHP patched.

## License

This project is licensed under the [MIT License](LICENSE).
