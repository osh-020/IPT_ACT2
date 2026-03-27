<?php
// Start session only if not already started
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
require_once __DIR__ . '/config.php';
require_once __DIR__ . '/auth.php';
requireAdminLogin();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>VOLTCORE Admin — <?php echo $pageTitle ?? 'Dashboard'; ?></title>
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
            --success: #4caf50;
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

        .badge-admin {
            background: var(--accent2);
            color: #000;
            padding: 0.3rem 0.8rem;
            border-radius: 20px;
            font-size: 0.75rem;
            font-weight: 600;
            margin-left: 0.5rem;
        }

        .nav-right {
            display: flex;
            align-items: center;
            gap: 1.5rem;
        }

        .user-info {
            font-size: 0.9rem;
            color: var(--muted);
        }

        .user-info strong { color: var(--text); }

        .logout-btn {
            background: var(--danger);
            color: #fff;
            border: none;
            border-radius: 8px;
            padding: 0.5rem 1rem;
            cursor: pointer;
            font-family: 'DM Sans', sans-serif;
            font-size: 0.85rem;
            transition: opacity 0.15s;
        }

        .logout-btn:hover { opacity: 0.9; }

        /* SIDEBAR */
        .container {
            display: grid;
            grid-template-columns: 250px 1fr;
            gap: 0;
            min-height: calc(100vh - 64px);
        }

        .sidebar {
            background: var(--surface);
            border-right: 1px solid var(--border);
            padding: 1.5rem 0;
        }

        .sidebar-item {
            display: flex;
            align-items: center;
            gap: 0.75rem;
            padding: 1rem 1.5rem;
            color: var(--muted);
            text-decoration: none;
            cursor: pointer;
            transition: all 0.15s;
            border-left: 3px solid transparent;
            margin: 0.25rem 0;
        }

        .sidebar-item:hover {
            background: var(--surface2);
            color: var(--accent);
            border-left-color: var(--accent);
        }

        .sidebar-item.active {
            background: var(--surface2);
            color: var(--accent);
            border-left-color: var(--accent);
            font-weight: 600;
        }

        /* MAIN CONTENT */
        .main-content {
            padding: 2rem;
            overflow-y: auto;
        }

        .header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 2rem;
        }

        .header h1 {
            font-family: 'Space Mono', monospace;
            font-size: 1.8rem;
        }

        .btn-primary {
            background: var(--accent);
            color: #000;
            border: none;
            border-radius: 8px;
            padding: 0.6rem 1.5rem;
            font-family: 'Space Mono', monospace;
            font-weight: 700;
            cursor: pointer;
            font-size: 0.85rem;
            transition: transform 0.15s, box-shadow 0.15s;
            text-decoration: none;
            display: inline-block;
        }

        .btn-primary:hover {
            transform: translateY(-2px);
            box-shadow: 0 8px 16px rgba(232,255,71,0.3);
        }

        /* FORMS */
        .form-group {
            display: flex;
            flex-direction: column;
            gap: 0.5rem;
            margin-bottom: 1rem;
        }

        label {
            font-size: 0.85rem;
            font-weight: 600;
            color: var(--text);
        }

        input, select, textarea {
            background: var(--surface2);
            border: 1px solid var(--border);
            border-radius: 8px;
            color: var(--text);
            font-family: 'DM Sans', sans-serif;
            font-size: 0.9rem;
            padding: 0.7rem;
            outline: none;
            transition: border-color 0.15s;
        }

        input:focus, select:focus, textarea:focus {
            border-color: var(--accent);
        }

        .form-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(300px, 1fr));
            gap: 1.5rem;
            background: var(--surface);
            border: 1px solid var(--border);
            border-radius: 12px;
            padding: 2rem;
            margin-bottom: 2rem;
        }

        .form-full {
            grid-column: 1 / -1;
        }

        .form-actions {
            display: flex;
            gap: 1rem;
            justify-content: flex-end;
            grid-column: 1 / -1;
        }

        .btn-submit {
            background: var(--accent);
            color: #000;
            border: none;
            border-radius: 8px;
            padding: 0.7rem 1.5rem;
            font-family: 'Space Mono', monospace;
            font-weight: 700;
            cursor: pointer;
            transition: opacity 0.15s;
        }

        .btn-submit:hover { opacity: 0.9; }

        .btn-cancel {
            background: var(--surface2);
            color: var(--text);
            border: 1px solid var(--border);
            border-radius: 8px;
            padding: 0.7rem 1.5rem;
            cursor: pointer;
            transition: border-color 0.15s;
        }

        .btn-cancel:hover { border-color: var(--accent); }

        /* TABLE */
        .data-table {
            width: 100%;
            border-collapse: collapse;
            background: var(--surface);
            border: 1px solid var(--border);
            border-radius: 12px;
            overflow: hidden;
        }

        .data-table thead {
            background: var(--surface2);
            border-bottom: 1px solid var(--border);
        }

        .data-table th {
            padding: 1rem;
            text-align: left;
            font-weight: 600;
            font-size: 0.85rem;
            color: var(--muted);
            text-transform: uppercase;
            letter-spacing: 0.5px;
        }

        .data-table td {
            padding: 1rem;
            border-bottom: 1px solid var(--border);
        }

        .data-table tr:last-child td {
            border-bottom: none;
        }

        .btn-edit {
            background: var(--accent2);
            color: #000;
            border: none;
            border-radius: 6px;
            padding: 0.4rem 0.9rem;
            cursor: pointer;
            font-size: 0.8rem;
            transition: opacity 0.15s;
        }

        .btn-edit:hover { opacity: 0.9; }

        .btn-delete {
            background: var(--danger);
            color: #fff;
            border: none;
            border-radius: 6px;
            padding: 0.4rem 0.9rem;
            cursor: pointer;
            font-size: 0.8rem;
            transition: opacity 0.15s;
        }

        .btn-delete:hover { opacity: 0.9; }

        .btn-view {
            background: var(--accent);
            color: #000;
            border: none;
            border-radius: 6px;
            padding: 0.4rem 0.9rem;
            cursor: pointer;
            font-size: 0.8rem;
            transition: opacity 0.15s;
        }

        .btn-view:hover { opacity: 0.9; }

        /* TOAST */
        .toast {
            position: fixed;
            bottom: 2rem;
            right: 2rem;
            background: var(--surface2);
            border: 1px solid var(--border);
            border-left: 3px solid var(--accent);
            color: var(--text);
            padding: 1rem 1.5rem;
            border-radius: 10px;
            font-size: 0.9rem;
            z-index: 300;
            animation: slideUp 0.3s ease;
        }

        .toast.success {
            border-left-color: var(--success);
        }

        .toast.error {
            border-left-color: var(--danger);
        }

        @keyframes slideUp {
            from { transform: translateY(20px); opacity: 0; }
            to { transform: translateY(0); opacity: 1; }
        }

        .empty-state {
            text-align: center;
            padding: 3rem 2rem;
            color: var(--muted);
        }

        .empty-state-icon {
            font-size: 3rem;
            margin-bottom: 1rem;
        }

        @media (max-width: 768px) {
            .container {
                grid-template-columns: 1fr;
            }

            .sidebar {
                display: none;
            }
        }
    </style>
</head>
<body>
    <nav>
        <div class="logo">VOLT<span>CORE</span> <span class="badge-admin">ADMIN</span></div>
        <div class="nav-right">
            <div class="user-info">Welcome, <strong><?php echo $_SESSION['admin_username']; ?></strong></div>
            <a href="../admin/logout.php" class="logout-btn">Logout</a>
        </div>
    </nav>

    <div class="container">
        <aside class="sidebar">
            <a href="../admin/dashboard.php" class="sidebar-item <?php echo (basename($_SERVER['PHP_SELF']) === 'dashboard.php') ? 'active' : ''; ?>">
                📊 Dashboard
            </a>
            <a href="../admin/products.php" class="sidebar-item <?php echo (basename($_SERVER['PHP_SELF']) === 'products.php') ? 'active' : ''; ?>">
                📦 Products
            </a>
            <a href="../admin/orders.php" class="sidebar-item <?php echo (basename($_SERVER['PHP_SELF']) === 'orders.php') ? 'active' : ''; ?>">
                📋 Orders
            </a>
        </aside>

        <main class="main-content">
