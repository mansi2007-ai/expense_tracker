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


// =========================
// CREATE NOTIFICATIONS
// =========================

$notifications = [];


// ---------- Latest Income ----------

$income = $conn->query("
SELECT source,amount,income_date
FROM income
WHERE user_id=$user_id
ORDER BY income_date DESC
LIMIT 5
");

while($row = $income->fetch_assoc()){

    $notifications[] = [

        "icon"=>"bi-cash-stack",

        "color"=>"success",

        "title"=>"Income Added",

        "message"=>"₹".number_format($row['amount'],2)." received from ".$row['source'],

        "date"=>$row['income_date']

    ];

}


// ---------- Latest Expenses ----------

$expense = $conn->query("
SELECT category,amount,expense_date
FROM expenses
WHERE user_id=$user_id
ORDER BY expense_date DESC
LIMIT 5
");

while($row = $expense->fetch_assoc()){

    $notifications[] = [

        "icon"=>"bi-wallet2",

        "color"=>"danger",

        "title"=>"Expense Recorded",

        "message"=>"₹".number_format($row['amount'],2)." spent on ".$row['category'],

        "date"=>$row['expense_date']

    ];

}


// ---------- Savings Goals ----------

$saving = $conn->query("
SELECT goal_name,target_amount,saved_amount
FROM savings
WHERE user_id=$user_id
");

while($row = $saving->fetch_assoc()){

    $percentage = 0;

    if($row['target_amount']>0){

        $percentage = ($row['saved_amount']/$row['target_amount'])*100;

    }

    if($percentage >= 100){

        $notifications[] = [

            "icon"=>"bi-trophy-fill",

            "color"=>"warning",

            "title"=>"Goal Completed",

            "message"=>"Congratulations! '".$row['goal_name']."' has reached its target.",

            "date"=>date("Y-m-d")

        ];

    }
    elseif($percentage >= 75){

        $notifications[] = [

            "icon"=>"bi-piggy-bank-fill",

            "color"=>"primary",

            "title"=>"Goal Almost Complete",

            "message"=>"".$row['goal_name']." is ".round($percentage)."% completed.",

            "date"=>date("Y-m-d")

        ];

    }

}


// =========================
// SORT BY DATE
// =========================

usort($notifications,function($a,$b){

    return strtotime($b['date']) - strtotime($a['date']);

});


// =========================
// TOTAL
// =========================

$total_notifications = count($notifications);

?>
<!DOCTYPE html>
<html lang="en">

<head>

<meta charset="UTF-8">

<meta name="viewport" content="width=device-width, initial-scale=1">

<title>Finora • Notifications</title>

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

/* Sidebar */

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

font-weight:500;

transition:.3s;

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

/* Main */

.main{

margin-left:270px;

padding:30px;

}

/* Topbar */

.topbar{

display:flex;

justify-content:space-between;

align-items:center;

margin-bottom:30px;

}

.glass{

background:rgba(18,25,51,.78);

border:1px solid rgba(255,255,255,.08);

backdrop-filter:blur(18px);

border-radius:20px;

padding:25px;

box-shadow:0 20px 40px rgba(0,0,0,.30);

margin-bottom:20px;

}

.badge-count{

background:#ef4444;

padding:6px 12px;

border-radius:50px;

font-size:14px;

}

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

<!-- Sidebar -->

<div class="sidebar" id="sidebar">

<div class="profile-box">

<img src="uploads/profile/<?= htmlspecialchars($profile_image) ?>">

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

<a href="notification.php" class="active">
<i class="bi bi-bell-fill"></i>
Notifications
</a>

<a href="logout.php">
<i class="bi bi-box-arrow-right"></i>
Logout
</a>

</div>

</div>

<!-- Main -->

<div class="main">

<div class="topbar">

<div class="d-flex align-items-center gap-3">

<button class="btn btn-warning d-lg-none"
onclick="toggleSidebar()">

<i class="bi bi-list"></i>

</button>

<div>

<h2 class="fw-bold mb-1">

<i class="bi bi-bell-fill text-warning me-2"></i>

Notifications

</h2>

<p class="text-secondary mb-0">

Stay updated with your latest financial activities.

</p>

</div>

</div>

<div>

<span class="badge-count">

<?= $total_notifications ?>

Notifications

</span>

</div>

</div>
<!-- ===========================
        NOTIFICATIONS LIST
=========================== -->

<div class="glass">

    <div class="d-flex justify-content-between align-items-center mb-4">

        <h4 class="fw-bold mb-0">

            <i class="bi bi-bell-fill text-warning me-2"></i>

            Recent Notifications

        </h4>

        <span class="badge bg-warning text-dark fs-6">

            <?= $total_notifications ?> Notification<?= $total_notifications != 1 ? 's' : '' ?>

        </span>

    </div>

    <?php if($total_notifications > 0): ?>

        <?php foreach($notifications as $notification): ?>

            <div class="card mb-3 border-0 shadow-sm"
                 style="background:rgba(255,255,255,.05); border-radius:18px;">

                <div class="card-body">

                    <div class="d-flex justify-content-between align-items-start">

                        <div class="d-flex">

                            <div
                                class="rounded-circle bg-<?= $notification['color'] ?> d-flex justify-content-center align-items-center me-3"
                                style="width:60px;height:60px;">

                                <i class="bi <?= $notification['icon'] ?> text-white fs-4"></i>

                            </div>

                            <div>

                                <h5 class="mb-1">

                                    <?= htmlspecialchars($notification['title']) ?>

                                </h5>

                                <p class="text-light mb-2">

                                    <?= htmlspecialchars($notification['message']) ?>

                                </p>

                                <small class="text-secondary">

                                    <i class="bi bi-calendar-event me-1"></i>

                                    <?= date('d M Y', strtotime($notification['date'])) ?>

                                </small>

                            </div>

                        </div>

                        <span class="badge bg-<?= $notification['color'] ?>">

                            New

                        </span>

                    </div>

                </div>

            </div>

        <?php endforeach; ?>

    <?php else: ?>

        <div class="text-center py-5">

            <i class="bi bi-bell-slash display-1 text-secondary"></i>

            <h4 class="mt-3">

                No Notifications

            </h4>

            <p class="text-secondary">

                You're all caught up! New income, expenses, and savings updates will appear here.

            </p>

        </div>

    <?php endif; ?>

</div>
<!-- ===========================
        NOTIFICATION SUMMARY
=========================== -->

<?php

$income_notifications = 0;
$expense_notifications = 0;
$saving_notifications = 0;

foreach($notifications as $n){

    if($n['title']=="Income Added"){
        $income_notifications++;
    }

    if($n['title']=="Expense Recorded"){
        $expense_notifications++;
    }

    if(
        $n['title']=="Goal Completed" ||
        $n['title']=="Goal Almost Complete"
    ){
        $saving_notifications++;
    }

}

?>

<div class="row g-4 mt-2 mb-4">

    <div class="col-md-4">

        <div class="glass text-center">

            <i class="bi bi-cash-stack text-success display-5"></i>

            <h2 class="mt-3"><?= $income_notifications ?></h2>

            <p class="text-secondary mb-0">
                Income Notifications
            </p>

        </div>

    </div>

    <div class="col-md-4">

        <div class="glass text-center">

            <i class="bi bi-wallet2 text-danger display-5"></i>

            <h2 class="mt-3"><?= $expense_notifications ?></h2>

            <p class="text-secondary mb-0">
                Expense Notifications
            </p>

        </div>

    </div>

    <div class="col-md-4">

        <div class="glass text-center">

            <i class="bi bi-piggy-bank-fill text-warning display-5"></i>

            <h2 class="mt-3"><?= $saving_notifications ?></h2>

            <p class="text-secondary mb-0">
                Savings Alerts
            </p>

        </div>

    </div>

</div>



<!-- ===========================
        QUICK ACTIONS
=========================== -->

<div class="glass">

    <h4 class="fw-bold mb-4">

        <i class="bi bi-lightning-fill text-warning me-2"></i>

        Quick Actions

    </h4>

    <div class="row g-3">

        <div class="col-lg-3 col-md-6">

            <a href="dashboard.php"
               class="btn btn-primary w-100 py-3">

                <i class="bi bi-speedometer2 me-2"></i>

                Dashboard

            </a>

        </div>

        <div class="col-lg-3 col-md-6">

            <a href="add_income.php"
               class="btn btn-success w-100 py-3">

                <i class="bi bi-cash-stack me-2"></i>

                Add Income

            </a>

        </div>

        <div class="col-lg-3 col-md-6">

            <a href="add_expense.php"
               class="btn btn-danger w-100 py-3">

                <i class="bi bi-wallet2 me-2"></i>

                Add Expense

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

</div>



<!-- ===========================
        FOOTER
=========================== -->

<footer class="text-center text-secondary py-4 mt-5">

    <hr class="border-secondary">

    <p class="mb-0">

        © <?= date('Y') ?> <strong>Finora</strong> |
        Personal Expense Manager

    </p>

</footer>

</div>
<!-- End Main -->



<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>



<!-- Sidebar Toggle -->

<script>

function toggleSidebar(){

    document.getElementById("sidebar").classList.toggle("active");

}

</script>



<!-- Active Menu -->

<script>

const currentPage = window.location.pathname.split("/").pop();

document.querySelectorAll(".menu a").forEach(function(link){

    if(link.getAttribute("href")===currentPage){

        link.classList.add("active");

    }

});

</script>



<!-- Smooth Card Animation -->

<script>

document.querySelectorAll(".glass").forEach(function(card){

    card.addEventListener("mouseenter",function(){

        card.style.transform="translateY(-6px)";

        card.style.transition=".3s";

    });

    card.addEventListener("mouseleave",function(){

        card.style.transform="translateY(0px)";

    });

});

</script>



<!-- Smooth Page Load -->

<script>

document.body.style.opacity="0";

window.addEventListener("load",function(){

    document.body.style.transition=".4s";

    document.body.style.opacity="1";

});

</script>

</body>
</html>