<?php
session_start();

// -- Login --
function requireLogin()
{
    if (!isset($_SESSION['user_id'])) {
        header("Location: login.php");
        exit();
    }
}

function currentUserId()
{
    requireLogin();
    return $_SESSION['user_id'];
}

// -- Role access --
function requireRole($role)
{
    requireLogin();

    if ($_SESSION['user_role'] != $role) {
        exit("You are not authorized to access this page.");
    }
}

function requireAdmin()
{
    requireRole('admin');
}

function requireCustomer()
{
    requireRole('customer');
}

// -- Navigation --
function renderAuthControls()
{
    if (!isset($_SESSION['user_id'])) {
        return;
    }

    echo '<header class="auth-bar">';
    echo '<span class="auth-user">Signed in as ' .
        htmlspecialchars($_SESSION['user_name'], ENT_QUOTES, 'UTF-8') .
        '</span>';

    if ($_SESSION['user_role'] == 'admin') {
        echo '<nav class="auth-nav">
            <a href="index.php">Admin dashboard</a> |
            <a href="admin_booking_requests.php">Booking requests</a> |
            <a href="admin_requests.php">Reschedule requests</a> |
            <a href="auditlog.php">Audit history</a>
        </nav>';
    } else {
        echo '<nav class="auth-nav">
            <a href="index.php">My flights</a> |
            <a href="booking_request.php">Request a booking</a> |
            <a href="customer_profile.php">Edit my information</a>
        </nav>';
    }

    echo '<form class="logout-form" action="core/logout.php" method="POST">
        <button type="submit">Log out</button>
    </form></header>';
}
?>
