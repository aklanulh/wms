# Flow Diagram MVC - PT. Mitrajaya Selaras Abadi Inventory System

## 1. ARSITEKTUR MVC OVERVIEW

```
┌─────────────────────────────────────────────────────────────────────────────┐
│                           LARAVEL MVC ARCHITECTURE                          │
├─────────────────────────────────────────────────────────────────────────────┤
│                                                                             │
│  ┌─────────────┐    ┌─────────────┐    ┌─────────────┐    ┌─────────────┐  │
│  │   BROWSER   │◄──►│   ROUTES    │◄──►│ CONTROLLER  │◄──►│    MODEL    │  │
│  │   (USER)    │    │  (web.php)  │    │   (Logic)   │    │   (Data)    │  │
│  └─────────────┘    └─────────────┘    └─────────────┘    └─────────────┘  │
│         ▲                                       │                   ▲       │
│         │                                       ▼                   │       │
│         │            ┌─────────────┐    ┌─────────────┐             │       │
│         └────────────►│    VIEW     │◄───│ MIDDLEWARE  │             │       │
│                       │ (.blade.php)│    │ (Auth, etc) │             │       │
│                       └─────────────┘    └─────────────┘             │       │
│                                                                      │       │
│                                          ┌─────────────┐             │       │
│                                          │  DATABASE   │◄────────────┘       │
│                                          │  (SQLite)   │                     │
│                                          └─────────────┘                     │
└─────────────────────────────────────────────────────────────────────────────┘
```

## 2. DETAILED MVC FLOW - DASHBOARD EXAMPLE

```
USER REQUEST: GET /dashboard
│
├─ 1. ROUTE LAYER (web.php)
│   │
│   └─► Route::get('/', [DashboardController::class, 'index'])
│       │
│       ├─ 2. MIDDLEWARE CHECK
│       │   │
│       │   └─► auth, super_admin middleware
│       │       │
│       │       ├─ ✓ Authenticated? → Continue
│       │       └─ ✗ Not authenticated? → Redirect to login
│       │
│       └─ 3. CONTROLLER LAYER (DashboardController@index)
│           │
│           ├─ 4. MODEL INTERACTIONS
│           │   │
│           │   ├─► Product::count() ──────────────┐
│           │   ├─► Product::lowStock()->count() ──┤
│           │   ├─► StockMovement::stockIn() ──────┤
│           │   ├─► StockMovement::stockOut() ─────┤── DATABASE QUERIES
│           │   ├─► Supplier::count() ─────────────┤
│           │   ├─► Customer::count() ─────────────┤
│           │   └─► StockMovement::with([...]) ────┘
│           │                                      │
│           ├─ 5. DATA PROCESSING                  │
│           │   │                                  ▼
│           │   ├─► Calculate KPIs            ┌─────────────┐
│           │   ├─► Generate chart data       │   SQLite    │
│           │   ├─► Stock aging analysis      │  Database   │
│           │   └─► Format statistics         └─────────────┘
│           │
│           └─ 6. RETURN VIEW
│               │
│               └─► return view('dashboard', compact(...))
│                   │
│                   └─ 7. VIEW LAYER (dashboard.blade.php)
│                       │
│                       ├─► Blade Template Processing
│                       ├─► Include CSS (Tailwind)
│                       ├─► Include JS (Alpine.js, Chart.js)
│                       ├─► Render HTML with data
│                       │
│                       └─ 8. RESPONSE TO BROWSER
│                           │
│                           └─► Complete HTML page with data
```

## 3. COMPONENT BREAKDOWN

### A. ROUTES (Entry Points)
```
routes/web.php
├─ Authentication Routes
│   ├─ GET  /login
│   ├─ POST /login
│   └─ POST /logout
│
├─ Super Admin Routes (middleware: auth, super_admin)
│   ├─ GET  / (dashboard)
│   ├─ CRUD /products
│   ├─ CRUD /suppliers
│   ├─ CRUD /customers
│   ├─ Stock /stock/in, /stock/out, /stock/opname
│   └─ Reports /reports/*
│
└─ Regular Admin Routes (middleware: auth)
    ├─ GET  /admin-dashboard
    ├─ VIEW /admin/products
    └─ Stock /admin/stock/*
```

### B. CONTROLLERS (Business Logic)
```
app/Http/Controllers/
├─ DashboardController.php
│   └─ index() → Dashboard KPIs, Charts, Analytics
│
├─ ProductController.php
│   ├─ index() → Product listing
│   ├─ create() → Product form
│   ├─ store() → Save product + AJAX
│   ├─ show() → Product detail
│   ├─ edit() → Edit form
│   ├─ update() → Update product
│   └─ destroy() → Delete product
│
├─ StockMovementController.php
│   ├─ stockInIndex() → Stock in listing
│   ├─ stockInCreate() → Stock in form
│   ├─ stockInStore() → Process stock in
│   ├─ stockOutIndex() → Stock out listing
│   ├─ stockOutCreate() → Stock out form
│   └─ stockOutStore() → Process stock out
│
├─ ReportController.php
│   ├─ stockReport() → Stock report
│   ├─ movementReport() → Movement report
│   ├─ supplierReport() → Supplier report
│   ├─ customerReport() → Customer report
│   └─ export*() → Excel/CSV exports
│
├─ SupplierController.php
│   └─ CRUD operations + AJAX store
│
└─ CustomerController.php
    └─ CRUD operations + AJAX store
```

### C. MODELS (Data Layer)
```
app/Models/
├─ Product.php
│   ├─ Relationships: belongsTo(ProductCategory), hasMany(StockMovement)
│   ├─ Scopes: lowStock(), outOfStock()
│   └─ Attributes: current_stock, minimum_stock, price
│
├─ StockMovement.php
│   ├─ Relationships: belongsTo(Product, Supplier, Customer)
│   ├─ Scopes: stockIn(), stockOut(), thisMonth()
│   └─ Attributes: type, quantity, unit_price, transaction_date
│
├─ Supplier.php
│   ├─ Relationships: hasMany(StockMovement)
│   └─ Attributes: name, contact_person, phone, address
│
├─ Customer.php
│   ├─ Relationships: hasMany(StockMovement)
│   └─ Attributes: name, contact_person, phone, address
│
├─ ProductCategory.php
│   ├─ Relationships: hasMany(Product)
│   └─ Attributes: name, description
│
└─ User.php
    ├─ Authentication model
    └─ Attributes: name, email, role
```

### D. VIEWS (Presentation Layer)
```
resources/views/
├─ layouts/
│   └─ app.blade.php → Main layout with sidebar, header
│
├─ dashboard.blade.php → Main dashboard
│   ├─ KPI Cards (6 cards)
│   ├─ Monthly Activity Cards (4 cards)
│   ├─ Stock Aging Analysis (3 sections)
│   ├─ Charts (Chart.js integration)
│   ├─ Top Products Table
│   ├─ Critical Stock Alerts
│   └─ Recent Activities Table
│
├─ products/ → Product management views
├─ stock/ → Stock transaction views
├─ suppliers/ → Supplier management views
├─ customers/ → Customer management views
└─ reports/ → Report views with export functionality
```

## 4. DATA FLOW EXAMPLE - STOCK IN TRANSACTION

```
USER FILLS STOCK IN FORM
│
├─ 1. FORM SUBMISSION
│   │
│   └─► POST /stock/in (with form data)
│       │
│       ├─ 2. ROUTE RESOLUTION
│       │   │
│       │   └─► StockMovementController@stockInStore
│       │       │
│       │       ├─ 3. VALIDATION
│       │       │   │
│       │       │   ├─► Validate product_id
│       │       │   ├─► Validate supplier_id
│       │       │   ├─► Validate quantity > 0
│       │       │   └─► Validate unit_price
│       │       │
│       │       ├─ 4. MODEL OPERATIONS
│       │       │   │
│       │       │   ├─► StockMovement::create([...])
│       │       │   │   │
│       │       │   │   └─► INSERT INTO stock_movements
│       │       │   │
│       │       │   └─► Product::find($id)->increment('current_stock', $qty)
│       │       │       │
│       │       │       └─► UPDATE products SET current_stock = current_stock + qty
│       │       │
│       │       ├─ 5. SUCCESS RESPONSE
│       │       │   │
│       │       │   └─► redirect()->route('stock.in.index')
│       │       │       │    ->with('success', 'Stock berhasil ditambahkan')
│       │       │
│       │       └─ 6. VIEW RENDERING
│       │           │
│       │           └─► stock/in/index.blade.php with success message
│
└─ 7. USER SEES UPDATED STOCK LIST
```

## 5. TECHNOLOGY STACK INTEGRATION

```
┌─────────────────────────────────────────────────────────────────┐
│                        FRONTEND STACK                          │
├─────────────────────────────────────────────────────────────────┤
│  ┌─────────────┐  ┌─────────────┐  ┌─────────────┐  ┌─────────┐ │
│  │ Tailwind CSS│  │  Alpine.js  │  │  Chart.js   │  │FontAwesome│ │
│  │  (Styling)  │  │(Interaction)│  │(Visualization)│ │ (Icons) │ │
│  └─────────────┘  └─────────────┘  └─────────────┘  └─────────┘ │
└─────────────────────────────────────────────────────────────────┘
                                │
                                ▼
┌─────────────────────────────────────────────────────────────────┐
│                        BACKEND STACK                           │
├─────────────────────────────────────────────────────────────────┤
│  ┌─────────────┐  ┌─────────────┐  ┌─────────────┐  ┌─────────┐ │
│  │   Laravel   │  │    Blade    │  │ Eloquent ORM│  │ SQLite  │ │
│  │ (Framework) │  │ (Templates) │  │   (Models)  │  │(Database)│ │
│  └─────────────┘  └─────────────┘  └─────────────┘  └─────────┘ │
└─────────────────────────────────────────────────────────────────┘
```

## 6. KEY FEATURES FLOW

### Real-time Dashboard Updates
```
Page Load → Controller queries all models → Fresh data → View renders → Charts update
```

### AJAX Form Submissions
```
Form Submit → AJAX request → Controller validation → Model save → JSON response → UI update
```

### Export Functionality
```
Export button → Controller query → Data formatting → Excel/CSV generation → File download
```

### Role-based Access
```
Route access → Middleware check → Role validation → Allow/Deny → Controller execution
```

Bagan ini menunjukkan alur lengkap MVC pada project inventory management system PT. Mitrajaya Selaras Abadi, mulai dari request user hingga response yang diterima browser.
