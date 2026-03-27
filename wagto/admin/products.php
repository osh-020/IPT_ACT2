<?php
$pageTitle = 'Manage Products';
require_once __DIR__ . '/../includes/header.php';

$message = '';
$messageType = '';

// Handle form submission for adding/updating product
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';
    
    if ($action === 'add') {
        $name = $_POST['product_name'] ?? '';
        $category = $_POST['product_category'] ?? '';
        $brand = $_POST['product_brand'] ?? '';
        $price = $_POST['product_price'] ?? 0;
        $old_price = $_POST['product_old_price'] ?? null;
        $spec = $_POST['product_spec'] ?? '';
        $icon = $_POST['product_icon'] ?? '';
        $badge = $_POST['product_badge'] ?? null;
        $stars = $_POST['product_stars'] ?? 4;

        if ($name && $category && $brand && $price && $spec && $icon) {
            // Store in session/file (for demo purposes)
            if (!isset($_SESSION['products'])) {
                $_SESSION['products'] = [];
            }
            
            $newProduct = [
                'id' => time(),
                'name' => $name,
                'category' => $category,
                'brand' => $brand,
                'price' => (float)$price,
                'old_price' => $old_price ? (float)$old_price : null,
                'spec' => $spec,
                'icon' => $icon,
                'badge' => $badge,
                'stars' => (int)$stars,
                'created_at' => date('Y-m-d H:i:s')
            ];
            
            $_SESSION['products'][] = $newProduct;
            $message = "✓ Product '{$name}' added successfully!";
            $messageType = 'success';
        } else {
            $message = '✗ Please fill in all required fields!';
            $messageType = 'error';
        }
    } elseif ($action === 'delete') {
        $productId = $_POST['product_id'] ?? '';
        if (isset($_SESSION['products'])) {
            $_SESSION['products'] = array_filter($_SESSION['products'], function($p) use ($productId) {
                return $p['id'] != $productId;
            });
            $message = '✓ Product deleted successfully!';
            $messageType = 'success';
        }
    }
}

$products = $_SESSION['products'] ?? [];
?>

<div class="header">
    <h1>Manage Products</h1>
    <button class="btn-primary" onclick="toggleAddForm()">➕ Add New Product</button>
</div>

<?php if ($message): ?>
    <div class="toast <?php echo $messageType; ?>" style="display: block; position: static; margin-bottom: 1rem; animation: none;">
        <?php echo htmlspecialchars($message); ?>
    </div>
<?php endif; ?>

<!-- ADD PRODUCT FORM -->
<div id="addForm" style="display: none; margin-bottom: 2rem;">
    <div class="form-grid">
        <form method="POST" style="display: contents;">
            <input type="hidden" name="action" value="add">
            
            <div class="form-group">
                <label>Product Name *</label>
                <input type="text" name="product_name" placeholder="e.g., Ryzen 9 7950X" required>
            </div>

            <div class="form-group">
                <label>Category *</label>
                <select name="product_category" required>
                    <option value="">Select Category</option>
                    <option value="CPU">CPU</option>
                    <option value="GPU">GPU</option>
                    <option value="RAM">RAM</option>
                    <option value="Storage">Storage</option>
                    <option value="Motherboard">Motherboard</option>
                    <option value="Cooling">Cooling</option>
                    <option value="PSU">PSU</option>
                    <option value="Case">Case</option>
                </select>
            </div>

            <div class="form-group">
                <label>Brand *</label>
                <input type="text" name="product_brand" placeholder="e.g., AMD" required>
            </div>

            <div class="form-group">
                <label>Price ($) *</label>
                <input type="number" name="product_price" placeholder="0.00" step="0.01" min="0" required>
            </div>

            <div class="form-group">
                <label>Old Price (optional)</label>
                <input type="number" name="product_old_price" placeholder="0.00" step="0.01" min="0">
            </div>

            <div class="form-group">
                <label>Icon/Emoji *</label>
                <input type="text" name="product_icon" placeholder="e.g., ⚙️" maxlength="2" required>
            </div>

            <div class="form-group form-full">
                <label>Specifications *</label>
                <textarea name="product_spec" placeholder="e.g., 16 cores / 32 threads / 5.7GHz" required></textarea>
            </div>

            <div class="form-group">
                <label>Badge (optional)</label>
                <select name="product_badge">
                    <option value="">No Badge</option>
                    <option value="NEW">NEW</option>
                    <option value="HOT">HOT</option>
                    <option value="SALE">SALE</option>
                </select>
            </div>

            <div class="form-group">
                <label>Rating (Stars 1-5)</label>
                <input type="number" name="product_stars" min="1" max="5" value="4">
            </div>

            <div class="form-actions">
                <button type="button" class="btn-cancel" onclick="toggleAddForm()">Cancel</button>
                <button type="submit" class="btn-submit">Add Product</button>
            </div>
        </form>
    </div>
</div>

<!-- PRODUCTS TABLE -->
<?php if (count($products) > 0): ?>
    <table class="data-table">
        <thead>
            <tr>
                <th>Product Name</th>
                <th>Category</th>
                <th>Brand</th>
                <th>Price</th>
                <th>Rating</th>
                <th>Badge</th>
                <th>Added</th>
                <th>Actions</th>
            </tr>
        </thead>
        <tbody>
            <?php foreach ($products as $product): ?>
                <tr>
                    <td><strong><?php echo htmlspecialchars($product['icon'] . ' ' . $product['name']); ?></strong></td>
                    <td><?php echo htmlspecialchars($product['category']); ?></td>
                    <td><?php echo htmlspecialchars($product['brand']); ?></td>
                    <td><strong style="color: var(--accent);">$<?php echo number_format($product['price'], 2); ?></strong></td>
                    <td><span style="color: #f0a500;">
                        <?php echo str_repeat('★', $product['stars']) . str_repeat('☆', 5 - $product['stars']); ?>
                    </span></td>
                    <td><?php echo $product['badge'] ? '<span style="background: var(--surface2); padding: 0.25rem 0.6rem; border-radius: 6px; font-size: 0.75rem;">' . htmlspecialchars($product['badge']) . '</span>' : '—'; ?></td>
                    <td><?php echo htmlspecialchars($product['created_at']); ?></td>
                    <td>
                        <form method="POST" style="display: inline;">
                            <input type="hidden" name="action" value="delete">
                            <input type="hidden" name="product_id" value="<?php echo $product['id']; ?>">
                            <button type="submit" class="btn-delete" onclick="return confirm('Delete this product?');">Delete</button>
                        </form>
                    </td>
                </tr>
            <?php endforeach; ?>
        </tbody>
    </table>
<?php else: ?>
    <div class="empty-state">
        <div class="empty-state-icon">📦</div>
        <h3>No products yet</h3>
        <p>Click "Add New Product" to create your first product!</p>
    </div>
<?php endif; ?>

<script>
function toggleAddForm() {
    const form = document.getElementById('addForm');
    form.style.display = form.style.display === 'none' ? 'block' : 'none';
}
</script>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>
