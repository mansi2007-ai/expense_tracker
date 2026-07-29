<?php
session_start();
include 'db.php';

$error = '';

if ($_SERVER['REQUEST_METHOD'] == 'POST') {

    $email = trim($_POST['email']);
    $password = $_POST['password'];

    $stmt = $conn->prepare("SELECT user_id, fullname, password FROM users WHERE email=?");
    $stmt->bind_param("s", $email);
    $stmt->execute();
    $result = $stmt->get_result();

    if ($result->num_rows == 1) {

        $user = $result->fetch_assoc();

        // If you used password_hash() during registration
        if (password_verify($password, $user['password'])) {

            $_SESSION['user_id'] = $user['user_id'];
            $_SESSION['fullname'] = $user['fullname'];

            header('Location: dashboard.php');
            exit();

        } else {
            $error = 'Invalid password!';
        }

    } else {
        $error = 'Email not found!';
    }
}
?>

<!DOCTYPE html>

<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1">
<title>Finora - Login </title>

<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
<link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.css" rel="stylesheet">

<style>
:root{
    --bg:#070b16;
    --panel:rgba(15,23,42,.72);
    --line:rgba(255,255,255,.10);
    --text:#eef2ff;
    --muted:#a5b4fc;
    --blue:#3b82f6;
    --blue2:#2563eb;
    --cyan:#06b6d4;
}

*{ box-sizing:border-box; }

body{
    min-height:100vh;
    margin:0;
    font-family:'Segoe UI',sans-serif;
    color:var(--text);
    background:
        radial-gradient(circle at 15% 20%, rgba(37,99,235,.45), transparent 28%),
        radial-gradient(circle at 85% 15%, rgba(6,182,212,.30), transparent 24%),
        radial-gradient(circle at 80% 80%, rgba(124,58,237,.28), transparent 26%),
        linear-gradient(135deg,#0b1020 0%, #070b16 45%, #02050d 100%);
    overflow:hidden;
}

.bg-grid{
    position:fixed;
    inset:0;
    background-image:
        linear-gradient(rgba(255,255,255,.04) 1px, transparent 1px),
        linear-gradient(90deg, rgba(255,255,255,.04) 1px, transparent 1px);
    background-size:40px 40px;
    mask-image: radial-gradient(circle at center, black 45%, transparent 85%);
    pointer-events:none;
}

.orb{
    position:absolute;
    border-radius:50%;
    filter: blur(10px);
    opacity:.45;
    animation: float 10s ease-in-out infinite;
}

.orb.one{
    width:220px;height:220px;
    background:#2563eb;
    top:8%; left:6%;
}

.orb.two{
    width:160px;height:160px;
    background:#06b6d4;
    bottom:10%; right:10%;
    animation-delay:-3s;
}

.orb.three{
    width:120px;height:120px;
    background:#7c3aed;
    top:55%; left:18%;
    animation-delay:-6s;
}

@keyframes float{
    0%,100%{ transform:translateY(0) translateX(0); }
    50%{ transform:translateY(-18px) translateX(10px); }
}

.login-shell{
    position:relative;
    z-index:2;
    min-height:100vh;
    display:grid;
    place-items:center;
    padding:24px;
}

.login-card{
    width:min(460px, 100%);
    background:var(--panel);
    border:1px solid var(--line);
    backdrop-filter: blur(22px);
    border-radius:30px;
    padding:34px;
    box-shadow:
        0 20px 60px rgba(0,0,0,.45),
        inset 0 1px 0 rgba(255,255,255,.06);
    position:relative;
    overflow:hidden;
}

.login-card::before{
    content:'';
    position:absolute;
    inset:-2px;
    border-radius:32px;
    padding:1px;
    background:linear-gradient(135deg, rgba(96,165,250,.9), rgba(34,197,94,.35), rgba(6,182,212,.7));
    -webkit-mask:
        linear-gradient(#000 0 0) content-box,
        linear-gradient(#000 0 0);
    -webkit-mask-composite:xor;
            mask-composite:exclude;
    pointer-events:none;
}

.brand{
    display:flex;
    align-items:center;
    gap:14px;
    margin-bottom:10px;
}

.brand-badge{
    width:58px;height:58px;
    border-radius:18px;
    display:grid;
    place-items:center;
    background:linear-gradient(135deg,var(--blue),var(--cyan));
    box-shadow:0 10px 24px rgba(37,99,235,.35);
    font-size:28px;
}

.brand h1{
    margin:0;
    font-size:28px;
    font-weight:800;
}

.brand p{
    margin:2px 0 0;
    color:var(--muted);
    font-size:14px;
}

.welcome{
    margin:18px 0 26px;
}

.welcome h2{
    font-size:26px;
    font-weight:800;
    margin-bottom:6px;
}

.welcome p{
    color:#c7d2fe;
    margin:0;
}

.input-group-text{
    background:#0f172a;
    border:1px solid #334155;
    color:#cbd5e1;
    border-radius:14px 0 0 14px;
}

.form-control{
    background:#0f172a;
    border:1px solid #334155;
    color:#fff;
    border-radius:0 14px 14px 0;
    padding:13px 14px;
}

.form-control:focus{
    background:#0f172a;
    color:#fff;
    border-color:#60a5fa;
    box-shadow:0 0 0 .2rem rgba(96,165,250,.18);
}

.toggle-btn{
    border-radius:0 14px 14px 0 !important;
}

.btn-login{
    width:100%;
    border:none;
    border-radius:16px;
    padding:14px;
    font-weight:700;
    font-size:16px;
    background:linear-gradient(135deg,var(--blue),var(--blue2));
    box-shadow:0 12px 26px rgba(37,99,235,.35);
    transition:transform .18s ease, box-shadow .18s ease;
}

.btn-login:hover{
    transform:translateY(-2px);
    box-shadow:0 16px 34px rgba(37,99,235,.45);
}

.btn-login:active{
    transform:translateY(0);
}

.divider{
    display:flex;
    align-items:center;
    gap:12px;
    color:#94a3b8;
    margin:22px 0;
    font-size:13px;
}

.divider::before,
.divider::after{
    content:'';
    height:1px;
    background:#334155;
    flex:1;
}

.quick-links{
    display:grid;
    grid-template-columns:1fr 1fr;
    gap:12px;
}

.quick-links a{
    text-decoration:none;
    color:#e2e8f0;
    background:#0f172a;
    border:1px solid #334155;
    border-radius:14px;
    padding:12px 14px;
    display:flex;
    align-items:center;
    justify-content:center;
    gap:8px;
    transition:.2s ease;
}

.quick-links a:hover{
    border-color:#60a5fa;
    transform:translateY(-2px);
}

.footer-note{
    text-align:center;
    color:#94a3b8;
    font-size:13px;
    margin-top:22px;
}

.footer-note a{
    color:#7dd3fc;
    text-decoration:none;
}

.alert{
    border-radius:14px;
}

@media (max-width: 520px){
    .login-card{ padding:26px; border-radius:24px; }
    .brand h1{ font-size:24px; }
    .welcome h2{ font-size:22px; }
    .quick-links{ grid-template-columns:1fr; }
}
</style>

</head>
<body>

<div class="bg-grid"></div>
<div class="orb one"></div>
<div class="orb two"></div>
<div class="orb three"></div>

<div class="login-shell">
    <div class="login-card">


    <div class="brand">
        <div class="brand-badge">
            <i class="bi bi-wallet2"></i>
        </div>
        <div>
            <h1>Finora</h1>
            <p>Smart personal finance manager</p>
        </div>
    </div>

    <div class="welcome">
        <h2>Sign in to continue</h2>
        <p>Access your dashboard, budgets, reports, and savings goals.</p>
    </div>

    <?php if($error != ''): ?>
        <div class="alert alert-danger d-flex align-items-center gap-2">
            <i class="bi bi-exclamation-triangle-fill"></i>
            <div><?php echo $error; ?></div>
        </div>
    <?php endif; ?>

    <form method="POST" autocomplete="off">

        <div class="mb-3">
            <label class="form-label">Email address</label>
            <div class="input-group">
                <span class="input-group-text"><i class="bi bi-envelope"></i></span>
                <input type="email" name="email" class="form-control" placeholder="you@example.com" required>
            </div>
        </div>

        <div class="mb-2">
            <label class="form-label">Password</label>
            <div class="input-group">
                <span class="input-group-text"><i class="bi bi-lock"></i></span>
                <input type="password" name="password" id="password" class="form-control" placeholder="Enter your password" required>

                <button type="button" class="btn btn-outline-secondary toggle-btn" onclick="togglePassword()">
                    <i class="bi bi-eye" id="eyeIcon"></i>
                </button>
            </div>
        </div>

        <div class="d-flex justify-content-between align-items-center mb-4">
            <div class="form-check">
                <input class="form-check-input" type="checkbox" id="remember">
                <label class="form-check-label" for="remember">Remember me</label>
            </div>

            <a href="#" class="text-info text-decoration-none small">Forgot password?</a>
        </div>

        <button type="submit" class="btn btn-primary btn-login">
            <i class="bi bi-box-arrow-in-right me-2"></i>Login to Dashboard
        </button>
    </form>

    <div class="divider">or continue with</div>

    <div class="quick-links">
        <a href="register.php">
            <i class="bi bi-person-plus"></i>Create account
        </a>

        <a href="index.php">
            <i class="bi bi-house"></i>Back to home
        </a>
    </div>

    <div class="footer-note">
        Don’t have an account?
        <a href="register.php">Register here</a>
    </div>
</div>
```

</div>

<script>
function togglePassword(){
    const password = document.getElementById('password');
    const eye = document.getElementById('eyeIcon');

    if(password.type === 'password'){
        password.type = 'text';
        eye.classList.remove('bi-eye');
        eye.classList.add('bi-eye-slash');
    }else{
        password.type = 'password';
        eye.classList.remove('bi-eye-slash');
        eye.classList.add('bi-eye');
    }
}
</script>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>

</body>
</html>