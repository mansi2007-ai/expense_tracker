<?php
session_start();
include 'db.php';

if(!isset($_SESSION['user_id'])){
    header('Location: login.php');
    exit();
}

$user_id = $_SESSION['user_id'];

// ===== CREATE DEFAULT SETTINGS IF NOT EXISTS =====
$check = $conn->prepare("SELECT * FROM user_settings WHERE user_id=?");
$check->bind_param("i", $user_id);
$check->execute();
$result = $check->get_result();

if($result->num_rows == 0){
    $insert = $conn->prepare("INSERT INTO user_settings(user_id) VALUES(?)");
    $insert->bind_param("i", $user_id);
    $insert->execute();
}

$settings = $conn->query("SELECT * FROM user_settings WHERE user_id=$user_id")->fetch_assoc();
$current_theme = $settings['theme'] ?? 'dark';

// ===== SAVE SETTINGS =====
if(isset($_POST['save_settings'])){

    $theme = $_POST['theme'];
    $currency = $_POST['currency'];
    $email_notifications = isset($_POST['email_notifications']) ? 1 : 0;
    $budget_alerts = isset($_POST['budget_alerts']) ? 1 : 0;
    $profile_visibility = $_POST['profile_visibility'];

    $stmt = $conn->prepare("UPDATE user_settings SET theme=?, currency=?, email_notifications=?, budget_alerts=?, profile_visibility=? WHERE user_id=?");

    $stmt->bind_param(
        "ssiisi",
        $theme,
        $currency,
        $email_notifications,
        $budget_alerts,
        $profile_visibility,
        $user_id
    );

    if($stmt->execute()){
        $success = "Settings saved successfully!";
        $_SESSION['theme'] = $theme;
        $settings = $conn->query("SELECT * FROM user_settings WHERE user_id=$user_id")->fetch_assoc();
    }else{
        $error = "Failed to save settings.";
    }
}

// ===== CLEAR ALL DATA =====
if(isset($_POST['clear_data'])){

    $conn->query("DELETE FROM expenses WHERE user_id=$user_id");
    $conn->query("DELETE FROM income WHERE user_id=$user_id");
    $conn->query("DELETE FROM budgets WHERE user_id=$user_id");

    $success = "All transaction data has been cleared.";
}
?>

<!DOCTYPE html>

<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1">
<title>Settings - Finora</title>

<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
<link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.css" rel="stylesheet">

<style>
<style>
:root{
<?php if($current_theme == 'light'): ?>
    --bg:#f4f7fb;
    --panel:rgba(255,255,255,0.92);
    --line:#d6deeb;
    --text:#0f172a;
    --muted:#475569;
    --input-bg:#ffffff;
    --input-border:#cbd5e1;
<?php else: ?>
    --bg:#0b1020;
    --panel:#121933cc;
    --line:#2a3566;
    --text:#eef2ff;
    --muted:#a5b4fc;
    --input-bg:#0f172a;
    --input-border:#334155;
<?php endif; ?>
}

body{
    background: var(--bg);
    color:var(--text);
    font-family:'Segoe UI',sans-serif;
    min-height:100vh;
    transition: background .3s ease, color .3s ease;
}

.glass{
    background:var(--panel);
    border:1px solid rgba(255,255,255,.08);
    backdrop-filter: blur(16px);
    border-radius:26px;
    box-shadow:0 12px 40px rgba(0,0,0,.28);
}

.form-control,.form-select{
    background:var(--input-bg);
    border:1px solid var(--input-border);
    color:var(--text);
    border-radius:14px;
    padding:12px 14px;
    transition: all .25s ease;
}

.form-control:focus,.form-select:focus{
    background:var(--input-bg);
    color:var(--text);
    border-color:#60a5fa;
    box-shadow:0 0 0 .2rem rgba(96,165,250,.15);
}

.section-title{
    display:flex;
    align-items:center;
    gap:10px;
    margin-bottom:20px;
}

.setting-row{
    padding:18px 0;
    border-bottom:1px solid rgba(255,255,255,.08);
}

.setting-row:last-child{
    border-bottom:none;
}

.danger-box{
    border:1px solid rgba(239,68,68,.4);
    background:rgba(239,68,68,.08);
    border-radius:18px;
    padding:20px;
}

.form-check-input{
    width:2.5rem;
    height:1.3rem;
    cursor:pointer;
}

.badge-soft{
    background:rgba(96,165,250,.15);
    color:#bfdbfe;
    border:1px solid rgba(96,165,250,.25);
}

small.text-secondary{ color:var(--muted)!important; }
</style>

</head>
<body>

<div class="container py-4">

```
<!-- HEADER -->
<div class="d-flex justify-content-between align-items-center mb-4 flex-wrap gap-3">
    <div>
        <h2 class="mb-0">Settings</h2>
        <small class="text-secondary">Customize your Finora experience</small>
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

<form method="POST">

    <!-- APPEARANCE -->
    <div class="glass p-4 mb-4">

        <div class="section-title">
            <i class="bi bi-palette fs-4 text-info"></i>
            <div>
                <h4 class="mb-0">Appearance</h4>
                <small class="text-secondary">Theme and display preferences</small>
            </div>
        </div>

        <div class="setting-row">
            <div class="row align-items-center">
                <div class="col-md-6">
                    <label class="form-label fw-semibold">Theme</label>
                    <small class="d-block text-secondary">Choose your preferred application theme</small>
                </div>
                <div class="col-md-6">
                    <select name="theme" class="form-select" id="themeSelect">
                    <option value="dark" <?= $current_theme == 'dark' ? 'selected' : '' ?>>🌙 Dark</option>
                    <option value="light" <?= $current_theme == 'light' ? 'selected' : '' ?>>☀️ Light</option>
                </select>
            </div>
            </div>
        </div>

        <div class="setting-row">
            <div class="row align-items-center">
                <div class="col-md-6">
                    <label class="form-label fw-semibold">Currency</label>
                    <small class="d-block text-secondary">Used across dashboard, reports and transactions</small>
                </div>
                <div class="col-md-6">
                    <select name="currency" class="form-select">
                        <option value="INR" <?php if($settings['currency']=='INR') echo 'selected'; ?>>₹ Indian Rupee (INR)</option>
                        <option value="USD" <?php if($settings['currency']=='USD') echo 'selected'; ?>>$ US Dollar (USD)</option>
                        <option value="EUR" <?php if($settings['currency']=='EUR') echo 'selected'; ?>>€ Euro (EUR)</option>
                        <option value="GBP" <?php if($settings['currency']=='GBP') echo 'selected'; ?>>£ British Pound (GBP)</option>
                    </select>
                </div>
            </div>
        </div>

    </div>

    <!-- NOTIFICATIONS -->
    <div class="glass p-4 mb-4">

        <div class="section-title">
            <i class="bi bi-bell fs-4 text-warning"></i>
            <div>
                <h4 class="mb-0">Notifications</h4>
                <small class="text-secondary">Manage alerts and reminders</small>
            </div>
        </div>

        <div class="setting-row">
            <div class="d-flex justify-content-between align-items-center">
                <div>
                    <div class="fw-semibold">Email notifications</div>
                    <small class="text-secondary">Receive account activity and summary emails</small>
                </div>

                <div class="form-check form-switch m-0">
                    <input class="form-check-input" type="checkbox" name="email_notifications"
                           <?php if($settings['email_notifications']) echo 'checked'; ?>>
                </div>
            </div>
        </div>

        <div class="setting-row">
            <div class="d-flex justify-content-between align-items-center">
                <div>
                    <div class="fw-semibold">Budget alerts</div>
                    <small class="text-secondary">Get notified when spending reaches 80% of your budget</small>
                </div>

                <div class="form-check form-switch m-0">
                    <input class="form-check-input" type="checkbox" name="budget_alerts"
                           <?php if($settings['budget_alerts']) echo 'checked'; ?>>
                </div>
            </div>
        </div>

    </div>

    <!-- PRIVACY -->
    <div class="glass p-4 mb-4">

        <div class="section-title">
            <i class="bi bi-shield-lock fs-4 text-success"></i>
            <div>
                <h4 class="mb-0">Privacy</h4>
                <small class="text-secondary">Control who can view your profile</small>
            </div>
        </div>

        <div class="setting-row">
            <div class="row align-items-center">
                <div class="col-md-6">
                    <label class="form-label fw-semibold">Profile visibility</label>
                    <small class="d-block text-secondary">Choose who can see your profile information</small>
                </div>
                <div class="col-md-6">
                    <select name="profile_visibility" class="form-select">
                        <option value="private" <?php if($settings['profile_visibility']=='private') echo 'selected'; ?>>🔒 Private</option>
                        <option value="friends" <?php if($settings['profile_visibility']=='friends') echo 'selected'; ?>>👥 Friends only</option>
                        <option value="public" <?php if($settings['profile_visibility']=='public') echo 'selected'; ?>>🌍 Public</option>
                    </select>
                </div>
            </div>
        </div>

    </div>

    <!-- SAVE BUTTON -->
    <div class="glass p-4 mb-4">
        <div class="d-flex flex-wrap gap-3 align-items-center">
            <button type="submit" name="save_settings" class="btn btn-primary px-4">
                <i class="bi bi-save me-2"></i>Save Settings
            </button>

            <span class="badge badge-soft px-3 py-2">
                <i class="bi bi-cloud-check me-1"></i>
                Changes are saved to your account
            </span>
        </div>
    </div>

</form>

<!-- DANGER ZONE -->
<div class="glass p-4">

    <div class="section-title">
        <i class="bi bi-exclamation-triangle fs-4 text-danger"></i>
        <div>
            <h4 class="mb-0 text-danger">Danger Zone</h4>
            <small class="text-secondary">These actions cannot be undone</small>
        </div>
    </div>

    <div class="danger-box">

        <div class="d-flex flex-column flex-md-row justify-content-between align-items-start align-items-md-center gap-3">
            <div>
                <h5 class="mb-1 text-danger">Clear all transaction data</h5>
                <p class="mb-0 text-secondary">
                    This will permanently delete all your expenses, income records, and budgets.
                </p>
            </div>

            <form method="POST" onsubmit="return confirm('Are you sure? This action cannot be undone.');">
                <button type="submit" name="clear_data" class="btn btn-danger">
                    <i class="bi bi-trash me-2"></i>Clear Data
                </button>
            </form>
        </div>

    </div>
</div>
```

</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>

<script>
const themeSelect = document.getElementById('themeSelect');

themeSelect.addEventListener('change', function () {
    if (this.value === 'light') {
        document.documentElement.style.setProperty('--bg', '#f4f7fb');
        document.documentElement.style.setProperty('--text', '#0f172a');
        document.documentElement.style.setProperty('--panel', 'rgba(255,255,255,0.92)');
        document.documentElement.style.setProperty('--input-bg', '#ffffff');
        document.documentElement.style.setProperty('--input-border', '#cbd5e1');
    } else {
        document.documentElement.style.setProperty('--bg', '#0b1020');
        document.documentElement.style.setProperty('--text', '#eef2ff');
        document.documentElement.style.setProperty('--panel', '#121933cc');
        document.documentElement.style.setProperty('--input-bg', '#0f172a');
        document.documentElement.style.setProperty('--input-border', '#334155');
    }
});
</script>



</body>
</html>