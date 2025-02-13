# Laravel Query Filtering Library  

A **lightweight yet powerful** query filtering system for Laravel applications, designed to streamline API filtering with an intuitive query string format.  

With **elegant and flexible filtering**, this library enables dynamic query construction, relationship-based filtering, sorting, and pagination—**all through simple URL parameters**.  

✅ **No complex query logic in controllers**  
✅ **Highly customizable and extendable**  
✅ **Works seamlessly with Eloquent relationships**  

## Why Use This Library?  

🔹 **Effortless filtering** – Apply conditions directly via query parameters  
🔹 **Elegant syntax** – Readable and intuitive API usage  
🔹 **Advanced relations** – Query nested relationships with ease  
🔹 **Sorting & pagination** – Enhance API responses efficiently  
🔹 **Custom filters** – Extend functionality with reusable filters  

This library ensures clean, maintainable, and scalable query handling, making your Laravel applications **more powerful and flexible**. 🚀 

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

# 🛠️ Allowed Filter Operators  

This library supports various filtering operators for constructing dynamic queries.  

## 📌 List of Available Operators  

Below are the available operators you can use in filtering:  

### **Equality Operators**  

| Operator  | SQL Equivalent | Description                         | Example Usage                    |
|-----------|---------------|-------------------------------------|----------------------------------|
| `eq`      | `=`           | Equal to                           | `filters[status:eq]=active`      |
| `neq`     | `!=`          | Not equal to                       | `filters[status:neq]=inactive`   |

### **Comparison Operators**  

| Operator  | SQL Equivalent | Description                         | Example Usage                    |
|-----------|---------------|-------------------------------------|----------------------------------|
| `gt`      | `>`           | Greater than                       | `filters[price:gt]=100`         |
| `lt`      | `<`           | Less than                          | `filters[price:lt]=1000`        |
| `gte`     | `>=`          | Greater than or equal              | `filters[rating:gte]=4`         |
| `lte`     | `<=`          | Less than or equal                 | `filters[discount:lte]=50`      |

### **String Matching Operators**  

| Operator  | SQL Equivalent | Description                         | Example Usage                    |
|-----------|---------------|-------------------------------------|----------------------------------|
| `like`    | `LIKE`        | Partial match                      | `filters[name:like]=phone`      |
| `nLike`   | `NOT LIKE`    | Does not match pattern             | `filters[name:nLike]=tablet`    |

### **Null Checking Operators**  

| Operator  | SQL Equivalent | Description                         | Example Usage                    |
|-----------|---------------|-------------------------------------|----------------------------------|
| `null`    | `IS NULL`     | Field is null                      | `filters[deleted_at:null]`      |
| `nNull`   | `IS NOT NULL` | Field is not null                  | `filters[updated_at:nNull]`     |

### **List-Based Operators**  

| Operator  | SQL Equivalent | Description                         | Example Usage                    |
|-----------|---------------|-------------------------------------|----------------------------------|
| `in`      | `IN`          | Value in list                      | `filters[status:in]=active,pending` |
| `nIn`     | `NOT IN`      | Value not in list                  | `filters[status:nIn]=banned`    |

### **Range Operators**  

| Operator   | SQL Equivalent  | Description                         | Example Usage                    |
|------------|----------------|-------------------------------------|----------------------------------|
| `between`  | `BETWEEN`       | Value in range                     | `filters[price:between]=100,500` |
| `nBetween` | `NOT BETWEEN`   | Value outside range                | `filters[age:nBetween]=18,60`   |

### **Regular Expression Operators**  

| Operator  | SQL Equivalent | Description                         | Example Usage                    |
|-----------|---------------|-------------------------------------|----------------------------------|
| `regexp`  | `REGEXP`      | Matches regex pattern              | `filters[sku:regexp]=^[A-Z]+`   |
| `nRegexp` | `NOT REGEXP`  | Does not match regex pattern       | `filters[code:nRegexp]=[0-9]+`  |

---

## 🎯 Usage Example  

### **Example Request**  

```plaintext
GET /products?filters[category:eq]=Electronics&filters[price:gte]=500


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
