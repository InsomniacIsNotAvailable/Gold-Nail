<?php
require_once __DIR__ . '/config.php';

header('Content-Type: application/json; charset=utf-8');

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Collect and sanitize input data
    $name = isset($_POST['name']) ? trim($_POST['name']) : '';
    $email = isset($_POST['email']) ? filter_var(trim($_POST['email']), FILTER_SANITIZE_EMAIL) : '';
    $phone = isset($_POST['phone']) ? preg_replace('/\D+/', '', $_POST['phone']) : '';
    $appointment_datetime = isset($_POST['appointment_datetime']) ? trim($_POST['appointment_datetime']) : '';
    $purpose = isset($_POST['purpose']) ? trim($_POST['purpose']) : '';
    $message = isset($_POST['message']) ? trim($_POST['message']) : '';

    // --- Validation ---
    if ($name === '') {
        echo json_encode(['status' => 'error', 'field' => 'appointment-name', 'message' => 'Name is required.']);
        exit;
    }

    if ($email === '' || !filter_var($email, FILTER_VALIDATE_EMAIL)) {
        echo json_encode(['status' => 'error', 'field' => 'appointment-email', 'message' => 'Invalid email format.']);
        exit;
    }

    if (!preg_match('/^[0-9]{11}$/', $phone)) {
        echo json_encode(['status' => 'error', 'field' => 'appointment-phone', 'message' => 'Phone number must contain exactly 11 digits.']);
        exit;
    }

    if ($appointment_datetime === '') {
        echo json_encode(['status' => 'error', 'field' => 'appointment-datetime', 'message' => 'Please select a date and time.']);
        exit;
    }

    if ($purpose === '') {
        echo json_encode(['status' => 'error', 'field' => 'appointment-purpose', 'message' => 'Please select a purpose.']);
        exit;
    }

    if (strlen($message) > 250) {
        echo json_encode(['status' => 'error', 'field' => 'appointment-message', 'message' => 'Message cannot exceed 250 characters.']);
        exit;
    }

    // --- Save to database ---
    $conn = get_connection();
    if (!$conn) {
        echo json_encode(['status' => 'error', 'field' => 'appointment-general', 'message' => 'Database connection failed.']);
        exit;
    }

    $sql = "INSERT INTO appointments (name, email, phone, appointment_datetime, purpose, message)
            VALUES (?, ?, ?, ?, ?, ?)";

    $stmt = mysqli_prepare($conn, $sql);
    if ($stmt === false) {
        echo json_encode(['status' => 'error', 'field' => 'appointment-general', 'message' => 'Failed to prepare SQL statement.']);
        mysqli_close($conn);
        exit;
    }

    mysqli_stmt_bind_param($stmt, "ssssss", $name, $email, $phone, $appointment_datetime, $purpose, $message);

    if (mysqli_stmt_execute($stmt)) {
        echo json_encode(['status' => 'success', 'message' => 'Appointment booked successfully!']);
    } else {
        echo json_encode(['status' => 'error', 'field' => 'appointment-general', 'message' => 'Database error, please try again.']);
    }

    mysqli_stmt_close($stmt);
    mysqli_close($conn);
    exit;
}

// Non-POST fallback
echo json_encode(['status' => 'error', 'field' => 'appointment-general', 'message' => 'Invalid request method.']);
exit;
