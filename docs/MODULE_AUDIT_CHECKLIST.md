# Module Audit Verification Checklist

Use this checklist for future module audits or when adding new modules.

## 1. Backend Verification

### Controllers
- [ ] Controller exists in appropriate namespace (app/Http/Controllers or app/Http/Controllers/Branch)
- [ ] All controller methods are properly typed
- [ ] Controller uses proper authorization (policies, gates, or middleware)
- [ ] No unused controller methods

### Routes
- [ ] Routes follow canonical naming: `app.{module}.{resource}.{action}`
- [ ] Route model binding used (e.g., `{branch}` not `{branchId}`)
- [ ] Middleware properly applied (auth, permissions)
- [ ] No duplicate route names
- [ ] No overlapping URI patterns

### Models
- [ ] Model exists in app/Models
- [ ] Proper relationships defined (belongsTo, hasMany, etc.)
- [ ] Fillable/guarded attributes configured
- [ ] Casts defined for special attributes
- [ ] Scopes for common queries
- [ ] No N+1 query issues (eager loading configured)

### Migrations
- [ ] Migration creates all necessary tables
- [ ] Foreign keys properly constrained
- [ ] Indexes on commonly queried columns
- [ ] No conflicting table/column names
- [ ] Migration is reversible (down method works)

## 2. API Verification (if applicable)

### Branch API Routes
- [ ] Routes under `/api/v1/branches/{branch}` prefix
- [ ] Middleware: `api-core`, `api-auth`, `api-branch`
- [ ] Module-specific routes use `module.enabled:{module}` middleware
- [ ] `{branch}` model binding used consistently
- [ ] Controllers use `Branch $branch` parameter

### API Controllers
- [ ] Proper request validation
- [ ] Consistent response format (JSON)
- [ ] Error handling implemented
- [ ] Rate limiting configured (if needed)
- [ ] API documentation (OpenAPI/Swagger)

## 3. Frontend Verification

### Livewire Components
- [ ] Component exists in app/Livewire/{Module}
- [ ] Proper authorization in mount()
- [ ] Validation rules defined
- [ ] Redirects use canonical route names
- [ ] No hardcoded IDs or paths
- [ ] Proper error handling

### Views
- [ ] View exists in resources/views/livewire/{module}
- [ ] Uses layout: `#[Layout('layouts.app')]`
- [ ] All route() calls use canonical names
- [ ] No broken route references
- [ ] Responsive design (if applicable)

### Navigation
- [ ] Module listed in ModuleNavigationSeeder.php
- [ ] Sidebar link uses correct route name
- [ ] Icon assigned (emoji or icon class)
- [ ] Permissions properly checked
- [ ] Sort order configured

### Quick Actions
- [ ] Quick action defined in config/quick-actions.php (if applicable)
- [ ] Route name is correct
- [ ] Permission specified
- [ ] Color and icon configured

## 4. Form Verification

### Create/Edit Forms
- [ ] Uses route model binding in mount()
- [ ] Example: `mount(?Model $model = null)`
- [ ] Validation rules defined and tested
- [ ] Save method redirects to correct route
- [ ] Cancel/Back button uses correct route
- [ ] Success/error messages displayed

### Redirects Check
```php
// ✅ Correct patterns
$this->redirect(route('app.module.resource.index'));
$this->redirectRoute('app.module.resource.index');

// ❌ Avoid
$this->redirect('/hardcoded/path');
return redirect()->to('/hardcoded/path');
```

## 5. Data Architecture

### Product-Based Modules
If module uses products:
- [ ] Uses canonical `products` table
- [ ] Foreign key to `products.id`
- [ ] No duplicate product schema
- [ ] Relationships properly defined

### Non-Product Modules
If module has independent entities:
- [ ] Entity tables properly designed
- [ ] No naming conflicts with existing tables
- [ ] Clear separation from product schema

## 6. Module Registration

### ModuleNavigationSeeder
- [ ] Module listed once (no duplicates)
- [ ] Correct module_id reference
- [ ] Route name is valid
- [ ] Permissions are correct
- [ ] Parent/child hierarchy proper (if applicable)
- [ ] Sort order set

### Module Seeder (if applicable)
- [ ] Module registered in modules table
- [ ] Slug is unique
- [ ] Icon and color configured
- [ ] Active by default (or documented as optional)

## 7. Code Quality

### PHP Standards
- [ ] No syntax errors (`php -l`)
- [ ] PSR-12 coding standards
- [ ] Type hints on all methods
- [ ] Proper docblocks
- [ ] No unused imports

### Security
- [ ] Authorization checks in place
- [ ] Input validation on all forms
- [ ] SQL injection prevention (Eloquent)
- [ ] XSS prevention (Blade escaping)
- [ ] CSRF protection (enabled by default)

## 8. Testing

### Unit Tests
- [ ] Model tests (relationships, scopes)
- [ ] Service layer tests (if applicable)
- [ ] Helper function tests

### Feature Tests
- [ ] Route tests (can access, authorization)
- [ ] Form submission tests
- [ ] API endpoint tests
- [ ] Validation tests

### Browser Tests (if applicable)
- [ ] Critical user flows
- [ ] Form interactions
- [ ] Navigation tests

## 9. Documentation

### Code Documentation
- [ ] Controller methods documented
- [ ] Complex logic has comments
- [ ] API endpoints documented (OpenAPI)
- [ ] README for module (if complex)

### User Documentation
- [ ] Feature description
- [ ] User guide (if needed)
- [ ] API usage examples
- [ ] Configuration options

## 10. Regression Prevention

### Before Committing
- [ ] No syntax errors
- [ ] Existing tests pass
- [ ] New tests added (if applicable)
- [ ] Routes still work (no broken references)
- [ ] Navigation still correct

### After Merging
- [ ] CI/CD pipeline passes
- [ ] Staging environment tested
- [ ] No console errors
- [ ] Performance acceptable

---

## Common Issues Checklist

### Route Issues
- [ ] ❌ Using old route names (without `app.` prefix)
- [ ] ❌ Hardcoded paths instead of route()
- [ ] ❌ Route model binding not used (manual findOrFail)
- [ ] ❌ Duplicate route names

### Form Issues
- [ ] ❌ Using `?int $id` instead of `?Model $model`
- [ ] ❌ Redirecting to wrong route
- [ ] ❌ Missing validation rules
- [ ] ❌ Not handling errors

### API Issues
- [ ] ❌ Not under `/api/v1/branches/{branch}`
- [ ] ❌ Missing middleware stack
- [ ] ❌ Using `{branchId}` instead of `{branch}`
- [ ] ❌ Inconsistent response format

### Navigation Issues
- [ ] ❌ Route name in seeder doesn't match actual route
- [ ] ❌ Duplicate nav entries
- [ ] ❌ Wrong permissions
- [ ] ❌ Missing from sidebar

---

## Quick Verification Commands

```bash
# Check PHP syntax
find app -name "*.php" -exec php -l {} \; | grep -i error

# Find route definitions
grep -r "Route::" routes/ | wc -l

# Find Livewire components
find app/Livewire -name "*.php" | wc -l

# Find models
find app/Models -name "*.php" | wc -l

# Count migrations
ls -1 database/migrations/*.php | wc -l

# Search for route names
grep -r "route('app\." --include="*.php" --include="*.blade.php"

# Search for old route patterns
grep -r "route('" routes/ app/ resources/ | grep -v "app\."
```

---

**Last Updated**: 2025-12-12  
**Use for**: New module additions, periodic audits, troubleshooting
