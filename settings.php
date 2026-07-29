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

$success = "";
$error = "";


// =========================
// FETCH USER DATA
// =========================

$stmt = $conn->prepare("
SELECT *
FROM users
WHERE user_id=?
");

$stmt->bind_param("i",$user_id);
$stmt->execute();

$user = $stmt->get_result()->fetch_assoc();

$stmt->close();


// =========================
// UPDATE PROFILE
// =========================

if(isset($_POST['update_profile'])){

    $fullname = trim($_POST['fullname']);
    $phone = trim($_POST['phone']);

    $profile_image = $user['profile_image'];

    // Upload new profile image
    if(isset($_FILES['profile_image']) &&
       $_FILES['profile_image']['error']==0){

        $folder = "uploads/profile/";

        if(!is_dir($folder)){
            mkdir($folder,0777,true);
        }

        $filename = time()."_".basename($_FILES['profile_image']['name']);

        $target = $folder.$filename;

        move_uploaded_file(
            $_FILES['profile_image']['tmp_name'],
            $target
        );

        $profile_image = $filename;
    }

    $stmt = $conn->prepare("
    UPDATE users
    SET
        fullname=?,
        phone=?,
        profile_image=?
    WHERE user_id=?
    ");

    $stmt->bind_param(
        "sssi",
        $fullname,
        $phone,
        $profile_image,
        $user_id
    );

    if($stmt->execute()){

        $_SESSION['fullname'] = $fullname;

        $success = "Profile updated successfully.";

        // Refresh user data
        $stmt2 = $conn->prepare("
        SELECT *
        FROM users
        WHERE user_id=?
        ");

        $stmt2->bind_param("i",$user_id);
        $stmt2->execute();

        $user = $stmt2->get_result()->fetch_assoc();

        $stmt2->close();

    }else{

        $error = "Unable to update profile.";

    }

    $stmt->close();

}


// =========================
// CHANGE PASSWORD
// =========================

if(isset($_POST['change_password'])){

    $current = $_POST['current_password'];

    $new = $_POST['new_password'];

    $confirm = $_POST['confirm_password'];

    if(!password_verify($current,$user['password'])){

        $error = "Current password is incorrect.";

    }

    elseif($new != $confirm){

        $error = "New passwords do not match.";

    }

    elseif(strlen($new) < 6){

        $error = "Password must be at least 6 characters.";

    }

    else{

        $hash = password_hash($new,PASSWORD_DEFAULT);

        $stmt = $conn->prepare("
        UPDATE users
        SET password=?
        WHERE user_id=?
        ");

        $stmt->bind_param(
            "si",
            $hash,
            $user_id
        );

        if($stmt->execute()){

            $success = "Password changed successfully.";

        }else{

            $error = "Unable to change password.";

        }

        $stmt->close();

    }

}


// =========================
// VARIABLES
// =========================

$fullname = $user['fullname'];

$email = $user['email'];

$phone = $user['phone'];

$profile_image = !empty($user['profile_image'])
    ? $user['profile_image']
    : "default.png";

$member_since = date(
    "d M Y",
    strtotime($user['created_at'])
);

?>
<!DOCTYPE html>
<html lang="en">

<head>

<meta charset="UTF-8">

<meta name="viewport" content="width=device-width, initial-scale=1">

<title>Finora • Settings</title>

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

margin-bottom:35px;

}

.top-right{

display:flex;

align-items:center;

gap:20px;

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

/* Glass Card */

.glass{

background:rgba(18,25,51,.78);

border:1px solid rgba(255,255,255,.08);

backdrop-filter:blur(18px);

border-radius:20px;

padding:30px;

box-shadow:0 20px 40px rgba(0,0,0,.30);

margin-bottom:25px;

}

/* Inputs */

.form-control{

background:#111827;

border:1px solid #334155;

color:#fff;

}

.form-control:focus{

background:#111827;

color:#fff;

border-color:#f59e0b;

box-shadow:none;

}

/* Responsive */

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

<a href="settings.php" class="active">
<i class="bi bi-gear-fill"></i>
Settings
</a>

<a href="logout.php">
<i class="bi bi-box-arrow-right"></i>
Logout
</a>

</div>

</div>

<!-- Main -->

<div class="main">

<!-- Topbar -->

<div class="topbar">

<div class="d-flex align-items-center gap-3">

<button
class="btn btn-warning d-lg-none"
onclick="toggleSidebar()">

<i class="bi bi-list"></i>

</button>

<div>

<h2 class="fw-bold mb-1">

Settings

</h2>

<p class="text-secondary mb-0">

Manage your Finora account and security settings.

</p>

</div>

</div>

<div class="top-right">

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

<?php if($success!=""): ?>

<div class="alert alert-success">

<?= $success ?>

</div>

<?php endif; ?>

<?php if($error!=""): ?>

<div class="alert alert-danger">

<?= $error ?>

</div>

<?php endif; ?>
<!-- ===========================
        PROFILE SETTINGS
=========================== -->

<div class="glass">

    <h4 class="fw-bold mb-4">
        <i class="bi bi-person-circle text-warning me-2"></i>
        Profile Settings
    </h4>

    <form method="POST" enctype="multipart/form-data">

        <div class="row">

            <!-- Profile Image -->
            <div class="col-md-4 text-center mb-4">

                <img src="uploads/profile/<?= htmlspecialchars($profile_image) ?>"
                     class="rounded-circle shadow mb-3"
                     style="width:170px;height:170px;object-fit:cover;border:4px solid #f59e0b;">

                <div>

                    <label class="form-label fw-semibold">
                        Change Profile Picture
                    </label>

                    <input
                        type="file"
                        name="profile_image"
                        class="form-control"
                        accept="image/*">

                </div>

            </div>

            <!-- User Details -->
            <div class="col-md-8">

                <div class="mb-3">

                    <label class="form-label">
                        Full Name
                    </label>

                    <input
                        type="text"
                        name="fullname"
                        class="form-control"
                        value="<?= htmlspecialchars($fullname) ?>"
                        required>

                </div>

                <div class="mb-3">

                    <label class="form-label">
                        Email Address
                    </label>

                    <input
                        type="email"
                        class="form-control"
                        value="<?= htmlspecialchars($email) ?>"
                        readonly>

                    <small class="text-secondary">
                        Email cannot be changed.
                    </small>

                </div>

                <div class="mb-3">

                    <label class="form-label">
                        Phone Number
                    </label>

                    <input
                        type="text"
                        name="phone"
                        class="form-control"
                        value="<?= htmlspecialchars($phone) ?>">

                </div>

                <button
                    type="submit"
                    name="update_profile"
                    class="btn btn-warning px-5">

                    <i class="bi bi-check-circle me-2"></i>

                    Save Changes

                </button>

            </div>

        </div>

    </form>

</div>

<div class="glass">

    <h4 class="fw-bold mb-4">
        <i class="bi bi-palette-fill text-warning me-2"></i>
        Appearance
    </h4>

    <div class="d-flex justify-content-between align-items-center">

        <div>

            <h5 class="mb-1">Theme</h5>

            <small class="text-secondary">
                Switch between Light and Dark mode
            </small>

        </div>

        <button
            id="themeToggle"
            class="btn btn-warning">

            <i class="bi bi-moon-stars-fill me-2"></i>

            Dark Mode

        </button>

    </div>

</div>
<!-- ===========================
        CHANGE PASSWORD
=========================== -->

<div class="glass">

    <h4 class="fw-bold mb-4">
        <i class="bi bi-shield-lock-fill text-warning me-2"></i>
        Change Password
    </h4>

    <form method="POST">

        <div class="row">

            <div class="col-md-12 mb-3">

                <label class="form-label">
                    Current Password
                </label>

                <div class="input-group">

                    <input
                        type="password"
                        name="current_password"
                        id="current_password"
                        class="form-control"
                        required>

                    <button
                        class="btn btn-outline-secondary"
                        type="button"
                        onclick="togglePassword('current_password', this)">

                        <i class="bi bi-eye"></i>

                    </button>

                </div>

            </div>

            <div class="col-md-6 mb-3">

                <label class="form-label">
                    New Password
                </label>

                <div class="input-group">

                    <input
                        type="password"
                        name="new_password"
                        id="new_password"
                        class="form-control"
                        minlength="6"
                        required>

                    <button
                        class="btn btn-outline-secondary"
                        type="button"
                        onclick="togglePassword('new_password', this)">

                        <i class="bi bi-eye"></i>

                    </button>

                </div>

            </div>

            <div class="col-md-6 mb-3">

                <label class="form-label">
                    Confirm New Password
                </label>

                <div class="input-group">

                    <input
                        type="password"
                        name="confirm_password"
                        id="confirm_password"
                        class="form-control"
                        minlength="6"
                        required>

                    <button
                        class="btn btn-outline-secondary"
                        type="button"
                        onclick="togglePassword('confirm_password', this)">

                        <i class="bi bi-eye"></i>

                    </button>

                </div>

            </div>

        </div>

        <div class="alert alert-info mt-2">

            <i class="bi bi-info-circle me-2"></i>

            Password must contain at least
            <strong>6 characters</strong>.

        </div>

        <button
            type="submit"
            name="change_password"
            class="btn btn-danger px-5">

            <i class="bi bi-key-fill me-2"></i>

            Update Password

        </button>

    </form>

</div>
<!-- ===========================
        ACCOUNT INFORMATION
=========================== -->

<div class="glass">

    <h4 class="fw-bold mb-4">
        <i class="bi bi-info-circle-fill text-warning me-2"></i>
        Account Information
    </h4>

    <div class="row g-4">

        <div class="col-md-6">

            <div class="border rounded p-3 h-100">

                <h6 class="text-secondary">
                    User ID
                </h6>

                <h5 class="fw-bold">
                    #<?= $user_id ?>
                </h5>

            </div>

        </div>

        <div class="col-md-6">

            <div class="border rounded p-3 h-100">

                <h6 class="text-secondary">
                    Member Since
                </h6>

                <h5 class="fw-bold">
                    <?= $member_since ?>
                </h5>

            </div>

        </div>

        <div class="col-md-6">

            <div class="border rounded p-3 h-100">

                <h6 class="text-secondary">
                    Registered Email
                </h6>

                <h5 class="fw-bold text-break">
                    <?= htmlspecialchars($email) ?>
                </h5>

            </div>

        </div>

        <div class="col-md-6">

            <div class="border rounded p-3 h-100">

                <h6 class="text-secondary">
                    Phone Number
                </h6>

                <h5 class="fw-bold">
                    <?= !empty($phone) ? htmlspecialchars($phone) : 'Not Added' ?>
                </h5>

            </div>

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

            <a href="profile.php"
               class="btn btn-success w-100 py-3">

                <i class="bi bi-person-circle me-2"></i>

                Profile

            </a>

        </div>

        <div class="col-lg-3 col-md-6">

            <a href="reports.php"
               class="btn btn-warning w-100 py-3">

                <i class="bi bi-bar-chart-fill me-2"></i>

                Reports

            </a>

        </div>

        <div class="col-lg-3 col-md-6">

            <a href="logout.php"
               class="btn btn-danger w-100 py-3"
               onclick="return confirm('Are you sure you want to logout?');">

                <i class="bi bi-box-arrow-right me-2"></i>

                Logout

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

        © <?= date('Y') ?> <strong>Finora</strong> | Personal Expense Manager

    </p>

</footer>

</div>
<!-- End Main -->



<!-- Bootstrap JS -->

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
        SHOW / HIDE PASSWORD
=========================== -->

<script>

function togglePassword(id, button){

    const input = document.getElementById(id);

    const icon = button.querySelector("i");

    if(input.type === "password"){

        input.type = "text";

        icon.classList.remove("bi-eye");

        icon.classList.add("bi-eye-slash");

    }else{

        input.type = "password";

        icon.classList.remove("bi-eye-slash");

        icon.classList.add("bi-eye");

    }

}

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



<!-- ===========================
        SMOOTH PAGE LOAD
=========================== -->

<script>

document.body.style.opacity="0";

window.addEventListener("load",function(){

    document.body.style.transition=".4s";

    document.body.style.opacity="1";

});

</script>

</body>
</html>