<?php
$pageTitle = 'Dashboard';
require_once __DIR__ . '/../includes/header.php';
?>

<div class="header">
    <h1>Dashboard</h1>
</div>

<div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(250px, 1fr)); gap: 1.5rem; margin-bottom: 2rem;">
    <div style="background: var(--surface); border: 1px solid var(--border); border-radius: 12px; padding: 1.5rem;">
        <div style="color: var(--muted); font-size: 0.85rem; margin-bottom: 0.5rem;">Total Products</div>
        <div style="font-family: 'Space Mono', monospace; font-size: 2.5rem; color: var(--accent); font-weight: 700;">0</div>
    </div>
    
    <div style="background: var(--surface); border: 1px solid var(--border); border-radius: 12px; padding: 1.5rem;">
        <div style="color: var(--muted); font-size: 0.85rem; margin-bottom: 0.5rem;">Total Orders</div>
        <div style="font-family: 'Space Mono', monospace; font-size: 2.5rem; color: var(--accent2); font-weight: 700;">0</div>
    </div>

    <div style="background: var(--surface); border: 1px solid var(--border); border-radius: 12px; padding: 1.5rem;">
        <div style="color: var(--muted); font-size: 0.85rem; margin-bottom: 0.5rem;">Total Revenue</div>
        <div style="font-family: 'Space Mono', monospace; font-size: 2.5rem; color: #4caf50; font-weight: 700;">$0.00</div>
    </div>
</div>

<div style="background: var(--surface); border: 1px solid var(--border); border-radius: 12px; padding: 2rem;">
    <h2 style="font-family: 'Space Mono', monospace; font-size: 1.2rem; margin-bottom: 1rem;">Welcome to VOLTCORE Admin</h2>
    <p style="color: var(--muted); line-height: 1.6;">
        You can manage products, orders, and monitor your PC parts shop from this admin panel. 
        Use the sidebar to navigate between different sections.
    </p>
</div>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>
