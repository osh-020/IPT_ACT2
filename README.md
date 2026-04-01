# COMPUTRONIUM - E-Commerce Platform
## PC Components & Peripherals Store

---

## 📋 Project Overview

COMPUTRONIUM is a fully functional e-commerce platform built with **PHP, MySQL, HTML, and CSS**. It's designed for customers to browse and purchase computer components and peripherals from Lingayen, Pangasinan, Philippines.

**Location**: Lingayen, Pangasinan, Philippines  
**Currency**: Philippine Peso (₱)

---

## 🚀 Quick Start

### Prerequisites
- XAMPP (or Apache + MySQL + PHP)
- PHP 8.0+
- MySQL 5.7+
- Web Browser

### Installation Steps

1. **Extract Files**
   ```
   C:\xampp\htdocs\IPT_ACT2\
   ```

2. **Create Database**
   - Open phpMyAdmin: http://localhost/phpmyadmin
   - Import `ipt_act2.sql` to create the database and tables

3. **Insert Sample Products**
   ```bash
   mysql -u root -p ipt_act2 < sample_products.sql
   ```

4. **Insert Sample Users (Optional)**
   ```bash
   mysql -u root -p ipt_act2 < sample_users.sql
   ```

5. **Update Database Config** (if needed)
   - Edit: `includes/db_connect.php`
   - Update: `$server`, `$username`, `$password`, `$port`

6. **Start XAMPP**
   - Open http://localhost/IPT_ACT2/customer_only/home.php

---

## 📁 Project Structure

```
IPT_ACT2/
├── customer_only/              # Customer-facing pages
│   ├── home.php               # Landing page
│   ├── products.php           # Product listing with search/filter
│   ├── login.php              # User login
│   ├── register.php           # User registration
│   ├── dashboard.php          # User profile
│   ├── cart.php               # Shopping cart review
│   ├── checkout.php           # Order placement
│   ├── about.php              # About store
│   ├── contact.php            # Contact form
│   ├── header.php             # Navigation header
│   ├── footer.php             # Footer section
│   ├── logout.php             # User logout
│   └── style.css              # All styling
│
├── admin/                      # Admin pages (existing)
│   ├── manage_product.php
│   ├── upload_product.php
│   └── style.css
│
├── includes/
│   └── db_connect.php          # Database connection
│
├── ipt_act2.sql                # Database schema
├── sample_products.sql         # 15 PC components
├── sample_users.sql            # Test user accounts
└── README.md                   # This file
```

---

## 👥 Test Accounts

After running `sample_users.sql`, use these credentials:

### Account 1
- **Username**: `johndoe`
- **Password**: `Test1234`
- **Email**: john@example.com

### Account 2
- **Username**: `mariasantos`
- **Password**: `Password123`
- **Email**: maria@example.com

### Account 3 (Admin)
- **Username**: `admin`
- **Password**: `Admin2024`
- **Email**: admin@computronium.ph

---

## 🛍️ Features Implemented

### Customer Features
- ✅ Browse all products with images
- ✅ Search products by name/keyword
- ✅ Filter by category
- ✅ User registration with validation
- ✅ Secure login with password hashing (bcrypt)
- ✅ Shopping cart (session-based)
- ✅ Add/remove/update cart items
- ✅ Checkout with order summary
- ✅ User dashboard (profile & orders)
- ✅ About & Contact pages
- ✅ Responsive design (mobile-friendly)

### Security Features
- ✅ Session-based user authentication
- ✅ Bcrypt password hashing
- ✅ SQL prepared statements
- ✅ Input validation & sanitization
- ✅ CSRF/XSS protection basics
- ✅ Required login for cart operations

### Design
- ✅ Dark theme (black background)
- ✅ Lime green accents (#e8ff47)
- ✅ Mobile responsive layout
- ✅ Consistent styling across all pages
- ✅ Philippine Peso (₱) currency
- ✅ Lingayen, Pangasinan location

---

## 📊 Database Schema

### products table
- `id` - Product ID (AUTO_INCREMENT)
- `name` - Product name (varchar 100)
- `category` - Category (varchar 50)
- `brand` - Brand (varchar 50, optional)
- `price` - Price in ₱ (decimal 10,2)
- `stock` - Stock quantity (int 11)
- `description` - Product description (text)
- `keywords` - Search keywords (text)
- `image` - Image filename (varchar 255)
- `created_at` - Timestamp (auto)

### users table
- `user_id` - User ID (AUTO_INCREMENT)
- `full_name` - Full name (varchar 100)
- `email` - Email (varchar 100, UNIQUE)
- `username` - Username (varchar 15, UNIQUE)
- `password` - Hashed password (varchar 255)
- `age` - Age (int 11, 18-60)
- `gender` - Gender (varchar 15)
- `civil_status` - Civil status (varchar 50)
- `mobile_number` - Phone (varchar 11)
- `address` - Address (text)
- `zip_code` - Zip code (char 4)
- `created_at` - Timestamp (auto)

---

## 🧪 Testing Workflow

### 1. Register New Account
- Go to: http://localhost/IPT_ACT2/customer_only/register.php
- Fill in all required fields
- Click "Register"
- Auto-login after registration

### 2. Browse Products
- Go to: http://localhost/IPT_ACT2/customer_only/home.php
- Click "Shop Now" or Products in nav
- Search products (e.g., "CPU", "RAM")
- Filter by category (CPU, Storage, etc.)

### 3. Add to Cart
- Click "Add to Cart" on any product
- Select quantity (1-stock)
- Redirects to login if not logged in ✨ NEW
- After login, returns to shopping

### 4. Review Cart
- Go to: http://localhost/IPT_ACT2/customer_only/cart.php
- Update quantities
- Remove items
- Shows subtotal, tax (12%), total

### 5. Checkout
- Go to: http://localhost/IPT_ACT2/customer_only/checkout.php
- Review items & billing address
- Select payment method (COD)
- Click "Place Order"
- Success message + cart cleared

### 6. View Profile
- Go to: http://localhost/IPT_ACT2/customer_only/dashboard.php
- View profile info
- See member since date
- Logout option

---

## 🔧 Configuration

### Database Connection
File: `includes/db_connect.php`
```php
$server = 'localhost';      // Database host
$username = 'root';         // DB username
$password = '';             // DB password
$dbname = 'ipt_act2';       // Database name
$port = 3307;              // MySQL port
```

### Store Information
Files: `customer_only/` pages
- **Store Name**: COMPUTRONIUM
- **Location**: Lingayen, Pangasinan, Philippines
- **Email**: support@computronium.ph
- **Phone**: +63-75-123-4567
- **Currency**: Philippine Peso (₱)

---

## 📝 Troubleshooting

### Session Warning
**Error**: "session_start(): Ignoring session..."
- **Fixed**: Removed duplicate session_start() from header.php

### Login Required for Cart
**Feature**: Add to cart now redirects to login if not authenticated
- Helpful message: "Please log in to add items to your cart"
- Auto-redirects back after login ✨

### Database Connection Failed
- Check MySQL is running in XAMPP
- Verify credentials in `db_connect.php`
- Ensure `ipt_act2` database exists
- Check port (default: 3307)

### Products Not Showing
- Run `sample_products.sql` to insert data
- Check category filter isn't hiding them
- Verify stock > 0 (hidden products have 0 stock)

---

## 🚀 Future Enhancements

Potential features to add:
- Order history in dashboard
- Product reviews & ratings
- Wishlist functionality
- Email notifications
- Payment gateway integration
- Admin panel for product management
- Inventory management
- Customer support chat
- SMS notifications

---

## 📄 File Descriptions

| File | Purpose |
|------|---------|
| `home.php` | Featured products & landing page |
| `products.php` | Full product catalog with search/filter |
| `login.php` | User authentication |
| `register.php` | New user registration |
| `cart.php` | Shopping cart review |
| `checkout.php` | Order placement & confirmation |
| `dashboard.php` | User profile & account info |
| `about.php` | Store information |
| `contact.php` | Contact form & store details |
| `header.php` | Navigation bar (on all pages) |
| `footer.php` | Footer section (on all pages) |
| `style.css` | All CSS styling |
| `db_connect.php` | MySQL database connection |

---

## 🎨 Design Notes

- **Color Scheme**: Dark theme with green accents
- **Primary Color**: #e8ff47 (Lime Green)
- **Background**: #0d0d0f (Almost Black)
- **Cards**: #1c1c21 (Dark Gray)
- **Font**: Segoe UI, Tahoma, sans-serif
- **Responsive**: Mobile (320px), Tablet (768px), Desktop (1400px)

---

## 📞 Support

**Store Contact Info**:
- 📧 Email: support@computronium.ph
- 📞 Phone: +63-75-123-4567
- 📍 Address: Lingayen, Pangasinan, Philippines
- 🕒 Hours: Mon-Fri 9AM-6PM | Sat 10AM-4PM (Philippine Time)

---

## 📜 License

This project is created for educational purposes.

---

**Project Completed**: April 1, 2026  
**Status**: ✅ Ready for Testing & Deployment
