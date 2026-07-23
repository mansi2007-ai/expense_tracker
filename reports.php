<?php
session_start();
include 'db.php';

if (!isset($_SESSION['user_id'])) {
    header('Location: login.php');
    exit();
}

$user_id = $_SESSION['user_id'];
$fullname = $_SESSION['fullname'];

// Selected month
$selected_month = $_GET['month'] ?? date('Y-m');
$year  = date('Y', strtotime($selected_month));
$month = date('m', strtotime($selected_month));

// ===== TOTALS =====
$income_q = $conn->query("SELECT COALESCE(SUM(amount),0) total
                          FROM income
                          WHERE user_id=$user_id
                          AND MONTH(income_date)=$month
                          AND YEAR(income_date)=$year");
$total_income = $income_q->fetch_assoc()['total'];

$expense_q = $conn->query("SELECT COALESCE(SUM(amount),0) total
                           FROM expenses
                           WHERE user_id=$user_id
                           AND MONTH(expense_date)=$month
                           AND YEAR(expense_date)=$year");
$total_expense = $expense_q->fetch_assoc()['total'];

$balance = $total_income - $total_expense;
$savings_rate = ($total_income > 0) ? round(($balance / $total_income) * 100) : 0;

// ===== CATEGORY DATA =====
$cat_q = $conn->query("
    SELECT c.category_name, SUM(e.amount) AS total
    FROM expenses e
    JOIN categories c ON e.category_id = c.category_id
    WHERE e.user_id=$user_id
      AND MONTH(e.expense_date)=$month
      AND YEAR(e.expense_date)=$year
    GROUP BY c.category_name
    ORDER BY total DESC
");

$labels = [];
$values = [];
$category_rows = [];

while($row = $cat_q->fetch_assoc()){
    $labels[] = $row['category_name'];
    $values[] = $row['total'];
    $category_rows[] = $row;
}

// ===== TOP CATEGORY =====
$top_category = $category_rows[0]['category_name'] ?? 'N/A';
$top_amount   = $category_rows[0]['total'] ?? 0;
?>

<!DOCTYPE html>

<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1">
<title>Creative Reports - Finora</title>

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
    border-radius:26px;
    box-shadow:0 12px 40px rgba(0,0,0,.28);
}

.hero{
    position:relative;
    overflow:hidden;
    padding:28px;
}

.hero::before{
    content:'';
    position:absolute;
    width:280px;height:280px;
    border-radius:50%;
    background:radial-gradient(circle,#60a5fa55,transparent 70%);
    top:-80px; right:-40px;
}

.kpi{
    padding:22px;
    position:relative;
    overflow:hidden;
    min-height:150px;
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

.kpi-income{ background:linear-gradient(135deg,#0f5132,#16a34a); }
.kpi-expense{ background:linear-gradient(135deg,#7f1d1d,#dc2626); }
.kpi-balance{ background:linear-gradient(135deg,#1e3a8a,#2563eb); }
.kpi-savings{ background:linear-gradient(135deg,#78350f,#f59e0b); }

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

.insight{
    border-left:4px solid var(--cyan);
    padding:14px 16px;
    background:rgba(6,182,212,.08);
    border-radius:14px;
}

.table-darkish{
    --bs-table-bg: transparent;
    --bs-table-color: var(--text);
    --bs-table-border-color: #243056;
    --bs-table-striped-bg: rgba(255,255,255,.03);
    --bs-table-hover-bg: rgba(255,255,255,.05);
}

.form-control{
    background:#0f172a;
    border:1px solid #334155;
    color:#fff;
    border-radius:12px;
}

.form-control:focus{
    background:#0f172a;
    color:#fff;
    border-color:#60a5fa;
    box-shadow:0 0 0 .2rem rgba(96,165,250,.15);
}

@media print{
    .sidebar,.topbar,.no-print{ display:none; }
    .main{ margin:0; }
    body{ background:#fff; color:#000; }
    .glass{ background:#fff; border:1px solid #ddd; box-shadow:none; }
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
<a href="transactions.php"><i class="bi bi-arrow-left-right me-2"></i>Transactions</a>
<a href="reports.php" class="active"><i class="bi bi-bar-chart me-2"></i>Reports</a>
<a href="logout.php" class="text-danger"><i class="bi bi-box-arrow-right me-2"></i>Logout</a>
```

</div>

<!-- MAIN -->

<div class="main">

```
<div class="topbar d-flex justify-content-between align-items-center">
    <div>
        <h3 class="mb-0">Financial Analytics</h3>
        <small class="text-secondary">Executive summary and spending intelligence</small>
    </div>

    <div class="d-flex align-items-center gap-3">
        <span class="fw-semibold"><?php echo htmlspecialchars($fullname); ?></span>
        <img src="https://ui-avatars.com/api/?name=<?php echo urlencode($fullname); ?>&background=2563eb&color=fff"
             width="42" height="42" class="rounded-circle">
    </div>
</div>

<div class="container-fluid p-4">

    <!-- HERO -->
    <div class="glass hero mb-4">
        <div class="d-flex flex-wrap align-items-center gap-3">
            <div>
                <div class="text-uppercase text-info fw-semibold small">Executive Financial Report</div>
                <h2 class="fw-bold mb-2"><?php echo date('F Y', strtotime($selected_month)); ?> Performance</h2>
                <p class="text-light-emphasis mb-0">
                    Income of ₹ <?php echo number_format($total_income,0); ?> and expenses of
                    ₹ <?php echo number_format($total_expense,0); ?> generated a net balance of
                    ₹ <?php echo number_format($balance,0); ?>.
                </p>
            </div>

            <div class="ms-auto d-flex gap-2 no-print">
                <button class="btn btn-outline-light" onclick="window.print()">
                    <i class="bi bi-printer me-2"></i>Print
                </button>
                <button class="btn btn-primary" onclick="exportCSV()">
                    <i class="bi bi-download me-2"></i>Export CSV
                </button>
            </div>
        </div>
    </div>

    <!-- FILTER -->
    <div class="glass p-4 mb-4 no-print">
        <form method="GET" class="row g-3 align-items-end">
            <div class="col-md-4">
                <label class="form-label">Select Month</label>
                <input type="month" name="month" value="<?php echo $selected_month; ?>" class="form-control">
            </div>

            <div class="col-md-4 d-grid">
                <button class="btn btn-primary">
                    <i class="bi bi-funnel me-2"></i>Apply Filter
                </button>
            </div>

            <div class="col-md-4 text-md-end">
                <div class="text-secondary small">Report generated on</div>
                <div class="fw-semibold"><?php echo date('d M Y, h:i A'); ?></div>
            </div>
        </form>
    </div>

    <!-- KPI CARDS -->
    <div class="row g-4 mb-4">
        <div class="col-lg-3 col-md-6">
            <div class="glass kpi kpi-income">
                <i class="bi bi-arrow-down-left-circle icon"></i>
                <div class="text-white-50">Total Income</div>
                <h2 class="fw-bold mt-2">₹ <?php echo number_format($total_income,2); ?></h2>
                <small class="text-white-50">All income sources</small>
            </div>
        </div>

        <div class="col-lg-3 col-md-6">
            <div class="glass kpi kpi-expense">
                <i class="bi bi-arrow-up-right-circle icon"></i>
                <div class="text-white-50">Total Expenses</div>
                <h2 class="fw-bold mt-2">₹ <?php echo number_format($total_expense,2); ?></h2>
                <small class="text-white-50">Operational spending</small>
            </div>
        </div>

        <div class="col-lg-3 col-md-6">
            <div class="glass kpi kpi-balance">
                <i class="bi bi-wallet2 icon"></i>
                <div class="text-white-50">Net Balance</div>
                <h2 class="fw-bold mt-2">₹ <?php echo number_format($balance,2); ?></h2>
                <small class="text-white-50">Income minus expenses</small>
            </div>
        </div>

        <div class="col-lg-3 col-md-6">
            <div class="glass kpi kpi-savings">
                <i class="bi bi-piggy-bank icon"></i>
                <div class="text-white-50">Savings Rate</div>
                <h2 class="fw-bold mt-2"><?php echo $savings_rate; ?>%</h2>
                <small class="text-white-50">Monthly retention ratio</small>
            </div>
        </div>
    </div>

    <!-- CHARTS -->
    <div class="row g-4 mb-4">

        <div class="col-xl-8">
            <div class="glass p-4 h-100">
                <div class="d-flex justify-content-between align-items-center mb-3">
                    <div>
                        <h5 class="mb-0">Income vs Expense Trend</h5>
                        <small class="text-secondary">Comparative monthly movement</small>
                    </div>
                    <span class="badge text-bg-primary">FY <?php echo $year; ?></span>
                </div>

                <canvas id="trendChart" height="120"></canvas>
            </div>
        </div>

        <div class="col-xl-4">
            <div class="glass p-4 h-100">
                <div class="d-flex justify-content-between align-items-center mb-3">
                    <h5 class="mb-0">Savings Health</h5>
                    <i class="bi bi-activity text-success fs-4"></i>
                </div>

                <div class="ring-wrap">
                    <div class="ring" style="--p:<?php echo max(5,min(95,$savings_rate)); ?>;"></div>

                    <div class="ring-center">
                        <div>
                            <div class="small text-secondary">Retention</div>
                            <div class="fs-2 fw-bold"><?php echo $savings_rate; ?>%</div>
                        </div>
                    </div>
                </div>

                <div class="text-center mt-4">
                    <div class="text-secondary small">Target savings rate</div>
                    <div class="fw-semibold fs-5">30%</div>
                </div>
            </div>
        </div>
    </div>

    <!-- CATEGORY + INSIGHTS -->
    <div class="row g-4 mb-4">

        <div class="col-lg-5">
            <div class="glass p-4 h-100">
                <div class="d-flex justify-content-between align-items-center mb-3">
                    <div>
                        <h5 class="mb-0">Expense Distribution</h5>
                        <small class="text-secondary">Category-wise allocation</small>
                    </div>
                    <i class="bi bi-pie-chart text-info fs-4"></i>
                </div>

                <canvas id="pieChart" height="260"></canvas>
            </div>
        </div>

        <div class="col-lg-7">
            <div class="glass p-4 h-100">
                <div class="d-flex justify-content-between align-items-center mb-3">
                    <div>
                        <h5 class="mb-0">Smart Insights</h5>
                        <small class="text-secondary">Automated observations</small>
                    </div>
                    <i class="bi bi-lightbulb text-warning fs-4"></i>
                </div>

                <div class="insight mb-3">
                    <div class="fw-semibold">Top spending category</div>
                    <div><?php echo htmlspecialchars($top_category); ?> — ₹ <?php echo number_format($top_amount,2); ?></div>
                </div>

                <div class="insight mb-3">
                    <div class="fw-semibold">Savings performance</div>
                    <div>
                        <?php if($savings_rate >= 30): ?>
                            Excellent! You saved <?php echo $savings_rate; ?>% of your income this month.
                        <?php elseif($savings_rate >= 15): ?>
                            Good progress. Consider reducing discretionary spending to improve savings.
                        <?php else: ?>
                            Savings are below the recommended 15%. Review recurring expenses.
                        <?php endif; ?>
                    </div>
                </div>

                <div class="insight">
                    <div class="fw-semibold">Cash flow status</div>
                    <div>
                        <?php echo ($balance >= 0)
                            ? 'Positive cash flow maintained throughout the month.'
                            : 'Expenses exceeded income. Immediate budget review is recommended.'; ?>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- CATEGORY TABLE -->
    <div class="glass p-4">
        <div class="d-flex justify-content-between align-items-center mb-3">
            <div>
                <h5 class="mb-0">Detailed Category Report</h5>
                <small class="text-secondary">Sorted by highest spending</small>
            </div>

            <span class="badge text-bg-primary"><?php echo count($category_rows); ?> categories</span>
        </div>

        <div class="table-responsive">
            <table class="table table-darkish table-hover align-middle" id="reportTable">
                <thead>
                    <tr>
                        <th>Category</th>
                        <th class="text-end">Amount (₹)</th>
                        <th class="text-end">Share</th>
                    </tr>
                </thead>

                <tbody>
                <?php if(count($category_rows) > 0): ?>
                    <?php foreach($category_rows as $row):
                        $share = ($total_expense > 0) ? round(($row['total'] / $total_expense) * 100, 1) : 0;
                    ?>
                    <tr>
                        <td class="fw-semibold"><?php echo htmlspecialchars($row['category_name']); ?></td>

                        <td class="text-end text-danger fw-semibold">
                            ₹ <?php echo number_format($row['total'],2); ?>
                        </td>

                        <td class="text-end">
                            <span class="badge text-bg-info"><?php echo $share; ?>%</span>
                        </td>
                    </tr>
                    <?php endforeach; ?>
                <?php else: ?>
                    <tr>
                        <td colspan="3" class="text-center py-5 text-secondary">
                            <i class="bi bi-inbox fs-1 d-block mb-3"></i>
                            No expenses found for this month.
                        </td>
                    </tr>
                <?php endif; ?>
                </tbody>

                <tfoot>
                    <tr>
                        <th>Total</th>
                        <th class="text-end">₹ <?php echo number_format($total_expense,2); ?></th>
                        <th class="text-end">100%</th>
                    </tr>
                </tfoot>
            </table>
        </div>
    </div>

</div>
```

</div>

<script>
// ===== TREND CHART =====
new Chart(document.getElementById('trendChart'),{
    type:'line',
    data:{
        labels:['Jan','Feb','Mar','Apr','May','Jun','Jul'],
        datasets:[
            {
                label:'Income',
                data:[42000,46000,45000,52000,50000,56000,<?php echo $total_income ?: 60000; ?>],
                borderColor:'#22c55e',
                backgroundColor:'rgba(34,197,94,.14)',
                fill:true,
                tension:.4,
                pointRadius:4,
                pointBackgroundColor:'#22c55e'
            },
            {
                label:'Expenses',
                data:[18000,22000,20000,25000,24000,27000,<?php echo $total_expense ?: 30000; ?>],
                borderColor:'#ef4444',
                backgroundColor:'rgba(239,68,68,.12)',
                fill:true,
                tension:.4,
                pointRadius:4,
                pointBackgroundColor:'#ef4444'
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

// ===== PIE CHART =====
new Chart(document.getElementById('pieChart'),{
    type:'doughnut',
    data:{
        labels: <?php echo json_encode($labels); ?>,
        datasets:[{
            data: <?php echo json_encode($values); ?>,
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

// ===== EXPORT CSV =====
function exportCSV(){
    let csv = [];
    const rows = document.querySelectorAll('#reportTable tr');

    rows.forEach(row => {
        let cols = row.querySelectorAll('th,td');
        let data = [];

        cols.forEach(col => {
            data.push('"'+col.innerText.replace(/"/g,'""')+'"');
        });

        csv.push(data.join(','));
    });

    const blob = new Blob([csv.join('\\n')], {type:'text/csv'});
    const link = document.createElement('a');

    link.download = 'financial_report_<?php echo $selected_month; ?>.csv';
    link.href = URL.createObjectURL(blob);
    link.click();
}
</script>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>

</body>
</html>