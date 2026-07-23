<?php
session_start();
include 'db.php';

if (!isset($_SESSION['user_id'])) {
    header('Location: login.php');
    exit();
}

$user_id = $_SESSION['user_id'];
$fullname = $_SESSION['fullname'];

// ===== TOTALS =====
$income_q = $conn->query("SELECT COALESCE(SUM(amount),0) total FROM income WHERE user_id=$user_id");
$total_income = $income_q->fetch_assoc()['total'];

$expense_q = $conn->query("SELECT COALESCE(SUM(amount),0) total FROM expenses WHERE user_id=$user_id");
$total_expense = $expense_q->fetch_assoc()['total'];

$balance = $total_income - $total_expense;

// ===== CURRENT MONTH =====
$month_income_q = $conn->query("
    SELECT COALESCE(SUM(amount),0) total
    FROM income
    WHERE user_id=$user_id
      AND MONTH(income_date)=MONTH(CURDATE())
      AND YEAR(income_date)=YEAR(CURDATE())
");
$current_month_income = $month_income_q->fetch_assoc()['total'];

$month_exp_q = $conn->query("
    SELECT COALESCE(SUM(amount),0) total
    FROM expenses
    WHERE user_id=$user_id
      AND MONTH(expense_date)=MONTH(CURDATE())
      AND YEAR(expense_date)=YEAR(CURDATE())
");
$current_month_expense = $month_exp_q->fetch_assoc()['total'];

// ===== BUDGET =====
$budget_q = $conn->query("
 SELECT COALESCE(MAX(budget_amount),50000) AS budget
FROM budgets
WHERE user_id=$user_id "); // Semicolon added!
$budget_amount = $budget_q->fetch_assoc()['budget'];
$budget_percent = ($budget_amount > 0)
    ? min(100, ($current_month_expense / $budget_amount) * 100)
    : 0;

// ===== SAVINGS =====
$savings_rate = ($total_income > 0)
    ? round(($balance / $total_income) * 100)
    : 0;

// ===== CATEGORY DATA =====
$cat_q = $conn->query("
    SELECT c.category_name, SUM(e.amount) AS total
    FROM expenses e
    JOIN categories c ON e.category_id=c.category_id
    WHERE e.user_id=$user_id
    GROUP BY c.category_name
    ORDER BY total DESC
");

$cat_labels = [];
$cat_values = [];

while($row = $cat_q->fetch_assoc()){
    $cat_labels[] = $row['category_name'];
    $cat_values[] = $row['total'];
}

// ===== RECENT TRANSACTIONS =====
$recent = $conn->query("
    SELECT e.amount, e.description, e.expense_date, c.category_name
    FROM expenses e
    JOIN categories c ON e.category_id=c.category_id
    WHERE e.user_id=$user_id
    ORDER BY e.expense_date DESC, e.expense_id DESC
    LIMIT 6
");

// ===== NOTIFICATIONS =====
$notifications = [
    ['title'=>'Budget Exceeded','message'=>'Your expenses exceeded the monthly budget.','type'=>'danger','time'=>'10:42 AM'],
    ['title'=>'Low Balance','message'=>'Your balance is below ₹5,000.','type'=>'warning','time'=>'10:30 AM'],
    ['title'=>'Expense Added','message'=>'Grocery expense added successfully.','type'=>'success','time'=>'09:15 AM']
];
$unread_count = count($notifications);
?>

<!DOCTYPE html>

<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1">
<title>Dashboard - ExpenseTracker</title>

<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
<link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.css" rel="stylesheet">
<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>

<style>
:root{
    --bg:#0b1020;
    --panel:#121933cc;
    --line:#2a3566;
    --text:#eef2ff;
    --muted:#a5b4fc;
    --blue:#5b8cff;
    --green:#22c55e;
    --red:#ef4444;
    --yellow:#f59e0b;
    --cyan:#06b6d4;
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
    z-index:1000;
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
    position:sticky;
    top:0;
    z-index:2000;
    background:#0f172acc;
    backdrop-filter: blur(16px);
    border-bottom:1px solid var(--line);
    padding:16px 24px;
}

.glass{
    background:var(--panel);
    border:1px solid rgba(255,255,255,.08);
    backdrop-filter: blur(16px);
    border-radius:26px;
    box-shadow:0 12px 40px rgba(0,0,0,.28);
}

.kpi{
    padding:22px;
    min-height:150px;
    position:relative;
    overflow:hidden;
    transition:transform .25s ease;
}

.kpi:hover{ transform:translateY(-6px); }

.kpi .icon{
    position:absolute;
    right:18px;
    top:18px;
    font-size:42px;
    opacity:.18;
}

.income{ background:linear-gradient(135deg,#0f5132,#16a34a); }
.expense{ background:linear-gradient(135deg,#7f1d1d,#dc2626); }
.balance{ background:linear-gradient(135deg,#1e3a8a,#2563eb); }
.budget{ background:linear-gradient(135deg,#78350f,#f59e0b); }

.ring-wrap{
    width:170px;height:170px;
    position:relative;margin:auto;
}

.ring{
    width:170px;height:170px;border-radius:50%;
    background:conic-gradient(var(--green) calc(var(--p)*1%), rgba(255,255,255,.08) 0);
    display:grid;place-items:center;
}

.ring::before{
    content:'';
    width:128px;height:128px;border-radius:50%;
    background:#0f172a;border:1px solid var(--line);
}

.ring-center{
    position:absolute; inset:0;
    display:grid; place-items:center;
    text-align:center;
}

.heatmap{
    display:grid;
    grid-template-columns: repeat(7,1fr);
    gap:8px;
}

.heat{
    height:34px;
    border-radius:10px;
    background:#1f2937;
}

.heat.l1{ background:#123a5a; }
.heat.l2{ background:#155e75; }
.heat.l3{ background:#0ea5a4; }
.heat.l4{ background:#22c55e; }

.dropdown-menu.glass{
    background:#121933ee !important;
    border:1px solid rgba(255,255,255,.08) !important;
    backdrop-filter: blur(16px);
    border-radius:20px;
    box-shadow:0 18px 50px rgba(0,0,0,.35);
    z-index:3000 !important;
}

.dropdown-menu .dropdown-item{
    color:white;
    transition:all .2s ease;
}

.dropdown-menu .dropdown-item:hover{
    background:rgba(59,130,246,.12) !important;
    transform:translateX(4px);
}

.table-darkish{
    --bs-table-bg: transparent;
    --bs-table-color: var(--text);
    --bs-table-border-color: #243056;
    --bs-table-striped-bg: rgba(255,255,255,.03);
    --bs-table-hover-bg: rgba(255,255,255,.05);
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
<a href="dashboard.php" class="active"><i class="bi bi-speedometer2 me-2"></i>Dashboard</a>
<a href="add_income.php"><i class="bi bi-plus-circle me-2"></i>Add Income</a>
<a href="add_expense.php"><i class="bi bi-dash-circle me-2"></i>Add Expense</a>
<a href="savings.php" class="nav-link text-white"> <i class="bi bi-piggy-bank me-2"></i>Savings</a>
<a href="transactions.php"><i class="bi bi-arrow-left-right me-2"></i>Transactions</a>
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
        <h3 class="mb-0">Dashboard</h3>
        <small class="text-secondary">Welcome back, <?php echo htmlspecialchars($fullname); ?> 👋</small>
    </div>

    <div class="d-flex align-items-center gap-3">

        <!-- NOTIFICATIONS -->
        <div class="dropdown">
            <button class="btn btn-dark position-relative border border-secondary" data-bs-toggle="dropdown">
                <i class="bi bi-bell fs-5"></i>

                <span id="notifCount" class="position-absolute top-0 start-100 translate-middle badge rounded-pill bg-danger">
                    <?php echo $unread_count; ?>
                </span>
            </button>

            <div class="dropdown-menu dropdown-menu-end glass p-0" style="width:340px;">
                <div class="d-flex justify-content-between align-items-center px-3 py-3 border-bottom border-secondary">
                    <h6 class="mb-0 text-white">Notifications</h6>
                    <small id="unreadText" class="text-secondary"><?php echo $unread_count; ?> unread</small>
                </div>

                <div id="notifList">
                    <?php foreach($notifications as $n): ?>
                    <div class="dropdown-item px-3 py-3 border-bottom border-secondary">
                        <div class="d-flex align-items-start gap-3">
                            <i class="bi bi-info-circle text-info fs-5 mt-1"></i>

                            <div class="flex-grow-1">
                                <div class="d-flex justify-content-between align-items-start">
                                    <strong><?php echo $n['title']; ?></strong>
                                    <span class="badge bg-primary new-badge">New</span>
                                </div>

                                <div class="small text-secondary mt-1"><?php echo $n['message']; ?></div>
                                <div class="small text-secondary mt-2">
                                    <i class="bi bi-clock me-1"></i><?php echo $n['time']; ?>
                                </div>
                            </div>
                        </div>
                    </div>
                    <?php endforeach; ?>
                </div>

                <div class="p-3 border-top border-secondary">
                    <button class="btn btn-outline-light btn-sm w-100" onclick="markAllRead()">
                        <i class="bi bi-check2-all me-1"></i>Mark all as read
                    </button>
                </div>
            </div>
        </div>

        <!-- PROFILE -->
        <div class="dropdown">
            <button class="btn btn-dark border border-secondary d-flex align-items-center gap-2 px-2 py-1" data-bs-toggle="dropdown">
                <img src="https://ui-avatars.com/api/?name=<?php echo urlencode($fullname); ?>&background=2563eb&color=fff"
                     width="38" height="38" class="rounded-circle border border-secondary">

                <span class="fw-semibold d-none d-md-inline text-white"><?php echo htmlspecialchars($fullname); ?></span>
                <i class="bi bi-chevron-down text-secondary small"></i>
            </button>

            <ul class="dropdown-menu dropdown-menu-end glass border-0 shadow-lg mt-2" style="min-width:240px;">
                <li><a class="dropdown-item py-2" href="profile.php"><i class="bi bi-person me-2 text-info"></i>My Profile</a></li>
                <li><a class="dropdown-item py-2" href="settings.php"><i class="bi bi-gear me-2 text-warning"></i>Settings</a></li>
                <li><a class="dropdown-item py-2" href="reports.php"><i class="bi bi-bar-chart me-2 text-success"></i>Reports</a></li>
                <li><hr class="dropdown-divider border-secondary"></li>
                <li class="px-3 py-2">
                    <a class="btn btn-outline-danger w-100" href="logout.php">
                        <i class="bi bi-box-arrow-right me-2"></i>Logout
                    </a>
                </li>
            </ul>
        </div>

    </div>
</div>

<!-- CONTENT -->
<div class="container-fluid p-4">

    <!-- QUICK ACTIONS -->
    <div class="glass p-4 mb-4">
        <div class="row g-3">
            <div class="col-md-3 d-grid">
                <a href="add_income.php" class="btn btn-success btn-lg">
                    <i class="bi bi-plus-circle me-2"></i>Add Income
                </a>
            </div>

            <div class="col-md-3 d-grid">
                <a href="add_expense.php" class="btn btn-danger btn-lg">
                    <i class="bi bi-dash-circle me-2"></i>Add Expense
                </a>
            </div>

            <div class="col-md-3 d-grid">
                <a href="transactions.php" class="btn btn-primary btn-lg">
                    <i class="bi bi-arrow-left-right me-2"></i>Transactions
                </a>
            </div>

            <div class="col-md-3 d-grid">
                <a href="reports.php" class="btn btn-warning btn-lg text-dark">
                    <i class="bi bi-bar-chart me-2"></i>Reports
                </a>
            </div>
        </div>
    </div>

    <!-- KPI CARDS -->
    <div class="row g-4 mb-4">

        <div class="col-lg-3 col-md-6">
            <div class="glass kpi income">
                <i class="bi bi-arrow-down-left-circle icon"></i>
                <div class="text-white-50">Total Income</div>
                <h2 class="fw-bold mt-2">₹ <?php echo number_format($total_income,2); ?></h2>
                <small class="text-white-50">All income sources</small>
            </div>
        </div>

        <div class="col-lg-3 col-md-6">
            <div class="glass kpi expense">
                <i class="bi bi-arrow-up-right-circle icon"></i>
                <div class="text-white-50">Total Expenses</div>
                <h2 class="fw-bold mt-2">₹ <?php echo number_format($total_expense,2); ?></h2>
                <small class="text-white-50">Operational spending</small>
            </div>
        </div>

        <div class="col-lg-3 col-md-6">
            <div class="glass kpi balance">
                <i class="bi bi-wallet2 icon"></i>
                <div class="text-white-50">Net Balance</div>
                <h2 class="fw-bold mt-2">₹ <?php echo number_format($balance,2); ?></h2>
                <small class="text-white-50">Income minus expenses</small>
            </div>
        </div>

        <div class="col-lg-3 col-md-6">
            <div class="glass kpi budget">
                <i class="bi bi-pie-chart icon"></i>
                <div class="text-white-50">Budget Used</div>
                <h2 class="fw-bold mt-2"><?php echo round($budget_percent); ?>%</h2>

                <div class="progress mt-3" style="height:10px;">
                    <div class="progress-bar bg-light" style="width:<?php echo $budget_percent; ?>%"></div>
                </div>
            </div>
        </div>

    </div>

    <!-- CHARTS -->
    <div class="row g-4 mb-4">

        <div class="col-xl-8">
            <div class="glass p-4 h-100">
                <div class="d-flex justify-content-between align-items-center mb-3">
                    <div>
                        <h5 class="mb-0">Income vs Expenses</h5>
                        <small class="text-secondary">Monthly cash flow trend</small>
                    </div>
                </div>

                <canvas id="financeChart" height="120"></canvas>
            </div>
        </div>

        <div class="col-xl-4">
            <div class="glass p-4 h-100">
                <div class="d-flex justify-content-between align-items-center mb-3">
                    <h5 class="mb-0">Financial Health</h5>
                    <i class="bi bi-heart-pulse text-success fs-4"></i>
                </div>

                <div class="ring-wrap">
                    <div class="ring" style="--p:<?php echo max(5,min(95,$savings_rate)); ?>;"></div>

                    <div class="ring-center">
                        <div>
                            <div class="small text-secondary">Savings Rate</div>
                            <div class="fs-2 fw-bold"><?php echo $savings_rate; ?>%</div>
                        </div>
                    </div>
                </div>

                <div class="text-center mt-3">
                    <div class="text-secondary small">Current balance</div>
                    <div class="fw-semibold fs-5">₹ <?php echo number_format($balance,0); ?></div>
                </div>
            </div>
        </div>

    </div>

    <!-- CATEGORY + SAVINGS -->
    <div class="row g-4 mb-4">

        <div class="col-lg-6">
            <div class="glass p-4 h-100">
                <div class="d-flex justify-content-between align-items-center mb-3">
                    <div>
                        <h5 class="mb-0">Expense Categories</h5>
                        <small class="text-secondary">Real database data</small>
                    </div>
                    <i class="bi bi-pie-chart text-info fs-4"></i>
                </div>

                <canvas id="categoryChart" height="220"></canvas>
            </div>
        </div>

        <div class="col-lg-6">
            <div class="glass p-4 h-100">
                <div class="d-flex justify-content-between align-items-center mb-3">
                    <div>
                        <h5 class="mb-0">Savings Goals</h5>
                        <small class="text-secondary">Track progress towards your target</small>
                    </div>
                    <i class="bi bi-piggy-bank text-warning fs-4"></i>
                </div>

                <div class="mb-4">
                    <div class="d-flex justify-content-between mb-2">
                        <span>Emergency Fund</span>
                        <span>₹ 75,000 / ₹ 1,00,000</span>
                    </div>

                    <div class="progress" style="height:12px;">
                        <div class="progress-bar bg-success" style="width:75%"></div>
                    </div>
                </div>

                <div class="mb-4">
                    <div class="d-flex justify-content-between mb-2">
                        <span>Vacation</span>
                        <span>₹ 22,000 / ₹ 50,000</span>
                    </div>

                    <div class="progress" style="height:12px;">
                        <div class="progress-bar bg-info" style="width:44%"></div>
                    </div>
                </div>

                <div>
                    <div class="d-flex justify-content-between mb-2">
                        <span>New Laptop</span>
                        <span>₹ 48,000 / ₹ 80,000</span>
                    </div>

                    <div class="progress" style="height:12px;">
                        <div class="progress-bar bg-warning" style="width:60%"></div>
                    </div>
                </div>
            </div>
        </div>

    </div>

    <!-- HEATMAP + BUDGET -->
    <div class="row g-4 mb-4">

        <div class="col-lg-7">
            <div class="glass p-4 h-100">
                <div class="d-flex justify-content-between align-items-center mb-3">
                    <div>
                        <h5 class="mb-0">Spending Heatmap</h5>
                        <small class="text-secondary">Higher intensity means higher spending</small>
                    </div>
                    <i class="bi bi-grid-3x3-gap text-info fs-4"></i>
                </div>

                <div class="heatmap">
                    <div class="heat l1"></div><div class="heat l2"></div><div class="heat l3"></div><div class="heat l2"></div><div class="heat l4"></div><div class="heat l1"></div><div class="heat l3"></div>
                    <div class="heat l2"></div><div class="heat l3"></div><div class="heat l1"></div><div class="heat l4"></div><div class="heat l2"></div><div class="heat l3"></div><div class="heat l1"></div>
                    <div class="heat l3"></div><div class="heat l4"></div><div class="heat l2"></div><div class="heat l1"></div><div class="heat l3"></div><div class="heat l2"></div><div class="heat l4"></div>
                    <div class="heat l1"></div><div class="heat l2"></div><div class="heat l3"></div><div class="heat l4"></div><div class="heat l2"></div><div class="heat l1"></div><div class="heat l3"></div>
                </div>

                <div class="d-flex align-items-center gap-3 mt-3 small text-secondary">
                    <span class="d-flex align-items-center gap-1"><span class="heat l1" style="width:18px;height:18px;"></span>Low</span>
                    <span class="d-flex align-items-center gap-1"><span class="heat l2" style="width:18px;height:18px;"></span>Moderate</span>
                    <span class="d-flex align-items-center gap-1"><span class="heat l3" style="width:18px;height:18px;"></span>High</span>
                    <span class="d-flex align-items-center gap-1"><span class="heat l4" style="width:18px;height:18px;"></span>Very High</span>
                </div>
            </div>
        </div>

        <div class="col-lg-5">
            <div class="glass p-4 h-100">
                <div class="d-flex justify-content-between align-items-center mb-3">
                    <div>
                        <h5 class="mb-0">Budget Tracker</h5>
                        <small class="text-secondary">Monthly spending vs budget</small>
                    </div>
                    <i class="bi bi-wallet2 text-warning fs-4"></i>
                </div>

                <div class="mb-3">
                    <div class="d-flex justify-content-between">
                        <span>Budget</span>
                        <span>₹ <?php echo number_format($budget_amount,0); ?></span>
                    </div>
                </div>

                <div class="mb-3">
                    <div class="d-flex justify-content-between">
                        <span>Spent</span>
                        <span>₹ <?php echo number_format($current_month_expense,0); ?></span>
                    </div>
                </div>

                <div class="mb-3">
                    <div class="d-flex justify-content-between">
                        <span>Remaining</span>
                        <span>₹ <?php echo number_format(max(0,$budget_amount-$current_month_expense),0); ?></span>
                    </div>
                </div>

                <div class="progress mb-3" style="height:14px;">
                    <div class="progress-bar <?php echo $budget_percent > 85 ? 'bg-danger' : ($budget_percent > 60 ? 'bg-warning' : 'bg-success'); ?>"
                         style="width:<?php echo $budget_percent; ?>%"></div>
                </div>

                <div class="small text-secondary">
                    <?php echo round($budget_percent); ?>% of your monthly budget has been used.
                </div>
            </div>
        </div>

    </div>

    <!-- RECENT TRANSACTIONS -->
    <div class="glass p-4">
        <div class="d-flex justify-content-between align-items-center mb-3">
            <div>
                <h5 class="mb-0">Recent Transactions</h5>
                <small class="text-secondary">Latest expenses from your database</small>
            </div>

            <a href="transactions.php" class="btn btn-outline-light btn-sm">
                <i class="bi bi-arrow-right me-1"></i>View all
            </a>
        </div>

        <div class="table-responsive">
            <table class="table table-darkish table-hover align-middle">
                <thead>
                    <tr>
                        <th>Date</th>
                        <th>Category</th>
                        <th>Description</th>
                        <th class="text-end">Amount</th>
                    </tr>
                </thead>

                <tbody>
                <?php while($row = $recent->fetch_assoc()): ?>
                    <tr>
                        <td><?php echo date('d M Y', strtotime($row['expense_date'])); ?></td>

                        <td>
                            <span class="badge text-bg-info"><?php echo htmlspecialchars($row['category_name']); ?></span>
                        </td>

                        <td><?php echo htmlspecialchars($row['description']); ?></td>

                        <td class="text-end text-danger fw-semibold">
                            ₹ <?php echo number_format($row['amount'],2); ?>
                        </td>
                    </tr>
                <?php endwhile; ?>
                </tbody>
            </table>
        </div>
    </div>

</div>
```

</div>

<script>
// ===== INCOME VS EXPENSE =====
new Chart(document.getElementById('financeChart'),{
    type:'line',
    data:{
        labels:['Jan','Feb','Mar','Apr','May','Jun','Jul'],
        datasets:[
            {
                label:'Income',
                data:[42000,46000,45000,52000,50000,56000,<?php echo $current_month_income ?: 60000; ?>],
                borderColor:'#22c55e',
                backgroundColor:'rgba(34,197,94,.15)',
                fill:true,
                tension:.4
            },
            {
                label:'Expenses',
                data:[18000,22000,20000,25000,24000,27000,<?php echo $current_month_expense ?: 30000; ?>],
                borderColor:'#ef4444',
                backgroundColor:'rgba(239,68,68,.12)',
                fill:true,
                tension:.4
            }
        ]
    },
    options:{
        responsive:true,
        plugins:{
            legend:{ labels:{ color:'#e5e7eb' } }
        },
        scales:{
            x:{
                ticks:{ color:'#c7d2fe' },
                grid:{ color:'rgba(255,255,255,.06)' }
            },
            y:{
                ticks:{ color:'#c7d2fe' },
                grid:{ color:'rgba(255,255,255,.06)' }
            }
        }
    }
});

// ===== CATEGORY DOUGHNUT =====
new Chart(document.getElementById('categoryChart'),{
    type:'doughnut',
    data:{
        labels: <?php echo json_encode($cat_labels); ?>,
        datasets:[{
            data: <?php echo json_encode($cat_values); ?>,
            backgroundColor:[
                '#2563eb','#16a34a','#f59e0b','#dc2626',
                '#7c3aed','#06b6d4','#ea580c','#059669'
            ],
            borderWidth:0
        }]
    },
    options:{
        responsive:true,
        plugins:{
            legend:{
                position:'bottom',
                labels:{ color:'#e5e7eb' }
            }
        },
        cutout:'68%'
    }
});

// ===== NOTIFICATION ACTION =====
function markAllRead(){
    const badge=document.getElementById('notifCount');
    if(badge) badge.style.display='none';

    document.querySelectorAll('.new-badge').forEach(b=>b.remove());

    const unread=document.getElementById('unreadText');
    if(unread) unread.innerText='0 unread';
}
</script>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>

</body>
</html>