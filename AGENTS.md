# MigrationsHelper

Wrapper module that runs RockMigrations-based schema migrations from structured PHP files inside a module's `migrations/` directory. Provides a simple convention-over-configuration API so modules can ship their own schema as plain PHP arrays.

## API variable

Not registered as a PW API variable. Load it explicitly:

```php
$mh = wire('modules')->get('MigrationsHelper');
```

## Usage

Call `run()` with the module folder name. It reads all migration files from `site/modules/{ModuleName}/migrations/` and applies them via RockMigrations.

```php
$mh = wire('modules')->get('MigrationsHelper');
$mh->run('MyModule');
```

Typically called from a module's `___install()` or `ready()`:

```php
public function ___install(): void {
    wire('modules')->get('MigrationsHelper')->run('MyModule');
}
```

## Migration file structure

Create a `migrations/` directory inside your module. MigrationsHelper looks for these files:

```
site/modules/MyModule/
└── migrations/
    ├── roles.php        # returns array of role definitions
    ├── fields.php       # returns array of field definitions
    ├── templates.php    # returns array of template definitions
    ├── pages.php        # returns array of page definitions
    └── permissions.php  # returns array of permission definitions (name => title)
```

All files are optional. Missing files are silently skipped.

### `fields.php` example

```php
<?php
return [
    'product_sku' => [
        'type'  => 'FieldtypeText',
        'label' => 'SKU',
    ],
    'product_price' => [
        'type'  => 'FieldtypeFloat',
        'label' => 'Price',
    ],
];
```

### `templates.php` example

```php
<?php
return [
    'product' => [
        'label'  => 'Product',
        'fields' => ['title', 'product_sku', 'product_price', 'body', 'images'],
    ],
];
```

### `roles.php` example

```php
<?php
return [
    'shop-manager' => [
        'permissions' => ['page-view', 'page-edit'],
    ],
];
```

### `pages.php` example

```php
<?php
return [
    [
        'template' => 'product',
        'parent'   => '/products/',
        'name'     => 'sample-product',
        'data'     => ['title' => 'Sample Product'],
    ],
];
```

### `permissions.php` example

```php
<?php
return [
    'shop-manage-orders' => 'Manage Orders',
    'shop-manage-stock'  => 'Manage Stock',
];
```

## What `run()` does internally

1. Checks RockMigrations and Autoloader are installed (returns silently if not).
2. Calls `$rm->migrate(['fields' => ..., 'templates' => ..., 'roles' => ...])` with merged data.
3. Creates each page from `pages.php` if it does not already exist (idempotent).
4. Creates each permission from `permissions.php` via `$rm->createPermission()`.

## Dependencies

Requires both **RockMigrations** and **Autoloader** modules to be installed. `run()` returns silently if either is missing.

## Notes

- `autoload = false` — the module is not loaded until explicitly requested.
- `run()` is idempotent: calling it multiple times on the same module does not duplicate fields, templates, roles, or permissions (RockMigrations handles deduplication).
- Page creation is guarded by a `$pages->get(parent + name)` check — existing pages are not recreated.
- For complex migrations with custom logic, use AgentTools migrations (`site/assets/at/migrations/`) instead of this module.
