<?php
session_start();
include 'db.php';

// =============================
// LOGIN CHECK
// =============================
if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit();
}

$user_id = $_SESSION['user_id'];
$fullname = $_SESSION['fullname'] ?? 'User';


// =============================
// DATE FILTER
// =============================

$from_date = $_GET['from'] ?? '';
$to_date   = $_GET['to'] ?? '';

$income_where = "WHERE user_id=?";
$expense_where = "WHERE user_id=?";

$income_types = "i";
$expense_types = "i";

$income_params = [$user_id];
$expense_params = [$user_id];

if (!empty($from_date) && !empty($to_date)) {

    $income_where .= " AND income_date BETWEEN ? AND ?";
    $expense_where .= " AND expense_date BETWEEN ? AND ?";

    $income_types .= "ss";
    $expense_types .= "ss";

    $income_params[] = $from_date;
    $income_params[] = $to_date;

    $expense_params[] = $from_date;
    $expense_params[] = $to_date;
}


// =============================
// TOTAL INCOME
// =============================

$sql = "SELECT COALESCE(SUM(amount),0) AS total
        FROM income
        $income_where";

$stmt = $conn->prepare($sql);
$stmt->bind_param($income_types, ...$income_params);
$stmt->execute();

$result = $stmt->get_result();
$total_income = $result->fetch_assoc()['total'];

$stmt->close();


// =============================
// TOTAL EXPENSE
// =============================

$sql = "SELECT COALESCE(SUM(amount),0) AS total
        FROM expenses
        $expense_where";

$stmt = $conn->prepare($sql);
$stmt->bind_param($expense_types, ...$expense_params);
$stmt->execute();

$result = $stmt->get_result();
$total_expense = $result->fetch_assoc()['total'];

$stmt->close();


// =============================
// CURRENT BALANCE
// =============================

$current_balance = $total_income - $total_expense;


// =============================
// THIS MONTH INCOME
// =============================

$stmt = $conn->prepare("
SELECT COALESCE(SUM(amount),0) AS total
FROM income
WHERE user_id=?
AND MONTH(income_date)=MONTH(CURDATE())
AND YEAR(income_date)=YEAR(CURDATE())
");

$stmt->bind_param("i",$user_id);
$stmt->execute();

$this_month_income = $stmt
->get_result()
->fetch_assoc()['total'];

$stmt->close();


// =============================
// THIS MONTH EXPENSE
// =============================

$stmt = $conn->prepare("
SELECT COALESCE(SUM(amount),0) AS total
FROM expenses
WHERE user_id=?
AND MONTH(expense_date)=MONTH(CURDATE())
AND YEAR(expense_date)=YEAR(CURDATE())
");

$stmt->bind_param("i",$user_id);
$stmt->execute();

$this_month_expense = $stmt
->get_result()
->fetch_assoc()['total'];

$stmt->close();


// =============================
// EXPENSE CATEGORY REPORT
// =============================

$stmt = $conn->prepare("
SELECT category,
SUM(amount) AS total
FROM expenses
WHERE user_id=?
GROUP BY category
ORDER BY total DESC
");

$stmt->bind_param("i",$user_id);
$stmt->execute();

$category_result = $stmt->get_result();

$category_labels = [];
$category_amounts = [];

while($row = $category_result->fetch_assoc()){

    $category_labels[] = $row['category'];
    $category_amounts[] = $row['total'];

}

$stmt->close();


// =============================
// MONTHLY REPORT
// =============================

$stmt = $conn->prepare("
SELECT
MONTH(expense_date) AS month,
SUM(amount) AS total
FROM expenses
WHERE user_id=?
AND YEAR(expense_date)=YEAR(CURDATE())
GROUP BY MONTH(expense_date)
ORDER BY MONTH(expense_date)
");

$stmt->bind_param("i",$user_id);
$stmt->execute();

$monthly_result = $stmt->get_result();

$months = [];
$monthly_expense = [];

while($row = $monthly_result->fetch_assoc()){

    $months[] = date("M", mktime(0,0,0,$row['month'],1));
    $monthly_expense[] = $row['total'];

}

$stmt->close();


// =============================
// RECENT INCOME
// =============================

$income_result = $conn->query("
SELECT
income_date,
source,
amount
FROM income
WHERE user_id=$user_id
ORDER BY income_date DESC
LIMIT 5
");


// =============================
// RECENT EXPENSE
// =============================

$expense_result = $conn->query("
SELECT
expense_date,
category,
description,
amount
FROM expenses
WHERE user_id=$user_id
ORDER BY expense_date DESC
LIMIT 5
");

?>
<!DOCTYPE html>
<html lang="en">

<head>

<meta charset="UTF-8">

<meta name="viewport" content="width=device-width, initial-scale=1.0">

<title>Finora • Reports</title>

<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">

<link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.css" rel="stylesheet">

<link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700&display=swap" rel="stylesheet">

<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>

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

    min-height:100vh;

}


/* ===========================
        Scrollbar
=========================== */

::-webkit-scrollbar{

    width:8px;

}

::-webkit-scrollbar-thumb{

    background:#7c3aed;

    border-radius:20px;

}


/* ===========================
        Glass Card
=========================== */

.card-glass{

    background:rgba(18,25,51,.75);

    border:1px solid rgba(255,255,255,.08);

    backdrop-filter:blur(18px);

    border-radius:24px;

    box-shadow:0 20px 45px rgba(0,0,0,.35);

}


/* ===========================
        Summary Cards
=========================== */

.summary{

    padding:25px;

    border-radius:24px;

    color:#fff;

    transition:.35s;

    box-shadow:0 18px 35px rgba(0,0,0,.25);

}

.summary:hover{

    transform:translateY(-6px);

}

.s1{

    background:linear-gradient(135deg,#16a34a,#22c55e);

}

.s2{

    background:linear-gradient(135deg,#dc2626,#ef4444);

}

.s3{

    background:linear-gradient(135deg,#2563eb,#3b82f6);

}

.s4{

    background:linear-gradient(135deg,#7c3aed,#9333ea);

}


/* ===========================
        Charts
=========================== */

.chart-card{

    padding:25px;

    min-height:420px;

}

.chart-card canvas{

    max-height:320px;

}


/* ===========================
        Forms
=========================== */

.form-control,
.form-select{

    background:#111827;

    color:#fff;

    border:1px solid #374151;

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


/* ===========================
        Buttons
=========================== */

.btn-warning{

    border:none;

    color:#111827;

    font-weight:600;

    border-radius:50px;

}

.btn-outline-light{

    border-radius:50px;

}


/* ===========================
        Tables
=========================== */

.table{

    color:#fff;

}

.table-dark{

    --bs-table-bg:transparent;

    --bs-table-border-color:rgba(255,255,255,.08);

}

.table tbody tr:hover{

    background:rgba(255,255,255,.05);

}


/* ===========================
        Badges
=========================== */

.badge{

    font-size:.8rem;

    padding:.55rem .8rem;

    border-radius:20px;

}


/* ===========================
        Headings
=========================== */

.page-title{

    font-size:2rem;

    font-weight:700;

}

.subtitle{

    color:#94a3b8;

}


/* ===========================
        Footer
=========================== */

footer{

    color:#94a3b8;

}


/* ===========================
        Responsive
=========================== */

@media(max-width:768px){

.page-title{

    font-size:1.5rem;

}

.summary{

    margin-bottom:20px;

}

.chart-card{

    min-height:350px;

}

}

</style>

</head>

<body>

<div class="container py-4">
<!-- =======================================
            PAGE HEADER
======================================= -->

<div class="d-flex justify-content-between align-items-center flex-wrap mb-4">

    <div>

        <h2 class="page-title">

            <i class="bi bi-bar-chart-fill text-warning me-2"></i>

            Financial Reports

        </h2>

        <p class="subtitle mb-0">

            Welcome,
            <strong><?= htmlspecialchars($fullname) ?></strong>

        </p>

    </div>

    <div class="mt-3 mt-md-0">

        <a href="dashboard.php" class="btn btn-outline-light px-4">

            <i class="bi bi-arrow-left-circle me-2"></i>

            Dashboard

        </a>

    </div>

</div>


<!-- =======================================
            DATE FILTER
======================================= -->

<div class="card-glass p-4 mb-4">

    <form method="GET">

        <div class="row align-items-end g-3">

            <div class="col-md-4">

                <label class="form-label">

                    From Date

                </label>

                <input
                    type="date"
                    name="from"
                    class="form-control"
                    value="<?= htmlspecialchars($from_date) ?>"
                >

            </div>

            <div class="col-md-4">

                <label class="form-label">

                    To Date

                </label>

                <input
                    type="date"
                    name="to"
                    class="form-control"
                    value="<?= htmlspecialchars($to_date) ?>"
                >

            </div>

            <div class="col-md-4 d-grid">

                <button class="btn btn-warning">

                    <i class="bi bi-funnel-fill me-2"></i>

                    Generate Report

                </button>

            </div>

        </div>

    </form>

</div>



<!-- =======================================
            SUMMARY CARDS
======================================= -->

<div class="row g-4 mb-4">


    <!-- TOTAL INCOME -->

    <div class="col-lg-3 col-md-6">

        <div class="summary s1">

            <div class="d-flex justify-content-between">

                <div>

                    <small class="text-uppercase opacity-75">

                        Total Income

                    </small>

                    <h3 class="fw-bold mt-2">

                        ₹<?= number_format($total_income,2) ?>

                    </h3>

                </div>

                <i class="bi bi-wallet2 display-5 opacity-50"></i>

            </div>

        </div>

    </div>



    <!-- TOTAL EXPENSE -->

    <div class="col-lg-3 col-md-6">

        <div class="summary s2">

            <div class="d-flex justify-content-between">

                <div>

                    <small class="text-uppercase opacity-75">

                        Total Expense

                    </small>

                    <h3 class="fw-bold mt-2">

                        ₹<?= number_format($total_expense,2) ?>

                    </h3>

                </div>

                <i class="bi bi-cash-stack display-5 opacity-50"></i>

            </div>

        </div>

    </div>



    <!-- BALANCE -->

    <div class="col-lg-3 col-md-6">

        <div class="summary s3">

            <div class="d-flex justify-content-between">

                <div>

                    <small class="text-uppercase opacity-75">

                        Current Balance

                    </small>

                    <h3 class="fw-bold mt-2">

                        ₹<?= number_format($current_balance,2) ?>

                    </h3>

                </div>

                <i class="bi bi-piggy-bank display-5 opacity-50"></i>

            </div>

        </div>

    </div>



    <!-- THIS MONTH -->

    <div class="col-lg-3 col-md-6">

        <div class="summary s4">

            <div class="d-flex justify-content-between">

                <div>

                    <small class="text-uppercase opacity-75">

                        This Month

                    </small>

                    <div class="small mt-2">

                        <div>

                            Income :
                            <strong>

                                ₹<?= number_format($this_month_income,2) ?>

                            </strong>

                        </div>

                        <div>

                            Expense :
                            <strong>

                                ₹<?= number_format($this_month_expense,2) ?>

                            </strong>

                        </div>

                    </div>

                </div>

                <i class="bi bi-calendar-month display-5 opacity-50"></i>

            </div>

        </div>

    </div>

</div>
<!-- =======================================
            CHARTS SECTION
======================================= -->

<div class="row g-4 mb-4">

    <!-- Expense by Category -->

    <div class="col-lg-6">

        <div class="card-glass chart-card">

            <h4 class="fw-bold mb-4">

                <i class="bi bi-pie-chart-fill text-warning me-2"></i>

                Expense by Category

            </h4>

            <canvas id="categoryChart"></canvas>

        </div>

    </div>



    <!-- Monthly Expense -->

    <div class="col-lg-6">

        <div class="card-glass chart-card">

            <h4 class="fw-bold mb-4">

                <i class="bi bi-bar-chart-fill text-warning me-2"></i>

                Monthly Expense

            </h4>

            <canvas id="monthlyChart"></canvas>

        </div>

    </div>

</div>



<script>

// ================================
// Expense Category Pie Chart
// ================================

const categoryCtx = document.getElementById('categoryChart');

new Chart(categoryCtx,{

    type:'pie',

    data:{

        labels:<?= json_encode($category_labels) ?>,

        datasets:[{

            data:<?= json_encode($category_amounts) ?>,

            backgroundColor:[

                '#ef4444',
                '#3b82f6',
                '#f59e0b',
                '#10b981',
                '#8b5cf6',
                '#ec4899',
                '#14b8a6',
                '#64748b'

            ],

            borderWidth:2,

            borderColor:'#0b1020'

        }]

    },

    options:{

        responsive:true,

        plugins:{

            legend:{

                position:'bottom',

                labels:{

                    color:'#ffffff',

                    padding:20,

                    font:{
                        size:13
                    }

                }

            }

        }

    }

});




// ================================
// Monthly Expense Bar Chart
// ================================

const monthlyCtx = document.getElementById('monthlyChart');

new Chart(monthlyCtx,{

    type:'bar',

    data:{

        labels:<?= json_encode($months) ?>,

        datasets:[{

            label:'Monthly Expense',

            data:<?= json_encode($monthly_expense) ?>,

            backgroundColor:'#f97316',

            borderRadius:10,

            borderSkipped:false

        }]

    },

    options:{

        responsive:true,

        maintainAspectRatio:false,

        plugins:{

            legend:{

                labels:{

                    color:'#ffffff'

                }

            }

        },

        scales:{

            x:{

                ticks:{

                    color:'#ffffff'

                },

                grid:{

                    color:'rgba(255,255,255,.08)'

                }

            },

            y:{

                beginAtZero:true,

                ticks:{

                    color:'#ffffff'

                },

                grid:{

                    color:'rgba(255,255,255,.08)'

                }

            }

        }

    }

});

</script>
<!-- =======================================
        RECENT TRANSACTIONS
======================================= -->

<div class="row g-4 mb-4">

    <!-- Recent Income -->

    <div class="col-lg-6">

        <div class="card-glass p-4 h-100">

            <h4 class="fw-bold mb-4">

                <i class="bi bi-arrow-down-circle-fill text-success me-2"></i>

                Recent Income

            </h4>

            <div class="table-responsive">

                <table class="table table-dark table-hover align-middle">

                    <thead>

                        <tr>

                            <th>Date</th>

                            <th>Source</th>

                            <th class="text-end">Amount</th>

                        </tr>

                    </thead>

                    <tbody>

                    <?php if($income_result->num_rows > 0): ?>

                        <?php while($row = $income_result->fetch_assoc()): ?>

                        <tr>

                            <td>

                                <?= date("d M Y", strtotime($row['income_date'])) ?>

                            </td>

                            <td>

                                <?= htmlspecialchars($row['source']) ?>

                            </td>

                            <td class="text-end text-success fw-bold">

                                ₹<?= number_format($row['amount'],2) ?>

                            </td>

                        </tr>

                        <?php endwhile; ?>

                    <?php else: ?>

                        <tr>

                            <td colspan="3" class="text-center text-secondary py-4">

                                No income records found.

                            </td>

                        </tr>

                    <?php endif; ?>

                    </tbody>

                </table>

            </div>

        </div>

    </div>



    <!-- Recent Expense -->

    <div class="col-lg-6">

        <div class="card-glass p-4 h-100">

            <h4 class="fw-bold mb-4">

                <i class="bi bi-arrow-up-circle-fill text-danger me-2"></i>

                Recent Expenses

            </h4>

            <div class="table-responsive">

                <table class="table table-dark table-hover align-middle">

                    <thead>

                        <tr>

                            <th>Date</th>

                            <th>Category</th>

                            <th>Description</th>

                            <th class="text-end">Amount</th>

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

                                $badge = "bg-secondary";

                                switch($row['category']){

                                    case "Food":
                                        $badge = "bg-success";
                                        break;

                                    case "Transport":
                                        $badge = "bg-primary";
                                        break;

                                    case "Shopping":
                                        $badge = "bg-warning text-dark";
                                        break;

                                    case "Bills":
                                        $badge = "bg-danger";
                                        break;

                                    case "Entertainment":
                                        $badge = "bg-info text-dark";
                                        break;

                                    case "Education":
                                        $badge = "bg-dark";
                                        break;

                                    case "Health":
                                        $badge = "bg-success";
                                        break;

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

                            <td class="text-end text-danger fw-bold">

                                ₹<?= number_format($row['amount'],2) ?>

                            </td>

                        </tr>

                        <?php endwhile; ?>

                    <?php else: ?>

                        <tr>

                            <td colspan="4" class="text-center text-secondary py-4">

                                No expense records found.

                            </td>

                        </tr>

                    <?php endif; ?>

                    </tbody>

                </table>

            </div>

        </div>

    </div>

</div>
</div>

<!-- =======================================
                FOOTER
======================================= -->

<footer class="text-center text-secondary py-4 mt-5">

    <hr class="border-secondary">

    <p class="mb-0">

        <i class="bi bi-graph-up-arrow text-warning"></i>

        © <?= date('Y'); ?> <strong>Finora</strong> | Financial Reports Dashboard

    </p>

</footer>



<!-- =======================================
            BOOTSTRAP JS
======================================= -->

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>



<!-- =======================================
        AUTO REFRESH CHARTS ON RESIZE
======================================= -->

<script>

window.addEventListener('resize', function () {

    if (typeof Chart !== 'undefined') {

        Chart.helpers.each(Chart.instances, function(instance) {
            instance.resize();
        });

    }

});

</script>



<!-- =======================================
        PRINT REPORT
======================================= -->

<script>

function printReport(){

    window.print();

}

</script>



<!-- =======================================
        EXPORT TABLE TO CSV
======================================= -->

<script>

function exportTableToCSV(filename) {

    let csv = [];

    let rows = document.querySelectorAll("table tr");

    rows.forEach(function(row){

        let cols = row.querySelectorAll("td, th");

        let rowData = [];

        cols.forEach(function(col){

            rowData.push('"' + col.innerText.replace(/"/g,'""') + '"');

        });

        csv.push(rowData.join(","));

    });

    let csvFile = new Blob([csv.join("\n")], {type: "text/csv"});

    let downloadLink = document.createElement("a");

    downloadLink.download = filename;

    downloadLink.href = window.URL.createObjectURL(csvFile);

    downloadLink.style.display = "none";

    document.body.appendChild(downloadLink);

    downloadLink.click();

}

</script>

</body>

</html>
