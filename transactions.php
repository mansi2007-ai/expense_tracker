<?php
session_start();
include 'db.php';

// =========================
// LOGIN CHECK
// =========================

if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit();
}

$user_id = $_SESSION['user_id'];


// =========================
// USER DETAILS
// =========================

$stmt = $conn->prepare("
SELECT fullname, email, profile_image
FROM users
WHERE user_id=?
");

$stmt->bind_param("i", $user_id);
$stmt->execute();

$user = $stmt->get_result()->fetch_assoc();

$stmt->close();

$fullname = $user['fullname'];
$email = $user['email'];
$profile_image = !empty($user['profile_image'])
    ? $user['profile_image']
    : "default.png";


// =========================
// SEARCH & FILTER
// =========================

$search = trim($_GET['search'] ?? '');
$type = $_GET['type'] ?? 'All';


// =========================
// INCOME QUERY
// =========================

$income_sql = "
SELECT
    income_id AS id,
    'Income' AS type,
    source AS category,
    description,
    amount,
    income_date AS trans_date
FROM income
WHERE user_id = $user_id
";

if ($search != '') {

    $safe = $conn->real_escape_string($search);

    $income_sql .= "
    AND (
        source LIKE '%$safe%'
        OR description LIKE '%$safe%'
    )";
}


// =========================
// EXPENSE QUERY
// =========================

$expense_sql = "
SELECT
    expense_id AS id,
    'Expense' AS type,
    category,
    description,
    amount,
    expense_date AS trans_date
FROM expenses
WHERE user_id = $user_id
";

if ($search != '') {

    $safe = $conn->real_escape_string($search);

    $expense_sql .= "
    AND (
        category LIKE '%$safe%'
        OR description LIKE '%$safe%'
    )";
}


// =========================
// COMBINE DATA
// =========================

if ($type == "Income") {

    $sql = "
    $income_sql
    ORDER BY trans_date DESC
    ";

}
elseif ($type == "Expense") {

    $sql = "
    $expense_sql
    ORDER BY trans_date DESC
    ";

}
else {

    $sql = "
    ($income_sql)

    UNION ALL

    ($expense_sql)

    ORDER BY trans_date DESC
    ";

}

$transactions = $conn->query($sql);


// =========================
// SUMMARY
// =========================

$total_income = $conn->query("
SELECT COALESCE(SUM(amount),0) total
FROM income
WHERE user_id=$user_id
")->fetch_assoc()['total'];

$total_expense = $conn->query("
SELECT COALESCE(SUM(amount),0) total
FROM expenses
WHERE user_id=$user_id
")->fetch_assoc()['total'];

$balance = $total_income - $total_expense;


// =========================
// TOTAL TRANSACTIONS
// =========================

$total_transactions = $transactions->num_rows;

?>
<!DOCTYPE html>
<html lang="en">

<head>

<meta charset="UTF-8">

<meta name="viewport" content="width=device-width, initial-scale=1">

<title>Finora • Transactions</title>

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

color:#fff;

overflow-x:hidden;

}

/* ================= Sidebar ================= */

.sidebar{

position:fixed;

left:0;

top:0;

width:270px;

height:100vh;

background:rgba(18,25,51,.88);

backdrop-filter:blur(20px);

border-right:1px solid rgba(255,255,255,.08);

padding:25px;

z-index:1000;

}

.profile-box{

text-align:center;

padding-bottom:25px;

margin-bottom:25px;

border-bottom:1px solid rgba(255,255,255,.08);

}

.profile-box img{

width:90px;

height:90px;

border-radius:50%;

object-fit:cover;

border:4px solid #f59e0b;

}

.profile-box h5{

margin-top:15px;

font-weight:600;

}

.profile-box small{

color:#94a3b8;

}

.menu a{

display:flex;

align-items:center;

gap:12px;

padding:13px 16px;

margin-bottom:10px;

border-radius:14px;

text-decoration:none;

color:#fff;

transition:.3s;

font-weight:500;

}

.menu a:hover{

background:#7c3aed;

transform:translateX(5px);

}

.menu a.active{

background:#f59e0b;

color:#111827;

font-weight:600;

}

/* ================= Main ================= */

.main{

margin-left:270px;

padding:30px;

}

/* ================= Topbar ================= */

.topbar{

display:flex;

justify-content:space-between;

align-items:center;

margin-bottom:35px;

}

.top-right{

display:flex;

align-items:center;

gap:20px;

}

.notification{

font-size:22px;

cursor:pointer;

}

.user-link{

display:flex;

align-items:center;

gap:12px;

text-decoration:none;

color:#fff;

}

.user-link img{

width:45px;

height:45px;

border-radius:50%;

object-fit:cover;

border:2px solid #f59e0b;

}

/* ================= Glass Card ================= */

.glass{

background:rgba(18,25,51,.78);

border:1px solid rgba(255,255,255,.08);

backdrop-filter:blur(18px);

border-radius:20px;

box-shadow:0 20px 40px rgba(0,0,0,.30);

}

/* ================= Table ================= */

.table-dark{

--bs-table-bg:transparent;

--bs-table-border-color:rgba(255,255,255,.08);

}

.table td,
.table th{

vertical-align:middle;

}

/* ================= Mobile ================= */

@media(max-width:992px){

.sidebar{

left:-270px;

transition:.4s;

}

.sidebar.active{

left:0;

}

.main{

margin-left:0;

}

}

</style>

</head>

<body>

<!-- ================= Sidebar ================= -->

<div class="sidebar" id="sidebar">

<div class="profile-box">

<img src="uploads/profile/<?= htmlspecialchars($profile_image) ?>" alt="Profile">

<h5><?= htmlspecialchars($fullname) ?></h5>

<small><?= htmlspecialchars($email) ?></small>

</div>

<div class="menu">

<a href="dashboard.php">
<i class="bi bi-grid-fill"></i>
Dashboard
</a>

<a href="add_income.php">
<i class="bi bi-cash-stack"></i>
Income
</a>

<a href="add_expense.php">
<i class="bi bi-wallet2"></i>
Expenses
</a>

<a href="transactions.php" class="active">
<i class="bi bi-arrow-left-right"></i>
Transactions
</a>

<a href="savings.php">
<i class="bi bi-piggy-bank-fill"></i>
Savings
</a>

<a href="reports.php">
<i class="bi bi-bar-chart-fill"></i>
Reports
</a>

<a href="profile.php">
<i class="bi bi-person-circle"></i>
Profile
</a>

<a href="settings.php">
<i class="bi bi-gear-fill"></i>
Settings
</a>

<a href="logout.php">
<i class="bi bi-box-arrow-right"></i>
Logout
</a>

</div>

</div>

<!-- ================= Main ================= -->

<div class="main">

<!-- ================= Topbar ================= -->

<div class="topbar">

<div class="d-flex align-items-center gap-3">

<button
class="btn btn-warning d-lg-none"
onclick="document.getElementById('sidebar').classList.toggle('active')">

<i class="bi bi-list"></i>

</button>

<div>

<h2 class="fw-bold mb-1">

Transactions

</h2>

<p class="text-secondary mb-0">

View all your income and expense records in one place.

</p>

</div>

</div>

<div class="top-right">

<i class="bi bi-bell-fill notification"></i>

<a href="profile.php" class="user-link">

<img src="uploads/profile/<?= htmlspecialchars($profile_image) ?>" alt="Profile">

<div>

<div class="fw-semibold">

<?= htmlspecialchars($fullname) ?>

</div>

<small class="text-secondary">

View Profile

</small>

</div>

</a>

</div>

</div>
<!-- ===========================
        SUMMARY CARDS
=========================== -->

<style>

.summary-card{

padding:25px;

border-radius:22px;

color:#fff;

transition:.3s;

box-shadow:0 15px 35px rgba(0,0,0,.25);

}

.summary-card:hover{

transform:translateY(-8px);

}

.income-card{

background:linear-gradient(135deg,#16a34a,#22c55e);

}

.expense-card{

background:linear-gradient(135deg,#dc2626,#ef4444);

}

.balance-card{

background:linear-gradient(135deg,#2563eb,#3b82f6);

}

.transaction-card{

background:linear-gradient(135deg,#7c3aed,#9333ea);

}

.summary-card i{

font-size:2.4rem;

}

.summary-card h6{

margin-top:15px;

opacity:.9;

}

.summary-card h2{

font-weight:700;

margin-top:8px;

}

</style>



<div class="row g-4 mb-4">

<div class="col-xl-3 col-md-6">

<div class="summary-card income-card">

<i class="bi bi-cash-stack"></i>

<h6>Total Income</h6>

<h2>

₹<?= number_format($total_income,2) ?>

</h2>

</div>

</div>



<div class="col-xl-3 col-md-6">

<div class="summary-card expense-card">

<i class="bi bi-wallet2"></i>

<h6>Total Expense</h6>

<h2>

₹<?= number_format($total_expense,2) ?>

</h2>

</div>

</div>



<div class="col-xl-3 col-md-6">

<div class="summary-card balance-card">

<i class="bi bi-bank2"></i>

<h6>Net Balance</h6>

<h2>

₹<?= number_format($balance,2) ?>

</h2>

</div>

</div>



<div class="col-xl-3 col-md-6">

<div class="summary-card transaction-card">

<i class="bi bi-arrow-left-right"></i>

<h6>Total Transactions</h6>

<h2>

<?= $total_transactions ?>

</h2>

</div>

</div>

</div>



<!-- ===========================
        SEARCH & FILTER
=========================== -->

<div class="glass p-4 mb-4">

<form method="GET">

<div class="row g-3 align-items-end">

<div class="col-lg-6">

<label class="form-label">

Search

</label>

<input
type="text"
name="search"
class="form-control"
placeholder="Search by source, category or description..."
value="<?= htmlspecialchars($search) ?>">

</div>



<div class="col-lg-3">

<label class="form-label">

Transaction Type

</label>

<select
name="type"
class="form-select">

<option value="All" <?= $type=="All"?"selected":"" ?>>

All Transactions

</option>

<option value="Income" <?= $type=="Income"?"selected":"" ?>>

Income

</option>

<option value="Expense" <?= $type=="Expense"?"selected":"" ?>>

Expense

</option>

</select>

</div>



<div class="col-lg-3 d-grid">

<button
class="btn btn-warning">

<i class="bi bi-search me-2"></i>

Apply Filter

</button>

</div>

</div>

</form>

</div>
<!-- ===========================
        TRANSACTIONS TABLE
=========================== -->

<div class="glass p-4 mb-4">

<div class="d-flex justify-content-between align-items-center mb-4">

<h4 class="fw-bold mb-0">

<i class="bi bi-clock-history text-warning me-2"></i>

Transaction History

</h4>

<span class="badge bg-primary fs-6">

<?= $total_transactions ?> Records

</span>

</div>

<div class="table-responsive">

<table class="table table-dark table-hover align-middle">

<thead>

<tr>

<th>Date</th>

<th>Type</th>

<th>Category / Source</th>

<th>Description</th>

<th class="text-end">Amount</th>

<th class="text-center">Action</th>

</tr>

</thead>

<tbody>

<?php if($transactions->num_rows>0): ?>

<?php while($row=$transactions->fetch_assoc()): ?>

<tr>

<td>

<?= date("d M Y",strtotime($row['trans_date'])) ?>

</td>

<td>

<?php if($row['type']=="Income"): ?>

<span class="badge bg-success">

<i class="bi bi-arrow-down-circle me-1"></i>

Income

</span>

<?php else: ?>

<span class="badge bg-danger">

<i class="bi bi-arrow-up-circle me-1"></i>

Expense

</span>

<?php endif; ?>

</td>

<td>

<span class="badge bg-info text-dark">

<?= htmlspecialchars($row['category']) ?>

</span>

</td>

<td>

<?= htmlspecialchars($row['description'] ?: 'No Description') ?>

</td>

<td class="text-end fw-bold">

<?php if($row['type']=="Income"): ?>

<span class="text-success">

+₹<?= number_format($row['amount'],2) ?>

</span>

<?php else: ?>

<span class="text-danger">

-₹<?= number_format($row['amount'],2) ?>

</span>

<?php endif; ?>

</td>

<td class="text-center">

<?php if($row['type']=="Income"): ?>

<a href="add_income.php?edit=<?= $row['id'] ?>"

class="btn btn-sm btn-warning rounded-pill">

<i class="bi bi-pencil-square"></i>

</a>

<?php else: ?>

<a href="add_expense.php?edit=<?= $row['id'] ?>"

class="btn btn-sm btn-warning rounded-pill">

<i class="bi bi-pencil-square"></i>

</a>

<?php endif; ?>

</td>

</tr>

<?php endwhile; ?>

<?php else: ?>

<tr>

<td colspan="6" class="text-center py-5">

<i class="bi bi-receipt display-5 text-secondary d-block mb-3"></i>

<h5 class="text-secondary">

No Transactions Found

</h5>

<p class="text-secondary mb-0">

Try changing the search or filter.

</p>

</td>

</tr>

<?php endif; ?>

</tbody>

</table>

</div>

</div>



<!-- ===========================
        TRANSACTION SUMMARY
=========================== -->

<div class="glass p-4 mb-4">

<div class="row text-center">

<div class="col-md-4">

<h6 class="text-success">

Income

</h6>

<h3>

₹<?= number_format($total_income,2) ?>

</h3>

</div>

<div class="col-md-4">

<h6 class="text-danger">

Expense

</h6>

<h3>

₹<?= number_format($total_expense,2) ?>

</h3>

</div>

<div class="col-md-4">

<h6 class="text-info">

Balance

</h6>

<h3>

₹<?= number_format($balance,2) ?>

</h3>

</div>

</div>

</div>
<!-- ===========================
        QUICK ACTIONS
=========================== -->

<div class="glass p-4 mb-4">

    <h4 class="fw-bold mb-4">

        <i class="bi bi-lightning-charge-fill text-warning me-2"></i>

        Quick Actions

    </h4>

    <div class="row g-3">

        <div class="col-lg-3 col-md-6">

            <a href="add_income.php" class="btn btn-success w-100 py-3">

                <i class="bi bi-plus-circle me-2"></i>

                Add Income

            </a>

        </div>

        <div class="col-lg-3 col-md-6">

            <a href="add_expense.php" class="btn btn-danger w-100 py-3">

                <i class="bi bi-dash-circle me-2"></i>

                Add Expense

            </a>

        </div>

        <div class="col-lg-3 col-md-6">

            <a href="savings.php" class="btn btn-primary w-100 py-3">

                <i class="bi bi-piggy-bank me-2"></i>

                Savings

            </a>

        </div>

        <div class="col-lg-3 col-md-6">

            <a href="reports.php" class="btn btn-warning w-100 py-3">

                <i class="bi bi-bar-chart-fill me-2"></i>

                Reports

            </a>

        </div>

    </div>

</div>



<?php

// ============================
// TRANSACTION STATISTICS
// ============================

$income_count = $conn->query("
SELECT COUNT(*)
AS total
FROM income
WHERE user_id=$user_id
")->fetch_assoc()['total'];

$expense_count = $conn->query("
SELECT COUNT(*)
AS total
FROM expenses
WHERE user_id=$user_id
")->fetch_assoc()['total'];

$avg_income = $income_count > 0 ? $total_income / $income_count : 0;

$avg_expense = $expense_count > 0 ? $total_expense / $expense_count : 0;

?>



<!-- ===========================
        STATISTICS
=========================== -->

<div class="row g-4 mb-4">

<div class="col-md-6">

<div class="glass p-4 h-100">

<h5 class="fw-bold mb-4">

<i class="bi bi-graph-up-arrow text-success me-2"></i>

Income Statistics

</h5>

<p>

<strong>Total Income Entries:</strong>

<?= $income_count ?>

</p>

<p>

<strong>Average Income:</strong>

₹<?= number_format($avg_income,2) ?>

</p>

<p class="mb-0">

<strong>Total Income:</strong>

₹<?= number_format($total_income,2) ?>

</p>

</div>

</div>



<div class="col-md-6">

<div class="glass p-4 h-100">

<h5 class="fw-bold mb-4">

<i class="bi bi-graph-down-arrow text-danger me-2"></i>

Expense Statistics

</h5>

<p>

<strong>Total Expense Entries:</strong>

<?= $expense_count ?>

</p>

<p>

<strong>Average Expense:</strong>

₹<?= number_format($avg_expense,2) ?>

</p>

<p class="mb-0">

<strong>Total Expense:</strong>

₹<?= number_format($total_expense,2) ?>

</p>

</div>

</div>

</div>



<!-- ===========================
        SMART INSIGHTS
=========================== -->

<div class="glass p-4 mb-4">

<h4 class="fw-bold mb-4">

<i class="bi bi-lightbulb-fill text-warning me-2"></i>

Smart Financial Tips

</h4>

<div class="row g-3">

<div class="col-md-4">

<div class="border rounded p-3 h-100">

<h6 class="text-success">

Excellent

</h6>

<p class="mb-0">

<?php

if($balance > 0){

echo "Great! Your income is higher than your expenses.";

}else{

echo "Your expenses are higher than your income. Consider reducing unnecessary spending.";

}

?>

</p>

</div>

</div>



<div class="col-md-4">

<div class="border rounded p-3 h-100">

<h6 class="text-info">

Saving Advice

</h6>

<p class="mb-0">

<?php

if($total_income > 0){

$rate = ($balance/$total_income)*100;

echo "Current savings potential: ".number_format($rate,1)."%";

}else{

echo "Add income records to track savings potential.";

}

?>

</p>

</div>

</div>



<div class="col-md-4">

<div class="border rounded p-3 h-100">

<h6 class="text-warning">

Monthly Reminder

</h6>

<p class="mb-0">

Review your transactions regularly to keep your budget under control.

</p>

</div>

</div>

</div>

</div>
<!-- ===========================
        FOOTER
=========================== -->

<footer class="text-center text-secondary py-4 mt-5">

    <hr class="border-secondary">

    <p class="mb-0">
        © <?= date('Y') ?> <strong>Finora</strong> | Personal Expense Manager
    </p>

</footer>

</div>
<!-- End Main -->



<!-- Bootstrap -->
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>


<!-- ===========================
        MOBILE SIDEBAR
=========================== -->

<script>

function toggleSidebar(){

    document.getElementById("sidebar").classList.toggle("active");

}

</script>



<!-- ===========================
        ACTIVE MENU
=========================== -->

<script>

const currentPage = window.location.pathname.split("/").pop();

document.querySelectorAll(".menu a").forEach(function(link){

    if(link.getAttribute("href") === currentPage){

        link.classList.add("active");

    }

});

</script>



<!-- ===========================
        CARD HOVER EFFECT
=========================== -->

<script>

document.querySelectorAll(".summary-card").forEach(function(card){

    card.addEventListener("mouseenter",function(){

        card.style.transform="translateY(-8px)";
        card.style.transition=".3s";

    });

    card.addEventListener("mouseleave",function(){

        card.style.transform="translateY(0px)";

    });

});

</script>



<!-- ===========================
        AUTO HIDE ALERTS
=========================== -->

<script>

setTimeout(function(){

    document.querySelectorAll(".alert").forEach(function(alert){

        alert.style.transition=".5s";
        alert.style.opacity="0";

        setTimeout(function(){

            alert.remove();

        },500);

    });

},3000);

</script>



<!-- ===========================
        SMOOTH PAGE LOAD
=========================== -->

<script>

document.body.style.opacity = "0";

window.addEventListener("load",function(){

    document.body.style.transition = ".4s";
    document.body.style.opacity = "1";

});

</script>



<!-- ===========================
        TABLE ROW HOVER
=========================== -->

<script>

document.querySelectorAll("tbody tr").forEach(function(row){

    row.addEventListener("mouseenter",function(){

        this.style.transition=".2s";
        this.style.background="rgba(255,255,255,.05)";

    });

    row.addEventListener("mouseleave",function(){

        this.style.background="transparent";

    });

});

</script>

</body>
</html>