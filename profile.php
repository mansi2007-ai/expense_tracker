<?php
session_start();
include 'db.php';

// =====================================
// LOGIN CHECK
// =====================================

if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit();
}

$user_id = $_SESSION['user_id'];

$success = "";
$error = "";


// =====================================
// FETCH USER DETAILS
// =====================================

$stmt = $conn->prepare("
SELECT *
FROM users
WHERE user_id=?
");

$stmt->bind_param("i",$user_id);

$stmt->execute();

$user = $stmt->get_result()->fetch_assoc();

$stmt->close();


// =====================================
// UPDATE PROFILE
// =====================================

if(isset($_POST['update_profile'])){

    $fullname = trim($_POST['fullname']);
    $email    = trim($_POST['email']);
    $phone    = trim($_POST['phone']);

    $profile_image = $user['profile_image'];

    // Upload Image
    if(!empty($_FILES['profile_image']['name'])){

        $uploadDir = "uploads/profile/";

        if(!is_dir($uploadDir)){
            mkdir($uploadDir,0777,true);
        }

        $extension = strtolower(
            pathinfo($_FILES['profile_image']['name'],PATHINFO_EXTENSION)
        );

        $allowed = ["jpg","jpeg","png","webp"];

        if(in_array($extension,$allowed)){

            $fileName = time()."_".$user_id.".".$extension;

            move_uploaded_file(
                $_FILES['profile_image']['tmp_name'],
                $uploadDir.$fileName
            );

            if(
                $profile_image!="default.png" &&
                file_exists($uploadDir.$profile_image)
            ){
                unlink($uploadDir.$profile_image);
            }

            $profile_image = $fileName;

        }else{

            $error = "Only JPG, PNG and WEBP images are allowed.";

        }

    }

    if($error==""){

        $stmt = $conn->prepare("
        UPDATE users
        SET
        fullname=?,
        email=?,
        phone=?,
        profile_image=?
        WHERE user_id=?
        ");

        $stmt->bind_param(
            "ssssi",
            $fullname,
            $email,
            $phone,
            $profile_image,
            $user_id
        );

        if($stmt->execute()){

            $_SESSION['fullname'] = $fullname;

            header("Location: profile.php?updated=1");
            exit();

        }

    }

}



// =====================================
// CHANGE PASSWORD
// =====================================

if(isset($_POST['change_password'])){

    $old_password = $_POST['old_password'];

    $new_password = $_POST['new_password'];

    $confirm_password = $_POST['confirm_password'];

    if(!password_verify($old_password,$user['password'])){

        $error = "Old password is incorrect.";

    }

    elseif(strlen($new_password)<6){

        $error = "Password must contain at least 6 characters.";

    }

    elseif($new_password!=$confirm_password){

        $error = "New passwords do not match.";

    }

    else{

        $hashed = password_hash($new_password,PASSWORD_DEFAULT);

        $stmt = $conn->prepare("
        UPDATE users
        SET password=?
        WHERE user_id=?
        ");

        $stmt->bind_param("si",$hashed,$user_id);

        if($stmt->execute()){

            header("Location: profile.php?password=1");
            exit();

        }

    }

}



// =====================================
// REFRESH USER DATA
// =====================================

$stmt = $conn->prepare("
SELECT *
FROM users
WHERE user_id=?
");

$stmt->bind_param("i",$user_id);

$stmt->execute();

$user = $stmt->get_result()->fetch_assoc();

$stmt->close();

?>
<!DOCTYPE html>
<html lang="en">

<head>

<meta charset="UTF-8">

<meta name="viewport" content="width=device-width, initial-scale=1">

<title>Finora • My Profile</title>

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

.card-glass{

background:rgba(18,25,51,.75);

border:1px solid rgba(255,255,255,.08);

backdrop-filter:blur(18px);

border-radius:24px;

box-shadow:0 20px 45px rgba(0,0,0,.35);

}

.page-title{

font-size:2rem;

font-weight:700;

}

.subtitle{

color:#94a3b8;

}

.profile-image{

width:170px;

height:170px;

border-radius:50%;

object-fit:cover;

border:5px solid rgba(255,255,255,.15);

box-shadow:0 15px 30px rgba(0,0,0,.35);

}

.info-box{

background:rgba(255,255,255,.05);

padding:14px;

border-radius:15px;

margin-bottom:15px;

}

.info-title{

font-size:.85rem;

color:#94a3b8;

}

.info-value{

font-weight:600;

font-size:1rem;

}

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

.btn-warning{

border:none;

border-radius:50px;

font-weight:600;

}

.btn-outline-light{

border-radius:50px;

}

</style>

</head>

<body>

<div class="container py-4">

<!-- =========================
        PAGE HEADER
========================= -->

<div class="d-flex justify-content-between align-items-center flex-wrap mb-4">

<div>

<h2 class="page-title">

<i class="bi bi-person-circle text-warning me-2"></i>

My Profile

</h2>

<p class="subtitle">

Manage your account information

</p>

</div>

<a href="dashboard.php"

class="btn btn-outline-light px-4">

<i class="bi bi-arrow-left-circle me-2"></i>

Dashboard

</a>

</div>



<!-- =========================
        ALERTS
========================= -->

<?php if(isset($_GET['updated'])): ?>

<div class="alert alert-success">

Profile updated successfully.

</div>

<?php endif; ?>


<?php if(isset($_GET['password'])): ?>

<div class="alert alert-info">

Password changed successfully.

</div>

<?php endif; ?>


<?php if($error!=""): ?>

<div class="alert alert-danger">

<?= htmlspecialchars($error) ?>

</div>

<?php endif; ?>



<div class="row g-4">

<!-- =========================
        PROFILE CARD
========================= -->

<div class="col-lg-4">

<div class="card-glass p-4 text-center h-100">

<img

id="preview"

src="uploads/profile/<?= htmlspecialchars($user['profile_image']) ?>"

class="profile-image mb-4"

alt="Profile Image">

<h3 class="fw-bold">

<?= htmlspecialchars($user['fullname']) ?>

</h3>

<p class="text-secondary mb-4">

<?= htmlspecialchars($user['email']) ?>

</p>

<div class="info-box">

<div class="info-title">

Phone Number

</div>

<div class="info-value">

<?= !empty($user['phone']) ? htmlspecialchars($user['phone']) : 'Not Added'; ?>

</div>

</div>

<div class="info-box">

<div class="info-title">

Member Since

</div>

<div class="info-value">

<?= date("d M Y",strtotime($user['created_at'])) ?>

</div>

</div>

<div class="info-box">

<div class="info-title">

User ID

</div>

<div class="info-value">

#<?= $user['user_id'] ?>

</div>

</div>

</div>

</div>



<!-- =========================
        FORMS START HERE
========================= -->

<div class="col-lg-8">
<!-- ===========================
        UPDATE PROFILE
=========================== -->

<div class="card-glass p-4 mb-4">

    <h4 class="fw-bold mb-4">

        <i class="bi bi-pencil-square text-warning me-2"></i>

        Update Profile

    </h4>

    <form method="POST" enctype="multipart/form-data">

        <div class="row g-3">

            <div class="col-md-6">

                <label class="form-label">Full Name</label>

                <input
                    type="text"
                    name="fullname"
                    class="form-control"
                    required
                    value="<?= htmlspecialchars($user['fullname']) ?>"
                >

            </div>

            <div class="col-md-6">

                <label class="form-label">Email Address</label>

                <input
                    type="email"
                    name="email"
                    class="form-control"
                    required
                    value="<?= htmlspecialchars($user['email']) ?>"
                >

            </div>

            <div class="col-md-6">

                <label class="form-label">Phone Number</label>

                <input
                    type="text"
                    name="phone"
                    class="form-control"
                    value="<?= htmlspecialchars($user['phone']) ?>"
                >

            </div>

            <div class="col-md-6">

                <label class="form-label">Profile Picture</label>

                <input
                    type="file"
                    id="profileImage"
                    name="profile_image"
                    class="form-control"
                    accept=".jpg,.jpeg,.png,.webp"
                >

            </div>

        </div>

        <div class="mt-4">

            <button
                type="submit"
                name="update_profile"
                class="btn btn-warning px-4">

                <i class="bi bi-check-circle me-2"></i>

                Save Changes

            </button>

        </div>

    </form>

</div>



<!-- ===========================
        CHANGE PASSWORD
=========================== -->

<div class="card-glass p-4">

    <h4 class="fw-bold mb-4">

        <i class="bi bi-shield-lock-fill text-warning me-2"></i>

        Change Password

    </h4>

    <form method="POST">

        <div class="row g-3">

            <div class="col-md-12">

                <label class="form-label">

                    Current Password

                </label>

                <input
                    type="password"
                    name="old_password"
                    class="form-control"
                    required
                >

            </div>

            <div class="col-md-6">

                <label class="form-label">

                    New Password

                </label>

                <input
                    type="password"
                    name="new_password"
                    class="form-control"
                    required
                >

            </div>

            <div class="col-md-6">

                <label class="form-label">

                    Confirm Password

                </label>

                <input
                    type="password"
                    name="confirm_password"
                    class="form-control"
                    required
                >

            </div>

        </div>

        <div class="mt-4">

            <button
                type="submit"
                name="change_password"
                class="btn btn-warning px-4">

                <i class="bi bi-key-fill me-2"></i>

                Update Password

            </button>

        </div>

    </form>

</div>

</div>

</div>
<!-- ===========================
        FOOTER
=========================== -->

<footer class="text-center text-secondary py-4 mt-5">

    <hr class="border-secondary">

    <p class="mb-0">

        <i class="bi bi-person-circle text-warning"></i>

        © <?= date('Y') ?> <strong>Finora</strong> | User Profile

    </p>

</footer>

</div>



<!-- ===========================
        BOOTSTRAP JS
=========================== -->

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>



<!-- ===========================
        LIVE IMAGE PREVIEW
=========================== -->

<script>

document.getElementById("profileImage").addEventListener("change",function(e){

    const file=e.target.files[0];

    if(file){

        document.getElementById("preview").src=

        URL.createObjectURL(file);

    }

});

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
        PASSWORD MATCH CHECK
=========================== -->

<script>

const newPass=document.querySelector('input[name="new_password"]');

const confirmPass=document.querySelector('input[name="confirm_password"]');

function validatePassword(){

    if(confirmPass.value===""){

        confirmPass.setCustomValidity("");

        return;

    }

    if(newPass.value!==confirmPass.value){

        confirmPass.setCustomValidity("Passwords do not match.");

    }

    else{

        confirmPass.setCustomValidity("");

    }

}

newPass.addEventListener("keyup",validatePassword);

confirmPass.addEventListener("keyup",validatePassword);

</script>



<!-- ===========================
        IMAGE SIZE VALIDATION
=========================== -->

<script>

document.getElementById("profileImage").addEventListener("change",function(){

    const file=this.files[0];

    if(!file) return;

    if(file.size > 2 * 1024 * 1024){

        alert("Please choose an image smaller than 2 MB.");

        this.value="";

        document.getElementById("preview").src="uploads/profile/<?= htmlspecialchars($user['profile_image']) ?>";

    }

});

</script>

</body>
</html>