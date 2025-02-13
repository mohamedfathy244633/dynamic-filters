# Laravel Query Filtering Library  

A powerful and flexible query filtering system for Laravel applications.  
It allows filtering, sorting, and relationship-based queries using a simple query string format.  

---

## 📌 Features  

- 🔍 **Dynamic filtering** via query parameters (e.g., `filters[price:gte]=100`)  
- 📂 **Custom filters** for advanced conditions  
- 🔗 **Relation filtering** (e.g., `filters[supplier.country:eq]=USA`)  
- 📑 **Sorting and pagination**  
- 🏗 **Easily extendable**  

---

## 🛠 Installation  

Install via Composer:  

```bash
composer require your-vendor/laravel-query-filter
```

---

## 📂 Library Structure  

```bash
app/
├── Filters/              # Custom filters (optional)
│   ├── ExpensiveProductFilter.php
│   ├── ActiveUserFilter.php
│   └── ...
├── Http/
│   ├── Controllers/
│   │   ├── ProductController.php
│   │   ├── UserController.php
│   │   └── ...
│   ├── Requests/
│   └── ...
├── Models/
│   ├── Product.php       # Example Model
│   ├── User.php
│   └── ...
└── Providers/
    ├── QueryFilterServiceProvider.php
```

---

## 🚀 Usage  

### 1️⃣ **Apply Filtering in Model**  

Include the `HasDynamicFilters` trait:  

```php
<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use YourVendor\QueryFilter\HasDynamicFilters;

class Product extends Model
{
    use HasDynamicFilters;

    protected array $allowedFilters = ['name', 'price', 'category'];
    protected array $allowedRelations = ['supplier'];
    protected array $allowedOrdering = ['price', 'name'];
}
```

---

### 2️⃣ **Applying Filters in Controller**  

Instead of applying filters in every method, use it **once** in the constructor:  

```php
<?php

namespace App\Http\Controllers;

use App\Models\Product;
use Illuminate\Http\Request;

class ProductController extends Controller
{
    private $query;

    public function __construct(Request $request)
    {
        $this->query = Product::filter($request->query());
    }

    public function index()
    {
        return response()->json($this->query->get());
    }
}
```

---

## 🔍 Query Examples  

### ✅ **Standard Filtering**  

#### Request:  
```plaintext
GET /products?filters[category:eq]=Electronics
```
#### SQL:  
```sql
SELECT * FROM products WHERE category = 'Electronics';
```

---

### ✅ **Multiple Filters**  

#### Request:  
```plaintext
GET /products?filters[category:eq]=Electronics&filters[price:gte]=500
```
#### SQL:  
```sql
SELECT * FROM products WHERE category = 'Electronics' AND price >= 500;
```

---

### ✅ **Sorting**  

#### Request:  
```plaintext
GET /products?order_by=-price
```
#### SQL:  
```sql
SELECT * FROM products ORDER BY price DESC;
```

---

### ✅ **Filtering by Relationship**  

#### Request:  
```plaintext
GET /products?filters[supplier.country:eq]=USA
```
#### SQL:  
```sql
SELECT * FROM products 
WHERE EXISTS (
    SELECT * FROM suppliers 
    WHERE suppliers.id = products.supplier_id 
    AND suppliers.country = 'USA'
);
```

---

### ✅ **Custom Filter Example**  

#### Request:  
```plaintext
GET /products?filters[expensive]=true
```
#### SQL:  
```sql
SELECT * FROM products WHERE price > 1000;
```

**Custom Filter Class:**  
```php
<?php

namespace App\Filters;

use Illuminate\Database\Eloquent\Builder;

class ExpensiveProductFilter
{
    public function apply(Builder $query, $value)
    {
        if ($value === 'true') {
            $query->where('price', '>', 1000);
        }
    }
}
```

---

## 🔧 Extending with Custom Filters  

1️⃣ **Create a Filter Class**  
```php
<?php

namespace App\Filters;

use Illuminate\Database\Eloquent\Builder;

class ActiveUserFilter
{
    public function apply(Builder $query, $value)
    {
        if ($value === 'true') {
            $query->where('status', '=', 'active');
        }
    }
}
```

2️⃣ **Register the Filter in Model**  
```php
protected array $allowedFilters = ['name', 'email', 'status', 'expensive'];
```

3️⃣ **Use it in Requests**  
```plaintext
GET /users?filters[active]=true
```

---

## ✅ Conclusion  

- **Simple API** for filtering, sorting, and relationship handling  
- **Customizable filters** for advanced use cases  
- **Efficient query execution**  

---

## 📌 Need Help?  

Feel free to contribute or raise an issue if you have suggestions! 🚀  
