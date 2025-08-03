<?php
// header.php
// This file contains the common navigation bar for FinFlow
// It assumes session_start() has been called in the main page already.

$loggedInUserName = "Guest"; // Default value if not logged in

if (isset($_SESSION['full_name'])) {
    $loggedInUserName = htmlspecialchars($_SESSION['full_name']); // Sanitize output
}
?>

<nav class="navbar navbar-expand-sm navbar-light bg-white border-bottom py-1">
    <div class="container">
        <a href="dashboard.php" class="navbar-brand d-flex align-items-center">
            <img src="logo.png" alt="FinFlow Logo">
            <span class="fw-bold text-primary ms-2">FinFlow</span>
        </a>

        <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#mainNavbar">
            <span class="navbar-toggler-icon"></span>
        </button>

        <div class="collapse navbar-collapse" id="mainNavbar">
            <ul class="navbar-nav mx-auto">
                <li class="nav-item">
                    <a href="dashboard.php" class="nav-link">Dashboard</a>
                </li>
                <li class="nav-item">
                    <a href="transactions.php" class="nav-link">Transactions</a>
                </li>
                <li class="nav-item">
                    <a href="budgets.php" class="nav-link">Budgets</a>
                </li>
                <li class="nav-item">
                    <a href="reports.php" class="nav-link">Reports</a>
                </li>
                <li class="nav-item">
                    <a href="contact.php" class="nav-link">Contact Us</a>
                </li>
            </ul>

            <div class="ms-auto d-flex align-items-center">
                <span class="navbar-text me-3">
                    Hello, <?php echo $loggedInUserName; ?>
                </span>
                <a href="signout_process.php" class="nav-link text-secondary">Sign Out</a> </div>
        </div>
    </div>
</nav>