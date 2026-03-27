<?php
// Start session only if not already started
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Initialize cart if not exists
if (!isset($_SESSION['cart'])) {
    $_SESSION['cart'] = [];
}

// Check if customer is logged in
$isLoggedIn = isset($_SESSION['customer_logged_in']) && $_SESSION['customer_logged_in'];

// Sample products for demo
$products = [
    ['id' => 1, 'name' => 'Ryzen 9 7950X', 'category' => 'CPU', 'brand' => 'AMD', 'price' => 699, 'oldPrice' => 799, 'spec' => '16 cores / 32 threads / 5.7GHz', 'icon' => '⚙️', 'badge' => 'HOT', 'stars' => 5],
    ['id' => 2, 'name' => 'Core i9-14900K', 'category' => 'CPU', 'brand' => 'Intel', 'price' => 549, 'oldPrice' => null, 'spec' => '24 cores / 32 threads / 6.0GHz', 'icon' => '⚙️', 'badge' => 'NEW', 'stars' => 5],
    ['id' => 3, 'name' => 'Ryzen 5 7600X', 'category' => 'CPU', 'brand' => 'AMD', 'price' => 249, 'oldPrice' => 299, 'spec' => '6 cores / 12 threads / 5.3GHz', 'icon' => '⚙️', 'badge' => null, 'stars' => 4],
    ['id' => 4, 'name' => 'RTX 4090', 'category' => 'GPU', 'brand' => 'NVIDIA', 'price' => 1599, 'oldPrice' => 1799, 'spec' => '24GB GDDR6X / 16384 CUDA cores', 'icon' => '🎮', 'badge' => 'HOT', 'stars' => 5],
    ['id' => 5, 'name' => 'RX 7900 XTX', 'category' => 'GPU', 'brand' => 'AMD', 'price' => 999, 'oldPrice' => null, 'spec' => '24GB GDDR6 / 96 compute units', 'icon' => '🎮', 'badge' => 'NEW', 'stars' => 5],
    ['id' => 6, 'name' => 'RTX 4070 Super', 'category' => 'GPU', 'brand' => 'NVIDIA', 'price' => 599, 'oldPrice' => 649, 'spec' => '12GB GDDR6X / 7168 CUDA cores', 'icon' => '🎮', 'badge' => null, 'stars' => 4],
    ['id' => 7, 'name' => 'Vengeance DDR5 32GB', 'category' => 'RAM', 'brand' => 'Corsair', 'price' => 129, 'oldPrice' => 159, 'spec' => 'DDR5-6000 / CL30 / 2×16GB', 'icon' => '💾', 'badge' => 'SALE', 'stars' => 4],
    ['id' => 8, 'name' => 'Trident Z5 RGB 64GB', 'category' => 'RAM', 'brand' => 'G.Skill', 'price' => 219, 'oldPrice' => null, 'spec' => 'DDR5-6400 / CL32 / 2×32GB', 'icon' => '💾', 'badge' => 'NEW', 'stars' => 5],
];

// Handle add to cart
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'add_to_cart') {
    $productId = (int)$_POST['product_id'];
    $product = array_values(array_filter($products, fn($p) => $p['id'] === $productId))[0] ?? null;
    
    if ($product) {
        $found = false;
        foreach ($_SESSION['cart'] as &$item) {
            if ($item['id'] === $productId) {
                $item['qty']++;
                $found = true;
                break;
            }
        }
        if (!$found) {
            $_SESSION['cart'][] = array_merge($product, ['qty' => 1]);
        }
        header("Location: " . $_SERVER['PHP_SELF']);
        exit();
    }
}

$cartTotal = 0;
foreach ($_SESSION['cart'] as $item) {
    $cartTotal += $item['price'] * $item['qty'];
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>VOLTCORE — PC Parts Store</title>
    <link href="https://fonts.googleapis.com/css2?family=Space+Mono:wght@400;700&family=DM+Sans:wght@300;400;500;600&display=swap" rel="stylesheet">
    <style>
        :root {
            --bg: #0d0d0f;
            --surface: #141417;
            --surface2: #1c1c21;
            --border: #2a2a32;
            --accent: #e8ff47;
            --accent2: #47d4ff;
            --text: #f0f0f0;
            --muted: #888;
            --danger: #ff4747;
        }

        * { margin: 0; padding: 0; box-sizing: border-box; }

        body {
            background: var(--bg);
            color: var(--text);
            font-family: 'DM Sans', sans-serif;
            min-height: 100vh;
        }

        /* NAV */
        nav {
            display: flex;
            align-items: center;
            justify-content: space-between;
            padding: 0 2rem;
            height: 64px;
            background: var(--surface);
            border-bottom: 1px solid var(--border);
            position: sticky;
            top: 0;
            z-index: 100;
        }

        .logo {
            font-family: 'Space Mono', monospace;
            font-size: 1.2rem;
            font-weight: 700;
            letter-spacing: -0.5px;
            color: var(--accent);
        }

        .logo span { color: var(--text); }

        .nav-center {
            display: flex;
            gap: 2rem;
            margin-left: 2rem;
        }

        .nav-right {
            display: flex;
            align-items: center;
            gap: 1rem;
        }

        .search-bar {
            display: flex;
            align-items: center;
            background: var(--surface2);
            border: 1px solid var(--border);
            border-radius: 8px;
            padding: 0.4rem 0.8rem;
            gap: 0.5rem;
        }

        .search-bar input {
            background: none;
            border: none;
            outline: none;
            color: var(--text);
            font-family: 'DM Sans', sans-serif;
            font-size: 0.875rem;
            width: 200px;
        }

        .search-bar input::placeholder { color: var(--muted); }

        .cart-btn {
            position: relative;
            background: var(--accent);
            color: #000;
            border: none;
            border-radius: 8px;
            padding: 0.45rem 1rem;
            font-family: 'Space Mono', monospace;
            font-size: 0.8rem;
            font-weight: 700;
            cursor: pointer;
            display: flex;
            align-items: center;
            gap: 0.5rem;
        }

        .cart-count {
            background: #000;
            color: var(--accent);
            border-radius: 999px;
            width: 18px;
            height: 18px;
            font-size: 0.65rem;
            display: flex;
            align-items: center;
            justify-content: center;
        }

        .admin-btn {
            background: var(--surface2);
            color: var(--text);
            border: 1px solid var(--border);
            border-radius: 8px;
            padding: 0.45rem 1rem;
            text-decoration: none;
            font-size: 0.85rem;
            transition: all 0.15s;
        }

        .admin-btn:hover {
            border-color: var(--accent);
            color: var(--accent);
        }

        /* HERO */
        .hero {
            padding: 3rem 2rem 2rem;
            border-bottom: 1px solid var(--border);
            display: flex;
            align-items: center;
            justify-content: space-between;
            gap: 2rem;
        }

        .hero h1 {
            font-family: 'Space Mono', monospace;
            font-size: 3rem;
            line-height: 1.1;
            font-weight: 700;
        }

        .hero-text p {
            margin-top: 0.75rem;
            color: var(--muted);
            font-size: 0.95rem;
            max-width: 400px;
            line-height: 1.6;
        }

        .hero em {
            font-style: normal;
            color: var(--accent);
        }

        /* FILTERS */
        .filters {
            display: flex;
            gap: 0.5rem;
            padding: 1.25rem 2rem;
            border-bottom: 1px solid var(--border);
            overflow-x: auto;
        }

        .filter-btn {
            background: var(--surface2);
            border: 1px solid var(--border);
            border-radius: 999px;
            color: var(--muted);
            padding: 0.35rem 0.9rem;
            cursor: pointer;
            white-space: nowrap;
            transition: all 0.15s;
            font-size: 0.82rem;
        }

        .filter-btn:hover, .filter-btn.active {
            border-color: var(--accent);
            color: var(--accent);
            background: rgba(232,255,71,0.07);
        }

        /* PRODUCTS */
        .products-area {
            padding: 2rem;
        }

        .products-grid {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(230px, 1fr));
            gap: 1.25rem;
        }

        .card {
            background: var(--surface);
            border: 1px solid var(--border);
            border-radius: 12px;
            overflow: hidden;
            transition: transform 0.2s, border-color 0.2s;
            cursor: pointer;
            position: relative;
        }

        .card:hover {
            transform: translateY(-3px);
            box-shadow: 0 12px 32px rgba(0,0,0,0.4);
        }

        .card-img {
            background: var(--surface2);
            height: 160px;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 3rem;
            position: relative;
        }

        .card-badge {
            position: absolute;
            top: 10px;
            left: 10px;
            background: var(--accent);
            color: #000;
            font-family: 'Space Mono', monospace;
            font-size: 0.65rem;
            font-weight: 700;
            padding: 0.2rem 0.5rem;
            border-radius: 4px;
            z-index: 1;
        }

        .card-badge.new { background: var(--accent2); }
        .card-badge.hot { background: #ff6b35; }

        .card-body { padding: 1rem; }

        .card-category {
            font-size: 0.72rem;
            color: var(--muted);
            text-transform: uppercase;
            letter-spacing: 1px;
            margin-bottom: 0.3rem;
        }

        .card-name {
            font-size: 0.9rem;
            font-weight: 600;
            line-height: 1.3;
            margin-bottom: 0.5rem;
        }

        .card-spec {
            font-size: 0.78rem;
            color: var(--muted);
            margin-bottom: 0.75rem;
        }

        .card-footer {
            display: flex;
            align-items: center;
            justify-content: space-between;
            margin-top: 0.75rem;
        }

        .card-price {
            font-family: 'Space Mono', monospace;
            font-size: 1rem;
            font-weight: 700;
            color: var(--accent);
        }

        .card-price .old-price {
            display: block;
            font-size: 0.7rem;
            color: var(--muted);
            text-decoration: line-through;
            font-weight: 400;
        }

        .add-btn {
            background: var(--surface2);
            border: 1px solid var(--border);
            border-radius: 8px;
            color: var(--text);
            font-size: 0.8rem;
            font-weight: 600;
            padding: 0.4rem 0.8rem;
            cursor: pointer;
            transition: all 0.15s;
        }

        .add-btn:hover {
            background: var(--accent);
            color: #000;
            border-color: var(--accent);
        }

        .stars { color: #f0a500; font-size: 0.75rem; margin-bottom: 0.25rem; }

        .empty-state {
            text-align: center;
            padding: 3rem;
            color: var(--muted);
        }

        .empty-state-icon { font-size: 3rem; margin-bottom: 1rem; }

        @media (max-width: 768px) {
            .hero { flex-direction: column; }
            .search-bar { display: none; }
            .nav-center { display: none !important; }
        }
    </style>
</head>
<body>

<!-- NAV -->
<nav>
    <div class="logo">VOLT<span>CORE</span></div>
    <div class="nav-center" style="display: flex; gap: 2rem; margin-left: 2rem;">
        <a href="../client/index.php" class="admin-btn" style="background: transparent; border: none;">🏠 Home</a>
        <a href="../client/index.php#about" class="admin-btn" style="background: transparent; border: none;">ℹ️ About Us</a>
        <a href="../client/index.php#gallery" class="admin-btn" style="background: transparent; border: none;">🖼️ Gallery</a>
        <a href="../client/index.php#contact" class="admin-btn" style="background: transparent; border: none;">✉️ Contact</a>
    </div>
    <div class="nav-right">
        <div class="search-bar">
            <svg width="14" height="14" fill="none" stroke="#888" stroke-width="2" viewBox="0 0 24 24"><circle cx="11" cy="11" r="8"/><line x1="21" y1="21" x2="16.65" y2="16.65"/></svg>
            <input type="text" placeholder="Search parts...">
        </div>
        <?php if ($isLoggedIn): ?>
            <a href="../client/profile.php" class="admin-btn" style="background: var(--accent2);">👤 <?php echo substr($_SESSION['customer_name'], 0, 10); ?></a>
        <?php else: ?>
            <a href="../login.php" class="admin-btn">🔐 Login</a>
            <a href="../client/register.php" class="admin-btn" style="background: var(--accent2); color: #000;">✍️ Register</a>
        <?php endif; ?>
        <a href="../client/cart.php" class="cart-btn">
            🛒 Cart
            <span class="cart-count"><?php echo count($_SESSION['cart'] ?? []); ?></span>
        </a>
    </div>
</nav>

<!-- HERO -->
<div class="hero">
    <div class="hero-text">
        <h1>Build Your<br><em>Dream Rig.</em></h1>
        <p>Top-tier components for enthusiasts, gamers, and pros. No fluff — just the parts you need.</p>
    </div>
    <div style="background: var(--surface2); border: 1px solid var(--border); border-radius: 12px; padding: 1.25rem 1.75rem; text-align: center; min-width: 200px;">
        <div style="font-family: 'Space Mono', monospace; font-size: 2.5rem; font-weight: 700; color: var(--accent2);">500+</div>
        <div style="color: var(--muted); font-size: 0.8rem; margin-top: 0.25rem;">PC Components</div>
    </div>
</div>

<!-- FILTERS -->
<div class="filters">
    <button class="filter-btn active">All Parts</button>
    <button class="filter-btn">⚙️ CPUs</button>
    <button class="filter-btn">🎮 GPUs</button>
    <button class="filter-btn">💾 RAM</button>
    <button class="filter-btn">💿 Storage</button>
</div>

<!-- PRODUCTS -->
<div class="products-area">
    <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 1.5rem;">
        <h2 style="font-family: 'Space Mono', monospace; font-size: 0.9rem; color: var(--muted);">
            Showing <span style="color: var(--accent);"><?php echo count($products); ?></span> products
        </h2>
    </div>

    <div class="products-grid">
        <?php foreach ($products as $p): ?>
            <div class="card">
                <div class="card-img">
                    <?php if ($p['badge']): ?>
                        <div class="card-badge <?php echo strtolower($p['badge']); ?>"><?php echo $p['badge']; ?></div>
                    <?php endif; ?>
                    <?php echo $p['icon']; ?>
                </div>
                <div class="card-body">
                    <div class="card-category"><?php echo htmlspecialchars($p['category'] . ' • ' . $p['brand']); ?></div>
                    <div class="card-name"><?php echo htmlspecialchars($p['name']); ?></div>
                    <div class="stars"><?php echo str_repeat('★', $p['stars']) . str_repeat('☆', 5 - $p['stars']); ?></div>
                    <div class="card-spec"><?php echo htmlspecialchars($p['spec']); ?></div>
                    <div class="card-footer">
                        <div class="card-price">
                            <?php if ($p['oldPrice']): ?>
                                <span class="old-price">$<?php echo number_format($p['oldPrice'], 2); ?></span>
                            <?php endif; ?>
                            $<?php echo number_format($p['price'], 2); ?>
                        </div>
                        <form method="POST" style="display: inline;">
                            <input type="hidden" name="action" value="add_to_cart">
                            <input type="hidden" name="product_id" value="<?php echo $p['id']; ?>">
                            <button type="submit" class="add-btn">+ Add</button>
                        </form>
                    </div>
                </div>
            </div>
        <?php endforeach; ?>
    </div>
</div>

<!-- ABOUT US SECTION -->
<section id="about" style="padding: 3rem 2rem; background: var(--surface); border-top: 1px solid var(--border);">
    <div style="max-width: 1200px; margin: 0 auto;">
        <h2 style="font-family: 'Space Mono', monospace; font-size: 2rem; margin-bottom: 1rem; color: var(--accent2);">About Us</h2>
        <p style="color: var(--muted); line-height: 1.6; max-width: 600px;">
            VOLTCORE is your ultimate destination for premium PC components and hardware. We specialize in providing top-tier CPUs, GPUs, RAM, and storage solutions for enthusiasts, gamers, and professionals. Our mission is to help you build the perfect rig with quality parts and exceptional service.
        </p>
    </div>
</section>

<!-- GALLERY SECTION -->
<section id="gallery" style="padding: 3rem 2rem; background: var(--bg); border-top: 1px solid var(--border);">
    <div style="max-width: 1200px; margin: 0 auto;">
        <h2 style="font-family: 'Space Mono', monospace; font-size: 2rem; margin-bottom: 1rem; color: var(--accent2);">Gallery</h2>
        <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(250px, 1fr)); gap: 1.5rem;">
            <div style="background: var(--surface); padding: 1.5rem; border-radius: 12px; border: 1px solid var(--border); text-align: center;">
                <div style="font-size: 3rem; margin-bottom: 1rem;">⚙️</div>
                <h3>Premium CPUs</h3>
                <p style="color: var(--muted); font-size: 0.9rem; margin-top: 0.5rem;">Latest generation processors from AMD and Intel</p>
            </div>
            <div style="background: var(--surface); padding: 1.5rem; border-radius: 12px; border: 1px solid var(--border); text-align: center;">
                <div style="font-size: 3rem; margin-bottom: 1rem;">🎮</div>
                <h3>Graphics Cards</h3>
                <p style="color: var(--muted); font-size: 0.9rem; margin-top: 0.5rem;">High-performance GPUs for gaming and workstations</p>
            </div>
            <div style="background: var(--surface); padding: 1.5rem; border-radius: 12px; border: 1px solid var(--border); text-align: center;">
                <div style="font-size: 3rem; margin-bottom: 1rem;">💾</div>
                <h3>Memory & Storage</h3>
                <p style="color: var(--muted); font-size: 0.9rem; margin-top: 0.5rem;">Fast RAM and SSD solutions for optimal performance</p>
            </div>
        </div>
    </div>
</section>

<!-- CONTACT SECTION -->
<section id="contact" style="padding: 3rem 2rem; background: var(--surface); border-top: 1px solid var(--border);">
    <div style="max-width: 600px; margin: 0 auto;">
        <h2 style="font-family: 'Space Mono', monospace; font-size: 2rem; margin-bottom: 1rem; color: var(--accent2);">Contact Us</h2>
        <form method="POST" style="display: flex; flex-direction: column; gap: 1rem;">
            <input type="text" placeholder="Your Name" required style="padding: 0.75rem; background: var(--surface2); border: 1px solid var(--border); border-radius: 8px; color: var(--text);">
            <input type="email" placeholder="Your Email" required style="padding: 0.75rem; background: var(--surface2); border: 1px solid var(--border); border-radius: 8px; color: var(--text);">
            <textarea placeholder="Your Message" rows="5" required style="padding: 0.75rem; background: var(--surface2); border: 1px solid var(--border); border-radius: 8px; color: var(--text); resize: none;"></textarea>
            <button type="submit" style="padding: 0.75rem; background: var(--accent); color: #000; border: none; border-radius: 8px; font-weight: 700; cursor: pointer;">Send Message</button>
        </form>
        <div style="margin-top: 2rem; color: var(--muted); font-size: 0.9rem;">
            <p>📧 Email: info@voltcore.com</p>
            <p>📞 Phone: +1 (800) 123-4567</p>
        </div>
    </div>
</section>

<!-- FOOTER -->
<footer style="padding: 2rem; background: var(--surface); border-top: 1px solid var(--border); text-align: center; color: var(--muted); font-size: 0.85rem;">
    <p>&copy; 2026 VOLTCORE. All rights reserved.</p>
</footer>

</body>
</html>
