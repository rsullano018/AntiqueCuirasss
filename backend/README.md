# Backend API Documentation

## Overview

The backend API for Antique Cuirass e-commerce website uses a JSON database approach, reading from the `converted_database.json` file. This makes it easy to develop and test locally without needing a FileMaker server.

## Setup

1. **Prerequisites:**
   - PHP 7.4 or higher
   - Web server (Apache/Nginx) or PHP built-in server

2. **Installation:**
   ```bash
   # Navigate to backend directory
   cd backend
   
   # Start PHP built-in server
   php -S localhost:8000
   ```

3. **Test the API:**
   - Open `http://localhost:8000/test-api.php` in your browser
   - This will show database statistics and test all endpoints

## API Endpoints

### Health Check
- **GET** `/api/health.php`
- Returns system status and database statistics

### Categories
- **GET** `/api/categories.php` - Get all categories
- **GET** `/api/categories.php?id={id}` - Get category by ID
- **POST** `/api/categories.php` - Create new category
- **PUT** `/api/categories.php?id={id}` - Update category
- **DELETE** `/api/categories.php?id={id}` - Delete category

### Products
- **GET** `/api/products.php` - Get all products
- **GET** `/api/products.php?id={id}` - Get product by ID
- **GET** `/api/products.php?featured=1` - Get featured products
- **GET** `/api/products.php?category={id}` - Get products by category
- **GET** `/api/products.php?search={query}` - Search products
- **POST** `/api/products.php` - Create new product
- **PUT** `/api/products.php?id={id}` - Update product
- **DELETE** `/api/products.php?id={id}` - Delete product

## Query Parameters

### Products API
- `limit` - Number of products to return (default: 20)
- `offset` - Number of products to skip (default: 0)
- `category` - Filter by category ID
- `search` - Search in product name and description
- `condition` - Filter by condition rating
- `price_max` - Maximum price filter
- `era` - Filter by era period

### Examples
```
GET /api/products.php?limit=10&offset=0
GET /api/products.php?category=3&search=cuirass
GET /api/products.php?condition=Excellent&price_max=5000
```

## Response Format

All API responses follow this format:

### Success Response
```json
{
    "success": true,
    "data": [...],
    "count": 10,
    "message": "Operation completed successfully"
}
```

### Error Response
```json
{
    "success": false,
    "error": {
        "message": "Error description",
        "code": 400
    }
}
```

## Database Structure

The JSON database contains the following tables:
- **Products** - Product information
- **Categories** - Product categories
- **Customers** - Customer information
- **Orders** - Order information
- **Orders_Items** - Order line items
- **Product_Images** - Product images

## Development

### Adding New Endpoints

1. Create a new PHP file in the `api/` directory
2. Include the JSONDatabase class
3. Handle HTTP methods and route parameters
4. Return JSON responses

### Testing

Use the `test-api.php` file to test all endpoints and see the database structure.

### Error Handling

All endpoints include proper error handling and return appropriate HTTP status codes.

## CORS

The API includes CORS headers to allow cross-origin requests from the frontend.

## Security

For production use, consider adding:
- Authentication middleware
- Input validation
- Rate limiting
- HTTPS enforcement
