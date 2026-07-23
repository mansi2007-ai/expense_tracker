<?php
include 'db.php';

$message = '';
$msg_type = 'success';

if ($_SERVER['REQUEST_METHOD'] == 'POST') {

    $fullname = trim($_POST['fullname']);
    $email = trim($_POST['email']);
    $password = $_POST['password'];
    $confirm_password = $_POST['confirm_password'];

    // Validation
    if ($password !== $confirm_password) {

        $message = 'Passwords do not match!';
        $msg_type = 'danger';

    } else {

        // Check if email already exists
        $check = $conn->prepare("SELECT user_id FROM users WHERE email=?");
        $check->bind_param("s", $email);
        $check->execute();
        $result = $check->get_result();

        if ($result->num_rows > 0) {

            $message = 'Email already registered!';
            $msg_type = 'danger';

        } else {

            // Hash password
            $hashed_password = password_hash($password, PASSWORD_DEFAULT);

            $stmt = $conn->prepare("INSERT INTO users(fullname,email,password) VALUES(?,?,?)");
            $stmt->bind_param("sss", $fullname, $email, $hashed_password); 

            if ($stmt->execute()) {

                $message = 'Registration successful! You can now login.';
                $msg_type = 'success';

            } else {

                $message = 'Something went wrong. Please try again.';
                $msg_type = 'danger';
            }
        }
    }
}
?>

<!DOCTYPE html>

<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1">
<title>Create Account - ExpenseTracker</title>

<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
<link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.css" rel="stylesheet">

<style>
:root{
    --bg:#070b16;
    --panel:rgba(15,23,42,.74);
    --line:rgba(255,255,255,.10);
    --text:#eef2ff;
    --muted:#a5b4fc;
    --blue:#3b82f6;
    --blue2:#2563eb;
    --cyan:#06b6d4;
    --green:#22c55e;
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

.register-shell{
    position:relative;
    z-index:2;
    min-height:100vh;
    display:grid;
    place-items:center;
    padding:24px;
}

.register-card{
    width:min(560px, 100%);
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

.register-card::before{
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
    font-size:28px;
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

.password-meter{
    height:8px;
    border-radius:999px;
    background:#1f2a4d;
    overflow:hidden;
    margin-top:10px;
}

.password-meter span{
    display:block;
    height:100%;
    width:0%;
    background:linear-gradient(90deg,#ef4444,#f59e0b,#22c55e);
    transition:width .25s ease;
}

.btn-register{
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

.btn-register:hover{
    transform:translateY(-2px);
    box-shadow:0 16px 34px rgba(37,99,235,.45);
}

.btn-register:active{
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

@media (max-width: 576px){
    .register-card{
        padding:26px;
        border-radius:24px;
    }

    .brand h1{ font-size:24px; }
    .welcome h2{ font-size:24px; }

    .quick-links{
        grid-template-columns:1fr;
    }
}
</style>

</head>
<body>

<div class="bg-grid"></div>
<div class="orb one"></div>
<div class="orb two"></div>
<div class="orb three"></div>

<div class="register-shell">
    <div class="register-card">

```
    <div class="brand">
        <div class="brand-badge">
            <i class="bi bi-wallet2"></i>
        </div>
        <div>
            <h1>ExpenseTracker</h1>
            <p>Create your smart finance account</p>
        </div>
    </div>

    <div class="welcome">
        <h2>Create your account 🚀</h2>
        <p>Start tracking income, expenses, budgets, and savings goals in one beautiful dashboard.</p>
    </div>

    <?php if($message != ''): ?>
        <div class="alert alert-<?php echo $msg_type; ?> d-flex align-items-center gap-2">
            <i class="bi <?php echo $msg_type == 'success' ? 'bi-check-circle-fill' : 'bi-exclamation-triangle-fill'; ?>"></i>
            <div><?php echo $message; ?></div>
        </div>
    <?php endif; ?>

    <form method="POST" autocomplete="off">

        <div class="mb-3">
            <label class="form-label">Full name</label>
            <div class="input-group">
                <span class="input-group-text"><i class="bi bi-person"></i></span>
                <input type="text" name="fullname" class="form-control" placeholder="Enter your full name" required>
            </div>
        </div>

        <div class="mb-3">
            <label class="form-label">Email address</label>
            <div class="input-group">
                <span class="input-group-text"><i class="bi bi-envelope"></i></span>
                <input type="email" name="email" class="form-control" placeholder="you@example.com" required>
            </div>
        </div>

        <div class="row g-3">
            <div class="col-md-6">
                <label class="form-label">Password</label>

                <div class="input-group">
                    <span class="input-group-text"><i class="bi bi-lock"></i></span>

                    <input type="password" name="password" id="password" class="form-control" placeholder="Create password" required>

                    <button type="button" class="btn btn-outline-secondary toggle-btn" onclick="togglePassword('password','eye1')">
                        <i class="bi bi-eye" id="eye1"></i>
                    </button>
                </div>

                <div class="password-meter"><span id="strengthBar"></span></div>
                <small id="strengthText" class="text-secondary">Use at least 8 characters</small>
            </div>

            <div class="col-md-6">
                <label class="form-label">Confirm password</label>

                <div class="input-group">
                    <span class="input-group-text"><i class="bi bi-shield-lock"></i></span>

                    <input type="password" name="confirm_password" id="confirm_password" class="form-control" placeholder="Confirm password" required>

                    <button type="button" class="btn btn-outline-secondary toggle-btn" onclick="togglePassword('confirm_password','eye2')">
                        <i class="bi bi-eye" id="eye2"></i>
                    </button>
                </div>
            </div>
        </div>

        <div class="form-check mt-4 mb-4">
            <input class="form-check-input" type="checkbox" id="terms" required>
            <label class="form-check-label" for="terms">
                I agree to the <a href="#" class="text-info text-decoration-none">Terms & Conditions</a>
                and <a href="#" class="text-info text-decoration-none">Privacy Policy</a>.
            </label>
        </div>

        <button type="submit" class="btn btn-primary btn-register">
            <i class="bi bi-person-plus me-2"></i>Create Account
        </button>
    </form>

    <div class="divider">already have an account?</div>

    <div class="quick-links">
        <a href="login.php">
            <i class="bi bi-box-arrow-in-right"></i>Login
        </a>

        <a href="index.php">
            <i class="bi bi-house"></i>Back to Home
        </a>
    </div>

    <div class="footer-note">
        By creating an account, you’ll get access to budgeting tools, reports, charts, and savings goal tracking.
    </div>
</div>
```

</div>

<script>
function togglePassword(inputId, eyeId){
    const input = document.getElementById(inputId);
    const eye = document.getElementById(eyeId);

    if(input.type === 'password'){
        input.type = 'text';
        eye.classList.replace('bi-eye','bi-eye-slash');
    }else{
        input.type = 'password';
        eye.classList.replace('bi-eye-slash','bi-eye');
    }
}

// Password strength meter
const passwordInput = document.getElementById('password');
const strengthBar = document.getElementById('strengthBar');
const strengthText = document.getElementById('strengthText');

passwordInput.addEventListener('input', () => {
    const value = passwordInput.value;
    let score = 0;

    if(value.length >= 8) score++;
    if(/[A-Z]/.test(value)) score++;
    if(/[0-9]/.test(value)) score++;
    if(/[^A-Za-z0-9]/.test(value)) score++;

    const widths = ['0%','25%','50%','75%','100%'];
    const labels = ['Very weak','Weak','Medium','Strong','Very strong'];

    strengthBar.style.width = widths[score];
    strengthText.textContent = labels[score];
});
</script>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>

</body>
</html>