# Dynamic API Query Filters for Laravel Applications

A **lightweight and flexible** query filtering package for Laravel, supporting **standard filters, relationship filters, and custom filters** via URL parameters. It enables **sorting and pagination** effortlessly while keeping query logic clean and maintainable. With intuitive syntax and extendability, it simplifies API request handling for scalable applications.  

---

# 🚀 Basic Usage  

Filter a query based on a request:  

## Example Request  

```bash
GET /products?filters[category:eq]=electronics
```

## SQL:  
```sql
SELECT * FROM products WHERE category = 'Electronics';
```


## Adding Filters to the `Product` Model 
```php
<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use MohamedFathy\DynamicFilters\HasDynamicFilters;

class Product extends Model
{
    use HasDynamicFilters;

    /**
     * Define the fields that can be filtered dynamically.
     * Only these attributes can be used in query filters.
     */
    protected array $allowedFilters = ['name', 'price', 'category'];

    /**
     * Define the relationships that are allowed for filtering or eager loading.
     * Only these relations can be included in queries to prevent unwanted data exposure.
     */
    protected array $allowedRelations = ['provider'];

    /**
     * Define the fields that can be used for ordering results.
     * Only these attributes can be used in ORDER BY clauses to maintain query integrity.
     */
    protected array $allowedOrdering = ['id'];

    public function provider(): BelongsTo
    {
        return $this->belongsTo(Provider::class);
    }
}
```


## Usage in Controller:
```php
/**
 * list products.
 *
 * @param Request $request
 * @return JsonResponse
 */
public function index(Request $request): JsonResponse
{
    // The $request->all() resolves to an array like: ['filters' => ['category:eq' => 'electronics']]
    $data = Product::filter($request->all())->get();
    return response()->json($data);
}
```

---

## 🛠 Installation  

Install via Composer:  

```bash
composer require mohamedfathy/dynamic-filters
```

---

## 📌 List of Available Operators  

Below are the available operators you can use in filtering:  

| Operator  | SQL Equivalent | Description                         | Example Usage                    |
|-----------|---------------|-------------------------------------|----------------------------------|
| `eq`      | `=`           | Equal to                           | `filters[status:eq]=active`      |
| `neq`     | `!=`          | Not equal to                       | `filters[status:neq]=active`   |
| `gt`      | `>`           | Greater than                       | `filters[price:gt]=100`         |
| `lt`      | `<`           | Less than                          | `filters[price:lt]=1000`        |
| `gte`     | `>=`          | Greater than or equal              | `filters[rating:gte]=4`         |
| `lte`     | `<=`          | Less than or equal                 | `filters[discount:lte]=50`      |
| `like`    | `LIKE`        | Partial match                      | `filters[name:like]=car`      |
| `nLike`   | `NOT LIKE`    | Does not match pattern             | `filters[name:nLike]=tablet`    |
| `null`    | `IS NULL`     | Field is null                      | `filters[deleted_at:null]`      |
| `nNull`   | `IS NOT NULL` | Field is not null                  | `filters[updated_at:nNull]`     |
| `in`      | `IN`          | Value in list                      | `filters[status:in]=active,pending` |
| `nIn`     | `NOT IN`      | Value not in list                  | `filters[status:nIn]=banned`    |
| `between`  | `BETWEEN`       | Value in range                     | `filters[price:between]=100,500` |
| `nBetween` | `NOT BETWEEN`   | Value outside range                | `filters[age:nBetween]=18,60`   |
| `regexp`  | `REGEXP`      | Matches regex pattern              | `filters[sku:regexp]=^[A-Z]+`   |
| `nRegexp` | `NOT REGEXP`  | Does not match regex pattern       | `filters[code:nRegexp]=[0-9]+`  |

---

## 🔍 Query Examples  

### ✅ **Standard Filtering**  

#### Request:  
```plaintext
GET /products?filters[price:gte]=1000
```
#### SQL:  
```sql
SELECT * FROM products WHERE price >= 1000;
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
GET /products?orderBy=-price
```
#### SQL:  
```sql
SELECT * FROM products ORDER BY price DESC;
```

---

### ✅ **Pagination**  

#### Request:  
```plaintext
GET /products?page=2&perPage=10
```
#### SQL:  
```sql
SELECT * FROM products LIMIT 10 OFFSET 10;
```

---

### ✅ **Filtering by Relationship**  

#### Request:  
```plaintext
GET /products?relationFilters[provider.name:like]=mohamed
```
#### SQL:  
```sql
SELECT * FROM products
WHERE EXISTS (
    SELECT * FROM providers
    WHERE products.provider_id = providers.id
        AND name = 'mohamed'
);
```

---

### ✅ **Custom Filter Example**  

#### Request:  
```plaintext
GET /products?customFilters[stock]=low
```
#### SQL:  
```sql
SELECT * FROM products WHERE stock < 10;
```

# Custom Filtering in Laravel

## Why Use Custom Filters?

By default, standard filters and relation filters cover most scenarios.  
However, in some cases, you may need **more flexibility**.  

For example, if your filtering logic involves **complex conditions**,  
**multiple values**, or **custom operations**, it's best to create a dedicated **Filter Class**  
for each model and handle all related filters inside it.

---

## **How to Create Custom Filters**

### **Create a Filter Class for Each Model**
Instead of writing filtering logic inside controllers or query scopes,  
you should create a **dedicated filter class** for each model.  

For example, let's create a **ProductFilters** class that handles filtering for the `Product` model.

```php
<?php

namespace App\Filters;

use Illuminate\Database\Eloquent\Builder;

class ProductFilters
{
    protected Builder $query;

    public function __construct(Builder $query)
    {
        $this->query = $query;
    }

    /**
     * Filter products by stock level.
     */
    public function stock($value): void
    {
        if ($value === 'low') {
            $this->query->where('stock', '<', 10);
        }
        if ($value === 'out') {
            $this->query->where('stock', 0);
        }
    }
}
```
---

## 🔧 Extending with Custom Filters  

**Create a Filter Class for each model**  
```php
<?php

namespace App\Filters;

use Illuminate\Database\Eloquent\Builder;

class ProductFilters
{
    protected Builder $query;

    public function __construct(Builder $query)
    {
        $this->query = $query;
    }

    // * - `customFilters[stock]=low` → Filters products with stock less than 10.
    // * - `customFilters[stock]=out` → Filters products with stock equal to 0.
    public function stock($value): void
    {
        if ($value === 'low') {
            $this->query->where('stock', '<', 10);
        }
        if ($value === 'out') {
            $this->query->where('stock', 0);
        }
    }

}
```
---

## ✅ Conclusion  

- **Simple API** for filtering, sorting, and relationship handling  
- **Customizable filters** for advanced use cases  
- **Efficient query execution**  

---

## 📌 Need Help?  

Feel free to contribute or raise an issue if you have suggestions! 🚀  
