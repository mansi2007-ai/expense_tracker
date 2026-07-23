<?php
session_start();
include 'db.php';

// Check login
if (!isset($_SESSION['user_id'])) {
    header('Location: login.php');
    exit();
}

$user_id = $_SESSION['user_id'];
$fullname = $_SESSION['fullname'];
$message = "";
$msg_type = "success";

// Save income
if ($_SERVER['REQUEST_METHOD'] == 'POST') {

    $amount = $_POST['amount'];
    $source = trim($_POST['source']);
    $description = trim($_POST['description']);
    $income_date = $_POST['income_date'];

    if ($amount <= 0) {
        $message = "Amount must be greater than 0";
        $msg_type = "danger";
    } else {

        $stmt = $conn->prepare("INSERT INTO income (user_id, amount, source, description, income_date) VALUES (?, ?, ?, ?, ?)");

        $stmt->bind_param("idsss", $user_id, $amount, $source, $description, $income_date);

        if ($stmt->execute()) {
            $message = "Income added successfully!";
        } else {
            $message = "Error: " . $stmt->error;
            $msg_type = "danger";
        }
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Add Income</title>

    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.css" rel="stylesheet">

    <style>
        body{
            background:#f4f7fb;
            font-family:'Segoe UI',sans-serif;
        }

        .sidebar{
            width:250px;
            min-height:100vh;
            background:#111827;
            position:fixed;
        }

        .sidebar .brand{
            color:#fff;
            font-size:24px;
            font-weight:700;
            padding:24px 20px;
        }

        .sidebar a{
            color:#cbd5e1;
            text-decoration:none;
            display:block;
            padding:12px 20px;
            margin:6px 10px;
            border-radius:12px;
        }

        .sidebar a:hover,.sidebar a.active{
            background:#2563eb;
            color:#fff;
        }

        .main{
            margin-left:250px;
        }

        .topbar{
            background:#fff;
            padding:16px 24px;
            border-bottom:1px solid #e5e7eb;
        }

        .card-modern{
            border:none;
            border-radius:22px;
            box-shadow:0 10px 30px rgba(15,23,42,.06);
        }

        .form-control,.form-select{
            border-radius:12px;
            padding:12px;
        }

        .btn-save{
            border-radius:12px;
            padding:12px 18px;
            font-weight:600;
        }
    </style>
</head>
<body>

<!-- Sidebar -->
<div class="sidebar">
    <div class="brand"><i class="bi bi-wallet2 me-2"></i>Finora</div>

    <a href="dashboard.php"><i class="bi bi-speedometer2 me-2"></i>Dashboard</a>
    <a href="add_income.php" class="active"><i class="bi bi-plus-circle me-2"></i>Add Income</a>
    <a href="add_expense.php"><i class="bi bi-dash-circle me-2"></i>Add Expense</a>
    <a href="transactions.php"><i class="bi bi-arrow-left-right me-2"></i>Transactions</a>
    <a href="reports.php"><i class="bi bi-bar-chart me-2"></i>Reports</a>
    <a href="logout.php" class="text-danger"><i class="bi bi-box-arrow-right me-2"></i>Logout</a>
</div>

<!-- Main content -->
<div class="main">

    <div class="topbar d-flex justify-content-between align-items-center">
        <div>
            <h3 class="mb-0">Add Income</h3>
            <small class="text-muted">Record a new income entry</small>
        </div>

        <div class="d-flex align-items-center gap-3">
            <span class="fw-semibold"><?= htmlspecialchars($fullname) ?></span>
            <img src="https://ui-avatars.com/api/?name=<?= urlencode($fullname) ?>&background=16a34a&color=fff" width="42" height="42" class="rounded-circle">
        </div>
    </div>

    <div class="container-fluid p-4">

        <?php if($message != ""): ?>
            <div class="alert alert-<?= $msg_type ?> alert-dismissible fade show">
                <i class="bi bi-info-circle me-2"></i><?= $message ?>
                <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
            </div>
        <?php endif; ?>

        <div class="row justify-content-center">
            <div class="col-lg-8">

                <div class="card card-modern">
                    <div class="card-body p-4">

                        <form method="POST">

                            <div class="row g-4">

                                <div class="col-md-6">
                                    <label class="form-label fw-semibold">Amount (₹)</label>
                                    <div class="input-group">
                                        <span class="input-group-text">₹</span>
                                        <input type="number" step="0.01" min="1" name="amount" class="form-control" placeholder="Enter amount" required>
                                    </div>
                                </div>

                                <div class="col-md-6">
                                    <label class="form-label fw-semibold">Income Date</label>
                                    <input type="date" name="income_date" class="form-control" value="<?= date('Y-m-d') ?>" required>
                                </div>

                                <div class="col-md-6">
                                    <label class="form-label fw-semibold">Income Source</label>
                                    <select name="source" class="form-select" required>
                                        <option value="">Select source</option>
                                        <option value="Salary">Salary</option>
                                        <option value="Business">Business</option>
                                        <option value="Freelancing">Freelancing</option>
                                        <option value="Investment">Investment</option>
                                        <option value="Rental">Rental Income</option>
                                        <option value="Gift">Gift / Bonus</option>
                                        <option value="Other">Other</option>
                                    </select>
                                </div>

                                <div class="col-md-6">
                                    <label class="form-label fw-semibold">Reference / Note</label>
                                    <input type="text" class="form-control" placeholder="Salary for July / Client payment / etc.">
                                </div>

                                <div class="col-12">
                                    <label class="form-label fw-semibold">Description</label>
                                    <textarea name="description" rows="4" class="form-control" placeholder="Enter income details (optional)"></textarea>
                                </div>

                            </div>

                            <div class="d-flex justify-content-between align-items-center mt-4">
                                <a href="dashboard.php" class="btn btn-outline-secondary btn-save">
                                    <i class="bi bi-arrow-left me-2"></i>Back to Dashboard
                                </a>

                                <button type="submit" class="btn btn-success btn-save">
                                    <i class="bi bi-save me-2"></i>Save Income
                                </button>
                            </div>

                        </form>

                    </div>
                </div>

            </div>
        </div>

    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>