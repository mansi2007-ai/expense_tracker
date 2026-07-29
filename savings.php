<?php
session_start();
include 'db.php';

// ==============================
// LOGIN CHECK
// ==============================

if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit();
}

$user_id = $_SESSION['user_id'];
$fullname = $_SESSION['fullname'] ?? "User";

$error = "";
$edit_mode = false;
$edit_data = [];


// ==============================
// DELETE GOAL
// ==============================

if (isset($_GET['delete'])) {

    $goal_id = (int)$_GET['delete'];

    $stmt = $conn->prepare("
        DELETE FROM savings
        WHERE goal_id=? AND user_id=?
    ");

    $stmt->bind_param("ii",$goal_id,$user_id);

    if($stmt->execute()){

        header("Location: savings.php?deleted=1");
        exit();

    }

}


// ==============================
// EDIT GOAL
// ==============================

if(isset($_GET['edit'])){

    $goal_id=(int)$_GET['edit'];

    $stmt=$conn->prepare("
        SELECT *
        FROM savings
        WHERE goal_id=? AND user_id=?
    ");

    $stmt->bind_param("ii",$goal_id,$user_id);

    $stmt->execute();

    $result=$stmt->get_result();

    if($result->num_rows>0){

        $edit_data=$result->fetch_assoc();

        $edit_mode=true;

    }

}


// ==============================
// SAVE / UPDATE GOAL
// ==============================

if($_SERVER["REQUEST_METHOD"]=="POST"){

    $goal_id=$_POST['goal_id'] ?? "";

    $goal_name=trim($_POST['goal_name']);

    $target_amount=(float)$_POST['target_amount'];

    $saved_amount=(float)$_POST['saved_amount'];

    $target_date=$_POST['target_date'];

    if($target_amount<=0){

        $error="Target amount must be greater than zero.";

    }

    elseif($saved_amount<0){

        $error="Saved amount cannot be negative.";

    }

    else{

        if($goal_id!=""){

            // UPDATE

            $goal_id=(int)$goal_id;

            $stmt=$conn->prepare("
                UPDATE savings
                SET
                goal_name=?,
                target_amount=?,
                saved_amount=?,
                target_date=?
                WHERE goal_id=? AND user_id=?
            ");

            $stmt->bind_param(
                "sddsii",
                $goal_name,
                $target_amount,
                $saved_amount,
                $target_date,
                $goal_id,
                $user_id
            );

            if($stmt->execute()){

                header("Location: savings.php?updated=1");
                exit();

            }

        }

        else{

            // INSERT

            $stmt=$conn->prepare("
                INSERT INTO savings
                (
                    user_id,
                    goal_name,
                    target_amount,
                    saved_amount,
                    target_date
                )

                VALUES(?,?,?,?,?)
            ");

            $stmt->bind_param(
                "isdds",
                $user_id,
                $goal_name,
                $target_amount,
                $saved_amount,
                $target_date
            );

            if($stmt->execute()){

                header("Location: savings.php?added=1");
                exit();

            }

        }

    }

}



// ==============================
// FETCH ALL GOALS
// ==============================

$stmt=$conn->prepare("
SELECT *
FROM savings
WHERE user_id=?
ORDER BY target_date ASC
");

$stmt->bind_param("i",$user_id);

$stmt->execute();

$goal_result=$stmt->get_result();



// ==============================
// SUMMARY
// ==============================

// Total Target

$stmt=$conn->prepare("
SELECT COALESCE(SUM(target_amount),0)
FROM savings
WHERE user_id=?
");

$stmt->bind_param("i",$user_id);

$stmt->execute();

$stmt->bind_result($total_target);

$stmt->fetch();

$stmt->close();



// Total Saved

$stmt=$conn->prepare("
SELECT COALESCE(SUM(saved_amount),0)
FROM savings
WHERE user_id=?
");

$stmt->bind_param("i",$user_id);

$stmt->execute();

$stmt->bind_result($total_saved);

$stmt->fetch();

$stmt->close();



// Goals Count

$stmt=$conn->prepare("
SELECT COUNT(*)
FROM savings
WHERE user_id=?
");

$stmt->bind_param("i",$user_id);

$stmt->execute();

$stmt->bind_result($goal_count);

$stmt->fetch();

$stmt->close();



// Overall Progress

$overall_progress=0;

if($total_target>0){

    $overall_progress=
    ($total_saved/$total_target)*100;

    if($overall_progress>100){

        $overall_progress=100;

    }

}

?>
<!DOCTYPE html>
<html lang="en">

<head>

<meta charset="UTF-8">

<meta name="viewport" content="width=device-width, initial-scale=1">

<title>Finora • Savings Goals</title>

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

min-height:100vh;

}

::-webkit-scrollbar{
width:8px;
}

::-webkit-scrollbar-thumb{

background:#7c3aed;

border-radius:20px;

}

/* Glass Card */

.card-glass{

background:rgba(18,25,51,.75);

border:1px solid rgba(255,255,255,.08);

backdrop-filter:blur(18px);

border-radius:24px;

box-shadow:0 20px 45px rgba(0,0,0,.35);

}

/* Summary Cards */

.summary{

padding:24px;

border-radius:24px;

color:#fff;

transition:.3s;

box-shadow:0 18px 35px rgba(0,0,0,.25);

}

.summary:hover{

transform:translateY(-6px);

}

.s1{

background:linear-gradient(135deg,#16a34a,#22c55e);

}

.s2{

background:linear-gradient(135deg,#2563eb,#3b82f6);

}

.s3{

background:linear-gradient(135deg,#7c3aed,#9333ea);

}

/* Progress */

.progress{

height:14px;

background:#1f2937;

border-radius:30px;

}

.progress-bar{

background:linear-gradient(90deg,#22c55e,#16a34a);

}

/* Form */

.form-control{

background:#111827;

border:1px solid #374151;

color:#fff;

border-radius:14px;

}

.form-control:focus{

background:#111827;

color:#fff;

border-color:#f59e0b;

box-shadow:none;

}

/* Table */

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

/* Buttons */

.btn-warning{

border:none;

color:#111827;

font-weight:600;

border-radius:50px;

}

.btn-outline-light{

border-radius:50px;

}

.page-title{

font-size:2rem;

font-weight:700;

}

.subtitle{

color:#94a3b8;

}

</style>

</head>

<body>

<div class="container py-4">


<!-- ===========================
        HEADER
=========================== -->

<div class="d-flex justify-content-between align-items-center flex-wrap mb-4">

<div>

<h2 class="page-title">

<i class="bi bi-piggy-bank-fill text-warning me-2"></i>

Savings Goals

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



<!-- ===========================
        ALERTS
=========================== -->

<?php if(isset($_GET['added'])): ?>

<div class="alert alert-success">

Goal added successfully.

</div>

<?php endif; ?>


<?php if(isset($_GET['updated'])): ?>

<div class="alert alert-info">

Goal updated successfully.

</div>

<?php endif; ?>


<?php if(isset($_GET['deleted'])): ?>

<div class="alert alert-warning">

Goal deleted successfully.

</div>

<?php endif; ?>


<?php if($error!=""): ?>

<div class="alert alert-danger">

<?= htmlspecialchars($error) ?>

</div>

<?php endif; ?>



<!-- ===========================
        SUMMARY CARDS
=========================== -->

<div class="row g-4 mb-4">

<div class="col-lg-4">

<div class="summary s1">

<small>Total Target</small>

<h2 class="fw-bold mt-2">

₹<?= number_format($total_target,2) ?>

</h2>

</div>

</div>



<div class="col-lg-4">

<div class="summary s2">

<small>Total Saved</small>

<h2 class="fw-bold mt-2">

₹<?= number_format($total_saved,2) ?>

</h2>

</div>

</div>



<div class="col-lg-4">

<div class="summary s3">

<small>Goals Created</small>

<h2 class="fw-bold mt-2">

<?= $goal_count ?>

</h2>

</div>

</div>

</div>



<!-- ===========================
        OVERALL PROGRESS
=========================== -->

<div class="card-glass p-4 mb-4">

<div class="d-flex justify-content-between mb-3">

<h5 class="fw-bold mb-0">

Overall Savings Progress

</h5>

<strong>

<?= number_format($overall_progress,1) ?>%

</strong>

</div>

<div class="progress">

<div

class="progress-bar"

style="width:<?= $overall_progress ?>%;">

</div>

</div>

</div>
<!-- =======================================
        ADD / EDIT SAVINGS GOAL
======================================= -->

<div class="card-glass p-4 mb-4">

    <h4 class="fw-bold mb-4">

        <i class="bi bi-plus-circle-fill text-warning me-2"></i>

        <?= $edit_mode ? "Edit Savings Goal" : "Create Savings Goal"; ?>

    </h4>

    <form method="POST">

        <input type="hidden"
               name="goal_id"
               value="<?= $edit_data['goal_id'] ?? '' ?>">

        <div class="row g-3">

            <div class="col-md-6">

                <label class="form-label">Goal Name</label>

                <input
                    type="text"
                    name="goal_name"
                    class="form-control"
                    required
                    value="<?= htmlspecialchars($edit_data['goal_name'] ?? '') ?>"
                >

            </div>

            <div class="col-md-3">

                <label class="form-label">Target Amount</label>

                <input
                    type="number"
                    step="0.01"
                    name="target_amount"
                    class="form-control"
                    required
                    value="<?= $edit_data['target_amount'] ?? '' ?>"
                >

            </div>

            <div class="col-md-3">

                <label class="form-label">Saved Amount</label>

                <input
                    type="number"
                    step="0.01"
                    name="saved_amount"
                    class="form-control"
                    required
                    value="<?= $edit_data['saved_amount'] ?? 0 ?>"
                >

            </div>

            <div class="col-md-6">

                <label class="form-label">Target Date</label>

                <input
                    type="date"
                    name="target_date"
                    class="form-control"
                    required
                    value="<?= $edit_data['target_date'] ?? '' ?>"
                >

            </div>

        </div>

        <div class="mt-4">

            <button class="btn btn-warning px-4">

                <i class="bi bi-check-circle me-2"></i>

                <?= $edit_mode ? "Update Goal" : "Save Goal"; ?>

            </button>

            <?php if($edit_mode): ?>

                <a href="savings.php"
                   class="btn btn-outline-light ms-2">

                    Cancel

                </a>

            <?php endif; ?>

        </div>

    </form>

</div>



<!-- =======================================
        SAVINGS GOALS TABLE
======================================= -->

<div class="card-glass p-4">

    <h4 class="fw-bold mb-4">

        <i class="bi bi-list-check text-warning me-2"></i>

        Savings Goals

    </h4>

    <div class="table-responsive">

        <table class="table table-dark table-hover align-middle">

            <thead>

                <tr>

                    <th>Goal</th>

                    <th>Target</th>

                    <th>Saved</th>

                    <th width="220">Progress</th>

                    <th>Target Date</th>

                    <th class="text-center">Actions</th>

                </tr>

            </thead>

            <tbody>

            <?php if($goal_result->num_rows>0): ?>

                <?php while($row=$goal_result->fetch_assoc()): ?>

                <?php

                    $progress=0;

                    if($row['target_amount']>0){

                        $progress=
                        ($row['saved_amount']/$row['target_amount'])*100;

                        if($progress>100){

                            $progress=100;

                        }

                    }

                ?>

                <tr>

                    <td>

                        <strong>

                            <?= htmlspecialchars($row['goal_name']) ?>

                        </strong>

                    </td>

                    <td>

                        ₹<?= number_format($row['target_amount'],2) ?>

                    </td>

                    <td>

                        ₹<?= number_format($row['saved_amount'],2) ?>

                    </td>

                    <td>

                        <div class="progress mb-2">

                            <div class="progress-bar"

                                 style="width:<?= $progress ?>%;">

                            </div>

                        </div>

                        <small>

                            <?= number_format($progress,1) ?>%

                        </small>

                    </td>

                    <td>

                        <?= date("d M Y",strtotime($row['target_date'])) ?>

                    </td>

                    <td class="text-center">

                        <a href="savings.php?edit=<?= $row['goal_id'] ?>"

                           class="btn btn-sm btn-warning text-dark rounded-pill">

                            <i class="bi bi-pencil-square"></i>

                        </a>

                        <a href="savings.php?delete=<?= $row['goal_id'] ?>"

                           class="btn btn-sm btn-danger rounded-pill"

                           onclick="return confirm('Delete this goal?')">

                            <i class="bi bi-trash"></i>

                        </a>

                    </td>

                </tr>

                <?php endwhile; ?>

            <?php else: ?>

                <tr>

                    <td colspan="6"

                        class="text-center py-5 text-secondary">

                        <i class="bi bi-piggy-bank display-5 d-block mb-3"></i>

                        No savings goals created yet.

                    </td>

                </tr>

            <?php endif; ?>

            </tbody>

        </table>

    </div>

</div>
</div>

<!-- =======================================
                FOOTER
======================================= -->

<footer class="text-center text-secondary py-4 mt-5">

    <hr class="border-secondary">

    <p class="mb-0">

        <i class="bi bi-piggy-bank-fill text-warning"></i>

        © <?= date('Y'); ?> <strong>Finora</strong> | Smart Savings Manager

    </p>

</footer>



<!-- =======================================
            BOOTSTRAP JS
======================================= -->

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>



<!-- =======================================
        AUTO HIDE ALERTS
======================================= -->

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



<!-- =======================================
        DELETE CONFIRMATION
======================================= -->

<script>

document.querySelectorAll(".btn-danger").forEach(function(btn){

    btn.addEventListener("click",function(e){

        if(!confirm("Are you sure you want to delete this savings goal?")){

            e.preventDefault();

        }

    });

});

</script>



<!-- =======================================
        PROGRESS BAR ANIMATION
======================================= -->

<script>

window.addEventListener("load",function(){

    document.querySelectorAll(".progress-bar").forEach(function(bar){

        let width = bar.style.width;

        bar.style.width = "0%";

        setTimeout(function(){

            bar.style.transition = "width 1.2s ease";

            bar.style.width = width;

        },200);

    });

});

</script>



<!-- =======================================
        TOOLTIPS
======================================= -->

<script>

const tooltipTriggerList = [].slice.call(document.querySelectorAll('[data-bs-toggle="tooltip"]'));

tooltipTriggerList.map(function (tooltipTriggerEl) {

    return new bootstrap.Tooltip(tooltipTriggerEl);

});

</script>

</body>

</html>