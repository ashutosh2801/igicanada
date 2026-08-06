# Multi-storefront production handoff

## DNS

Point both apex domains to the same production server:

| Host | Type | Value |
|---|---|---|
| `igicanada.ca` | A/AAAA | Production server IP |
| `www.igicanada.ca` | CNAME | `igicanada.ca` |
| `leatherwallets.ca` | A/AAAA | Production server IP |
| `www.leatherwallets.ca` | CNAME | `leatherwallets.ca` |

Keep DNS proxying disabled until the first TLS certificate has been issued and both origin hosts have been tested.

## Production environment

```dotenv
APP_ENV=production
APP_DEBUG=false
APP_URL=https://igicanada.ca

WHOLESALE_DOMAIN=igicanada.ca
WHOLESALE_DOMAIN_ALIASES=www.igicanada.ca
RETAIL_DOMAIN=leatherwallets.ca
RETAIL_DOMAIN_ALIASES=www.leatherwallets.ca
ADMIN_DOMAIN=igicanada.ca
STOREFRONT_DEFAULT_CHANNEL=wholesale

# Keep sessions isolated by hostname.
SESSION_DOMAIN=null
SESSION_SECURE_COOKIE=true

# Enable only after confirming GST/HST registration details.
RETAIL_GST_HST_REGISTERED=false
RETAIL_GST_HST_NUMBER=
RETAIL_TAX_REVIEWED=false
SHIPPING_RATES_REVIEWED=false
RETAIL_LEGAL_REVIEWED=false

PAYPAL_MODE=live
PAYPAL_CLIENT_ID=
PAYPAL_CLIENT_SECRET=
PAYPAL_WEBHOOK_ID=
```

Never commit the populated production `.env` file.

## Web server and TLS

1. Copy `deploy/nginx-multistorefront.conf.example` into the server's Nginx sites directory.
2. Update the project root and PHP-FPM socket.
3. Issue certificates for both apex and `www` names using Certbot or the host's certificate manager.
4. Test the Nginx configuration before reloading it.
5. Confirm `https://leatherwallets.ca/admin` returns 404 and `https://igicanada.ca/admin` opens the admin login.

## PayPal

Create or update the live PayPal application with these return surfaces:

- `https://igicanada.ca/payments/paypal/return`
- `https://igicanada.ca/payments/paypal/cancel`
- `https://leatherwallets.ca/payments/paypal/return`
- `https://leatherwallets.ca/payments/paypal/cancel`
- Webhook: `https://igicanada.ca/payments/paypal/webhook`

Subscribe the webhook to payment capture completed events and place the resulting webhook ID in `PAYPAL_WEBHOOK_ID`.

## Tax sign-off

The application supports destination-based Canadian GST/HST. Keep collection disabled until the business confirms its registration number with its accountant. Provincial PST, RST, and QST registration obligations are separate and are not automatically enabled by this implementation.

Set `RETAIL_TAX_REVIEWED=true` only after an accountant confirms the registration status, GST/HST number, place-of-supply treatment, and any separate BC PST, Manitoba RST, Saskatchewan PST, or Quebec QST obligations.

## Shipping sign-off

The admin contains separate CA/US slabs for Wholesale and Retail. Confirm each carrier cost and commercial rule in Admin → Shipping charges, including the current Retail Canada `$100–$199.99` value, before setting `SHIPPING_RATES_REVIEWED=true`. The launch check verifies that active ranges start at zero, have no gaps, and cover at least `$10,000`.

## Legal content sign-off

The migration creates Privacy Policy, Terms of Sale, and Shipping & Returns pages with status `Review required`. In Admin → Content pages:

1. Add privacy contact details and all analytics/marketing providers.
2. Add the return window, refund timing, exclusions, and return address.
3. Confirm warranty, cancellation, governing-law, and consumer-protection language.
4. Change each approved page to `Published`. Published retail legal pages appear automatically in the Leather Wallets footer.

Remove every “Review required” / “Before publishing” instruction from the page bodies and set `RETAIL_LEGAL_REVIEWED=true` only after the business or its lawyer approves the final wording. The launch check rejects published placeholder drafts.

## Backup and atomic release

Confirm `mysqldump` is installed, then use the release helper from the project root:

```bash
chmod +x deploy/release.sh
./deploy/release.sh
```

It creates a transaction-consistent SQL backup plus SHA-256 checksum before changing dependencies or entering maintenance mode. If a later command fails, the exit trap brings the application back online. Backups are written to `storage/app/backups/`; copy them to encrypted off-server storage and periodically test a restore on a non-production database.

## Release commands

These commands are automated by `deploy/release.sh`. Do not run production migrations manually unless a verified pre-deploy backup already exists.

Run queue workers under Supervisor or systemd. Verify the `/up` health endpoint, a retail guest-cart checkout in PayPal sandbox, a wholesale login/order, admin channel filters, customer emails, and inventory reduction before switching PayPal to live mode.

After configuration and content review, run `php artisan retail:launch-check`. Do not open retail traffic until every application check reports `PASS`.
