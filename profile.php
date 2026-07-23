<?php
session_start();
include 'db.php';

if (!isset($_SESSION['user_id'])) {
    header('Location: login.php');
    exit();
}

$user_id = $_SESSION['user_id'];

// ===== FETCH USER DATA =====
$stmt = $conn->prepare("SELECT * FROM users WHERE user_id=?");
$stmt->bind_param("i", $user_id);
$stmt->execute();
$user = $stmt->get_result()->fetch_assoc();

// ===== UPDATE PROFILE =====
if(isset($_POST['update_profile'])){

    $fullname   = trim($_POST['fullname']);
    $email      = trim($_POST['email']);
    $phone      = trim($_POST['phone']);
    $address    = trim($_POST['address']);
    $occupation = trim($_POST['occupation']); 

        // ===== PROFILE PHOTO UPLOAD =====
$profile_photo = $user['profile_photo'] ?? '';

if(isset($_FILES['profile_photo']) && $_FILES['profile_photo']['error'] == 0){

    $allowed = ['jpg','jpeg','png','webp'];

    $file_name = $_FILES['profile_photo']['name'];
    $file_tmp  = $_FILES['profile_photo']['tmp_name'];
    $file_size = $_FILES['profile_photo']['size'];

    $ext = strtolower(pathinfo($file_name, PATHINFO_EXTENSION));

    if(in_array($ext, $allowed) && $file_size <= 2*1024*1024){

        // Unique file name
        $new_name = 'user_'.$user_id.'_'.time().'.'.$ext;

        // Absolute uploads folder path
        $upload_dir = __DIR__ . DIRECTORY_SEPARATOR . 'uploads';

        // Create folder only if it does NOT exist
        if(!file_exists($upload_dir)){
            mkdir($upload_dir, 0777, true);
        }

        // Full file path on server
        $upload_path = $upload_dir . DIRECTORY_SEPARATOR . $new_name;

        // Relative path to store in database
        $db_path = 'uploads/' . $new_name;

        // Move uploaded file
        if(move_uploaded_file($file_tmp, $upload_path)){
            $profile_photo = $db_path;
        } else {
            $error = "Failed to upload image.";
        }
    } else {
        $error = "Only JPG, PNG, and WEBP images under 2MB are allowed.";
    }
}
  
$update = $conn->prepare("UPDATE users SET fullname=?, email=?, phone=?, address=?, occupation=?, profile_photo=? WHERE user_id=?");
$update->bind_param("ssssssi", $fullname, $email, $phone, $address, $occupation, $profile_photo, $user_id);
    if($update->execute()){
        $_SESSION['fullname'] = $fullname;
        $success = "Profile updated successfully!";
        $user['fullname'] = $fullname;
        $user['email'] = $email;
        $user['phone'] = $phone;
        $user['address'] = $address;
        $user['occupation'] = $occupation;
        $user['profile_photo'] = $profile_photo;
    }else{
        $error = "Failed to update profile.";
    }
}

// ===== CHANGE PASSWORD =====
if(isset($_POST['change_password'])){

    $current_password = $_POST['current_password'];
    $new_password     = $_POST['new_password'];
    $confirm_password = $_POST['confirm_password'];

    if($new_password !== $confirm_password){
        $pass_error = "New passwords do not match.";
    }else{

        // Verify current password
        if(password_verify($current_password, $user['password'])){

            $hashed = password_hash($new_password, PASSWORD_DEFAULT);

            $pass_stmt = $conn->prepare("UPDATE users SET password=? WHERE user_id=?");
            $pass_stmt->bind_param("si", $hashed, $user_id);

            if($pass_stmt->execute()){
                $pass_success = "Password changed successfully!";
            }else{
                $pass_error = "Failed to change password.";
            }

        }else{
            $pass_error = "Current password is incorrect.";
        }
    }
}
?>

<!DOCTYPE html>

<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1">
<title>My Profile - ExpenseTracker</title>

<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
<link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.css" rel="stylesheet">

<style>
:root{
    --bg:#0b1020;
    --panel:#121933cc;
    --line:#2a3566;
    --text:#eef2ff;
}

body{
    background: radial-gradient(circle at top left,#1d4ed8 0%,#0b1020 35%,#070b16 100%);
    color:var(--text);
    font-family:'Segoe UI',sans-serif;
    min-height:100vh;
}

.glass{
    background:var(--panel);
    border:1px solid rgba(255,255,255,.08);
    backdrop-filter: blur(16px);
    border-radius:26px;
    box-shadow:0 12px 40px rgba(0,0,0,.28);
}

.profile-cover{
    height:180px;
    border-radius:26px 26px 0 0;
    background: linear-gradient(135deg,#2563eb,#7c3aed,#06b6d4);
    position:relative;
    overflow:hidden;
}

.profile-cover::before{
    content:'';
    position:absolute;
    width:260px;height:260px;
    border-radius:50%;
    background:rgba(255,255,255,.12);
    top:-80px; right:-40px;
}

.avatar-wrap{
    position:relative;
    margin-top:-72px;
}

.avatar{
    width:140px;
    height:140px;
    border-radius:50%;
    border:6px solid #0f172a;
    object-fit:cover;
    box-shadow:0 10px 30px rgba(0,0,0,.35);
}

.form-control, .form-select{
    background:#0f172a;
    border:1px solid #334155;
    color:#fff;
    border-radius:14px;
    padding:12px 14px;
}

.form-control:focus{
    background:#0f172a;
    color:#fff;
    border-color:#60a5fa;
    box-shadow:0 0 0 .2rem rgba(96,165,250,.15);
}

.info-card{
    padding:18px;
    border-radius:18px;
    background:rgba(255,255,255,.04);
    border:1px solid rgba(255,255,255,.06);
}

.stat{
    text-align:center;
    padding:18px;
    border-radius:18px;
    background:rgba(255,255,255,.04);
    border:1px solid rgba(255,255,255,.06);
}

.back-btn{
    text-decoration:none;
}

@media (max-width:768px){
    .avatar{
        width:110px;
        height:110px;
    }
}
</style>

</head>
<body>

<div class="container py-4">

```
<!-- HEADER -->
<div class="d-flex justify-content-between align-items-center mb-4">
    <div>
        <h2 class="mb-0">My Profile</h2>
        <small class="text-secondary">Manage your personal information and security</small>
    </div>

    <a href="dashboard.php" class="btn btn-outline-light back-btn">
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

<!-- PROFILE CARD -->
<div class="glass mb-4">

    <div class="profile-cover"></div>

    <div class="px-4 pb-4">

        <div class="avatar-wrap d-flex flex-column flex-md-row align-items-center align-items-md-end gap-4">

          <?php
// if user uploaded a photo, use it; otherwise use generated avatar
$avatar = !empty($user['profile_photo'])
    ? $user['profile_photo']
    : 'https://ui-avatars.com/api/?name='.urlencode($user['fullname']).'&background=2563eb&color=fff&size=256';
?>

<img src="<?php echo htmlspecialchars($avatar); ?>" class="avatar" alt="Avatar">
<div class="flex-grow-1 text-center text-md-start">
                <h2 class="mb-1"><?php echo htmlspecialchars($user['fullname']); ?></h2>

                <div class="text-secondary mb-2">
                    <i class="bi bi-envelope me-1"></i><?php echo htmlspecialchars($user['email']); ?>
                </div>

                <div class="d-flex flex-wrap gap-2 justify-content-center justify-content-md-start">
                    <span class="badge text-bg-primary px-3 py-2">
                        <!-- Swapped ?: for ?? below -->
                        <i class="bi bi-person-badge me-1"></i><?php echo htmlspecialchars($user['occupation'] ?? 'User'); ?>
                    </span>

                    <span class="badge text-bg-success px-3 py-2">
                        <i class="bi bi-check-circle me-1"></i>Verified
                    </span>
                </div>
            </div>

            <button class="btn btn-primary px-4" onclick="document.getElementById('fullname').focus()">
                <i class="bi bi-pencil-square me-2"></i>Edit Profile
            </button>
        </div>

        <!-- STATS -->
        <div class="row g-3 mt-4">

            <div class="col-md-4">
                <div class="stat">
                    <div class="text-secondary small">Member Since</div>
                    <div class="fs-5 fw-bold">
                        <?php echo isset($user['created_at']) ? date('M Y', strtotime($user['created_at'])) : date('M Y'); ?>
                    </div>
                </div>
            </div>

            <div class="col-md-4">
                <div class="stat">
                    <div class="text-secondary small">Account Type</div>
                    <div class="fs-5 fw-bold">Personal</div>
                </div>
            </div>

            <div class="col-md-4">
                <div class="stat">
                    <div class="text-secondary small">Status</div>
                    <div class="fs-5 fw-bold text-success">Active</div>
                </div>
            </div>

        </div>
    </div>
</div>

<div class="row g-4">

    <!-- EDIT PROFILE -->
    <div class="col-lg-8">
        <div class="glass p-4">

            <div class="d-flex align-items-center gap-2 mb-4">
                <i class="bi bi-person-lines-fill text-info fs-4"></i>
                <div>
                    <h4 class="mb-0">Personal Information</h4>
                    <small class="text-secondary">Update your profile details</small>
                </div>
            </div>

            <form method="POST" enctype="multipart/form-data">

                <div class="row g-3">

                    <div class="col-md-6">
                        <div class="col-12">
    <label class="form-label">Profile Picture</label>
    <input type="file" name="profile_photo" class="form-control" accept="image/*">
    <small class="text-secondary">Upload JPG, PNG or WEBP (max 2MB)</small>
</div>
                        <label class="form-label">Full Name</label>
                        <input type="text" id="fullname" name="fullname"
                               class="form-control"
                               value="<?php echo htmlspecialchars($user['fullname']); ?>"
                               required>
                    </div>

                    <div class="col-md-6">
                        <label class="form-label">Email Address</label>
                        <input type="email" name="email"
                               class="form-control"
                               value="<?php echo htmlspecialchars($user['email']); ?>"
                               required>
                    </div>

                    <div class="col-md-6">
                        <label class="form-label">Phone Number</label>
                        <input type="text" name="phone"
                               class="form-control"
                               placeholder="+91 9876543210"
                               value="<?php echo htmlspecialchars($user['phone'] ?? ''); ?>">
                    </div>

                    <div class="col-md-6">
                        <label class="form-label">Occupation</label>
                        <input type="text" name="occupation"
                               class="form-control"
                               placeholder="Software Developer"
                               value="<?php echo htmlspecialchars($user['occupation'] ?? ''); ?>">
                    </div>

                    <div class="col-12">
                        <label class="form-label">Address</label>
                        <textarea name="address" rows="3" class="form-control"
                                  placeholder="Enter your address"><?php echo htmlspecialchars($user['address'] ?? ''); ?></textarea>
                    </div>

                </div>

                <div class="d-flex gap-2 mt-4">
                    <button type="submit" name="update_profile" class="btn btn-primary px-4">
                        <i class="bi bi-save me-2"></i>Save Changes
                    </button>

                    <a href="dashboard.php" class="btn btn-outline-light">
                        Cancel
                    </a>
                </div>

            </form>
        </div>
    </div>

    <!-- SECURITY -->
    <div class="col-lg-4">
        <div class="glass p-4 mb-4">

            <div class="d-flex align-items-center gap-2 mb-3">
                <i class="bi bi-shield-lock text-warning fs-4"></i>
                <div>
                    <h4 class="mb-0">Security</h4>
                    <small class="text-secondary">Change your password</small>
                </div>
            </div>

            <?php if(isset($pass_success)): ?>
                <div class="alert alert-success py-2"><?php echo $pass_success; ?></div>
            <?php endif; ?>

            <?php if(isset($pass_error)): ?>
                <div class="alert alert-danger py-2"><?php echo $pass_error; ?></div>
            <?php endif; ?>

            <form method="POST">

                <div class="mb-3">
                    <label class="form-label">Current Password</label>
                    <input type="password" name="current_password" class="form-control" required>
                </div>

                <div class="mb-3">
                    <label class="form-label">New Password</label>
                    <input type="password" name="new_password" class="form-control" required>
                </div>

                <div class="mb-3">
                    <label class="form-label">Confirm New Password</label>
                    <input type="password" name="confirm_password" class="form-control" required>
                </div>

                <button type="submit" name="change_password" class="btn btn-warning text-dark w-100">
                    <i class="bi bi-key me-2"></i>Change Password
                </button>

            </form>
        </div>

        <!-- QUICK INFO -->
        <div class="glass p-4">

            <div class="d-flex align-items-center gap-2 mb-3">
                <i class="bi bi-info-circle text-info fs-4"></i>
                <div>
                    <h4 class="mb-0">Quick Info</h4>
                    <small class="text-secondary">Account details</small>
                </div>
            </div>

            <div class="info-card mb-3">
                <div class="text-secondary small">User ID</div>
                <div class="fw-semibold">#<?php echo $user['user_id']; ?></div>
            </div>

            <div class="info-card mb-3">
                <div class="text-secondary small">Email</div>
                <div class="fw-semibold"><?php echo htmlspecialchars($user['email']); ?></div>
            </div>

            <div class="info-card">
                <div class="text-secondary small">Phone</div>
                <div class="fw-semibold">
                    <?php echo htmlspecialchars($user['phone'] ?: 'Not added'); ?>
                </div>
            </div>

        </div>
    </div>

</div>
```

</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>

</body>
</html>