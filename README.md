# Supershop Management System (SSMS)

An all-in-one supermarket manager for Admins, Employees, and Customers. Fast inventory, simple checkout, clean UI, and role-based access.

## Table of Contents

1. Overview
2. Why SSMS
3. Tech Stack
4. Features
5. Project Structure
6. Installation
7. Configuration
8. Database Setup
9. Running the App
10. Test Accounts
11. How to Use
12. Contributing
13. Team
14. Author
15. License

## 1. Overview

SSMS is a lightweight retail system that helps:

- Admins manage staff, inventory, and orders
- Employees process sales and check stock
- Customers browse products, add to cart, and place orders

Built for small/medium stores that need something simpler than big ERPs.

## 2. Why SSMS

Common pain points we target:

- Manual stock mistakes
- Slow/unclear checkout flow
- Scattered sales records

SSMS brings a single, clean workflow with role-based access and a modern UI.

## 3. Tech Stack

- Frontend: HTML, CSS, JavaScript
- Backend: PHP (procedural + mysqli)
- Database: MySQL / MariaDB
- Local Dev: XAMPP / WAMP / MAMP
- Composer / Laravel not required

## 4. Features

### Role Matrix

| Capability | Admin | Employee | Customer |
|---|---|---|---|
| Secure Login | ✅ | ✅ | ✅ |
| Manage Products | ✅ | ✅ | ❌ |
| View / Update Orders | ✅ | ✅ | ❌ |
| Manage Users (CRUD) | ✅ | ❌ | ❌ |
| Browse Products | ✅ | ✅ | ✅ |
| Cart & Checkout | ✅ | ✅ | ✅ |
| Order History | ✅ | ✅ | ✅ |

### Highlights

- Role-based redirects
- Admin dashboard
- Employee panel
- Customer pages
- Clean responsive UI
- Product management
- Order management
- Session-based cart
- Order summary and VAT calculation
- Starter SQL for products, users, and orders

## 5. Project Structure

```text
Supershop_webtech_project/
├── css/
│   ├── index.css
│   ├── login.css
│   ├── payment.css
│   ├── order.css
│   ├── registration.css
│   └── user.css
├── img/
├── php/
│   ├── config.php
│   ├── index.php
│   ├── login.php
│   ├── regustration.php
│   ├── registrationem.php
│   ├── home.php
│   ├── dashboard.php
│   ├── payment.php
│   ├── orderlist.php
│   ├── order.php
│   ├── user.php
│   ├── admin_products.php
│   ├── logout.php
│   └── ...
├── sql/
│   └── addproductdb.sql
└── README.md
```

## 6. Installation

### Prerequisites

- PHP 8.0+
- MySQL / MariaDB
- XAMPP / WAMP / MAMP

### Clone Repository

```bash
git clone https://github.com/arshad055/Supershop_webtech_project.git
cd Supershop_webtech_project
```

### Move to Web Root

For XAMPP on Windows, copy the folder into:

```text
C:\xampp\htdocs\https://github.com/Supershop_webtech_project\
```

## 7. Configuration

Create or update `php/config.php`:

```php
<?php
declare(strict_types=1);

mysqli_report(MYSQLI_REPORT_ERROR | MYSQLI_REPORT_STRICT);

$DB_HOST = '127.0.0.1';
$DB_PORT = 3306;
$DB_USER = 'root';
$DB_PASS = '';
$DB_NAME = 'addproductdb';

$conn = new mysqli($DB_HOST, $DB_USER, $DB_PASS, $DB_NAME, $DB_PORT);
$conn->set_charset('utf8mb4');
```

## 8. Database Setup

1. Open phpMyAdmin.
2. Create a database named:

```text
addproductdb
```

3. Import the SQL file:

```text
sql/addproductdb.sql
```

Or run this schema manually:

```sql
CREATE TABLE IF NOT EXISTS users (
  id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  fullname VARCHAR(120) NOT NULL,
  email VARCHAR(120) NOT NULL UNIQUE,
  username VARCHAR(60) NOT NULL UNIQUE,
  password_hash VARCHAR(255) NOT NULL,
  phone VARCHAR(20),
  gender ENUM('male','female','other') DEFAULT 'other',
  role ENUM('ADMIN','EMPLOYEE','CUSTOMER') DEFAULT 'CUSTOMER',
  created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE IF NOT EXISTS products (
  id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  name VARCHAR(150) NOT NULL,
  price DECIMAL(10,2) NOT NULL DEFAULT 0.00,
  image VARCHAR(255),
  details TEXT,
  stock INT NOT NULL DEFAULT 0,
  created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE IF NOT EXISTS orders (
  id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  order_code VARCHAR(40) NOT NULL UNIQUE,
  user_id INT UNSIGNED NULL,
  status ENUM('pending','processing','paid','delivered','cancelled') NOT NULL DEFAULT 'pending',
  payment ENUM('unpaid','paid') NOT NULL DEFAULT 'unpaid',
  placed_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  customer_name VARCHAR(120) NOT NULL,
  customer_email VARCHAR(120) NOT NULL,
  address VARCHAR(255) NOT NULL,
  pay_method VARCHAR(60) NOT NULL,
  total DECIMAL(10,2) NOT NULL DEFAULT 0.00,
  FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE IF NOT EXISTS order_items (
  id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  order_id INT UNSIGNED NOT NULL,
  product_id INT UNSIGNED NULL,
  product_name VARCHAR(150) NOT NULL,
  qty INT UNSIGNED NOT NULL DEFAULT 1,
  unit_price DECIMAL(10,2) NOT NULL DEFAULT 0.00,
  line_total DECIMAL(10,2) NOT NULL DEFAULT 0.00,
  FOREIGN KEY (order_id) REFERENCES orders(id) ON DELETE CASCADE,
  FOREIGN KEY (product_id) REFERENCES products(id) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
```

If you see errors like:

```text
Unknown column o.status
```

Add the missing columns from the schema above.

## 9. Running the App

### With XAMPP

1. Start Apache.
2. Start MySQL.
3. Visit:

```text
http://localhost/Supershop_webtech_project/php/index.php
```

Login page:

```text
http://localhost/Supershop_webtech_project/php/login.php
```

### PHP Built-in Server

Optional:

```bash
php -S localhost:8000 -t php
```

Then visit:

```text
http://localhost:8000/index.php
```

## 10. Test Accounts

Change these in production.

- Admin — username: `admin`, password: `123456`
- Employee — username: `salman`, password: `123456`
- Customer — username: `apple`, password: `123456`

## 11. How to Use

1. Landing page: `index.php`
2. Click Login.
3. Register if needed:
   - Customer: `regustration.php`
   - Employee: `registrationem.php`
4. Login from `login.php`.
5. Shop products.
6. Add products to cart.
7. Go to `payment.php`.
8. Place order.
9. View orders:
   - Customer: `orderlist.php`
   - Admin: `order.php`

## 12. Contributing

1. Fork the repository.
2. Create a feature branch:

```bash
git checkout -b feature-name
```

3. Commit changes:

```bash
git commit -m "feat: add feature"
```

4. Push branch:

```bash
git push origin feature-name
```

5. Open a Pull Request.

## 13. Team

- Md. Arshad Islam — Employee — github.com/arshad055
- Salman Arefin — Customer — github.com/salmanarefin
- Md. Mahamodul Hasan Taj — Admin — github.com/Taj22-47271-1

## 14. Author

Md. Arshad Islam

## 15. License

This project is created for academic purposes.
