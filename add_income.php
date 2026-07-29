<?php
session_start();
include 'db.php';

if(!isset($_SESSION['user_id'])){
    header('Location: login.php');
    exit();
}

$user_id = $_SESSION['user_id'];
$fullname = $_SESSION['fullname'] ?? 'User';
$message = '';
$msg_type = 'success';
$edit_mode = false;
$edit_data = null;

// ===== DELETE INCOME =====
if(isset($_GET['delete'])){
    $income_id = (int)$_GET['delete'];

    $stmt = $conn->prepare("DELETE FROM income WHERE income_id=? AND user_id=?");
    $stmt->bind_param("ii", $income_id, $user_id);

    if($stmt->execute()){
        header("Location: add_income.php?deleted=1");
        exit();
    }
}

// ===== EDIT MODE =====
if(isset($_GET['edit'])){
    $income_id = (int)$_GET['edit'];

    $stmt = $conn->prepare("SELECT * FROM income WHERE income_id=? AND user_id=?");
    $stmt->bind_param("ii", $income_id, $user_id);
    $stmt->execute();
    $result = $stmt->get_result();

    if($result->num_rows > 0){
        $edit_data = $result->fetch_assoc();
        $edit_mode = true;
    }
}

// ===== SAVE / UPDATE =====
if($_SERVER['REQUEST_METHOD'] == 'POST'){

    $amount = (float)$_POST['amount'];
    $source = trim($_POST['source']);
    $description = trim($_POST['description']);
    $income_date = $_POST['income_date'];

    if($amount <= 0){
        $message = 'Amount must be greater than 0.';
        $msg_type = 'danger';
    } else {

        // UPDATE
        if(isset($_POST['income_id']) && $_POST['income_id'] != ''){

            $income_id = (int)$_POST['income_id'];

            $stmt = $conn->prepare("UPDATE income SET amount=?, source=?, description=?, income_date=? WHERE income_id=? AND user_id=?");

            $stmt->bind_param("dsssii", $amount, $source, $description, $income_date, $income_id, $user_id);

            if($stmt->execute()){
                header("Location: add_income.php?updated=1");
                exit();
            }

        } else {

            // INSERT
            $stmt = $conn->prepare("INSERT INTO income (user_id, amount, source, description, income_date) VALUES (?, ?, ?, ?, ?)");

            $stmt->bind_param("idsss", $user_id, $amount, $source, $description, $income_date);

            if($stmt->execute()){
                header("Location: add_income.php?added=1");
                exit();
            }
        }

        $message = 'Database operation failed.';
        $msg_type = 'danger';
    }
}

// ===== FILTER =====
$filter = $_GET['filter'] ?? '';
$sql = "SELECT * FROM income WHERE user_id=$user_id";
if($filter != ''){
    $safe = $conn->real_escape_string($filter);
    $sql .= " AND source='$safe'";
}
$sql .= " ORDER BY income_date DESC, income_id DESC";
$income_result = $conn->query($sql);

// ===== SUMMARY =====
$total_income = $conn->query("SELECT COALESCE(SUM(amount),0) total FROM income WHERE user_id=$user_id")->fetch_assoc()['total'];
$this_month = $conn->query("SELECT COALESCE(SUM(amount),0) total FROM income WHERE user_id=$user_id AND MONTH(income_date)=MONTH(CURDATE()) AND YEAR(income_date)=YEAR(CURDATE())")->fetch_assoc()['total'];
$entries = $conn->query("SELECT COUNT(*) c FROM income WHERE user_id=$user_id")->fetch_assoc()['c'];
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1">
<title>Finora • Income Manager</title>
<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
<link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.css" rel="stylesheet">
<style>
body{background:#0b1020;color:#eef2ff;font-family:'Segoe UI',sans-serif;}
.card-glass{background:rgba(18,25,51,.75);border:1px solid rgba(255,255,255,.08);backdrop-filter:blur(16px);border-radius:22px;}
.summary{padding:24px;border-radius:22px;color:#fff;box-shadow:0 18px 40px rgba(0,0,0,.28);}
.s1{background:linear-gradient(135deg,#2563eb,#7c3aed);}
.s2{background:linear-gradient(135deg,#059669,#10b981);}
.s3{background:linear-gradient(135deg,#ea580c,#f59e0b);}
.table-dark{--bs-table-bg:transparent;--bs-table-border-color:rgba(255,255,255,.08);}
.form-control,.form-select{background:#0f172a;border:1px solid #334155;color:#eef2ff;}
.form-control:focus,.form-select:focus{background:#0f172a;color:#fff;border-color:#60a5fa;box-shadow:none;}
</style>
</head>
<body>
<div class="container py-4">

<div class="d-flex justify-content-between align-items-center mb-4">
  <div>
    <h2 class="fw-bold mb-1"><i class="bi bi-cash-stack me-2 text-success"></i>Income Manager</h2>
    <div class="text-secondary">Welcome, <strong><?= htmlspecialchars($fullname) ?></strong></div>
  </div>
  <a href="dashboard.php" class="btn btn-outline-light rounded-pill px-4"><i class="bi bi-arrow-left me-2"></i>Dashboard</a>
</div>

<?php if(isset($_GET['added'])): ?>
  <div class="alert alert-success">Income added successfully!</div>
<?php endif; ?>
<?php if(isset($_GET['updated'])): ?>
  <div class="alert alert-info">Income updated successfully!</div>
<?php endif; ?>
<?php if(isset($_GET['deleted'])): ?>
  <div class="alert alert-warning">Income deleted successfully!</div>
<?php endif; ?>

<div class="row g-4 mb-4">
  <div class="col-md-4"><div class="summary s1"><div class="text-uppercase small">Total Income</div><div class="display-6 fw-bold">₹<?= number_format($total_income,2) ?></div></div></div>
  <div class="col-md-4"><div class="summary s2"><div class="text-uppercase small">This Month</div><div class="display-6 fw-bold">₹<?= number_format($this_month,2) ?></div></div></div>
  <div class="col-md-4"><div class="summary s3"><div class="text-uppercase small">Entries</div><div class="display-6 fw-bold"><?= $entries ?></div></div></div>
</div>

<div class="card-glass p-4 mb-4">
  <h4 class="fw-bold mb-3"><?= $edit_mode ? 'Edit Income' : 'Add New Income' ?></h4>
  <form method="POST">
    <input type="hidden" name="income_id" value="<?= $edit_data['income_id'] ?? '' ?>">
    <div class="row g-3">
      <div class="col-md-6">
        <label class="form-label">Amount (₹)</label>
        <input type="number" step="0.01" name="amount" class="form-control" value="<?= $edit_data['amount'] ?? '' ?>" required>
      </div>
      <div class="col-md-6">
        <label class="form-label">Income Date</label>
        <input type="date" name="income_date" class="form-control" value="<?= $edit_data['income_date'] ?? date('Y-m-d') ?>" required>
      </div>
      <div class="col-md-6">
        <label class="form-label">Source</label>
        <select name="source" class="form-select" required>
          <option value="">Select source</option>
          <?php $src = $edit_data['source'] ?? ''; ?>
          <option value="Salary" <?= $src=='Salary'?'selected':'' ?>>Salary</option>
          <option value="Business" <?= $src=='Business'?'selected':'' ?>>Business</option>
          <option value="Freelance" <?= $src=='Freelance'?'selected':'' ?>>Freelance</option>
          <option value="Investment" <?= $src=='Investment'?'selected':'' ?>>Investment</option>
          <option value="Other" <?= $src=='Other'?'selected':'' ?>>Other</option>
        </select>
      </div>
      <div class="col-md-6">
        <label class="form-label">Reference / Note</label>
        <input type="text" name="description" class="form-control" value="<?= htmlspecialchars($edit_data['description'] ?? '') ?>">
      </div>
    </div>
    <div class="mt-4 d-flex gap-2">
      <button type="submit" class="btn btn-success px-4 rounded-pill">
        <i class="bi bi-check2-circle me-2"></i><?= $edit_mode ? 'Update Income' : 'Save Income' ?>
      </button>
      <?php if($edit_mode): ?>
        <a href="add_income.php" class="btn btn-outline-light rounded-pill">Cancel</a>
      <?php endif; ?>
    </div>
  </form>
</div>

<div class="card-glass p-4">
  <div class="d-flex justify-content-between align-items-center mb-3">
    <h4 class="fw-bold mb-0">Recent Income Records</h4>
    <form class="d-flex gap-2" method="GET">
      <select name="filter" class="form-select form-select-sm">
        <option value="">All Sources</option>
        <option value="Salary" <?= $filter=='Salary'?'selected':'' ?>>Salary</option>
        <option value="Business" <?= $filter=='Business'?'selected':'' ?>>Business</option>
        <option value="Freelance" <?= $filter=='Freelance'?'selected':'' ?>>Freelance</option>
        <option value="Investment" <?= $filter=='Investment'?'selected':'' ?>>Investment</option>
        <option value="Other" <?= $filter=='Other'?'selected':'' ?>>Other</option>
      </select>
      <button class="btn btn-primary btn-sm">Apply</button>
    </form>
  </div>

  <div class="table-responsive">
    <table class="table table-dark table-hover align-middle">
      <thead>
        <tr>
          <th>Date</th>
          <th>Source</th>
          <th>Description</th>
          <th class="text-end">Amount</th>
          <th class="text-center">Actions</th>
        </tr>
      </thead>
      <tbody>
      <?php if($income_result->num_rows > 0): while($row = $income_result->fetch_assoc()): ?>
        <tr>
          <td><?= date('d M Y', strtotime($row['income_date'])) ?></td>
          <td><span class="badge bg-primary-subtle text-primary-emphasis"><?= htmlspecialchars($row['source']) ?></span></td>
          <td><?= htmlspecialchars($row['description']) ?: '<span class="text-secondary">No description</span>' ?></td>
          <td class="text-end fw-bold text-success">₹<?= number_format($row['amount'],2) ?></td>
          <td class="text-center">
            <a href="add_income.php?edit=<?= $row['income_id'] ?>" class="btn btn-sm btn-warning rounded-pill">
              <i class="bi bi-pencil-square me-1"></i>Edit
            </a>
            <a href="add_income.php?delete=<?= $row['income_id'] ?>"
               class="btn btn-sm btn-danger rounded-pill"
               onclick="return confirm('Delete this income record?')">
              <i class="bi bi-trash3 me-1"></i>Delete
            </a>
          </td>
        </tr>
      <?php endwhile; else: ?>
        <tr><td colspan="5" class="text-center text-secondary py-4">No income records found.</td></tr>
      <?php endif; ?>
      </tbody>
    </table>
  </div>
</div>

</div>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>