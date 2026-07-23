
<?php
session_start();
include 'db.php';

if(!isset($_SESSION['user_id'])){
    header('Location: login.php');
    exit();
}

$user_id = $_SESSION['user_id'];

// ===== ADD SAVING =====
if(isset($_POST['add_saving'])){

    $amount       = $_POST['amount'];
    $account_type = $_POST['account_type'];
    $goal_name    = $_POST['goal_name'];
    $notes        = $_POST['notes'];
    $saving_date  = $_POST['saving_date'];

    $stmt = $conn->prepare("INSERT INTO savings(user_id, amount, account_type, goal_name, notes, saving_date)
                            VALUES(?,?,?,?,?,?)");

    $stmt->bind_param(
        "idssss",
        $user_id,
        $amount,
        $account_type,
        $goal_name,
        $notes,
        $saving_date
    );

    if($stmt->execute()){
        $success = "Saving added successfully!";
    }else{
        $error = "Failed to add saving.";
    }
}

// ===== TOTAL SAVINGS =====
$total_q = $conn->query("SELECT COALESCE(SUM(amount),0) total FROM savings WHERE user_id=$user_id");
$total_savings = $total_q->fetch_assoc()['total'];

// ===== THIS MONTH SAVINGS =====
$month_q = $conn->query("SELECT COALESCE(SUM(amount),0) total
                         FROM savings
                         WHERE user_id=$user_id
                         AND MONTH(saving_date)=MONTH(CURDATE())
                         AND YEAR(saving_date)=YEAR(CURDATE())");
$this_month = $month_q->fetch_assoc()['total'];

// ===== RECENT SAVINGS =====
$recent = $conn->query("SELECT * FROM savings
                        WHERE user_id=$user_id
                        ORDER BY saving_date DESC
                        LIMIT 5");
?>

<!DOCTYPE html>

<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1">
<title>Savings - ExpenseTracker</title>

<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
<link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.css" rel="stylesheet">

<style>
body{
    background: radial-gradient(circle at top left,#14532d 0%,#07120d 40%,#03070a 100%);
    color:#fff;
    font-family:'Segoe UI',sans-serif;
    min-height:100vh;
}

.glass{
    background:rgba(17,24,39,.78);
    border:1px solid rgba(255,255,255,.08);
    backdrop-filter:blur(14px);
    border-radius:24px;
    box-shadow:0 12px 36px rgba(0,0,0,.35);
}

.form-control,.form-select{
    background:#0f172a;
    border:1px solid #334155;
    color:#fff;
    border-radius:14px;
    padding:12px 14px;
}

.form-control:focus,.form-select:focus{
    background:#0f172a;
    color:#fff;
    border-color:#22c55e;
    box-shadow:0 0 0 .2rem rgba(34,197,94,.15);
}

.summary-card{
    padding:24px;
    border-radius:22px;
    background:linear-gradient(135deg,rgba(34,197,94,.18),rgba(16,185,129,.08));
    border:1px solid rgba(34,197,94,.22);
}

.progress{
    height:10px;
    background:#1f2937;
}

.progress-bar{
    background:linear-gradient(90deg,#22c55e,#10b981);
}
</style>

</head>
<body>

<div class="container py-4">

```
<!-- HEADER -->
<div class="d-flex justify-content-between align-items-center mb-4 flex-wrap gap-3">
    <div>
        <h2 class="mb-0"><i class="bi bi-piggy-bank me-2 text-success"></i>Savings</h2>
        <small class="text-secondary">Track your savings and achieve your financial goals</small>
    </div>

    <a href="dashboard.php" class="btn btn-outline-light">
        <i class="bi bi-arrow-left me-2"></i>Back to Dashboard
    </a>
</div>

<?php if(isset($success)): ?>
    <div class="alert alert-success alert-dismissible fade show">
        <i class="bi bi-check-circle me-2"></i><?php echo $success; ?>
        <button class="btn-close" data-bs-dismiss="alert"></button>
    </div>
<?php endif; ?>

<?php if(isset($error)): ?>
    <div class="alert alert-danger alert-dismissible fade show">
        <i class="bi bi-x-circle me-2"></i><?php echo $error; ?>
        <button class="btn-close" data-bs-dismiss="alert"></button>
    </div>
<?php endif; ?>

<!-- SUMMARY -->
<div class="row g-4 mb-4">

    <div class="col-md-6">
        <div class="summary-card h-100">
            <div class="d-flex align-items-center justify-content-between">
                <div>
                    <div class="text-secondary">Total Savings</div>
                    <h2 class="mt-2 mb-0">₹ <?php echo number_format($total_savings,2); ?></h2>
                </div>
                <div class="fs-1 text-success"><i class="bi bi-wallet2"></i></div>
            </div>
        </div>
    </div>

    <div class="col-md-6">
        <div class="summary-card h-100">
            <div class="d-flex align-items-center justify-content-between">
                <div>
                    <div class="text-secondary">This Month</div>
                    <h2 class="mt-2 mb-0">₹ <?php echo number_format($this_month,2); ?></h2>
                </div>
                <div class="fs-1 text-success"><i class="bi bi-graph-up-arrow"></i></div>
            </div>
        </div>
    </div>

</div>

<div class="row g-4">

    <!-- ADD SAVING -->
    <div class="col-lg-5">
        <div class="glass p-4">

            <div class="d-flex align-items-center gap-2 mb-4">
                <i class="bi bi-plus-circle fs-4 text-success"></i>
                <div>
                    <h4 class="mb-0">Add Saving</h4>
                    <small class="text-secondary">Record a new saving entry</small>
                </div>
            </div>

            <form method="POST">

                <div class="mb-3">
                    <label class="form-label">Amount</label>
                    <div class="input-group">
                        <span class="input-group-text bg-dark text-white border-secondary">₹</span>
                        <input type="number" step="0.01" name="amount" class="form-control" required>
                    </div>
                </div>

                <div class="mb-3">
                    <label class="form-label">Account Type</label>
                    <select name="account_type" class="form-select" required>
                        <option value="">Select account</option>
                        <option>Bank Account</option>
                        <option>Cash</option>
                        <option>UPI Wallet</option>
                        <option>Fixed Deposit</option>
                        <option>Mutual Fund</option>
                        <option>Stocks</option>
                        <option>Emergency Fund</option>
                    </select>
                </div>

                <div class="mb-3">
                    <label class="form-label">Goal Name</label>
                    <input type="text" name="goal_name" class="form-control"
                           placeholder="Emergency Fund / New Laptop / Trip" required>
                </div>

                <div class="mb-3">
                    <label class="form-label">Date</label>
                    <input type="date" name="saving_date" class="form-control"
                           value="<?php echo date('Y-m-d'); ?>" required>
                </div>

                <div class="mb-3">
                    <label class="form-label">Notes</label>
                    <textarea name="notes" rows="3" class="form-control"
                              placeholder="Optional notes"></textarea>
                </div>

                <button type="submit" name="add_saving" class="btn btn-success w-100 py-2">
                    <i class="bi bi-piggy-bank me-2"></i>Add Saving
                </button>

            </form>
        </div>
    </div>

    <!-- GOALS + RECENT -->
    <div class="col-lg-7">

        <!-- GOAL PROGRESS -->
        <div class="glass p-4 mb-4">

            <div class="d-flex justify-content-between align-items-center mb-3">
                <h4 class="mb-0"><i class="bi bi-bullseye text-warning me-2"></i>Goal Progress</h4>
                <span class="badge bg-success">Live</span>
            </div>

            <?php
            $goal_target = 100000; // You can make this dynamic later
            $goal_percent = min(100, ($total_savings / $goal_target) * 100);
            ?>

            <div class="d-flex justify-content-between mb-2">
                <span>Target: ₹ <?php echo number_format($goal_target); ?></span>
                <span><?php echo round($goal_percent); ?>%</span>
            </div>

            <div class="progress mb-3">
                <div class="progress-bar" style="width: <?php echo $goal_percent; ?>%"></div>
            </div>

            <div class="small text-secondary">
                ₹ <?php echo number_format(max(0, $goal_target - $total_savings)); ?> remaining to reach your goal.
            </div>
        </div>

        <!-- RECENT SAVINGS -->
        <div class="glass p-4">

            <div class="d-flex justify-content-between align-items-center mb-3">
                <h4 class="mb-0"><i class="bi bi-clock-history text-info me-2"></i>Recent Savings</h4>
                <span class="badge bg-dark">Last 5</span>
            </div>

            <div class="table-responsive">
                <table class="table table-dark table-hover align-middle mb-0">
                    <thead>
                        <tr>
                            <th>Date</th>
                            <th>Goal</th>
                            <th>Account</th>
                            <th class="text-end">Amount</th>
                        </tr>
                    </thead>
                    <tbody>

                    <?php if($recent->num_rows > 0): ?>
                        <?php while($row = $recent->fetch_assoc()): ?>
                        <tr>
                            <td><?php echo date('d M Y', strtotime($row['saving_date'])); ?></td>
                            <td>
                                <div class="fw-semibold"><?php echo htmlspecialchars($row['goal_name']); ?></div>
                                <small class="text-secondary"><?php echo htmlspecialchars($row['notes']); ?></small>
                            </td>
                            <td><?php echo htmlspecialchars($row['account_type']); ?></td>
                            <td class="text-end text-success fw-bold">
                                +₹<?php echo number_format($row['amount'],2); ?>
                            </td>
                        </tr>
                        <?php endwhile; ?>

                    <?php else: ?>
                        <tr>
                            <td colspan="4" class="text-center text-secondary py-4">
                                <i class="bi bi-piggy-bank fs-2 d-block mb-2"></i>
                                No savings added yet.
                            </td>
                        </tr>
                    <?php endif; ?>

                    </tbody>
                </table>
            </div>

        </div>
    </div>

</div>
```

</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>

</body>
</html>