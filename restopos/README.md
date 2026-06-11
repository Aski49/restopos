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
- Import `database.sql`

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

| Role     | Username  | Password     |
|----------|-----------|--------------|
| Admin    | admin     | admin123     |
| Manager  | manager   | manager123   |
| Cashier  | cashier   | cashier123   |

---

## Modules Included

| Module        | Description                                      |
|---------------|--------------------------------------------------|
| Dashboard     | Live stats, low stock alerts, recent bills       |
| POS / Billing | Full item selection, cart, payment, receipt      |
| Sales Reports | By date/type/payment, charts, CSV export         |
| Inventory     | Stock register, purchase recording, low stock    |
| Expenses      | Record & categorise expenses, reports            |
| Debtors       | Debtor ledger, payment recording                 |
| Payroll       | EPF 8%/12%, ETF 3%, payslip printing            |
| Cash/Banking  | Bank accounts, transactions, cash flow           |
| Menu Manager  | Add/edit/deactivate menu items & categories      |
| Employees     | Attendance marking, employee register            |
| Reports       | P&L, Expense, Cash Flow, Debtor, Inventory, Payroll |
| Settings      | Business info, tax %, user management           |

---

## Sri Lanka Payroll Formula
- **EPF Employee**: 8% of (Basic + Allowances)
- **EPF Employer**: 12% of (Basic + Allowances)
- **ETF Employer**: 3% of (Basic + Allowances)
- **Net = Gross − EPF(Emp) − Advance − Other Deductions**

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
