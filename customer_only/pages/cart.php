<?php
session_start();
include '../includes/db_connect.php';

// Check if user is logged in
if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit;
}

// Handle Remove from Cart
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['remove_from_cart'])) {
    $product_id = intval($_POST['product_id']);
    if (isset($_SESSION['cart'][$product_id])) {
        unset($_SESSION['cart'][$product_id]);
    }
}

// Handle Update Cart Quantity
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['update_quantity'])) {
    $product_id = intval($_POST['product_id']);
    $quantity = intval($_POST['quantity']);
    
    if (isset($_SESSION['cart'][$product_id]) && $quantity > 0) {
        $_SESSION['cart'][$product_id]['quantity'] = $quantity;
    }
}

// Calculate cart totals
$subtotal = 0;
$cart_items = [];

if (isset($_SESSION['cart']) && !empty($_SESSION['cart'])) {
    foreach ($_SESSION['cart'] as $product_id => $item) {
        $item_total = $item['price'] * $item['quantity'];
        $subtotal += $item_total;
        $cart_items[] = [
            'id' => $product_id,
            'name' => $item['name'],
            'price' => $item['price'],
            'quantity' => $item['quantity'],
            'total' => $item_total
        ];
    }
}

$tax = $subtotal * 0.12; // 12% tax
$total = $subtotal + $tax;
?>

<?php include '../includes/header.php'; ?>

<main class="main-content">
    <div class="cart-container">
        <h2>Shopping Cart</h2>

        <?php
        if (empty($cart_items)) {
            echo "<div class='empty-cart'>";
            echo "<p>Your cart is empty</p>";
            echo "<a href='products.php' class='btn btn-primary'>Continue Shopping</a>";
            echo "</div>";
        } else {
            echo "<div class='cart-wrapper'>";
            
            // Cart Items Table
            echo "<div class='cart-items'>";
            echo "<table class='cart-table'>";
            echo "<thead>";
            echo "<tr>";
            echo "<th>Product</th>";
            echo "<th>Price</th>";
            echo "<th>Quantity</th>";
            echo "<th>Total</th>";
            echo "<th>Action</th>";
            echo "</tr>";
            echo "</thead>";
            echo "<tbody>";

            foreach ($cart_items as $item) {
                $price_formatted = number_format($item['price'], 2);
                $total_formatted = number_format($item['total'], 2);
                echo "<tr>";
                echo "<td>" . htmlspecialchars($item['name']) . "</td>";
                echo "<td>₱" . $price_formatted . "</td>";
                echo "<td>";
                echo "<form method='POST' action='cart.php' class='qty-form'>";
                echo "<input type='hidden' name='product_id' value='" . $item['id'] . "'>";
                echo "<input type='number' name='quantity' value='" . $item['quantity'] . "' min='1' class='qty-input-small'>";
                echo "<button type='submit' name='update_quantity' value='1' class='btn-update'>Update</button>";
                echo "</form>";
                echo "</td>";
                echo "<td>₱" . $total_formatted . "</td>";
                echo "<td>";
                echo "<form method='POST' action='cart.php'>";
                echo "<input type='hidden' name='product_id' value='" . $item['id'] . "'>";
                echo "<button type='submit' name='remove_from_cart' value='1' class='btn-remove'>Remove</button>";
                echo "</form>";
                echo "</td>";
                echo "</tr>";
            }

            echo "</tbody>";
            echo "</table>";
            echo "</div>";

            // Cart Summary
            echo "<div class='cart-summary'>";
            echo "<h3>Order Summary</h3>";
            echo "<div class='summary-row'>";
            echo "<span>Subtotal:</span>";
            echo "<span>₱" . number_format($subtotal, 2) . "</span>";
            echo "</div>";
            echo "<div class='summary-row'>";
            echo "<span>Tax (12%):</span>";
            echo "<span>₱" . number_format($tax, 2) . "</span>";
            echo "</div>";
            echo "<div class='summary-row total'>";
            echo "<span>Total:</span>";
            echo "<span>₱" . number_format($total, 2) . "</span>";
            echo "</div>";
            echo "<a href='checkout.php' class='btn btn-primary btn-checkout'>Proceed to Checkout</a>";
            echo "</div>";

            echo "</div>";
        }
        ?>
    </div>
</main>

<?php include '../includes/footer.php'; ?>
