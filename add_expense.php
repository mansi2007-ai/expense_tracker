<?php
session_start();
include 'db.php';

// ======================
// LOGIN CHECK
// ======================
if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit();
}

$user_id = $_SESSION['user_id'];
$fullname = $_SESSION['fullname'] ?? 'User';

$edit_mode = false;
$edit_data = [];
$error = "";


// ======================
// DELETE EXPENSE
// ======================
if (isset($_GET['delete'])) {

    $expense_id = (int)$_GET['delete'];

    $stmt = $conn->prepare("DELETE FROM expenses WHERE expense_id=? AND user_id=?");
    $stmt->bind_param("ii", $expense_id, $user_id);

    if ($stmt->execute()) {
        header("Location: add_expense.php?deleted=1");
        exit();
    }
}


// ======================
// EDIT EXPENSE
// ======================
if (isset($_GET['edit'])) {

    $expense_id = (int)$_GET['edit'];

    $stmt = $conn->prepare("SELECT * FROM expenses WHERE expense_id=? AND user_id=?");
    $stmt->bind_param("ii", $expense_id, $user_id);
    $stmt->execute();

    $result = $stmt->get_result();

    if ($result->num_rows > 0) {

        $edit_data = $result->fetch_assoc();
        $edit_mode = true;
    }
}



// ======================
// ADD / UPDATE EXPENSE
// ======================
if ($_SERVER["REQUEST_METHOD"] == "POST") {

    $expense_id = $_POST['expense_id'] ?? "";

    $amount = floatval($_POST['amount']);
    $category = trim($_POST['category']);
    $description = trim($_POST['description']);
    $expense_date = $_POST['expense_date'];

    if ($amount <= 0) {

        $error = "Amount must be greater than 0.";

    } else {

        // ======================
        // UPDATE
        // ======================
        if ($expense_id != "") {

            $expense_id = (int)$expense_id;

            $stmt = $conn->prepare("
                UPDATE expenses
                SET amount=?,
                    category=?,
                    description=?,
                    expense_date=?
                WHERE expense_id=? AND user_id=?
            ");

            $stmt->bind_param(
                "dsssii",
                $amount,
                $category,
                $description,
                $expense_date,
                $expense_id,
                $user_id
            );

            if ($stmt->execute()) {

                header("Location: add_expense.php?updated=1");
                exit();
            }

        }

        // ======================
        // INSERT
        // ======================
        else {

            $stmt = $conn->prepare("
                INSERT INTO expenses
                (user_id, amount, category, description, expense_date)
                VALUES (?, ?, ?, ?, ?)
            ");

            $stmt->bind_param(
                "idsss",
                $user_id,
                $amount,
                $category,
                $description,
                $expense_date
            );

            if ($stmt->execute()) {

                header("Location: add_expense.php?added=1");
                exit();
            }

        }
    }
}



// ======================
// FILTER
// ======================

$filter = $_GET['filter'] ?? '';

$sql = "SELECT * FROM expenses WHERE user_id=?";

if ($filter != "") {
    $sql .= " AND category=?";
}

$sql .= " ORDER BY expense_date DESC, expense_id DESC";

$stmt = $conn->prepare($sql);

if ($filter != "") {

    $stmt->bind_param("is", $user_id, $filter);

} else {

    $stmt->bind_param("i", $user_id);

}

$stmt->execute();

$expense_result = $stmt->get_result();



// ======================
// SUMMARY
// ======================

// Total Expense
$stmt = $conn->prepare("
SELECT COALESCE(SUM(amount),0)
FROM expenses
WHERE user_id=?
");

$stmt->bind_param("i",$user_id);
$stmt->execute();
$stmt->bind_result($total_expense);
$stmt->fetch();
$stmt->close();


// This Month Expense
$stmt = $conn->prepare("
SELECT COALESCE(SUM(amount),0)
FROM expenses
WHERE user_id=?
AND MONTH(expense_date)=MONTH(CURDATE())
AND YEAR(expense_date)=YEAR(CURDATE())
");

$stmt->bind_param("i",$user_id);
$stmt->execute();
$stmt->bind_result($this_month);
$stmt->fetch();
$stmt->close();


// Total Entries
$stmt = $conn->prepare("
SELECT COUNT(*)
FROM expenses
WHERE user_id=?
");

$stmt->bind_param("i",$user_id);
$stmt->execute();
$stmt->bind_result($entries);
$stmt->fetch();
$stmt->close();

?>
<!DOCTYPE html>
<html lang="en">

<head>

<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1">

<title>Finora • Expense Manager</title>

<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">

<link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.css" rel="stylesheet">

<link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700&display=swap" rel="stylesheet">

<style>

*{
    margin:0;
    padding:0;
    box-sizing:border-box;
}

body{

    font-family:'Poppins',sans-serif;

    background:
        radial-gradient(circle at top left,#312e81 0%,transparent 30%),
        radial-gradient(circle at bottom right,#7c3aed 0%,transparent 35%),
        #0b1020;

    color:#ffffff;

    min-height:100vh;
}


/* Scrollbar */

::-webkit-scrollbar{
    width:8px;
}

::-webkit-scrollbar-thumb{
    background:#6d28d9;
    border-radius:10px;
}


/* Glass Card */

.card-glass{

    background:rgba(18,25,51,.75);

    border:1px solid rgba(255,255,255,.08);

    backdrop-filter:blur(18px);

    border-radius:24px;

    box-shadow:0 20px 50px rgba(0,0,0,.35);

}


/* Summary Cards */

.summary{

    padding:24px;

    border-radius:24px;

    color:#fff;

    overflow:hidden;

    position:relative;

    transition:.35s;

    box-shadow:0 18px 40px rgba(0,0,0,.35);

}

.summary:hover{

    transform:translateY(-6px);

}

.s1{

    background:linear-gradient(135deg,#ef4444,#f97316);

}

.s2{

    background:linear-gradient(135deg,#7c3aed,#8b5cf6);

}

.s3{

    background:linear-gradient(135deg,#2563eb,#38bdf8);

}


/* Form */

.form-control,
.form-select{

    background:#111827;

    border:1px solid #374151;

    color:#fff;

    border-radius:14px;

    padding:12px;

}

.form-control:focus,
.form-select:focus{

    background:#111827;

    color:#fff;

    border-color:#f59e0b;

    box-shadow:none;

}


/* Table */

.table{

    color:white;

}

.table-dark{

    --bs-table-bg:transparent;

    --bs-table-border-color:rgba(255,255,255,.08);

}

.table thead th{

    border-bottom:1px solid rgba(255,255,255,.15);

    font-weight:600;

}

.table tbody tr{

    transition:.25s;

}

.table tbody tr:hover{

    background:rgba(255,255,255,.05);

}


/* Buttons */

.btn-warning{

    background:#f59e0b;

    border:none;

    color:#111827;

    font-weight:600;

}

.btn-warning:hover{

    background:#fbbf24;

    color:#111827;

}

.btn-danger{

    border-radius:50px;

}

.btn-outline-light{

    border-radius:50px;

}


/* Badges */

.badge{

    font-size:.85rem;

    padding:.55rem .8rem;

    border-radius:30px;

}


/* Heading */

.page-title{

    font-size:2rem;

    font-weight:700;

}

.subtitle{

    color:#94a3b8;

}


/* Alerts */

.alert{

    border:none;

    border-radius:15px;

}


/* Responsive */

@media(max-width:768px){

.page-title{

    font-size:1.5rem;

}

.summary{

    margin-bottom:15px;

}

}

</style>

</head>

<body>

<div class="container py-4">
<!-- =========================
     PAGE HEADER
========================= -->

<div class="d-flex justify-content-between align-items-center flex-wrap mb-4">

    <div>
        <h2 class="page-title">
            <i class="bi bi-receipt-cutoff text-warning me-2"></i>
            Expense Manager
        </h2>

        <p class="subtitle mb-0">
            Welcome,
            <strong><?= htmlspecialchars($fullname) ?></strong>
        </p>
    </div>

    <div class="mt-3 mt-md-0">

        <a href="dashboard.php" class="btn btn-outline-light px-4">

            <i class="bi bi-arrow-left-circle me-2"></i>

            Back to Dashboard

        </a>

    </div>

</div>



<!-- =========================
     ALERT MESSAGES
========================= -->

<?php if(isset($_GET['added'])): ?>

<div class="alert alert-success shadow-sm">

    <i class="bi bi-check-circle-fill me-2"></i>

    Expense added successfully.

</div>

<?php endif; ?>


<?php if(isset($_GET['updated'])): ?>

<div class="alert alert-info shadow-sm">

    <i class="bi bi-pencil-square me-2"></i>

    Expense updated successfully.

</div>

<?php endif; ?>


<?php if(isset($_GET['deleted'])): ?>

<div class="alert alert-warning shadow-sm">

    <i class="bi bi-trash-fill me-2"></i>

    Expense deleted successfully.

</div>

<?php endif; ?>


<?php if(!empty($error)): ?>

<div class="alert alert-danger shadow-sm">

    <i class="bi bi-exclamation-triangle-fill me-2"></i>

    <?= htmlspecialchars($error) ?>

</div>

<?php endif; ?>



<!-- =========================
     SUMMARY CARDS
========================= -->

<div class="row g-4 mb-4">

    <!-- Total Expense -->

    <div class="col-lg-4">

        <div class="summary s1">

            <div class="d-flex justify-content-between align-items-center">

                <div>

                    <small class="text-uppercase opacity-75">

                        Total Expenses

                    </small>

                    <h2 class="fw-bold mt-2 mb-0">

                        ₹<?= number_format($total_expense,2) ?>

                    </h2>

                </div>

                <i class="bi bi-wallet2 display-4 opacity-50"></i>

            </div>

        </div>

    </div>



    <!-- Monthly Expense -->

    <div class="col-lg-4">

        <div class="summary s2">

            <div class="d-flex justify-content-between align-items-center">

                <div>

                    <small class="text-uppercase opacity-75">

                        This Month

                    </small>

                    <h2 class="fw-bold mt-2 mb-0">

                        ₹<?= number_format($this_month,2) ?>

                    </h2>

                </div>

                <i class="bi bi-calendar-check display-4 opacity-50"></i>

            </div>

        </div>

    </div>



    <!-- Total Entries -->

    <div class="col-lg-4">

        <div class="summary s3">

            <div class="d-flex justify-content-between align-items-center">

                <div>

                    <small class="text-uppercase opacity-75">

                        Total Entries

                    </small>

                    <h2 class="fw-bold mt-2 mb-0">

                        <?= $entries ?>

                    </h2>

                </div>

                <i class="bi bi-list-check display-4 opacity-50"></i>

            </div>

        </div>

    </div>

</div>
<!-- =======================================
        ADD / EDIT EXPENSE FORM
======================================= -->

<div class="card-glass p-4 mb-4">

    <div class="d-flex justify-content-between align-items-center mb-4">

        <h4 class="fw-bold mb-0">

            <i class="bi bi-cash-stack text-warning me-2"></i>

            <?= $edit_mode ? "Edit Expense" : "Add New Expense"; ?>

        </h4>

    </div>

    <form method="POST">

        <input
            type="hidden"
            name="expense_id"
            value="<?= $edit_data['expense_id'] ?? '' ?>"
        >

        <div class="row g-4">

            <!-- Amount -->

            <div class="col-md-6">

                <label class="form-label">

                    Amount (₹)

                </label>

                <input
                    type="number"
                    step="0.01"
                    min="1"
                    name="amount"
                    class="form-control"
                    placeholder="Enter amount"
                    value="<?= $edit_data['amount'] ?? '' ?>"
                    required
                >

            </div>



            <!-- Expense Date -->

            <div class="col-md-6">

                <label class="form-label">

                    Expense Date

                </label>

                <input
                    type="date"
                    name="expense_date"
                    class="form-control"
                    value="<?= $edit_data['expense_date'] ?? date('Y-m-d'); ?>"
                    required
                >

            </div>



            <!-- Category -->

            <div class="col-md-6">

                <label class="form-label">

                    Category

                </label>

                <?php
                $cat = $edit_data['category'] ?? '';
                ?>

                <select
                    name="category"
                    class="form-select"
                    required
                >

                    <option value="">Choose Category</option>

                    <option value="Food" <?= $cat=="Food"?"selected":""; ?>>
                        🍔 Food
                    </option>

                    <option value="Transport" <?= $cat=="Transport"?"selected":""; ?>>
                        🚗 Transport
                    </option>

                    <option value="Shopping" <?= $cat=="Shopping"?"selected":""; ?>>
                        🛍 Shopping
                    </option>

                    <option value="Bills" <?= $cat=="Bills"?"selected":""; ?>>
                        💡 Bills
                    </option>

                    <option value="Entertainment" <?= $cat=="Entertainment"?"selected":""; ?>>
                        🎬 Entertainment
                    </option>

                    <option value="Education" <?= $cat=="Education"?"selected":""; ?>>
                        📚 Education
                    </option>

                    <option value="Health" <?= $cat=="Health"?"selected":""; ?>>
                        ❤️ Health
                    </option>

                    <option value="Other" <?= $cat=="Other"?"selected":""; ?>>
                        📦 Other
                    </option>

                </select>

            </div>



            <!-- Description -->

            <div class="col-md-6">

                <label class="form-label">

                    Description / Note

                </label>

                <input
                    type="text"
                    name="description"
                    class="form-control"
                    placeholder="Optional note..."
                    value="<?= htmlspecialchars($edit_data['description'] ?? '') ?>"
                >

            </div>

        </div>



        <!-- Buttons -->

        <div class="mt-4 d-flex flex-wrap gap-3">

            <button
                type="submit"
                class="btn btn-warning px-5 py-2 rounded-pill"
            >

                <i class="bi bi-check-circle me-2"></i>

                <?= $edit_mode ? "Update Expense" : "Save Expense"; ?>

            </button>



            <?php if($edit_mode): ?>

                <a
                    href="add_expense.php"
                    class="btn btn-outline-light px-5 py-2 rounded-pill"
                >

                    <i class="bi bi-x-circle me-2"></i>

                    Cancel

                </a>

            <?php endif; ?>

        </div>

    </form>

</div>
<!-- =======================================
        EXPENSE HISTORY
======================================= -->

<div class="card-glass p-4">

    <div class="d-flex justify-content-between align-items-center flex-wrap mb-4">

        <h4 class="fw-bold mb-3 mb-md-0">

            <i class="bi bi-clock-history text-warning me-2"></i>

            Recent Expense Records

        </h4>

        <form method="GET" class="d-flex gap-2">

            <select name="filter" class="form-select">

                <option value="">All Categories</option>

                <option value="Food" <?= $filter=="Food"?"selected":""; ?>>Food</option>

                <option value="Transport" <?= $filter=="Transport"?"selected":""; ?>>Transport</option>

                <option value="Shopping" <?= $filter=="Shopping"?"selected":""; ?>>Shopping</option>

                <option value="Bills" <?= $filter=="Bills"?"selected":""; ?>>Bills</option>

                <option value="Entertainment" <?= $filter=="Entertainment"?"selected":""; ?>>Entertainment</option>

                <option value="Education" <?= $filter=="Education"?"selected":""; ?>>Education</option>

                <option value="Health" <?= $filter=="Health"?"selected":""; ?>>Health</option>

                <option value="Other" <?= $filter=="Other"?"selected":""; ?>>Other</option>

            </select>

            <button class="btn btn-primary">

                <i class="bi bi-funnel me-1"></i>

                Apply

            </button>

        </form>

    </div>



    <div class="table-responsive">

        <table class="table table-dark table-hover align-middle">

            <thead>

                <tr>

                    <th>Date</th>

                    <th>Category</th>

                    <th>Description</th>

                    <th class="text-end">Amount</th>

                    <th class="text-center">Actions</th>

                </tr>

            </thead>

            <tbody>

            <?php if($expense_result->num_rows > 0): ?>

                <?php while($row = $expense_result->fetch_assoc()): ?>

                <tr>

                    <td>

                        <?= date("d M Y", strtotime($row['expense_date'])) ?>

                    </td>

                    <td>

                        <?php

                        $badge="bg-secondary";

                        switch($row['category']){

                            case "Food":
                                $badge="bg-success";
                                break;

                            case "Transport":
                                $badge="bg-primary";
                                break;

                            case "Shopping":
                                $badge="bg-warning text-dark";
                                break;

                            case "Bills":
                                $badge="bg-danger";
                                break;

                            case "Entertainment":
                                $badge="bg-info text-dark";
                                break;

                            case "Education":
                                $badge="bg-dark";
                                break;

                            case "Health":
                                $badge="bg-success";
                                break;

                            default:
                                $badge="bg-secondary";

                        }

                        ?>

                        <span class="badge <?= $badge ?>">

                            <?= htmlspecialchars($row['category']) ?>

                        </span>

                    </td>

                    <td>

                        <?= !empty($row['description'])

                            ? htmlspecialchars($row['description'])

                            : '<span class="text-secondary">No description</span>'; ?>

                    </td>

                    <td class="text-end fw-bold text-danger">

                        ₹<?= number_format($row['amount'],2) ?>

                    </td>

                    <td class="text-center">

                        <a
                            href="add_expense.php?edit=<?= $row['expense_id'] ?>"
                            class="btn btn-sm btn-warning rounded-pill text-dark me-2"
                        >

                            <i class="bi bi-pencil-square"></i>

                            Edit

                        </a>

                        <a
                            href="add_expense.php?delete=<?= $row['expense_id'] ?>"
                            class="btn btn-sm btn-danger rounded-pill"
                            onclick="return confirm('Are you sure you want to delete this expense?');"
                        >

                            <i class="bi bi-trash"></i>

                            Delete

                        </a>

                    </td>

                </tr>

                <?php endwhile; ?>

            <?php else: ?>

                <tr>

                    <td colspan="5" class="text-center py-5 text-secondary">

                        <i class="bi bi-wallet2 display-5 d-block mb-3"></i>

                        No expense records found.

                    </td>

                </tr>

            <?php endif; ?>

            </tbody>

        </table>

    </div>

</div>
</div>

<!-- =========================
        FOOTER
========================= -->

<footer class="text-center text-secondary py-4">

    <small>

        © <?= date('Y'); ?> <strong>Finora</strong> |
        Smart Expense Tracker

    </small>

</footer>


<!-- =========================
        BOOTSTRAP JS
========================= -->

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>


<!-- =========================
        AUTO HIDE ALERTS
========================= -->

<script>

setTimeout(function(){

    let alerts = document.querySelectorAll(".alert");

    alerts.forEach(function(alert){

        alert.style.transition = "0.5s";
        alert.style.opacity = "0";

        setTimeout(function(){

            alert.remove();

        },500);

    });

},3000);

</script>


<!-- =========================
        CONFIRM DELETE
========================= -->

<script>

document.querySelectorAll(".btn-danger").forEach(function(button){

    button.addEventListener("click",function(e){

        if(!confirm("Are you sure you want to delete this expense?")){

            e.preventDefault();

        }

    });

});

</script>

</body>

</html>