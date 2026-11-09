<?php
require_once __DIR__ . '/auth.php';
require_once __DIR__ . '/dbConfig.php';
require_once __DIR__ . '/models.php';

requireLogin();

$user_id = currentUserId();

// -- Transactions --
function runInTransaction($pdo, $callback)
{
    $pdo->beginTransaction();

    try {
        $callback();
        $pdo->commit();
    } catch (Exception $e) {
        $pdo->rollBack();
        throw $e;
    }
}

// -- Passenger and booking actions --
if (isset($_POST['registerAndBookBtn'])) {
    requireAdmin();

    $passenger_id = insertPassenger(
        $pdo,
        $user_id,
        trim($_POST['first_name']),
        trim($_POST['last_name']),
        trim($_POST['email']),
        trim($_POST['phone'])
    );

    $customer = findUserByEmail($pdo, trim($_POST['email']));

    insertBooking(
        $pdo,
        $user_id,
        $passenger_id,
        $customer ? $customer['user_id'] : null,
        trim($_POST['flight_number']),
        trim($_POST['route_origin']),
        trim($_POST['route_destination']),
        trim($_POST['assigned_seat']),
        $_POST['ticket_price'],
        str_replace('T', ' ', $_POST['scheduled_departure'])
    );

    header('Location: ../index.php');
    exit();
}

// -- Passenger actions --
if (isset($_POST['insertPassengerBtn'])) {
    requireAdmin();

    insertPassenger(
        $pdo,
        $user_id,
        trim($_POST['first_name']),
        trim($_POST['last_name']),
        trim($_POST['email']),
        trim($_POST['phone'])
    );

    header('Location: ../index.php');
    exit();
}

if (isset($_POST['editPassengerBtn'])) {
    requireAdmin();

    updatePassenger(
        $pdo,
        $_GET['passenger_id'],
        $user_id,
        trim($_POST['first_name']),
        trim($_POST['last_name']),
        trim($_POST['email']),
        trim($_POST['phone'])
    );

    header('Location: ../index.php');
    exit();
}

if (isset($_POST['deletePassengerBtn'])) {
    requireAdmin();

    deletePassenger($pdo, $_GET['passenger_id'], $user_id);

    header('Location: ../index.php');
    exit();
}

// -- Booking actions --
if (isset($_POST['updateBookingBtn'])) {
    requireAdmin();

    updateBooking(
        $pdo,
        $_GET['booking_id'],
        $user_id,
        trim($_POST['flight_number']),
        trim($_POST['route_origin']),
        trim($_POST['route_destination']),
        trim($_POST['assigned_seat']),
        $_POST['ticket_price'],
        str_replace('T', ' ', $_POST['scheduled_departure'])
    );

    header('Location: ../index.php');
    exit();
}

if (isset($_POST['deleteBookingBtn'])) {
    requireAdmin();

    deleteBooking($pdo, $_GET['booking_id'], $user_id);

    header('Location: ../index.php');
    exit();
}

// -- Customer requests --
if (isset($_POST['requestRescheduleBtn'])) {
    requireCustomer();

    $booking_id = $_POST['booking_id'];
    $date = $_POST['requested_departure'];
    $reason = trim($_POST['reason']);

    if (!customerOwnsBooking($pdo, $booking_id, $user_id)) {
        exit('Ticket not found.');
    }

    createRescheduleRequest($pdo, $booking_id, $user_id, str_replace('T', ' ', $date), $reason);

    header('Location: ../reschedule.php');
    exit();
}

if (isset($_POST['submitBookingRequestBtn'])) {
    requireCustomer();

    $request_id = $_POST['request_id'] ?? 0;

    $first_name = trim($_POST['passenger_first_name']);
    $last_name = trim($_POST['passenger_last_name']);
    $email = trim($_POST['passenger_email']);
    $phone = trim($_POST['passenger_phone']);
    $flight = trim($_POST['flight_number']);
    $origin = trim($_POST['route_origin']);
    $destination = trim($_POST['route_destination']);
    $date = str_replace('T', ' ', $_POST['requested_departure']);

    if ($request_id > 0) {
        updateBookingRequest(
            $pdo, $request_id, $user_id,
            $first_name, $last_name, $email, $phone,
            $flight, $origin, $destination, $date
        );
    } else {
        createBookingRequest(
            $pdo, $user_id,
            $first_name, $last_name, $email, $phone,
            $flight, $origin, $destination, $date
        );
    }

    header('Location: ../booking_request.php');
    exit();
}

// -- Admin review actions --
if (isset($_POST['approveBookingRequestBtn']) ||
    isset($_POST['rejectBookingRequestBtn']) ||
    isset($_POST['returnBookingRequestBtn'])) {

    requireAdmin();

    if (isset($_POST['approveBookingRequestBtn'])) {
        $decision = 'approved';
    } elseif (isset($_POST['returnBookingRequestBtn'])) {
        $decision = 'needs_changes';
    } else {
        $decision = 'rejected';
    }

    reviewBookingRequest(
        $pdo,
        $_POST['request_id'],
        $user_id,
        $decision,
        trim($_POST['admin_response'] ?? ''),
        $_POST['assigned_seat'] ?? null,
        $_POST['ticket_price'] ?? null
    );

    header('Location: ../admin_booking_requests.php');
    exit();
}

if (isset($_POST['approveRescheduleBtn']) || isset($_POST['rejectRescheduleBtn'])) {
    requireAdmin();

    $decision = isset($_POST['approveRescheduleBtn']) ? 'approved' : 'rejected';

    reviewRescheduleRequest(
        $pdo,
        $_POST['request_id'],
        $user_id,
        $decision,
        trim($_POST['admin_response'] ?? '')
    );

    header('Location: ../admin_requests.php');
    exit();
}

echo "Invalid form submission.";
?>
