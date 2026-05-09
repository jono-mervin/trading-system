# Vortex Trading Platform (PHP + JS + Python AI)

This project scaffolds a trading platform based on your `TRADING PLATFORM.md` requirements:

- PHP + HTML + Tailwind frontend/backend
- MySQL schema with ledger-based wallet accounting
- Trader + Admin role workflows
- KYC submission and approval
- Deposit + withdrawal + trading + portfolio
- Audit logs + fraud flags
- Python AI risk-scoring microservice

## 1) Setup Database

1. Create/import DB using `sql/schema.sql`.
2. Update DB values in `includes/config.php` if needed.

## 2) Run Python AI Service

```bash
cd python_ai
pip install -r requirements.txt
python app.py
```

Runs on `http://127.0.0.1:8001`.

## 3) Configure Apache/XAMPP

Place project in `htdocs/Vortex` and open:

- `http://localhost/Vortex/index.php`

## 4) Create Admin Account

Use the bootstrap page (first admin only):

- `http://localhost/Vortex/setup_admin.php`

## 5) Core Pages

- Trader:
  - `/trader/dashboard.php`
  - `/trader/wallet.php`
  - `/trader/trade.php`
  - `/trader/portfolio.php`
  - `/trader/kyc.php`
  - `/trader/transactions.php`
  - `/trader/profile.php`
  - `/trader/bank_accounts.php`
  - `/trader/market_data.php`
- Admin:
  - `/admin/dashboard.php`
  - `/admin/kyc.php`
  - `/admin/withdrawals.php`
  - `/admin/fraud.php`
  - `/admin/payments.php`
  - `/admin/webhook_logs.php`
  - `/admin/users.php`
  - `/admin/trading_logs.php`
  - `/admin/audit_logs.php`
  - `/admin/reports.php`

## Notes

- Real deposit flow now uses PayMongo source checkout:
  - Create source: `api/deposit_create.php`
  - Webhook endpoint: `api/paymongo_webhook.php`
  - `source.chargeable` => server creates PayMongo Payment
  - `payment.paid` / `source.paid` => wallet ledger is credited
- Seamless local mode is available now:
  - Set `PAYMENT_MODE = 'workflow'` in `includes/config.php`
  - Deposit goes through internal checkout page: `/trader/deposit_checkout.php`
  - Admin approves/rejects pending workflow payments in `/admin/payments.php`
- Admin bootstrap page (first admin only):
  - `/setup_admin.php`
- Configure these in `includes/config.php`:
  - `PAYMONGO_SECRET_KEY`
  - `PAYMONGO_WEBHOOK_SECRET`
- Configure PayMongo webhook URL to:
  - `https://your-domain.com/Vortex/api/paymongo_webhook.php`
- For localhost testing, use a tunnel (ngrok/cloudflared) and set the tunnel URL in PayMongo webhook settings.
- PHP CLI is not required to run the web app, but recommended for linting.
- AI connectivity check pages:
  - UI: `/ai_health.php`
  - JSON: `/api/ai_health.php`
- Basic endpoint rate limiting is enabled for login/register/deposit/order/withdraw workflows.
