<?php
session_start();
include 'db.php';

// ======================================
// LOGIN CHECK
// ======================================

if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit();
}

$user_id = $_SESSION['user_id'];


// ======================================
// USER DETAILS
// ======================================

$stmt = $conn->prepare("
SELECT fullname,email,profile_image
FROM users
WHERE user_id=?
");

$stmt->bind_param("i",$user_id);
$stmt->execute();

$user = $stmt->get_result()->fetch_assoc();

$stmt->close();

$fullname = $user['fullname'];
$email = $user['email'];
$profile_image = !empty($user['profile_image'])
    ? $user['profile_image']
    : "default.png";


// ======================================
// TOTAL INCOME
// ======================================

$stmt = $conn->prepare("
SELECT COALESCE(SUM(amount),0)
FROM income
WHERE user_id=?
");

$stmt->bind_param("i",$user_id);
$stmt->execute();
$stmt->bind_result($total_income);
$stmt->fetch();
$stmt->close();


// ======================================
// TOTAL EXPENSE
// ======================================

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


// ======================================
// TOTAL SAVINGS
// ======================================

$stmt = $conn->prepare("
SELECT COALESCE(SUM(saved_amount),0)
FROM savings
WHERE user_id=?
");

$stmt->bind_param("i",$user_id);
$stmt->execute();
$stmt->bind_result($total_savings);
$stmt->fetch();
$stmt->close();


// ======================================
// NET BALANCE
// ======================================

$balance = $total_income - $total_expense;


// ======================================
// MONTHLY INCOME
// ======================================

$stmt = $conn->prepare("
SELECT COALESCE(SUM(amount),0)
FROM income
WHERE user_id=?
AND MONTH(income_date)=MONTH(CURDATE())
AND YEAR(income_date)=YEAR(CURDATE())
");

$stmt->bind_param("i",$user_id);
$stmt->execute();
$stmt->bind_result($monthly_income);
$stmt->fetch();
$stmt->close();


// ======================================
// MONTHLY EXPENSE
// ======================================

$stmt = $conn->prepare("
SELECT COALESCE(SUM(amount),0)
FROM expenses
WHERE user_id=?
AND MONTH(expense_date)=MONTH(CURDATE())
AND YEAR(expense_date)=YEAR(CURDATE())
");

$stmt->bind_param("i",$user_id);
$stmt->execute();
$stmt->bind_result($monthly_expense);
$stmt->fetch();
$stmt->close();


// ======================================
// RECENT INCOME
// ======================================

$income_sql = "
SELECT
'Income' AS type,
amount,
source AS category,
description,
income_date AS trans_date
FROM income
WHERE user_id=$user_id
";

// ======================================
// RECENT EXPENSE
// ======================================

$expense_sql = "
SELECT
'Expense' AS type,
amount,
category,
description,
expense_date AS trans_date
FROM expenses
WHERE user_id=$user_id
";


// ======================================
// COMBINED TRANSACTIONS
// ======================================

$recent_transactions = $conn->query("
($income_sql)

UNION ALL

($expense_sql)

ORDER BY trans_date DESC

LIMIT 8
");


// ======================================
// EXPENSE CATEGORY DATA
// ======================================

$category_result = $conn->query("
SELECT
category,
SUM(amount) total
FROM expenses
WHERE user_id=$user_id
GROUP BY category
");

$category_labels = [];
$category_amounts = [];

while($row = $category_result->fetch_assoc()){

    $category_labels[] = $row['category'];
    $category_amounts[] = $row['total'];

}


// ======================================
// CHART DATA
// ======================================

$chart_income = $monthly_income;
$chart_expense = $monthly_expense;
$chart_balance = $balance;

?>
<!DOCTYPE html>
<html lang="en">

<head>

<meta charset="UTF-8">

<meta name="viewport" content="width=device-width, initial-scale=1">

<title>Finora • Dashboard</title>

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

top:0;

left:0;

width:270px;

height:100vh;

background:rgba(18,25,51,.85);

backdrop-filter:blur(20px);

border-right:1px solid rgba(255,255,255,.08);

padding:25px;

z-index:1000;

}

.profile-box{

text-align:center;

padding-bottom:25px;

border-bottom:1px solid rgba(255,255,255,.08);

margin-bottom:25px;

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

margin-bottom:4px;

font-weight:600;

}

.profile-box small{

color:#94a3b8;

}

.menu a{

display:flex;

align-items:center;

gap:12px;

text-decoration:none;

color:#fff;

padding:13px 16px;

border-radius:14px;

margin-bottom:10px;

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

/* ================= Navbar ================= */

.topbar{
    display:flex;
    justify-content:space-between;
    align-items:center;
    margin-bottom:30px;
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

text-decoration:none;

color:#fff;

gap:12px;

}

.user-link img{

width:45px;

height:45px;

border-radius:50%;

object-fit:cover;

border:2px solid #f59e0b;

}

.glass{

background:rgba(18,25,51,.75);

backdrop-filter:blur(16px);

border:1px solid rgba(255,255,255,.08);

border-radius:20px;

box-shadow:0 20px 40px rgba(0,0,0,.30);

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

<img
src="uploads/profile/<?= htmlspecialchars($profile_image) ?>"
alt="Profile">

<h5><?= htmlspecialchars($fullname) ?></h5>

<small><?= htmlspecialchars($email) ?></small>

</div>

<div class="menu">

<a href="dashboard.php" class="active">
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

<a href="transactions.php">
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

<<div class="topbar">

    <!-- Left Side -->
    <div class="d-flex align-items-center gap-3">

        <button
            class="btn btn-warning d-lg-none"
            onclick="document.getElementById('sidebar').classList.toggle('active')">

            <i class="bi bi-list"></i>

        </button>

        <div>

            <h2 class="fw-bold mb-1">Dashboard</h2>

            <p class="text-secondary mb-0">
                Welcome back,
                <strong><?= htmlspecialchars($fullname) ?></strong>
            </p>

        </div>

    </div>

    <!-- Right Side -->
    <div class="d-flex align-items-center gap-4">

        <!-- Notification -->
        <a href="notification.php"
           class="text-white text-decoration-none position-relative">

            <i class="bi bi-bell-fill fs-4"></i>

        </a>

        <!-- Profile -->
        <a href="profile.php"
           class="d-flex align-items-center gap-3 text-decoration-none text-white">

            <img
                src="uploads/profile/<?= htmlspecialchars($profile_image) ?>"
                alt="Profile"
                style="width:48px;height:48px;border-radius:50%;object-fit:cover;border:2px solid #f59e0b;">

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

overflow:hidden;

position:relative;

}

.summary-card:hover{

transform:translateY(-8px);

box-shadow:0 20px 45px rgba(0,0,0,.35);

}

.summary-card i{

font-size:2.4rem;

opacity:.9;

}

.summary-card h6{

margin-top:15px;

font-weight:500;

opacity:.9;

}

.summary-card h2{

font-weight:700;

margin-top:8px;

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

.savings-card{

background:linear-gradient(135deg,#7c3aed,#9333ea);

}

</style>



<div class="row g-4 mb-4">

    <!-- Total Income -->

    <div class="col-xl-3 col-md-6">

        <div class="summary-card income-card">

            <i class="bi bi-cash-stack"></i>

            <h6>Total Income</h6>

            <h2>

                ₹<?= number_format($total_income,2) ?>

            </h2>

        </div>

    </div>



    <!-- Total Expense -->

    <div class="col-xl-3 col-md-6">

        <div class="summary-card expense-card">

            <i class="bi bi-wallet2"></i>

            <h6>Total Expenses</h6>

            <h2>

                ₹<?= number_format($total_expense,2) ?>

            </h2>

        </div>

    </div>



    <!-- Balance -->

    <div class="col-xl-3 col-md-6">

        <div class="summary-card balance-card">

            <i class="bi bi-bank2"></i>

            <h6>Net Balance</h6>

            <h2>

                ₹<?= number_format($balance,2) ?>

            </h2>

        </div>

    </div>



    <!-- Savings -->

    <div class="col-xl-3 col-md-6">

        <div class="summary-card savings-card">

            <i class="bi bi-piggy-bank-fill"></i>

            <h6>Total Savings</h6>

            <h2>

                ₹<?= number_format($total_savings,2) ?>

            </h2>

        </div>

    </div>

</div>



<!-- ===========================
        MONTHLY OVERVIEW
=========================== -->

<div class="glass p-4 mb-4">

    <div class="d-flex justify-content-between align-items-center mb-3">

        <h4 class="fw-bold mb-0">

            <i class="bi bi-calendar3 text-warning me-2"></i>

            Monthly Overview

        </h4>

    </div>

    <div class="row text-center">

        <div class="col-md-6">

            <h6 class="text-success">

                This Month Income

            </h6>

            <h3>

                ₹<?= number_format($monthly_income,2) ?>

            </h3>

        </div>

        <div class="col-md-6">

            <h6 class="text-danger">

                This Month Expense

            </h6>

            <h3>

                ₹<?= number_format($monthly_expense,2) ?>

            </h3>

        </div>

    </div>

</div>
<!-- ===========================
        CHART.JS
=========================== -->

<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>


<!-- ===========================
        ANALYTICS
=========================== -->

<div class="row g-4 mb-4">

    <!-- Income vs Expense -->

    <div class="col-lg-7">

        <div class="glass p-4 h-100">

            <h4 class="fw-bold mb-4">

                <i class="bi bi-bar-chart-fill text-warning me-2"></i>

                Income vs Expense

            </h4>

            <canvas id="incomeExpenseChart" height="120"></canvas>

        </div>

    </div>



    <!-- Expense Categories -->

    <div class="col-lg-5">

        <div class="glass p-4 h-100">

            <h4 class="fw-bold mb-4">

                <i class="bi bi-pie-chart-fill text-warning me-2"></i>

                Expense Categories

            </h4>

            <canvas id="expenseCategoryChart" height="220"></canvas>

        </div>

    </div>

</div>



<!-- ===========================
        FINANCIAL INSIGHTS
=========================== -->

<div class="glass p-4 mb-4">

    <h4 class="fw-bold mb-4">

        <i class="bi bi-lightbulb-fill text-warning me-2"></i>

        Financial Insights

    </h4>

    <div class="row">

        <div class="col-md-4 mb-3">

            <div class="p-3 rounded bg-success bg-opacity-25">

                <h6 class="text-success">

                    Income

                </h6>

                <h3>

                    ₹<?= number_format($total_income,2) ?>

                </h3>

            </div>

        </div>



        <div class="col-md-4 mb-3">

            <div class="p-3 rounded bg-danger bg-opacity-25">

                <h6 class="text-danger">

                    Expenses

                </h6>

                <h3>

                    ₹<?= number_format($total_expense,2) ?>

                </h3>

            </div>

        </div>



        <div class="col-md-4 mb-3">

            <div class="p-3 rounded bg-primary bg-opacity-25">

                <h6 class="text-info">

                    Savings Rate

                </h6>

                <h3>

                    <?php

                    if($total_income>0){

                        echo number_format(($total_savings/$total_income)*100,1);

                    }else{

                        echo "0";

                    }

                    ?>%

                </h3>

            </div>

        </div>

    </div>

</div>
<!-- ===========================
        RECENT TRANSACTIONS
=========================== -->

<div class="glass p-4 mb-4">

    <div class="d-flex justify-content-between align-items-center mb-4">

        <h4 class="fw-bold mb-0">

            <i class="bi bi-clock-history text-warning me-2"></i>

            Recent Transactions

        </h4>

        <a href="reports.php" class="btn btn-warning btn-sm">

            View All

        </a>

    </div>

    <div class="table-responsive">

        <table class="table table-dark table-hover align-middle">

            <thead>

                <tr>

                    <th>Type</th>

                    <th>Category</th>

                    <th>Description</th>

                    <th>Date</th>

                    <th class="text-end">Amount</th>

                </tr>

            </thead>

            <tbody>

            <?php if($recent_transactions->num_rows>0): ?>

            <?php while($row=$recent_transactions->fetch_assoc()): ?>

            <tr>

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

                    <span class="badge bg-primary">

                        <?= htmlspecialchars($row['category']) ?>

                    </span>

                </td>

                <td>

                    <?= htmlspecialchars($row['description'] ?: 'No Description') ?>

                </td>

                <td>

                    <?= date("d M Y",strtotime($row['trans_date'])) ?>

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

            </tr>

            <?php endwhile; ?>

            <?php else: ?>

            <tr>

                <td colspan="5" class="text-center py-5">

                    <i class="bi bi-receipt display-5 text-secondary d-block mb-3"></i>

                    <span class="text-secondary">

                        No recent transactions found.

                    </span>

                </td>

            </tr>

            <?php endif; ?>

            </tbody>

        </table>

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

            <a href="add_income.php"
               class="btn btn-success w-100 py-3">

                <i class="bi bi-plus-circle me-2"></i>

                Add Income

            </a>

        </div>

        <div class="col-lg-3 col-md-6">

            <a href="add_expense.php"
               class="btn btn-danger w-100 py-3">

                <i class="bi bi-dash-circle me-2"></i>

                Add Expense

            </a>

        </div>

        <div class="col-lg-3 col-md-6">

            <a href="savings.php"
               class="btn btn-primary w-100 py-3">

                <i class="bi bi-piggy-bank me-2"></i>

                Savings Goal

            </a>

        </div>

        <div class="col-lg-3 col-md-6">

            <a href="reports.php"
               class="btn btn-warning w-100 py-3">

                <i class="bi bi-bar-chart-fill me-2"></i>

                Reports

            </a>

        </div>

    </div>

</div><!-- ===========================
        DASHBOARD WIDGETS
=========================== -->

<?php

// Expense Percentage
$expense_percentage = 0;

if($total_income > 0){
    $expense_percentage = ($total_expense / $total_income) * 100;
    if($expense_percentage > 100){
        $expense_percentage = 100;
    }
}

// Savings Percentage
$savings_percentage = 0;

if($total_income > 0){
    $savings_percentage = ($total_savings / $total_income) * 100;
    if($savings_percentage > 100){
        $savings_percentage = 100;
    }
}

?>

<div class="row g-4 mb-4">

<!-- ================= Budget Progress ================= -->

<div class="col-lg-6">

<div class="glass p-4 h-100">

<h4 class="fw-bold mb-4">

<i class="bi bi-wallet-fill text-warning me-2"></i>

Budget Usage

</h4>

<div class="d-flex justify-content-between mb-2">

<span>Expenses</span>

<strong><?= number_format($expense_percentage,1) ?>%</strong>

</div>

<div class="progress mb-4" style="height:14px;">

<div
class="progress-bar bg-danger"
style="width:<?= $expense_percentage ?>%">

</div>

</div>

<small class="text-secondary">

You have spent

<strong>₹<?= number_format($total_expense,2) ?></strong>

out of

<strong>₹<?= number_format($total_income,2) ?></strong>

income.

</small>

</div>

</div>



<!-- ================= Savings Progress ================= -->

<div class="col-lg-6">

<div class="glass p-4 h-100">

<h4 class="fw-bold mb-4">

<i class="bi bi-piggy-bank-fill text-warning me-2"></i>

Savings Progress

</h4>

<div class="d-flex justify-content-between mb-2">

<span>Savings Rate</span>

<strong><?= number_format($savings_percentage,1) ?>%</strong>

</div>

<div class="progress mb-4" style="height:14px;">

<div
class="progress-bar bg-success"
style="width:<?= $savings_percentage ?>%">

</div>

</div>

<small class="text-secondary">

You have saved

<strong>₹<?= number_format($total_savings,2) ?></strong>

from your total income.

</small>

</div>

</div>

</div>



<!-- ================= SMART INSIGHTS ================= -->

<div class="glass p-4 mb-4">

<h4 class="fw-bold mb-4">

<i class="bi bi-lightbulb-fill text-warning me-2"></i>

Smart Financial Insights

</h4>

<div class="row g-3">

<div class="col-md-4">

<div class="p-3 rounded bg-success bg-opacity-25 h-100">

<h6 class="text-success">

Excellent

</h6>

<p class="mb-0">

<?php

if($expense_percentage < 50){

echo "Your expenses are well under control. Keep saving!";

}else{

echo "Try to reduce unnecessary spending to improve savings.";

}

?>

</p>

</div>

</div>



<div class="col-md-4">

<div class="p-3 rounded bg-primary bg-opacity-25 h-100">

<h6 class="text-info">

Savings Advice

</h6>

<p class="mb-0">

<?php

if($savings_percentage >= 20){

echo "Great job! Your savings rate is healthy.";

}else{

echo "Aim to save at least 20% of your income each month.";

}

?>

</p>

</div>

</div>



<div class="col-md-4">

<div class="p-3 rounded bg-warning bg-opacity-25 h-100">

<h6 class="text-warning">

Monthly Status

</h6>

<p class="mb-0">

<?php

if($balance > 0){

echo "You are running a positive balance this month.";

}else{

echo "Your expenses exceed your income. Review your budget.";

}

?>

</p>

</div>

</div>

</div>

</div>



<!-- ================= ACHIEVEMENTS ================= -->

<div class="glass p-4 mb-5">

<h4 class="fw-bold mb-4">

<i class="bi bi-trophy-fill text-warning me-2"></i>

Achievements

</h4>

<div class="row g-3">

<div class="col-md-3">

<div class="border rounded p-3 text-center">

<h1>💰</h1>

<h6>Income Added</h6>

<p class="mb-0">

₹<?= number_format($total_income,0) ?>

</p>

</div>

</div>

<div class="col-md-3">

<div class="border rounded p-3 text-center">

<h1>🎯</h1>

<h6>Total Savings</h6>

<p class="mb-0">

₹<?= number_format($total_savings,0) ?>

</p>

</div>

</div>

<div class="col-md-3">

<div class="border rounded p-3 text-center">

<h1>📊</h1>

<h6>Budget Used</h6>

<p class="mb-0">

<?= number_format($expense_percentage,1) ?>%

</p>

</div>

</div>

<div class="col-md-3">

<div class="border rounded p-3 text-center">

<h1>🏆</h1>

<h6>Balance</h6>

<p class="mb-0">

₹<?= number_format($balance,0) ?>

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



<!-- ===========================
        CHART.JS
=========================== -->

<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>

<script>

// ================= Income vs Expense =================

new Chart(document.getElementById("incomeExpenseChart"),{

    type:"bar",

    data:{

        labels:["Income","Expense"],

        datasets:[{

            label:"Amount (₹)",

            data:[

                <?= $chart_income ?>,

                <?= $chart_expense ?>

            ],

            backgroundColor:[

                "#22c55e",

                "#ef4444"

            ],

            borderRadius:10

        }]

    },

    options:{

        responsive:true,

        plugins:{

            legend:{

                display:false

            }

        },

        scales:{

            y:{

                beginAtZero:true,

                ticks:{

                    color:"#ffffff"

                },

                grid:{

                    color:"rgba(255,255,255,.08)"

                }

            },

            x:{

                ticks:{

                    color:"#ffffff"

                },

                grid:{

                    display:false

                }

            }

        }

    }

});



// ================= Expense Category =================

new Chart(document.getElementById("expenseCategoryChart"),{

    type:"doughnut",

    data:{

        labels:<?= json_encode($category_labels) ?>,

        datasets:[{

            data:<?= json_encode($category_amounts) ?>,

            backgroundColor:[

                "#3b82f6",

                "#22c55e",

                "#f59e0b",

                "#ef4444",

                "#8b5cf6",

                "#14b8a6",

                "#ec4899",

                "#64748b"

            ],

            borderWidth:0

        }]

    },

    options:{

        responsive:true,

        plugins:{

            legend:{

                position:"bottom",

                labels:{

                    color:"#ffffff",

                    padding:18

                }

            }

        }

    }

});



// ================= Sidebar Toggle =================

function toggleSidebar(){

    document.getElementById("sidebar").classList.toggle("active");

}

</script>



<!-- ===========================
        AUTO HIDE ALERTS
=========================== -->

<script>

setTimeout(function(){

    document.querySelectorAll(".alert").forEach(function(alert){

        alert.style.transition="0.5s";

        alert.style.opacity="0";

        setTimeout(function(){

            alert.remove();

        },500);

    });

},3000);

</script>



<!-- ===========================
        CARD ANIMATION
=========================== -->

<script>

const cards=document.querySelectorAll(".summary-card");

cards.forEach(function(card){

    card.addEventListener("mouseenter",function(){

        card.style.transform="translateY(-8px) scale(1.02)";

    });

    card.addEventListener("mouseleave",function(){

        card.style.transform="translateY(0) scale(1)";

    });

});

</script>



<!-- ===========================
        ACTIVE MENU
=========================== -->

<script>

const current=window.location.pathname.split("/").pop();

document.querySelectorAll(".menu a").forEach(function(link){

    if(link.getAttribute("href")==current){

        link.classList.add("active");

    }

});

</script>



<!-- ===========================
        PAGE LOADER
=========================== -->

<script>

window.addEventListener("load",function(){

    document.body.style.opacity="1";

});

document.body.style.opacity="0";

document.body.style.transition=".4s";

</script>



</body>
</html>