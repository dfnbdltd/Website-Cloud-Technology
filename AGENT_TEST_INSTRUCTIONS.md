# CloudTechnology — QA agent instructions

You are a QA agent. Test the features listed below on a **staging** copy of the
CloudTechnology site, in a real browser, and report PASS / FAIL / BLOCKED for each
test. Follow the steps exactly. Do not fix code. Do not guess: if the page does not
match "Expected", that is a FAIL — copy what you actually saw.

## 0. Safety rules (read first)

1. Test on **staging only**. Never run this on the live site with real customers.
2. **Never complete a payment.** If you reach a bKash / Nagad page or popup, stop,
   take a screenshot, and go back. Do not type a real wallet number, OTP or PIN.
3. Only use the test accounts below. Do not touch other customers' data.
4. Orders you create are saved as *pending / unpaid*. That is expected. Clean up with
   the SQL in section 6 when you finish.
5. Do not paste passwords, API keys or the `.env` file into your report.

## 1. What you need before you start

The person running the test gives you:

| Name | Meaning |
|---|---|
| `BASE_URL` | staging site address, e.g. `https://staging.example.com` (no trailing slash) |
| `DB` | read/write SQL access to the staging database (phpMyAdmin or a SQL client) |

### 1.1 Create four test accounts

Register these in the browser at `BASE_URL/register` (any phone number, strong
password such as `TestPass#2026`). Use e-mail addresses you control or fake ones:

| Role | E-mail | Purpose |
|---|---|---|
| `BUYER` | `qa.buyer@example.test` | the person who checks out |
| `CUSTOMER` | `qa.customer@example.test` | an existing customer the buyer buys *for* |
| `STRANGER` | `qa.stranger@example.test` | has no relation to either order |
| `UNVERIFIED` | `qa.unverified@example.test` | registered but **never verified** |

Then, in SQL, mark three of them verified (leave `UNVERIFIED` alone) and give BUYER
wallet money:

```sql
UPDATE users SET email_verified_at = NOW()
WHERE email IN ('qa.buyer@example.test','qa.customer@example.test','qa.stranger@example.test');

UPDATE users SET balance = 5000 WHERE email = 'qa.buyer@example.test';

SELECT id, email, customer_uid, email_verified_at, balance FROM users
WHERE email LIKE 'qa.%@example.test';
```

Write down each account's `id` and `customer_uid` (looks like `CT-000123`). Below,
`CUSTOMER_UID` means that value.

### 1.2 Database must be ready

Run `database/ssl_orders_and_buy_for_customer.sql` (steps 0–4) first. Check:

```sql
SHOW TABLES LIKE 'ssl_orders';                              -- 1 row
SHOW COLUMNS FROM domain_orders LIKE 'placed_by_user_id';   -- 1 row
```

If either returns no row, stop and report **BLOCKED: SQL not applied**.

### 1.3 Test domain for the domain-buying tests (staging only)

Domain checkout normally calls ResellerClub. To test ownership rules without a real
registration, the person running the test adds this to `config/services.php`
(**staging only, remove afterwards**) inside the returned array, then clears config cache:

```php
'test_domains' => ['qa-demo-domain.com' => ['price' => 1200, 'renewal' => 1200]],
```

Then searching for `qa-demo-domain.com` shows it as available at ৳1,200. If this is
not set up, mark the domain tests that need it **BLOCKED (no test domain)**.

## 2. How to report

For every test give: **ID · PASS / FAIL / BLOCKED · what you saw · screenshot name**.
For a FAIL also give the exact URL and any red text on the page. End with a table:

| ID | Result | Notes |
|---|---|---|

## 3. Security regression

**SEC-1 — old developer login routes are gone**
1. Open `BASE_URL/__probe-login` and `BASE_URL/__probe-rowcheck`.
2. Expected: both show a **404 / not found** page.
3. Then open `BASE_URL/dashboard` in the same browser without logging in.
4. Expected: you are sent to the login page (you are NOT signed in).

## 4. SSL certificate purchase

Public plan page: `BASE_URL/services/ssl-certificate`. Plan cards show prices in
Taka (৳) per year. Plans include *Positive SSL*, *Comodo SSL*, *Positive SSL
Wildcard* and *EV SSL*. Use whichever plans are shown.

**SSL-1 — Buy Now opens the real flow**
1. Open the plan page while logged out.
2. Click **Buy Now** on *Positive SSL*.
3. Expected: you land on `/ssl/connect?plan=positive-ssl&term=1`. **No "coming soon"
   pop-up appears.** Heading: "Which website is this certificate for?". A bottom bar
   shows the plan name, the term and the Taka price.
4. Go back. On a plan with a 2-year option (e.g. *EV SSL*), change the term dropdown
   to **2 Years**, click **Buy Now**.
5. Expected: URL contains `term=2` and the bottom bar shows "2 Years" with the 2-year total.
6. The price in the bottom bar must equal the "total for N year(s)" under the card.

**SSL-2 — the Continue button only works for a valid domain**
On the connect page:
1. Expected at start: **Continue to Cart** is greyed out / disabled.
2. Type each of these. Expected: button stays **disabled**:
   `abc` · `example` · `exa mple.com` · `-bad.com` · `http://` · `.com` · `a..com`
3. Type each of these. Expected: button becomes **enabled**, a green ✓ appears, and a
   line says "Certificate will be issued for <name>":
   `example.com` · `www.example.com` · `shop.example.co.uk` · `https://Example.com/path` (shown as `example.com`)
4. Clear the box. Expected: disabled again.
5. Enter `example.com` and click Continue. Expected: you go to `/ssl/checkout`.

**SSL-3 — wildcard plans**
1. Open the connect page for the **Positive SSL Wildcard** plan.
2. Expected: hint mentions subdomains / `*.example.com`.
3. Enter `example.com`, Continue. Expected: the order summary on checkout shows `*.example.com`.
4. Open the connect page for a **non-wildcard** plan, enter `*.example.com`.
5. Expected: button stays disabled and the hint says a wildcard needs a Wildcard SSL plan.

**SSL-4 — guest checkout shows the price and asks to sign in**
(logged out) Complete SSL-2 step 5 for *Positive SSL*, 1 year, `example.com`.
Expected on `/ssl/checkout`:
- The order summary shows the **Taka price** (same as the plan card), the domain
  (with a *Change* link), the term, and "Renews at ৳…/year".
- Left side shows **Log in to continue** and **Create new account** buttons.
- "How you'll pay" shows **bKash** and **Nagad** logos only as a preview (not selectable).
- There is **no** "Place order" button and no "Who are you buying for?" section.

**SSL-5 — login sends you back to the same checkout**
1. From SSL-4 click **Log in to continue**. The login URL should contain `return_to=`.
2. Log in as `BUYER`.
3. Expected: you land on **`/ssl/checkout`** with the same plan/domain/price — NOT the dashboard.

**SSL-6 — registering sends you back too**
1. Log out. Repeat SSL-4, click **Create new account**, register a new user
   (`qa.newuser@example.test`).
2. Verify it in SQL: `UPDATE users SET email_verified_at=NOW() WHERE email='qa.newuser@example.test';`
   (or use the e-mail link if mail works on staging).
3. Log in. Expected: you end up on `/ssl/checkout` with the selection still there.
   (If the e-mail link was used, it may show a green tick then continue to checkout.)

**SSL-7 — logged-in checkout layout**
Log in as `BUYER`, choose *Positive SSL*, domain `example.com`, Continue.
Expected:
- Section **"Who are you buying for?"** with two choices: **Myself** (shows BUYER's
  e-mail) and **Customer**.
- Section **"Choose payment method"** with **bKash** and **Nagad** only (no bank, no wallet).
- A button "Place order · ৳<total>" and an order summary on the right.

**SSL-8 — customer lookup (red / green messages)**
On the same page choose **Customer**. An input "Email or ID of existing customer"
and a **Confirm** button appear. Expected behaviour:
1. "Place order" is **disabled** while Customer is selected and not confirmed.
2. Type `ghost@example.test` → Confirm → **red** text: *Customer does not exist…*; Place order still disabled.
3. Type `qa.unverified@example.test` → Confirm → same **red** message (unverified accounts do not count).
4. Type `CT-999999` → Confirm → red message.
5. Type `qa.customer@example.test` → Confirm → **green** "✓ Customer found"; Place order enabled.
6. Type `CUSTOMER_UID` instead → Confirm → green too.
7. After a green result, change one character in the box. Expected: green message
   disappears and Place order is disabled again until you press Confirm again.
8. Switch back to **Myself**. Expected: Place order enabled again.

**SSL-9 — place an order for myself**
1. As `BUYER`, *Positive SSL*, `example.com`, **Myself**, **Nagad** → Place order.
2. Expected: page `/ssl/orders/SSL-YYYYMMDD-XXXXXX` showing the order reference, plan,
   domain, total, and that it is waiting for payment. **No payment is taken.**
3. SQL (replace the reference):
```sql
SELECT reference, user_id, placed_by_user_id, plan_key, domain, years, total, purchase_price,
       status, payment_method, payment_status, reseller_order_id
FROM ssl_orders ORDER BY id DESC LIMIT 1;
```
Expected: `user_id` = BUYER id, `placed_by_user_id` = BUYER id, `status`=`payment_pending`,
`payment_status`=`pending`, `payment_method`=`nagad`, `reseller_order_id` NULL,
`total` equals the price shown, `domain`=`example.com`.
4. Invoice check:
```sql
SELECT i.invoice_number, i.user_id, i.status, i.total, i.amount_paid, ii.description, ii.item_type, ii.years, ii.line_total
FROM invoices i JOIN invoice_items ii ON ii.invoice_id = i.id
WHERE i.source_type = 'App\\Models\\SslOrder' ORDER BY i.id DESC LIMIT 1;
```
Expected: `status`=`unpaid`, `amount_paid`=0, `item_type`=`ssl-certificate`, `line_total` = order total.

**SSL-10 — place an order for a customer**
1. As `BUYER`, *Comodo SSL* (or any), domain `customer-site.com`, choose **Customer**,
   enter `qa.customer@example.test`, Confirm (green), pick **bKash**, Place order.
2. Expected: the order page for the new reference ("Your SSL order is saved", status "Waiting for payment"). There is **no** "View invoice" button here, because the invoice belongs to the customer, not the buyer.
3. SQL: latest `ssl_orders` row has `user_id` = CUSTOMER id, `placed_by_user_id` = BUYER id,
   `customer_email` = `qa.customer@example.test`. Latest SSL invoice has `user_id` = CUSTOMER id.

**SSL-11 — typing a customer that does not exist, bypassing the button**
(Server-side check.) On checkout choose Customer, enter `ghost@example.test`. In the
browser developer tools remove the `disabled` attribute from the Place order button,
click it. Expected: you are back on checkout with a **red** "Customer does not exist…"
message under the field; **no** new row in `ssl_orders`.

**SSL-12 — who may open an order**
Use the references from SSL-9 (`REF_SELF`) and SSL-10 (`REF_CUSTOMER`). Visit `BASE_URL/ssl/orders/<REF>`:
| Logged in as | REF_SELF | REF_CUSTOMER |
|---|---|---|
| BUYER | opens | opens |
| CUSTOMER | 404 | opens |
| STRANGER | 404 | 404 |
| logged out | redirected to login | redirected to login |

**SSL-13 — the cart is cleared after ordering**
Right after placing an order, open `BASE_URL/ssl/checkout`. Expected: redirected back to
the SSL plans page with a message to choose a plan (the old selection is gone).

**SSL-14 — price cannot be tampered with**
1. As `BUYER` open a connect page. In developer tools change the hidden field
   `term` to `99`, enter `example.com`, click Continue.
2. Expected: nothing is saved and the term falls back to a valid one (the page
   reloads on `term=1`; the price shown is still the real plan price). This is
   a safe reset, not an error page.
3. Repeat with `plan` changed to `fake-plan`. Expected: sent back to the SSL plans
   page with an error, nothing saved.
4. On checkout there is no price field you can edit; the total always matches the plan card.
   Check `SELECT COUNT(*) FROM ssl_orders` did not change.

**SSL-15 — paid order shows up in My Store (simulated payment)**
The payment gateway is not connected yet, so simulate "payment confirmed" in SQL for
the order from SSL-9 (replace `REF_SELF`):
```sql
UPDATE ssl_orders SET payment_status='paid', status='paid' WHERE reference='REF_SELF';
UPDATE invoices SET status='paid', amount_paid=total, paid_at=NOW()
WHERE source_type='App\\Models\\SslOrder'
  AND source_id=(SELECT id FROM ssl_orders WHERE reference='REF_SELF');
```
Log in as `BUYER`, open `BASE_URL/mystore/ssl`. Expected: the certificate line
("SSL certificate — Positive SSL for example.com") is listed with its invoice link.
Log in as `STRANGER` and open the same page: the line must **not** appear.

## 5. Domain registration — buying for myself or a customer

Add the domain to the cart: `BASE_URL/domains`, search `qa-demo-domain.com`, add it
(1 year). The cart then goes to `BASE_URL/domains/checkout`.

**DOM-1 — guest checkout**
Logged out, go to checkout. Expected: a "Confirm with your account" panel with
**Log in to confirm** and **Create new account**, and a payment-methods preview. No
"Who are you buying for?" section. Click **Log in to confirm**, log in as `BUYER`:
expected to return to `/domains/checkout` with the cart intact.

**DOM-2 — logged-in checkout layout**
As `BUYER` on `/domains/checkout`. Expected: **"Who are you buying for?"** with
**Myself** (shows BUYER's e-mail) and **Customer**; then "Choose payment method"
(Wallet balance, bKash, Nagad, Bank transfer); order summary on the right.
Wallet shows ৳ 5,000.00 for BUYER.

**DOM-3 — customer lookup (same behaviour as SSL-8)**
Choose **Customer** and repeat SSL-8 steps 1–8 on this page: ghost / unverified /
`CT-999999` → **red** "Customer does not exist…"; `qa.customer@example.test` and
`CUSTOMER_UID` → **green** "✓ Customer found". Place order stays disabled until a
green confirmation, and again after editing the box.
When Customer is selected the buyer's own contact fields (if any are shown) disappear.

**DOM-4 — wallet purchase for a customer**
1. As `BUYER`, cart `qa-demo-domain.com`, choose **Customer** → `qa.customer@example.test` → Confirm → payment **Wallet balance** → Place order.
2. Expected: a confirmation page for the order (reference like `CT-YYYYMMDD-XXXXXX`) saying it is paid.
3. SQL:
```sql
SELECT o.reference, o.user_id, o.placed_by_user_id, o.customer_email, o.total, o.status, o.payment_status, o.payment_method
FROM domain_orders o ORDER BY o.id DESC LIMIT 1;
SELECT email, balance FROM users WHERE email IN ('qa.buyer@example.test','qa.customer@example.test');
```
Expected: `user_id` = CUSTOMER id, `placed_by_user_id` = BUYER id, `customer_email` =
`qa.customer@example.test`, `payment_status`=`paid`. BUYER balance dropped by the total
(5000 → 3800); CUSTOMER balance unchanged.
4. Invoice: the latest domain-order invoice has `user_id` = CUSTOMER id and status `paid`.
5. Log in as `CUSTOMER` → `BASE_URL/mystore/domains`: the domain appears there.
6. Log in as `BUYER` → open `BASE_URL/domains/confirmation/<reference>`: **opens**
   (the buyer can still see what they bought). The domain does **not** appear in the
   buyer's own My Store → Domains.

**DOM-5 — wallet purchase for myself (regression)**
As `BUYER`, cart a domain, choose **Myself**, **Wallet** → Place order. Expected: paid,
`user_id` = `placed_by_user_id` = BUYER id; the domain appears in BUYER's My Store → Domains;
wallet reduced by the total.

**DOM-6 — bKash for a customer (do NOT pay)**
As `BUYER`, cart a domain, choose **Customer** → confirm `qa.customer@example.test` →
**bKash** → Place order.
Expected: either the bKash payment page (`/bkash/pay`) or, if bKash is not configured
on staging, a page saying the order is saved and billing will contact you. **Stop here.**
SQL: latest `domain_orders` row has `status`=`payment_pending`, `payment_status`
`pending`/`initiated`, `user_id` = CUSTOMER id, `placed_by_user_id` = BUYER id.
Then press Back to the checkout page — the unpaid bKash attempt is cancelled (existing behaviour).

**DOM-7 — a stranger cannot open the order**
Using the reference from DOM-4, visit `BASE_URL/domains/confirmation/<reference>`:
| Logged in as | Result |
|---|---|
| BUYER | opens |
| CUSTOMER | opens |
| STRANGER | redirected to `/domains` with "Order not found…" |

**DOM-8 — server-side refusal**
As `BUYER` with a cart, choose Customer, enter `ghost@example.test`, remove `disabled`
from Place order in developer tools, click it. Expected: back on checkout, **red**
"Customer does not exist…" under the field, no new row in `domain_orders`.

**DOM-9 — guests cannot buy for a customer**
Logged out with a cart, in developer tools you cannot see the customer option, so this
is covered by the automated test below. Mark **SKIPPED (covered by PHPUnit)**.

## 6. Clean up (staging)

```sql
DELETE ii FROM invoice_items ii JOIN invoices i ON i.id = ii.invoice_id
WHERE i.user_id IN (SELECT id FROM users WHERE email LIKE 'qa.%@example.test');
DELETE FROM invoices WHERE user_id IN (SELECT id FROM users WHERE email LIKE 'qa.%@example.test');
DELETE FROM payments WHERE domain_order_id IN
  (SELECT id FROM domain_orders WHERE customer_email LIKE 'qa.%@example.test');
DELETE FROM domain_order_items WHERE domain_order_id IN
  (SELECT id FROM domain_orders WHERE customer_email LIKE 'qa.%@example.test');
DELETE FROM domain_orders WHERE customer_email LIKE 'qa.%@example.test';
DELETE FROM ssl_orders WHERE customer_email LIKE 'qa.%@example.test';
DELETE FROM users WHERE email LIKE 'qa.%@example.test';
```

Also ask the person running the test to remove the `test_domains` line from
`config/services.php` and clear the config cache.

## 7. Optional — automated tests (only if you have a shell on the server/dev machine)

```
composer install
vendor\bin\phpunit --filter=SslCheckoutTest
vendor\bin\phpunit --filter=DomainBuyForCustomerTest
vendor\bin\phpunit
```
Report the final "Tests: N passed / failed" line and the name of every failing test.
(Laravel 6 has no `php artisan test`; use PHPUnit directly, with PHP 8.0.)
