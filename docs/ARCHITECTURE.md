# HugouERP System Architecture

**Version:** 1.0  
**Last Updated:** 2025-12-11

---

## Table of Contents

1. [Overview](#overview)
2. [Module Architecture](#module-architecture)
3. [Database Architecture](#database-architecture)
4. [Route Structure](#route-structure)
5. [Module Dependencies](#module-dependencies)
6. [Security & Permissions](#security--permissions)
7. [Multi-Branch Support](#multi-branch-support)
8. [Best Practices](#best-practices)

---

## Overview

HugouERP is a modular Enterprise Resource Planning system built on Laravel 12, Livewire 3, and Tailwind CSS. The system is designed with:

- **Modular Architecture** - Independent modules that can be enabled/disabled per branch
- **Product-Centric Design** - Core inventory system supports multiple business flows
- **Multi-Branch Support** - Separate data silos for different business locations
- **Role-Based Access Control** - Granular permissions system
- **API-First Approach** - RESTful API for all business operations

---

## Module Architecture

### Module Types

HugouERP modules are categorized into three types:

#### 1. Core Modules (Always Enabled)
- **Inventory** - Product management, categories, units of measure
- **Sales** - Sales order management
- **Purchases** - Purchase order management  
- **POS** - Point of Sale terminal
- **Reports** - Analytics and reporting

#### 2. Product-Based Modules (Depend on Inventory)
These modules require the core Inventory module and work with products:

- **Manufacturing** - Production orders, BOMs, work centers
- **Warehouse** - Stock movements, transfers, adjustments
- **Spares** - Spare parts compatibility, vehicle models
- **Stores** - E-commerce integration

**Key Characteristic:** All product-based modules reference a **single `products` table**. There are no duplicate or shadow product tables.

#### 3. Independent Modules (No Product Dependency)
These modules operate independently and can function without the Inventory module:

- **Accounting** - Chart of accounts, journal entries, fiscal periods
- **HRM** - Human resources, attendance, payroll, leave management
- **Rental** - Property and equipment rental management
- **Fixed Assets** - Asset tracking and depreciation
- **Banking** - Bank account management and reconciliation
- **Expenses** - Expense tracking and categories
- **Income** - Income tracking and categories
- **Projects** - Project management, tasks, milestones
- **Documents** - Document management system
- **Helpdesk** - Ticketing system

---

## Database Architecture

### Schema Organization

```
┌─────────────────────────────────────────────────────────────────┐
│                         FOUNDATION LAYER                         │
├─────────────────────────────────────────────────────────────────┤
│ branches, users, roles, permissions, modules, system_settings   │
└─────────────────────────────────────────────────────────────────┘
                              │
                              ▼
┌─────────────────────────────────────────────────────────────────┐
│                       CORE BUSINESS LAYER                        │
├─────────────────────────────────────────────────────────────────┤
│ customers, suppliers                                             │
└─────────────────────────────────────────────────────────────────┘
                              │
                              ▼
┌─────────────────────────────────────────────────────────────────┐
│                     PRODUCT-CENTRIC LAYER                        │
├─────────────────────────────────────────────────────────────────┤
│ products ◄────┬────────────────────────────────┐                │
│               │                                 │                │
│    sales ────►│◄──── purchases                 │                │
│    sale_items │     purchase_items             │                │
│               │                                 │                │
│    pos_sessions                                 │                │
│               │                                 │                │
│    bills_of_materials                          │                │
│    bom_items, production_orders                │                │
│               │                                 │                │
│    stock_movements, inventory_batches          │                │
│    adjustments, transfers                      │                │
└─────────────────────────────────────────────────────────────────┘
                              │
                              ▼
┌─────────────────────────────────────────────────────────────────┐
│                     INDEPENDENT MODULES LAYER                    │
├─────────────────────────────────────────────────────────────────┤
│ accounts, journal_entries  │  hr_employees, attendances          │
│ rental_units, contracts    │  fixed_assets                       │
│ expenses, incomes          │  projects, documents, tickets       │
└─────────────────────────────────────────────────────────────────┘
                              │
                              ▼
┌─────────────────────────────────────────────────────────────────┐
│                     CROSS-CUTTING LAYER                          │
├─────────────────────────────────────────────────────────────────┤
│ audit_logs, notifications, attachments, notes                   │
└─────────────────────────────────────────────────────────────────┘
```

### Table Naming Conventions

- **Singular nouns** for models: `Product`, `Sale`, `Employee`
- **Plural nouns** for tables: `products`, `sales`, `hr_employees`
- **Module prefixes** for clarity: `rental_`, `hr_`, `bom_`
- **Relationship tables**: `module_name + entity` (e.g., `sale_items`, `purchase_items`)

### Foreign Key Patterns

```php
// Standard FK pattern
$table->foreignId('branch_id')->constrained('branches')->cascadeOnDelete();
$table->foreignId('product_id')->constrained('products')->restrictOnDelete();
$table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();

// Polymorphic relationships (cross-cutting tables)
$table->morphs('subject'); // Creates subject_type, subject_id
```

---

## Route Structure

### Route Naming Convention

All business module routes use the **canonical `app.*` prefix**:

```
app.{module}.{resource}.{action}
```

**Examples:**
```
app.inventory.products.index
app.manufacturing.boms.create
app.warehouse.transfers.edit
app.hrm.employees.show
app.rental.contracts.index
```

### Route Organization

```
routes/web.php
├── Root & Health Check
├── Authentication Routes
├── Authenticated Routes
│   ├── Dashboard
│   ├── Profile & Notifications
│   ├── POS Terminal (special case, no /app prefix)
│   ├── Business Modules (/app/{module})
│   │   ├── Sales
│   │   ├── Purchases
│   │   ├── Inventory
│   │   ├── Warehouse
│   │   ├── Rental
│   │   ├── Manufacturing
│   │   ├── HRM
│   │   ├── Banking
│   │   ├── Fixed Assets
│   │   ├── Projects
│   │   ├── Documents
│   │   ├── Helpdesk
│   │   ├── Accounting
│   │   ├── Expenses & Income
│   │   └── Customers & Suppliers
│   ├── Admin Area (/admin/*)
│   │   ├── Users & Roles
│   │   ├── Branches
│   │   ├── Modules
│   │   ├── Stores
│   │   ├── Currencies
│   │   ├── Settings
│   │   ├── Audit Logs
│   │   └── Reports
│   └── Legacy Redirects (backward compatibility)
```

### API Routes

```
routes/api.php
└── routes/api/v1/
    ├── auth.php          (Authentication endpoints)
    ├── core.php          (Shared resources)
    ├── branch.php        (Branch-specific endpoints)
    └── [module].php      (Per-module API routes)
```

---

## Module Dependencies

### Dependency Graph

```
┌─────────────────┐
│   FOUNDATION    │
│  branches       │
│  users, roles   │
│  permissions    │
└────────┬────────┘
         │
         ▼
┌─────────────────┐         ┌─────────────────┐
│  CORE MODULES   │────────►│  PRODUCT-BASED  │
│  inventory      │         │  Manufacturing  │
│  sales          │         │  Warehouse      │
│  purchases      │         │  Spares         │
│  pos            │         │  Stores         │
└─────────────────┘         └─────────────────┘
         │
         │
         ▼
┌─────────────────────────────────────────────┐
│         INDEPENDENT MODULES                  │
│  Accounting, HRM, Rental, Banking,          │
│  Fixed Assets, Expenses, Income,            │
│  Projects, Documents, Helpdesk              │
└─────────────────────────────────────────────┘
```

### Enabling/Disabling Modules

Modules can be enabled or disabled:
- **Globally** via system settings
- **Per-branch** via `branch_modules` table

**Rules:**
1. Core modules cannot be disabled
2. Product-based modules require Inventory module
3. Independent modules can be disabled without affecting others
4. Disabling a parent module automatically disables dependent modules

---

## Security & Permissions

### Permission Structure

Permissions follow the pattern: `{module}.{action}`

**Examples:**
```
inventory.products.view
inventory.products.create
sales.manage
manufacturing.view
hrm.payroll.run
accounting.journal-entries.post
```

### Permission Checking

```php
// In routes
->middleware('can:inventory.products.view')

// In controllers/Livewire components
$this->authorize('sales.manage');

// In Blade templates
@can('manufacturing.view')
    <!-- Content -->
@endcan
```

### Special Permissions

- **Super Admin** - Bypasses all permission checks
- **Branch Admin** - Full access within assigned branches
- **Screen-specific** - Some screens have custom permission keys defined in `config/screen_permissions.php`

---

## Multi-Branch Support

### Branch Isolation

Each branch operates as a separate business entity:

- **Separate data** - Sales, purchases, inventory per branch
- **Shared foundation** - Users, roles, modules are shared
- **Branch-specific settings** - Each branch can configure module settings

### Branch Switching

Users can switch between branches if assigned to multiple:

```php
// Get current branch
$branchId = current_branch_id();

// Switch branch (session-based)
session(['current_branch_id' => $branchId]);

// All queries automatically scope to current branch
Sale::all(); // Only returns sales for current branch
```

### Cross-Branch Features

- **Users** - Can be assigned to multiple branches
- **Products** - Optionally shared across branches (via settings)
- **Reports** - Can aggregate data across branches (with permission)

---

## Best Practices

### When Adding New Modules

1. **Determine module type**
   - Does it need products? → Product-based module
   - Independent of inventory? → Independent module

2. **Follow naming conventions**
   - Routes: `app.{module}.{resource}.{action}`
   - Tables: `{module_prefix}_{table_name}` (plural)
   - Models: `{ModuleName}` (singular, StudlyCase)

3. **Create proper migrations**
   - One migration per table (avoid duplicates)
   - Use conditional checks only for cross-module tables
   - Always add proper indexes and foreign keys

4. **Define permissions**
   - Add permission definitions to seeders
   - Follow pattern: `{module}.{action}`
   - Document screen permissions in `config/screen_permissions.php`

5. **Register in module system**
   - Add to `ModulesSeeder`
   - Add navigation in `ModuleNavigationSeeder`
   - Add quick actions in `config/quick-actions.php`

### Code Organization

```
app/
├── Models/               (Eloquent models)
├── Livewire/            (Livewire components by module)
│   ├── Manufacturing/
│   ├── Inventory/
│   └── ...
├── Http/
│   ├── Controllers/     (API controllers)
│   └── Middleware/      (Custom middleware)
└── Services/            (Business logic services)

resources/
├── views/
│   ├── livewire/       (Livewire view files)
│   └── layouts/        (Layout templates)
└── js/                 (JavaScript assets)

database/
├── migrations/         (Database migrations)
└── seeders/           (Database seeders)
```

### Migration Best Practices

1. **One table per migration** (with rare exceptions)
2. **Timestamp order matters** - Dependencies must be created first
3. **Use `if (!Schema::hasTable())` only for safety nets**
4. **Always provide `down()` method**
5. **Document complex migrations** with inline comments

### Testing Module Boundaries

When adding/modifying modules:

1. **Test independent module isolation**
   - Disable inventory module
   - Verify independent modules still function
   
2. **Test product dependencies**
   - Verify product-based modules break gracefully without inventory
   - Check all foreign keys point to `products` table (not shadow tables)

3. **Test branch isolation**
   - Create data in Branch A
   - Switch to Branch B
   - Verify Branch A data is not accessible

---

## Migration Timeline

### Phase 1: Foundation (2025-11-15)
- Branches, users, roles, permissions
- Modules and system settings
- Core business entities (customers, suppliers)

### Phase 2: Core Business (2025-11-15)
- Products, categories, units
- Sales and purchases
- Stock movements
- Vehicles and rentals

### Phase 3: Advanced Features (2025-11-25 - 2025-12-07)
- Module management system
- Store integrations
- Product variations
- Report templates and scheduling
- Currencies
- Manufacturing system
- Accounting enhancement
- Workflow engine
- Dashboard configurator
- Smart alerts

### Phase 4: Optimization (2025-12-08 - 2025-12-10)
- Performance indexes
- API filter optimization
- Column mismatch fixes
- Migration consolidation

---

## Conclusion

HugouERP's architecture is designed for:

- **Scalability** - Modular design allows adding features without affecting existing ones
- **Flexibility** - Independent modules can be used in various business contexts
- **Maintainability** - Clear separation of concerns and consistent naming
- **Multi-tenancy** - Branch isolation ensures data privacy

For questions or contributions, refer to `CONTRIBUTING.md`.

---

**Document Maintained By:** Development Team  
**Related Documents:**
- `DEEP_VERIFICATION_REPORT.md` - Detailed system verification
- `VERIFICATION_SUMMARY.md` - Executive summary
- `README.md` - Project overview and setup
