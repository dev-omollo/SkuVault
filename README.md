# SkuVault - Inventory Management System

A robust, modern full-stack Inventory Management System engineered with Laravel. SkuVault provides secure, data-driven supply chain tracking, automated warehouse workflows, and item life-cycle management from initial acquisition to customer delivery.

---

## 👥 Role-Based Access Control (RBAC)

SkuVault enforces strict structural permissions using three primary user roles:

| Role | Core Permissions | System Access |
| :--- | :--- | :--- |
| **System Admin** | Full access to user management, database backups, logs, configuration settings, and structural variables. | Entire System |
| **Warehouse Manager** | Stock level adjustments, supplier tracking, generating reports, inventory audits, and low-stock threshold triggers. | Inventory & Reporting Modules |
| **Staff Picker/Packer** | Viewing item lists, staging incoming orders, logging picking updates, updating delivery statuses, and scanning assets. | Order Fulfillment Operations |

---

## 🔌 Core REST API Endpoints

All data transactions interact seamlessly via secure JSON requests utilizing Laravel Sanctum tokens:

### Inventory Management
* **`GET /api/v1/inventory`** - Retrieve a paginated list of all active stock profiles.
* **`POST /api/v1/inventory`** - Create a new item entry (Requires Manager/Admin privileges).
* **`PUT /api/v1/inventory/{id}`** - Update specific item metrics, quantities, or warehouse locations.
* **`DELETE /api/v1/inventory/{id}`** - Archive an active inventory stock line profile.

### Authentication
* **`POST /api/v1/auth/login`** - Authenticate user credentials and return a secure Bearer token.
* **`POST /api/v1/auth/logout`** - Invalidate active session tokens.

---

## 🛠️ Local Development & Installation (Laragon)

Follow these steps to set up SkuVault inside your local Laragon development stack:

### 1. Project Placement
Clone or move your project directory into your main Laragon virtual directory:
```bash
cd C:\laragon\www\SkuVault
```

### 2. Configure Environment Files
Duplicate the default example environment profile:
```bash
cp .env.example .env
```
Open your newly created `.env` file and update your database connections to match Laragon's default Mysql instance:
```env
DB_CONNECTION=mysql
DB_HOST=127.0.0.1
DB_PORT=3306
DB_DATABASE=skuvault
DB_USERNAME=root
DB_PASSWORD=
```

### 3. Install Package Dependencies
Run the standard dependency download scripts via Composer and Node Package Manager:
```bash
composer install
npm install && npm run build
```

### 4. Key Generation & Database Setup
Generate a unique application storage encryption key and execute the database migration tracking tables:
```bash
php artisan key:generate
php artisan migrate --seed
```

---

## 🧪 Running Automated Tests

SkuVault features pre-configured unit and behavioral feature testing frameworks. Run the command below inside your terminal to execute your code test suites:

```bash
php artisan test
```
