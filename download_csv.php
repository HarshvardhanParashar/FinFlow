<?php
session_start(); // Start the session for user authentication
include 'db_connect.php'; // Include database connection

// --- Basic Page Protection ---
if (!isset($_SESSION['user_id'])) {
    http_response_code(401); // Unauthorized
    die("You must be logged in to download reports.");
}
$user_id = $_SESSION['user_id'];

// --- Get Filter Parameters (same logic as in reports.php) ---
$quickSelect = $_GET['quick_select'] ?? '';
$customStartDate = $_GET['start_date'] ?? '';
$customEndDate = $_GET['end_date'] ?? '';

$reportStartDate = null;
$reportEndDate = null;

if (!empty($quickSelect)) {
    switch ($quickSelect) {
        case 'this_month':
            $reportStartDate = date('Y-m-01');
            $reportEndDate = date('Y-m-t');
            break;
        case 'last_month':
            $reportStartDate = date('Y-m-01', strtotime('last month'));
            $reportEndDate = date('Y-m-t', strtotime('last month'));
            break;
        case 'this_year':
            $reportStartDate = date('Y-01-01');
            $reportEndDate = date('Y-12-31');
            break;
        case 'last_year':
            $reportStartDate = date('Y-01-01', strtotime('last year'));
            $reportEndDate = date('Y-12-31', strtotime('last year'));
            break;
        default:
            $reportStartDate = date('Y-m-01');
            $reportEndDate = date('Y-m-t');
            break;
    }
} elseif (!empty($customStartDate) && !empty($customEndDate)) {
    $reportStartDate = $customStartDate;
    $reportEndDate = $customEndDate;
} else {
    $reportStartDate = date('Y-m-01');
    $reportEndDate = date('Y-m-t');
}

// --- Ensure dates are valid, otherwise fall back to a reasonable default ---
if (!$reportStartDate || !$reportEndDate || $reportStartDate > $reportEndDate) {
    $reportStartDate = date('Y-m-01');
    $reportEndDate = date('Y-m-t');
}


// --- Set CSV Headers to force a download ---
$filename = "finflow_report_" . date('Y-m-d', strtotime($reportStartDate)) . "_to_" . date('Y-m-d', strtotime($reportEndDate)) . ".csv";
header('Content-Type: text/csv; charset=utf-8');
header('Content-Disposition: attachment; filename="' . $filename . '"');

// --- Open a file stream to write to the browser's output ---
$output = fopen('php://output', 'w');

// --- Set the CSV Header Row ---
fputcsv($output, ['Date', 'Description', 'Category', 'Type', 'Amount']);

// --- Fetch Data from Database for the report period ---
$stmt = $conn->prepare("SELECT transaction_date, description, category, type, amount FROM transactions WHERE user_id = ? AND transaction_date BETWEEN ? AND ? ORDER BY transaction_date DESC");

if ($stmt) {
    $stmt->bind_param("iss", $user_id, $reportStartDate, $reportEndDate);
    $stmt->execute();
    $result = $stmt->get_result();

    // --- Write fetched data to CSV ---
    while ($row = $result->fetch_assoc()) {
        fputcsv($output, $row);
    }
    $stmt->close();
}

fclose($output); // Close the file stream
$conn->close(); // Close the database connection
exit(); // End script execution
?>