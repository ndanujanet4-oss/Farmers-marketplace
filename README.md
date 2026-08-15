# Farmers Marketplace

A PHP + MySQL web app connecting farmers and consumers. Any user can act as
both a farmer (selling produce) and a customer (buying produce) on the same
account.

## Features
- Single login for both selling and buying
- Unified dashboard showing sales and purchases
- Shared produce catalog (Vegetables, Legumes, Cereals) - farmers set their
  own price and quantity per listing
- Multi-item checkout - one order can contain items from several farmers
- Per-item delivery workflow: farmer marks "out for delivery", customer
  confirms receipt, order auto-completes via a MySQL trigger once every
  item on it has been delivered

## Tech stack
- PHP (PDO for database access)
- MySQL / MariaDB
- Vanilla HTML/CSS (no framework)

## Setup

1. **Clone the repo**
   ```
   git clone <your-repo-url>
   cd farmers-marketplace
   ```

2. **Create the database**
   Open phpMyAdmin → SQL tab → paste and run `database_setup.sql`.
   This creates the database, all tables, the auto-completion trigger,
   and populates the produce catalog with sample data.

3. **Configure your database connection**
   ```
   cp db_connect.example.php db_connect.php
   ```
   Edit `db_connect.php` with your local MySQL username/password/port.
   This file is gitignored, so your credentials stay local.

4. **Serve the project**
   Point your local server (XAMPP/WAMP/MAMP, or `php -S localhost:8000`)
   at this folder, then visit `register.php` to create an account.

## Project structure

| File | Purpose |
|---|---|
| `database_setup.sql` | Full schema + trigger + sample data (fresh install) |
| `migration_delivery_status.sql` | Adds delivery tracking to an existing DB |
| `migration_order_completion_trigger.sql` | Adds the auto-complete trigger to an existing DB |
| `db_connect.example.php` | Template - copy to `db_connect.php` |
| `register.php` / `login.php` / `logout.php` | Authentication |
| `dashboard.php` | Sales, purchases, and listings overview |
| `add_listing.php` | Farmer lists produce for sale at their own price |
| `browse.php` | Browse all available produce, select multiple items to buy |
| `place_order.php` | Creates an order (possibly spanning several farmers) in one transaction |
| `mark_dispatched.php` | Farmer marks their sold item as out for delivery |
| `confirm_receipt.php` | Customer confirms receipt of a delivered item |
| `style.css` | Shared styling |
| `erd_diagram.drawio` | Entity-Relationship Diagram (open in draw.io) |
| `system_architecture.drawio` | System architecture diagram (open in draw.io) |
