# RestoPOS Sri Lanka — Setup Guide

## Requirements
- PHP 7.4+ (or PHP 8.x)
- MySQL 5.7+ or MariaDB 10.3+
- Apache / Nginx with mod_rewrite

---

## Installation Steps

### 1. Import Database
- Open phpMyAdmin or MySQL CLI
- Create a new database: `restopos`
- **Fresh install**: import `database.sql` (creates everything from scratch with sample data)
- **Upgrading an existing install**: instead, run `FINAL_UPGRADE.sql` — this safely adds all new tables/columns without deleting your existing data

```sql
mysql -u root -p restopos < database.sql
```

### 2. Configure Database Connection
Edit `includes/config.php`:
```php
define('DB_HOST', 'localhost');
define('DB_NAME', 'restopos');
define('DB_USER', 'root');      // your MySQL username
define('DB_PASS', '');          // your MySQL password
```

### 3. Upload Files
- Upload the entire `restopos/` folder to your web server
- Place in `htdocs/` (XAMPP) or `www/` (WAMP) or `/var/www/html/`

### 4. Open in Browser
```
http://localhost/restopos/
```

---

## Default Login Credentials

| Role         | Username  | Password     |
|--------------|-----------|--------------|
| Admin        | admin     | admin123     |
| Manager      | manager   | manager123   |
| Cashier      | cashier   | cashier123   |
| Kitchen Boy  | kitchen   | kitchen123   |

---

## Modules Included

| Module          | Description                                              |
|-----------------|-----------------------------------------------------------|
| Dashboard       | Live stats, low stock alerts, recent bills                |
| POS / Billing   | Full item selection, cart, promotions, payment, receipt   |
| Bill History    | View, edit, void, delete, reprint past bills               |
| Kitchen Display | Live kitchen order queue, tap-to-progress, printable KOT  |
| Reservations    | Table bookings, live search, printable confirmations & list PDF |
| Sales Reports   | By date/type/payment, charts, CSV export                  |
| Inventory       | Stock register, purchases, **recipes**, **usage report**, low stock |
| Expenses        | Record & categorise expenses, reports                      |
| Debtors         | Debtor ledger, payment recording                            |
| Payroll         | EPF 8%/12%, ETF 3%, flat-rate OT, payslip printing          |
| Cash/Banking    | Bank accounts, transactions, cash flow                      |
| Menu Manager    | Add/edit/deactivate menu items & categories; online menu link |
| Promotions      | % off, fixed off, Buy X Get Y Free — auto-applied in POS    |
| Employees       | Attendance marking (single + bulk), employee register      |
| Reports         | P&L, Expense, Cash Flow, Debtor, Inventory, Payroll, Attendance, Promotions |
| Activity Log    | Full audit trail of logins, bill actions, reservations      |
| Settings        | Business info, tax %, user & role management                |
| Online Menu     | Public QR-code-friendly menu page (`online_menu.php`, no login) |

### Role Access Summary
- **Admin**: everything
- **Manager**: everything except Settings
- **Cashier**: Dashboard, POS, Bill History, Sales Reports, Inventory, Expenses, Debtors, limited Reports, Reservations, Kitchen Display
- **Kitchen Boy**: Kitchen Display only

---

## Sri Lanka Payroll Formula
- **EPF Employee**: 8% of (Basic + Allowances)
- **EPF Employer**: 12% of (Basic + Allowances)
- **ETF Employer**: 3% of (Basic + Allowances)
- **Overtime**: Flat Rs. 100/hour, calculated for hours worked beyond 9 hours from each employee's actual Time In (not a fixed company-wide cutoff)
- **Net = Gross − EPF(Emp) − Advance − Other Deductions**

---

## Inventory Recipes & Auto Stock Usage
- Define what each menu item consumes via **Inventory → Recipes** (e.g. 1 Chicken Kottu = 0.3kg Chicken + 0.2kg Flour)
- Once a recipe is set, settling a bill in POS **automatically deducts** the matching stock — no manual entry needed
- View consumption history anytime via **Inventory → Usage Report**, filterable by date, month, or year, with PDF export
- Items without a recipe defined are unaffected — auto-deduction only applies once you set one up

---

## Order Types Supported
- Dine-In (with table selection + service charge)
- Takeaway
- Uber Eats
- PickMe Food
- Delivery

---

## Tech Stack
- **Backend**: PHP 7.4+ with PDO
- **Database**: MySQL / MariaDB
- **Frontend**: Vanilla HTML + CSS + JavaScript
- **Fonts**: Space Grotesk + JetBrains Mono (Google Fonts)
- **Theme**: Dark/modern restaurant tech style
