<?php
// PHP includes must be at the very top, before any HTML output, for sessions and database connection
session_start();

include 'db_connect.php';

// Basic protection: Redirect if user not logged in
if (!isset($_SESSION['user_id'])) {
    header("Location: signin.html?error=notloggedin");
    exit();
}
$user_id = $_SESSION['user_id'];

// Retrieve filter and sort parameters from GET request
$filterCategory = $_GET['category'] ?? '';
$filterType = $_GET['type'] ?? '';
$filterDatePeriod = $_GET['date_period'] ?? '';
$sortByDate = $_GET['sort_date'] ?? 'desc';
$sortByAmount = $_GET['sort_amount'] ?? '';

// Start building the SQL query
$sql = "SELECT id, description, amount, type, category, transaction_date FROM transactions WHERE user_id = ?";
$params = [$user_id];
$paramTypes = "i";

// Add filters based on user selection
if (!empty($filterCategory)) {
    $sql .= " AND category = ?";
    $params[] = $filterCategory;
    $paramTypes .= "s";
}
if (!empty($filterType)) {
    $sql .= " AND type = ?";
    $params[] = $filterType;
    $paramTypes .= "s";
}

// Handle date period filtering
if (!empty($filterDatePeriod)) {
    $currentDate = date('Y-m-d');
    $startDate = '';

    switch ($filterDatePeriod) {
        case 'last_month':
            $startDate = date('Y-m-01', strtotime('last month'));
            $endDate = date('Y-m-t', strtotime('last month'));
            $sql .= " AND transaction_date BETWEEN ? AND ?";
            $params[] = $startDate;
            $params[] = $endDate;
            $paramTypes .= "ss";
            break;
        case 'last_3_months':
            $startDate = date('Y-m-01', strtotime('-2 months', strtotime(date('Y-m-01'))));
            $sql .= " AND transaction_date >= ?";
            $params[] = $startDate;
            $paramTypes .= "s";
            break;
        case 'last_6_months':
            $startDate = date('Y-m-01', strtotime('-5 months', strtotime(date('Y-m-01'))));
            $sql .= " AND transaction_date >= ?";
            $params[] = $startDate;
            $paramTypes .= "s";
            break;
        case 'last_year':
            $startDate = date('Y-01-01', strtotime('-1 year'));
            $sql .= " AND transaction_date >= ?";
            $params[] = $startDate;
            $paramTypes .= "s";
            break;
    }
}

// Add sorting
$sql .= " ORDER BY ";
if (!empty($sortByAmount)) {
    $sql .= "amount " . ($sortByAmount == 'high_low' ? 'DESC' : 'ASC') . ", ";
}
$sql .= "transaction_date " . ($sortByDate == 'asc' ? 'ASC' : 'DESC') . ", id DESC";

$stmt = $conn->prepare($sql);

if (!$stmt) {
    die("Error preparing query: " . $conn->error);
}

// Dynamically bind parameters
$bind_names[] = $paramTypes;
for ($i = 0; $i < count($params); $i++) {
    $bind_name = 'bind' . $i;
    $$bind_name = $params[$i];
    $bind_names[] = &$$bind_name;
}
call_user_func_array([$stmt, 'bind_param'], $bind_names);

$stmt->execute();
$result = $stmt->get_result();
$transactions = $result->fetch_all(MYSQLI_ASSOC);
$stmt->close();
$conn->close();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Transactions - FinFlow</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://fonts.googleapis.com/css2?family=Material+Symbols+Outlined:opsz,wght,FILL,GRAD@24,400,0,0" />
    <style>
        /* Your custom styles */
    </style>
</head>
<body>

    <?php include 'header.php'; ?>

    <div class="container mt-4">
        <div class="d-flex justify-content-between align-items-center mb-4">
            <div>
                <h2 class="mb-1">All Transactions</h2>
                <p class="text-muted mb-0">A complete log of your income and expenses.</p>
            </div>
            <button class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#addTransactionModal">+ Add Transaction</button>
        </div>

    
        <div class="modal fade" id="addTransactionModal" tabindex="-1" aria-labelledby="addTransactionModalLabel" aria-hidden="true">
      <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content">
          <div class="modal-header">
            <h5 class="modal-title" id="addTransactionModalLabel">New Transaction</h5>
            <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
          </div>
          <div class="modal-body">
            <form action="add_transaction_process.php" method="POST">
              <div class="mb-3">
                <label for="transactionDescription" class="form-label">Description</label>
                <input type="text" class="form-control" id="transactionDescription" name="description" required>
              </div>
              <div class="row">
                  <div class="col">
                      <div class="mb-3">
                        <label for="transactionAmount" class="form-label">Amount</label>
                        <input type="number" class="form-control" id="transactionAmount" name="amount" placeholder="0.00" step="0.01" required>
                      </div>
                  </div>
                  <div class="col">
                      <div class="mb-3">
                        <label for="transactionDate" class="form-label">Date</label>
                        <input type="date" class="form-control" id="transactionDate" name="transaction_date" value="<?php echo date('Y-m-d'); ?>" required>
                      </div>
                  </div>
              </div>
              <div class="row">
                  <div class="col">
                      <div class="mb-3">
                        <label for="transactionType" class="form-label">Type</label>
                        <select class="form-select" id="transactionType" name="type" required>
                          <option value="expense" selected>Expense</option>
                          <option value="income">Income</option>
                        </select>
                      </div>
                  </div>
                  <div class="col">
                      <div class="mb-3">
                        <label for="transactionCategory" class="form-label">Category</label>
                        <select class="form-select" id="transactionCategory" name="category" required>
                          <option selected>Select a category</option>
                          <option value="food">Food</option>
                          <option value="transportation">Transportation</option>
                          <option value="shopping">Shopping</option>
                          <option value="health">Health & Wellness</soption>
                          <option value="entertainment">Entertainment</option>
                          <option value="bills">Bills & Subscriptions</option>
                          <option value="travel">Travel</option>
                          <option value="rent">Rent</option>
                          <option value="groceries">Groceries</option>
                          <option value="study">Study</option>
                          <option value="salary">Salary (Income)</option>
                          <option value="misc">Miscellaneous</option>
                          <option value="custom">Custom</option>
                        </select>
                      </div>
                  </div>
                  <div class="col" id="customCategoryInput" style="display: none;">
                                <div class="mb-3">
                                    <label for="transactionCustomCategory" class="form-label">Custom Category
                                        Name</label>
                                    <input type="text" class="form-control" id="transactionCustomCategory"
                                        name="custom_category" placeholder="e.g., Hobby, Gadgets">
                                </div>
                            </div>
              </div>
            
          </div>
          <div class="modal-footer">
            <button type="button" class="btn btn-light" data-bs-dismiss="modal">Cancel</button>
            <button type="submit" class="btn btn-primary">Save Transaction</button>
          </div>
            </form>
        </div>
      </div>
    </div>


        <div id="alertContainer" class="mt-3"></div>

        <div class="card filter-card shadow-sm mb-4">
            <div class="card-body">
                <form action="transactions.php" method="GET">
                    <div class="row g-3 align-items-end">
                        <div class="col-md-6 col-lg">
                            <label for="filterCategory" class="form-label small">Category</label>
                            <select class="form-select" id="filterCategory" name="category">
                                <option value="" <?php echo (empty($filterCategory) ? 'selected' : ''); ?>>All</option>
                                <option value="food" <?php echo ($filterCategory == 'food' ? 'selected' : ''); ?>>Food</option>
                                <option value="transportation" <?php echo ($filterCategory == 'transportation' ? 'selected' : ''); ?>>Transportation</option>
                                <option value="shopping" <?php echo ($filterCategory == 'shopping' ? 'selected' : ''); ?>>Shopping</option>
                                <option value="health" <?php echo ($filterCategory == 'health' ? 'selected' : ''); ?>>Health & Wellness</option>
                                <option value="entertainment" <?php echo ($filterCategory == 'entertainment' ? 'selected' : ''); ?>>Entertainment</option>
                                <option value="bills" <?php echo ($filterCategory == 'bills' ? 'selected' : ''); ?>>Bills & Subscriptions</option>
                                <option value="travel" <?php echo ($filterCategory == 'travel' ? 'selected' : ''); ?>>Travel</option>
                                <option value="rent" <?php echo ($filterCategory == 'rent' ? 'selected' : ''); ?>>Rent</option>
                                <option value="groceries" <?php echo ($filterCategory == 'groceries' ? 'selected' : ''); ?>>Groceries</option>
                                <option value="study" <?php echo ($filterCategory == 'study' ? 'selected' : ''); ?>>Study</option>
                                <option value="salary" <?php echo ($filterCategory == 'salary' ? 'selected' : ''); ?>>Salary (Income)</option>
                                <option value="misc" <?php echo ($filterCategory == 'misc' ? 'selected' : ''); ?>>Miscellaneous</option>
                            </select>
                        </div>
                        <div class="col-md-6 col-lg">
                            <label for="filterType" class="form-label small">Type</label>
                            <select class="form-select" id="filterType" name="type">
                                <option value="" <?php echo (empty($filterType) ? 'selected' : ''); ?>>All</option>
                                <option value="expense" <?php echo ($filterType == 'expense' ? 'selected' : ''); ?>>Expense</option>
                                <option value="income" <?php echo ($filterType == 'income' ? 'selected' : ''); ?>>Income</option>
                            </select>
                        </div>
                        <div class="col-md-6 col-lg">
                            <label for="filterDate" class="form-label small">Date Period</label>
                            <select class="form-select" id="filterDate" name="date_period">
                                <option value="" <?php echo (empty($filterDatePeriod) ? 'selected' : ''); ?>>All Time</option>
                                <option value="last_month" <?php echo ($filterDatePeriod == 'last_month' ? 'selected' : ''); ?>>Last month</option>
                                <option value="last_3_months" <?php echo ($filterDatePeriod == 'last_3_months' ? 'selected' : ''); ?>>Last 3 months</option>
                                <option value="last_6_months" <?php echo ($filterDatePeriod == 'last_6_months' ? 'selected' : ''); ?>>Last 6 months</option>
                                <option value="last_year" <?php echo ($filterDatePeriod == 'last_year' ? 'selected' : ''); ?>>Last year</option>
                            </select>
                        </div>
                        <div class="col-md-6 col-lg">
                            <label for="filterSortDate" class="form-label small">Sort by Date</label>
                            <select class="form-select" id="filterSortDate" name="sort_date">
                                <option value="desc" <?php echo ((!isset($_GET['sort_date']) || $sortByDate == 'desc') ? 'selected' : ''); ?>>Newest First</option>
                                <option value="asc" <?php echo ($sortByDate == 'asc' ? 'selected' : ''); ?>>Oldest first</option>
                            </select>
                        </div>
                        <div class="col-md-6 col-lg">
                            <label for="filterSortAmount" class="form-label small">Sort by Amount</label>
                            <select class="form-select" id="filterSortAmount" name="sort_amount">
                                <option value="" <?php echo (empty($sortByAmount) ? 'selected' : ''); ?>>Default</option>
                                <option value="high_low" <?php echo ($sortByAmount == 'high_low' ? 'selected' : ''); ?>>High to low</option>
                                <option value="low_high" <?php echo ($sortByAmount == 'low_high' ? 'selected' : ''); ?>>Low to high</option>
                            </select>
                        </div>
                        <div class="col-md-12 col-lg-auto d-flex">
                            <button type="submit" class="btn btn-primary w-100 me-2">Filter</button>
                            <button type="button" class="btn btn-light w-100" onclick="window.location.href='transactions.php'">Reset</button>
                        </div>
                    </div>
                </form>
            </div>
        </div>

        <div class="card shadow-sm">
             <div class="card-body">
                <div class="table-responsive">
                    <table class="table table-hover align-middle">
                        <thead>
                            <tr>
                                <th>Date</th>
                                <th>Description</th>
                                <th>Category</th>
                                <th class="text-end">Amount</th>
                                <th class="text-center">Type</th>
                                <th class="text-center">Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if (empty($transactions)): ?>
                                <tr><td colspan='6' class='text-center py-4 text-muted'>No transactions found matching your criteria.</td></tr>
                            <?php else: ?>
                                <?php foreach ($transactions as $row):
                                    $amount_display = ($row['type'] == 'expense' ? '- ' : '+ ') . '₹' . number_format($row['amount'], 2);
                                    $amount_class = ($row['type'] == 'expense' ? 'text-danger' : 'text-success');
                                    $badge_class = ($row['type'] == 'expense' ? 'bg-danger-subtle text-danger-emphasis' : 'bg-success-subtle text-success-emphasis');
                                    $date_obj = new DateTime($row['transaction_date']);
                                    $formatted_date = $date_obj->format('M d, Y');
                                    ?>
                                    <tr>
                                        <td><?php echo $formatted_date; ?></td>
                                        <td><?php echo htmlspecialchars($row['description']); ?></td>
                                        <td><?php echo htmlspecialchars($row['category']); ?></td>
                                        <td class="text-end <?php echo $amount_class; ?> fw-bold"><?php echo $amount_display; ?></td>
                                        <td class="text-center"><span class="badge <?php echo $badge_class; ?> rounded-pill"><?php echo ucfirst($row['type']); ?></span></td>
                                        <td class="text-center">
                                            <button class="btn btn-sm btn-light"
                                                    data-bs-toggle="modal"
                                                    data-bs-target="#editModal"
                                                    data-id="<?php echo $row['id']; ?>"
                                                    data-description="<?php echo htmlspecialchars($row['description']); ?>"
                                                    data-amount="<?php echo $row['amount']; ?>"
                                                    data-date="<?php echo $row['transaction_date']; ?>"
                                                    data-type="<?php echo $row['type']; ?>"
                                                    data-category="<?php echo htmlspecialchars($row['category']); ?>">
                                                <span class="material-symbols-outlined">edit</span>
                                            </button>
                                            <button class="btn btn-sm btn-light"
                                                    data-bs-toggle="modal"
                                                    data-bs-target="#deleteModal"
                                                    data-id="<?php echo $row['id']; ?>">
                                                <span class="material-symbols-outlined">delete</span>
                                            </button>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>

    <div class="modal fade" id="addTransactionModal" tabindex="-1" aria-labelledby="addTransactionModalLabel" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="addTransactionModalLabel">New Transaction</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <form action="add_transaction_process.php" method="POST">
                        <div class="mb-3">
                            <label for="transactionDescription" class="form-label">Description</label>
                            <input type="text" class="form-control" id="transactionDescription" name="description" required>
                        </div>
                        <div class="row">
                            <div class="col">
                                <div class="mb-3">
                                    <label for="transactionAmount" class="form-label">Amount</label>
                                    <input type="number" class="form-control" id="transactionAmount" name="amount" placeholder="0.00" step="0.01" required>
                                </div>
                            </div>
                            <div class="col">
                                <div class="mb-3">
                                    <label for="transactionDate" class="form-label">Date</label>
                                    <input type="date" class="form-control" id="transactionDate" name="transaction_date" value="<?php echo date('Y-m-d'); ?>" required>
                                </div>
                            </div>
                        </div>
                        <div class="row">
                            <div class="col">
                                <div class="mb-3">
                                    <label for="transactionType" class="form-label">Type</label>
                                    <select class="form-select" id="transactionType" name="type" required>
                                        <option value="expense" selected>Expense</option>
                                        <option value="income">Income</option>
                                    </select>
                                </div>
                            </div>
                            <div class="col">
                                <div class="mb-3">
                                    <label for="transactionCategory" class="form-label">Category</label>
                                    <select class="form-select" id="transactionCategory" name="category" required>
                                        <option selected>Select a category</option>
                                        <option value="food">Food</option>
                                        <option value="transportation">Transportation</option>
                                        <option value="shopping">Shopping</option>
                                        <option value="health">Health & Wellness</option>
                                        <option value="entertainment">Entertainment</option>
                                        <option value="bills">Bills & Subscriptions</option>
                                        <option value="travel">Travel</option>
                                        <option value="rent">Rent</option>
                                        <option value="groceries">Groceries</option>
                                        <option value="study">Study</option>
                                        <option value="salary">Salary (Income)</option>
                                        <option value="misc">Miscellaneous</option>
                                        <option value="custom">Custom</option>
                                    </select>
                                </div>
                            </div>
                            <div class="col" id="customCategoryInput" style="display: none;">
                                <div class="mb-3">
                                    <label for="transactionCustomCategory" class="form-label">Custom Category
                                        Name</label>
                                    <input type="text" class="form-control" id="transactionCustomCategory"
                                        name="custom_category" placeholder="e.g., Hobby, Gadgets">
                                </div>
                            </div>
                        </div>
                    </form>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-light" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-primary" form="addTransactionModalForm">Save Transaction</button>
                </div>
            </div>
        </div>
    </div>

    <div class="modal fade" id="editModal" tabindex="-1" aria-labelledby="editModalLabel" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="editModalLabel">Edit Transaction</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <form action="edit_transaction_process.php" method="POST" id="editForm">
                        <input type="hidden" name="transaction_id" id="editTransactionId">
                        <div class="mb-3">
                            <label for="editDescription" class="form-label">Description</label>
                            <input type="text" class="form-control" id="editDescription" name="description" required>
                        </div>
                        <div class="row">
                            <div class="col">
                                <div class="mb-3">
                                    <label for="editAmount" class="form-label">Amount</label>
                                    <input type="number" class="form-control" id="editAmount" name="amount" placeholder="0.00" step="0.01" required>
                                </div>
                            </div>
                            <div class="col">
                                <div class="mb-3">
                                    <label for="editDate" class="form-label">Date</label>
                                    <input type="date" class="form-control" id="editDate" name="transaction_date" required>
                                </div>
                            </div>
                        </div>
                        <div class="row">
                            <div class="col">
                                <div class="mb-3">
                                    <label for="editType" class="form-label">Type</label>
                                    <select class="form-select" id="editType" name="type" required>
                                        <option value="expense">Expense</option>
                                        <option value="income">Income</option>
                                    </select>
                                </div>
                            </div>
                            <div class="col">
                                <div class="mb-3">
                                    <label for="editCategory" class="form-label">Category</label>
                                    <select class="form-select" id="editCategory" name="category" required>
                                        <option value="food">Food</option>
                                        <option value="transportation">Transportation</option>
                                        <option value="shopping">Shopping</option>
                                        <option value="health">Health & Wellness</option>
                                        <option value="entertainment">Entertainment</option>
                                        <option value="bills">Bills & Subscriptions</option>
                                        <option value="travel">Travel</option>
                                        <option value="rent">Rent</option>
                                        <option value="groceries">Groceries</option>
                                        <option value="study">Study</option>
                                        <option value="salary">Salary (Income)</option>
                                        <option value="misc">Miscellaneous</option>
                                    </select>
                                </div>
                            </div>
                        </div>
                    </form>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-light" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-primary" form="editForm">Save Changes</button>
                </div>
            </div>
        </div>
    </div>

    <div class="modal fade" id="deleteModal" tabindex="-1" aria-labelledby="deleteModalLabel" aria-hidden="true">
      <div class="modal-dialog">
        <div class="modal-content">
          <div class="modal-header">
            <h5 class="modal-title" id="deleteModalLabel">Confirm Deletion</h5>
            <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
          </div>
          <div class="modal-body">
            Are you sure you want to delete this transaction? This action cannot be undone.
          </div>
          <div class="modal-footer">
            <button type="button" class="btn btn-light" data-bs-dismiss="modal">Cancel</button>
            <form action="delete_transaction_process.php" method="POST" id="deleteForm">
              <input type="hidden" name="transaction_id" id="transactionIdInput">
              <button type="submit" class="btn btn-danger">Delete</button>
            </form>
          </div>
        </div>
      </div>
    </div>


    <div class="py-3"></div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        document.addEventListener('DOMContentLoaded', function () {
            // JavaScript for showing/hiding custom category input
            const categorySelect = document.getElementById('transactionCategory');
            const customCategoryInputDiv = document.getElementById('customCategoryInput');
            const customCategoryInputField = document.getElementById('transactionCustomCategory');

            categorySelect.addEventListener('change', function () {
                if (this.value === 'custom') {
                    customCategoryInputDiv.style.display = 'block';
                    customCategoryInputField.setAttribute('required', 'required'); // Make it required when visible
                } else {
                    customCategoryInputDiv.style.display = 'none';
                    customCategoryInputField.removeAttribute('required');
                    customCategoryInputField.value = ''; // Clear value when hidden
                }
            });

            // JavaScript for handling modals
            const deleteModal = document.getElementById('deleteModal');
            if (deleteModal) {
                deleteModal.addEventListener('show.bs.modal', function (event) {
                    const button = event.relatedTarget;
                    const transactionId = button.getAttribute('data-id');
                    const modalTransactionIdInput = deleteModal.querySelector('#transactionIdInput');
                    modalTransactionIdInput.value = transactionId;
                });
            }

            const editModal = document.getElementById('editModal');
            if (editModal) {
                editModal.addEventListener('show.bs.modal', function (event) {
                    const button = event.relatedTarget;
                    const id = button.getAttribute('data-id');
                    const description = button.getAttribute('data-description');
                    const amount = button.getAttribute('data-amount');
                    const date = button.getAttribute('data-date');
                    const type = button.getAttribute('data-type');
                    const category = button.getAttribute('data-category');

                    const modalIdInput = editModal.querySelector('#editTransactionId');
                    const modalDescriptionInput = editModal.querySelector('#editDescription');
                    const modalAmountInput = editModal.querySelector('#editAmount');
                    const modalDateInput = editModal.querySelector('#editDate');
                    const modalTypeSelect = editModal.querySelector('#editType');
                    const modalCategorySelect = editModal.querySelector('#editCategory');

                    modalIdInput.value = id;
                    modalDescriptionInput.value = description;
                    modalAmountInput.value = amount;
                    modalDateInput.value = date;
                    modalTypeSelect.value = type;
                    modalCategorySelect.value = category;

                    // Add an event listener to the category dropdown inside the edit modal
                    modalCategorySelect.addEventListener('change', function() {
                        // The logic for showing custom category input in the edit modal should be here
                        // For now, we'll assume it's not a feature for the edit modal
                    });
                });
            }

            // Alerts JavaScript
            const urlParams = new URLSearchParams(window.location.search);
            const error = urlParams.get('error');
            const success = urlParams.get('success');
            const alertContainer = document.getElementById('alertContainer');

            if (alertContainer) {
                if (error) {
                    let message = '';
                    let alertType = 'alert-danger';
                    switch (error) {
                        case 'emptyfields':
                            message = 'Please fill in all transaction fields.';
                            break;
                        case 'invalidamount':
                            message = 'Please enter a valid positive amount.';
                            break;
                        case 'dberror_prepare':
                        case 'dberror_insert':
                        case 'dberror_delete':
                        case 'dberror_update':
                            message = 'A database error occurred. Please try again.';
                            break;
                        case 'notloggedin':
                            message = 'You must be logged in to add transactions.';
                            break;
                        case 'invalidid':
                            message = 'Invalid transaction ID.';
                            break;
                        case 'emptycustomcategory':
                            message = 'Please provide a name for your custom category.';
                            break;
                        default:
                            message = 'An unknown error occurred.';
                    }
                    alertContainer.innerHTML = `<div class="alert ${alertType} alert-dismissible fade show" role="alert">
                                                    ${message}
                                                    <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                                                </div>`;
                    setTimeout(() => {
                        const newUrl = new URL(window.location.href);
                        newUrl.searchParams.delete('error');
                        window.history.replaceState({}, document.title, newUrl.toString());
                    }, 3000);
                } else if (success === 'added') {
                    alertContainer.innerHTML = `<div class="alert alert-success alert-dismissible fade show" role="alert">
                                                    Transaction added successfully!
                                                    <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                                                </div>`;
                    setTimeout(() => {
                        const newUrl = new URL(window.location.href);
                        newUrl.searchParams.delete('success');
                        window.history.replaceState({}, document.title, newUrl.toString());
                    }, 3000);
                } else if (success === 'updated') {
                    alertContainer.innerHTML = `<div class="alert alert-success alert-dismissible fade show" role="alert">
                                                    Transaction updated successfully!
                                                    <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                                                </div>`;
                    setTimeout(() => {
                        const newUrl = new URL(window.location.href);
                        newUrl.searchParams.delete('success');
                        window.history.replaceState({}, document.title, newUrl.toString());
                    }, 3000);
                } else if (success === 'deleted') {
                    alertContainer.innerHTML = `<div class="alert alert-success alert-dismissible fade show" role="alert">
                                                    Transaction deleted successfully!
                                                    <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                                                </div>`;
                    setTimeout(() => {
                        const newUrl = new URL(window.location.href);
                        newUrl.searchParams.delete('success');
                        window.history.replaceState({}, document.title, newUrl.toString());
                    }, 3000);
                }
            }
        });
    </script>
</body>
</html>