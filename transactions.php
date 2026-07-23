<?php
session_start();
include 'db.php';

if (!isset($_SESSION['user_id'])) {
    header('Location: login.php');
    exit();
}

$user_id = $_SESSION['user_id'];
$fullname = $_SESSION['fullname'];

// ===== DELETE TRANSACTION =====
if (isset($_GET['delete'])) {
    $id = (int)$_GET['delete'];

    $stmt = $conn->prepare("DELETE FROM expenses WHERE expense_id=? AND user_id=?");
    $stmt->bind_param("ii", $id, $user_id);

    if ($stmt->execute()) {
        header('Location: transactions.php?msg=deleted');
        exit();
    }
}

// ===== FILTERS =====
$search = trim($_GET['search'] ?? '');
$category = $_GET['category'] ?? '';
$payment = $_GET['payment'] ?? '';
$from = $_GET['from'] ?? '';
$to = $_GET['to'] ?? '';

// ===== BUILD QUERY =====
$sql = "
SELECT e.expense_id, e.expense_date, e.amount, e.payment_method,
       e.description, c.category_name
FROM expenses e
JOIN categories c ON e.category_id = c.category_id
WHERE e.user_id = ?
";

$params = [$user_id];
$types = 'i';

if ($search !== '') {
    $sql .= " AND (e.description LIKE ? OR c.category_name LIKE ?)";
    $searchLike = "%$search%";
    $params[] = $searchLike;
    $params[] = $searchLike;
    $types .= 'ss';
}

if ($category !== '') {
    $sql .= " AND c.category_id = ?";
    $params[] = $category;
    $types .= 'i';
}

if ($payment !== '') {
    $sql .= " AND e.payment_method = ?";
    $params[] = $payment;
    $types .= 's';
}

if ($from !== '') {
    $sql .= " AND e.expense_date >= ?";
    $params[] = $from;
    $types .= 's';
}

if ($to !== '') {
    $sql .= " AND e.expense_date <= ?";
    $params[] = $to;
    $types .= 's';
}

$sql .= " ORDER BY e.expense_date DESC, e.expense_id DESC";

$stmt = $conn->prepare($sql);
$stmt->bind_param($types, ...$params);
$stmt->execute();
$transactions = $stmt->get_result();

// ===== CATEGORY LIST =====
$cat_result = $conn->query("SELECT category_id, category_name FROM categories ORDER BY category_name");

// ===== TOTAL FILTERED =====
$total_filtered = 0;
$temp = $transactions;
while ($row = $temp->fetch_assoc()) {
    $total_filtered += $row['amount'];
}
$transactions->data_seek(0);
?>

<!DOCTYPE html>

<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1">
<title>Transactions - Finora</title>

<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
<link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.css" rel="stylesheet">

<style>
:root{
    --bg:#0b1020;
    --panel:#121933cc;
    --line:#2a3566;
    --text:#eef2ff;
    --muted:#a5b4fc;
}

body{
    background: radial-gradient(circle at top left,#1d4ed8 0%,#0b1020 35%,#070b16 100%);
    color:var(--text);
    font-family:'Segoe UI',sans-serif;
    min-height:100vh;
}

.sidebar{
    width:250px;
    min-height:100vh;
    position:fixed;
    background:#0f172acc;
    backdrop-filter: blur(18px);
    border-right:1px solid var(--line);
}

.sidebar .brand{
    padding:24px 20px;
    font-size:24px;
    font-weight:700;
}

.sidebar a{
    color:#c7d2fe;
    text-decoration:none;
    display:block;
    padding:12px 18px;
    margin:6px 12px;
    border-radius:14px;
    transition:.2s ease;
}

.sidebar a:hover,
.sidebar a.active{
    background:linear-gradient(135deg,#2563eb,#3b82f6);
    color:white;
    transform:translateX(4px);
}

.main{ margin-left:250px; }

.topbar{
    background:#0f172acc;
    backdrop-filter: blur(16px);
    border-bottom:1px solid var(--line);
    padding:16px 24px;
}

.glass{
    background:var(--panel);
    border:1px solid rgba(255,255,255,.08);
    backdrop-filter: blur(16px);
    border-radius:24px;
    box-shadow:0 12px 40px rgba(0,0,0,.28);
}

.table-darkish{
    --bs-table-bg: transparent;
    --bs-table-color: var(--text);
    --bs-table-border-color: #243056;
    --bs-table-striped-bg: rgba(255,255,255,.03);
    --bs-table-hover-bg: rgba(255,255,255,.05);
}

.form-control,
.form-select{
    background:#0f172a;
    border:1px solid #334155;
    color:#fff;
    border-radius:12px;
}

.form-control:focus,
.form-select:focus{
    background:#0f172a;
    color:#fff;
    border-color:#60a5fa;
    box-shadow:0 0 0 .2rem rgba(96,165,250,.15);
}

.badge-method{
    border-radius:999px;
    padding:8px 12px;
}

.amount{
    font-weight:700;
    color:#ef4444;
}

@media (max-width: 992px){
    .sidebar{ position:relative; width:100%; min-height:auto; }
    .main{ margin-left:0; }
}
</style>

</head>
<body>

<!-- SIDEBAR -->

<div class="sidebar">
    <div class="brand"><i class="bi bi-wallet2 me-2"></i>Finora</div>

```
<a href="dashboard.php"><i class="bi bi-speedometer2 me-2"></i>Dashboard</a>
<a href="add_income.php"><i class="bi bi-plus-circle me-2"></i>Add Income</a>
<a href="add_expense.php"><i class="bi bi-dash-circle me-2"></i>Add Expense</a>
<a href="transactions.php" class="active"><i class="bi bi-arrow-left-right me-2"></i>Transactions</a>
<a href="reports.php"><i class="bi bi-bar-chart me-2"></i>Reports</a>
<a href="logout.php" class="text-danger"><i class="bi bi-box-arrow-right me-2"></i>Logout</a>
```

</div>

<!-- MAIN -->

<div class="main">

```
<!-- TOPBAR -->
<div class="topbar d-flex justify-content-between align-items-center">
    <div>
        <h3 class="mb-0">Transactions</h3>
        <small class="text-secondary">Search, filter, and manage all expenses</small>
    </div>

    <div class="d-flex align-items-center gap-3">
        <span class="fw-semibold"><?php echo htmlspecialchars($fullname); ?></span>
        <img src="https://ui-avatars.com/api/?name=<?php echo urlencode($fullname); ?>&background=2563eb&color=fff"
             width="42" height="42" class="rounded-circle">
    </div>
</div>

<div class="container-fluid p-4">

    <?php if(isset($_GET['msg']) && $_GET['msg']=='deleted'): ?>
        <div class="alert alert-success alert-dismissible fade show">
            <i class="bi bi-check-circle me-2"></i>Transaction deleted successfully.
            <button class="btn-close" data-bs-dismiss="alert"></button>
        </div>
    <?php endif; ?>

    <!-- FILTER CARD -->
    <div class="glass p-4 mb-4">
        <form method="GET" class="row g-3">

            <div class="col-lg-3">
                <label class="form-label">Search</label>
                <input type="text" name="search" value="<?php echo htmlspecialchars($search); ?>"
                       class="form-control" placeholder="Description or category">
            </div>

            <div class="col-lg-2">
                <label class="form-label">Category</label>
                <select name="category" class="form-select">
                    <option value="">All categories</option>
                    <?php while($cat = $cat_result->fetch_assoc()): ?>
                        <option value="<?php echo $cat['category_id']; ?>"
                            <?php if($category == $cat['category_id']) echo 'selected'; ?>>
                            <?php echo htmlspecialchars($cat['category_name']); ?>
                        </option>
                    <?php endwhile; ?>
                </select>
            </div>

            <div class="col-lg-2">
                <label class="form-label">Payment</label>
                <select name="payment" class="form-select">
                    <option value="">All methods</option>
                    <option value="Cash" <?php if($payment=='Cash') echo 'selected'; ?>>Cash</option>
                    <option value="UPI" <?php if($payment=='UPI') echo 'selected'; ?>>UPI</option>
                    <option value="Card" <?php if($payment=='Card') echo 'selected'; ?>>Card</option>
                    <option value="Bank Transfer" <?php if($payment=='Bank Transfer') echo 'selected'; ?>>Bank Transfer</option>
                </select>
            </div>

            <div class="col-lg-2">
                <label class="form-label">From</label>
                <input type="date" name="from" value="<?php echo $from; ?>" class="form-control">
            </div>

            <div class="col-lg-2">
                <label class="form-label">To</label>
                <input type="date" name="to" value="<?php echo $to; ?>" class="form-control">
            </div>

            <div class="col-lg-1 d-grid">
                <label class="form-label d-none d-lg-block">&nbsp;</label>
                <button class="btn btn-primary">
                    <i class="bi bi-funnel"></i>
                </button>
            </div>
        </form>

        <div class="d-flex flex-wrap gap-2 mt-3">
            <a href="transactions.php" class="btn btn-outline-light btn-sm">
                <i class="bi bi-arrow-counterclockwise me-1"></i>Reset
            </a>

            <button class="btn btn-outline-success btn-sm" onclick="exportTableToCSV()">
                <i class="bi bi-download me-1"></i>Export CSV
            </button>

            <button class="btn btn-outline-light btn-sm" onclick="window.print()">
                <i class="bi bi-printer me-1"></i>Print
            </button>

            <div class="ms-auto text-secondary small align-self-center">
                Filtered total:
                <span class="text-white fw-semibold">₹ <?php echo number_format($total_filtered,2); ?></span>
            </div>
        </div>
    </div>

    <!-- TABLE -->
    <div class="glass p-4">
        <div class="d-flex justify-content-between align-items-center mb-3">
            <h5 class="mb-0">Expense Transactions</h5>
            <span class="badge text-bg-primary"><?php echo $transactions->num_rows; ?> records</span>
        </div>

        <div class="table-responsive">
            <table class="table table-darkish table-hover align-middle" id="txTable">
                <thead>
                    <tr>
                        <th>Date</th>
                        <th>Category</th>
                        <th>Description</th>
                        <th>Payment</th>
                        <th class="text-end">Amount</th>
                        <th class="text-center">Actions</th>
                    </tr>
                </thead>

                <tbody>
                <?php if($transactions->num_rows > 0): ?>
                    <?php while($row = $transactions->fetch_assoc()): ?>
                        <tr>
                            <td>
                                <div class="fw-semibold">
                                    <?php echo date('d M Y', strtotime($row['expense_date'])); ?>
                                </div>
                            </td>

                            <td>
                                <span class="badge text-bg-info">
                                    <?php echo htmlspecialchars($row['category_name']); ?>
                                </span>
                            </td>

                            <td>
                                <?php echo htmlspecialchars($row['description'] ?: 'No description'); ?>
                            </td>

                            <td>
                                <span class="badge bg-secondary badge-method">
                                    <?php echo htmlspecialchars($row['payment_method']); ?>
                                </span>
                            </td>

                            <td class="text-end amount">
                                − ₹ <?php echo number_format($row['amount'],2); ?>
                            </td>

                            <td class="text-center">
                                <div class="btn-group btn-group-sm">
                                    <a href="edit_expense.php?id=<?php echo $row['expense_id']; ?>"
                                       class="btn btn-outline-warning" title="Edit">
                                        <i class="bi bi-pencil"></i>
                                    </a>

                                    <a href="transactions.php?delete=<?php echo $row['expense_id']; ?>"
                                       class="btn btn-outline-danger"
                                       onclick="return confirm('Delete this transaction?')"
                                       title="Delete">
                                        <i class="bi bi-trash"></i>
                                    </a>
                                </div>
                            </td>
                        </tr>
                    <?php endwhile; ?>
                <?php else: ?>
                    <tr>
                        <td colspan="6" class="text-center py-5 text-secondary">
                            <i class="bi bi-inbox fs-1 d-block mb-3"></i>
                            No transactions found for the selected filters.
                        </td>
                    </tr>
                <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>

</div>
```

</div>

<script>
function exportTableToCSV() {
    let csv = [];
    const rows = document.querySelectorAll('#txTable tr');

    rows.forEach(row => {
        let cols = row.querySelectorAll('th, td');
        let data = [];

        cols.forEach((col, index) => {
            // Skip actions column
            if(index !== cols.length - 1){
                data.push('"' + col.innerText.replace(/"/g, '""') + '"');
            }
        });

        csv.push(data.join(','));
    });

    const csvFile = new Blob([csv.join('\\n')], { type: 'text/csv' });
    const downloadLink = document.createElement('a');

    downloadLink.download = 'transactions.csv';
    downloadLink.href = window.URL.createObjectURL(csvFile);
    downloadLink.style.display = 'none';

    document.body.appendChild(downloadLink);
    downloadLink.click();
}
</script>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>

</body>
</html>