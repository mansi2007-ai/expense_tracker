

<!DOCTYPE html>

<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1">
<title>Finora — Smart Personal Finance</title>

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
    --cyan:#06b6d4;
    --green:#22c55e;
    --yellow:#f59e0b;
}

*{box-sizing:border-box}

body{
    margin:0;
    font-family:'Segoe UI',sans-serif;
    color:var(--text);
    background:
        radial-gradient(circle at 15% 20%, rgba(37,99,235,.45), transparent 28%),
        radial-gradient(circle at 85% 15%, rgba(6,182,212,.30), transparent 24%),
        radial-gradient(circle at 80% 80%, rgba(124,58,237,.28), transparent 26%),
        linear-gradient(135deg,#0b1020 0%, #070b16 45%, #02050d 100%);
    overflow-x:hidden;
}

.bg-grid{
    position:fixed;
    inset:0;
    background-image:
        linear-gradient(rgba(255,255,255,.04) 1px, transparent 1px),
        linear-gradient(90deg, rgba(255,255,255,.04) 1px, transparent 1px);
    background-size:42px 42px;
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

.orb.one{width:240px;height:240px;background:#2563eb;top:8%;left:5%;}
.orb.two{width:180px;height:180px;background:#06b6d4;top:22%;right:8%;animation-delay:-3s;}
.orb.three{width:140px;height:140px;background:#7c3aed;bottom:10%;left:12%;animation-delay:-6s;}

@keyframes float{
    0%,100%{transform:translateY(0) translateX(0)}
    50%{transform:translateY(-18px) translateX(12px)}
}

.glass{
    background:var(--panel);
    border:1px solid var(--line);
    backdrop-filter: blur(18px);
    border-radius:26px;
    box-shadow:0 14px 40px rgba(0,0,0,.28);
}

.navbar{
    background:rgba(10,15,30,.72)!important;
    backdrop-filter: blur(14px);
    border-bottom:1px solid var(--line);
}

.navbar .nav-link{color:#dbe4ff!important}
.navbar .nav-link:hover{color:#fff!important}

.btn-primary{
    background:linear-gradient(135deg,var(--blue),#2563eb);
    border:none;
    border-radius:14px;
    padding:12px 18px;
    font-weight:700;
}

.btn-outline-light{
    border-radius:14px;
    padding:12px 18px;
}

.hero{
    position:relative;
    padding:110px 0 70px;
}

.hero h1{
    font-size:clamp(2.6rem,5vw,4.6rem);
    line-height:1.02;
    font-weight:900;
    letter-spacing:-.03em;
}

.hero .lead{
    color:#d6ddff;
    max-width:620px;
    font-size:1.12rem;
}

.badge-soft{
    display:inline-flex;
    align-items:center;
    gap:8px;
    padding:10px 14px;
    border-radius:999px;
    background:rgba(37,99,235,.16);
    border:1px solid rgba(96,165,250,.28);
    color:#cfe0ff;
    font-weight:600;
}

.dashboard-card{
    padding:22px;
    position:relative;
    overflow:hidden;
}

.dashboard-card::before{
    content:'';
    position:absolute;
    inset:-1px;
    border-radius:26px;
    padding:1px;
    background:linear-gradient(135deg, rgba(96,165,250,.9), rgba(34,197,94,.35), rgba(6,182,212,.7));
    -webkit-mask:
        linear-gradient(#000 0 0) content-box,
        linear-gradient(#000 0 0);
    -webkit-mask-composite:xor;
            mask-composite:exclude;
    pointer-events:none;
}

.metric{
    display:flex;
    justify-content:space-between;
    align-items:center;
    margin-bottom:14px;
}

.metric .value{
    font-size:1.9rem;
    font-weight:800;
}

.spark{
    height:74px;
    border-radius:16px;
    background:
      linear-gradient(180deg, rgba(59,130,246,.28), rgba(59,130,246,0)),
      linear-gradient(90deg, rgba(255,255,255,.14) 1px, transparent 1px);
    background-size:auto, 28px 100%;
    position:relative;
    overflow:hidden;
}

.spark::after{
    content:'';
    position:absolute;
    left:0; right:0; bottom:16px;
    height:3px;
    background:linear-gradient(90deg,#60a5fa,#22c55e,#f59e0b);
    clip-path: polygon(0 70%, 10% 40%, 22% 58%, 34% 24%, 48% 48%, 62% 18%, 76% 44%, 88% 14%, 100% 36%, 100% 100%, 0 100%);
}

.section-title{
    text-align:center;
    margin-bottom:46px;
}

.section-title h2{
    font-size:2.2rem;
    font-weight:800;
}

.section-title p{
    color:#b9c4ff;
    max-width:760px;
    margin:12px auto 0;
}

.feature{
    padding:24px;
    height:100%;
    transition:transform .22s ease, box-shadow .22s ease;
}

.feature:hover{
    transform:translateY(-8px);
    box-shadow:0 22px 44px rgba(0,0,0,.32);
}

.feature .icon{
    width:62px;height:62px;
    border-radius:18px;
    display:grid;place-items:center;
    font-size:1.6rem;
    margin-bottom:18px;
    background:linear-gradient(135deg,rgba(59,130,246,.24),rgba(6,182,212,.24));
    border:1px solid rgba(96,165,250,.25);
}

.timeline{
    position:relative;
    padding-left:28px;
}

.timeline::before{
    content:'';
    position:absolute;
    left:8px; top:6px; bottom:6px;
    width:2px;
    background:linear-gradient(#60a5fa,#22c55e);
}

.step{
    position:relative;
    margin-bottom:26px;
    padding-left:22px;
}

.step::before{
    content:'';
    position:absolute;
    left:-2px; top:2px;
    width:14px;height:14px;border-radius:50%;
    background:#60a5fa;
    box-shadow:0 0 0 6px rgba(96,165,250,.18);
}

.goal{
    padding:18px;
    border-radius:20px;
    background:rgba(15,23,42,.72);
    border:1px solid var(--line);
}

.progress{
    height:10px;
    background:#1f2a4d;
    border-radius:999px;
    overflow:hidden;
}

.progress-bar{
    background:linear-gradient(90deg,#22c55e,#4ade80);
}

.testimonial{
    padding:24px;
    height:100%;
}

.avatar{
    width:56px;height:56px;border-radius:50%;
    display:grid;place-items:center;
    font-weight:800;
    background:linear-gradient(135deg,#2563eb,#06b6d4);
}

.cta{
    padding:34px;
    text-align:center;
    position:relative;
    overflow:hidden;
}

.cta::before{
    content:'';
    position:absolute;
    width:280px;height:280px;border-radius:50%;
    background:radial-gradient(circle, rgba(96,165,250,.22), transparent 70%);
    top:-120px; right:-80px;
}


/* CONTACT SECTION */

.glass{

background:rgba(255,255,255,.08);

backdrop-filter:blur(18px);

border:1px solid rgba(255,255,255,.10);

border-radius:20px;

box-shadow:0 15px 35px rgba(0,0,0,.25);

}

.icon-circle{

width:55px;

height:55px;

border-radius:50%;

display:flex;

align-items:center;

justify-content:center;

font-size:22px;

color:white;

}

#contact .form-control{

background:rgba(255,255,255,.06);

border:1px solid rgba(255,255,255,.10);

color:#fff;

}

#contact .form-control::placeholder{

color:#b8c2d1;

}

#contact .form-control:focus{

background:rgba(255,255,255,.08);

border-color:#f59e0b;

box-shadow:none;

color:#fff;

}

footer{
    border-top:1px solid var(--line);
    color:#aeb8ea;
}

@media (max-width: 992px){
    .hero{padding-top:88px}
}
</style>

</head>
<body>

<div class="bg-grid"></div>
<div class="orb one"></div>
<div class="orb two"></div>
<div class="orb three"></div>

<nav class="navbar navbar-expand-lg navbar-dark sticky-top">
  <div class="container py-2">
    <a class="navbar-brand fw-bold fs-4" href="#">
      <i class="bi bi-wallet2 me-2"></i>Finora
    </a>

```
<button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navMenu">
  <span class="navbar-toggler-icon"></span>
</button>

<div class="collapse navbar-collapse" id="navMenu">
  <ul class="navbar-nav ms-auto me-3">
    <li class="nav-item"><a class="nav-link" href="#features">Features</a></li>
    <li class="nav-item"><a class="nav-link" href="#how">How it works</a></li>
    <li class="nav-item"><a class="nav-link" href="#goals">Goals</a></li>
    <li class="nav-item"><a class="nav-link" href="#testimonials">Testimonials</a></li>
  </ul>

  <div class="d-flex gap-2">
    <a href="login.php" class="btn btn-outline-light">Login</a>
    <a href="register.php" class="btn btn-primary">Get Started</a>
  </div>
</div>
```

  </div>
</nav>

<section class="hero">
  <div class="container">
    <div class="row align-items-center g-5">
      <div class="col-lg-6">
        <div class="badge-soft mb-3">
          <i class="bi bi-stars"></i>
          AI-inspired personal finance experience
        </div>

```
    <h1>Control your money before it controls you.</h1>

    <p class="lead mt-4">
      Track income, monitor expenses, plan budgets, and visualize your financial growth with a modern dashboard built for students, professionals, and families.
    </p>

    <div class="d-flex flex-wrap gap-3 mt-4">
      <a href="register.php" class="btn btn-primary btn-lg">
        <i class="bi bi-rocket-takeoff me-2"></i>Create Free Account
      </a>

      <a href="login.php" class="btn btn-outline-light btn-lg">
        <i class="bi bi-play-circle me-2"></i>Open Dashboard
      </a>
    </div>

    <div class="d-flex flex-wrap gap-4 mt-5">
      <div>
        <div class="h3 fw-bold mb-0">₹12L+</div>
        <small class="text-secondary">Money tracked</small>
      </div>
      <div>
        <div class="h3 fw-bold mb-0">25k+</div>
        <small class="text-secondary">Transactions</small>
      </div>
      <div>
        <div class="h3 fw-bold mb-0">98%</div>
        <small class="text-secondary">User satisfaction</small>
      </div>
    </div>
  </div>

  <div class="col-lg-6">
    <div class="glass dashboard-card">
      <div class="metric">
        <div>
          <div class="text-secondary">Current Balance</div>
          <div class="value">₹ 1,24,850</div>
        </div>
        <span class="badge text-bg-success">+18%</span>
      </div>

      <div class="spark mb-4"></div>

      <div class="row g-3">
        <div class="col-6">
          <div class="glass p-3">
            <div class="text-secondary small">Income</div>
            <div class="fs-4 fw-bold text-success">₹ 82,000</div>
          </div>
        </div>

        <div class="col-6">
          <div class="glass p-3">
            <div class="text-secondary small">Expenses</div>
            <div class="fs-4 fw-bold text-danger">₹ 34,500</div>
          </div>
        </div>

        <div class="col-12">
          <div class="glass p-3 d-flex justify-content-between align-items-center">
            <div>
              <div class="text-secondary small">Budget used</div>
              <strong>68% of ₹ 50,000</strong>
            </div>
            <i class="bi bi-pie-chart fs-2 text-info"></i>
          </div>
        </div>
      </div>
    </div>
  </div>
</div>
```

  </div>
</section>

<section id="features" class="py-5">
  <div class="container">
    <div class="section-title">
      <h2>Everything you need to manage money</h2>
      <p>ExpenseTracker combines budgeting, analytics, goals, and reporting into one beautiful and easy-to-use platform.</p>
    </div>

```
<div class="row g-4">
  <div class="col-md-6 col-lg-3">
    <div class="glass feature">
      <div class="icon"><i class="bi bi-cash-stack"></i></div>
      <h5>Income Tracking</h5>
      <p class="text-secondary mb-0">Track salary, freelancing, investments, and other income sources with detailed notes.</p>
    </div>
  </div>

  <div class="col-md-6 col-lg-3">
    <div class="glass feature">
      <div class="icon"><i class="bi bi-receipt"></i></div>
      <h5>Expense Management</h5>
      <p class="text-secondary mb-0">Categorize expenses, add payment methods, and monitor spending patterns instantly.</p>
    </div>
  </div>

  <div class="col-md-6 col-lg-3">
    <div class="glass feature">
      <div class="icon"><i class="bi bi-bar-chart-line"></i></div>
      <h5>Interactive Analytics</h5>
      <p class="text-secondary mb-0">Visualize trends with line charts, category breakdowns, and monthly comparisons.</p>
    </div>
  </div>

  <div class="col-md-6 col-lg-3">
    <div class="glass feature">
      <div class="icon"><i class="bi bi-shield-lock"></i></div>
      <h5>Secure Access</h5>
      <p class="text-secondary mb-0">Password hashing and session-based authentication keep your financial data protected.</p>
    </div>
  </div>
</div>
```

  </div>
</section>

<section id="how" class="py-5">
  <div class="container">
    <div class="row align-items-center g-5">
      <div class="col-lg-5">
        <div class="section-title text-lg-start mb-3">
          <h2>How it works</h2>
          <p>Start tracking your finances in less than two minutes.</p>
        </div>

```
    <div class="timeline">
      <div class="step">
        <h5>Create your account</h5>
        <p class="text-secondary mb-0">Register securely and access your personal dashboard.</p>
      </div>

      <div class="step">
        <h5>Add income & expenses</h5>
        <p class="text-secondary mb-0">Record daily transactions with categories and payment methods.</p>
      </div>

      <div class="step">
        <h5>Set a monthly budget</h5>
        <p class="text-secondary mb-0">Define spending limits and receive visual progress updates.</p>
      </div>

      <div class="step mb-0">
        <h5>Analyze reports</h5>
        <p class="text-secondary mb-0">Generate monthly reports and discover opportunities to save more.</p>
      </div>
    </div>
  </div>

  <div class="col-lg-7">
    <div class="glass p-4">
      <div class="row g-4">
        <div class="col-sm-6">
          <div class="goal">
            <div class="d-flex justify-content-between mb-2">
              <span>Emergency Fund</span><strong>72%</strong>
            </div>
            <div class="progress"><div class="progress-bar" style="width:72%"></div></div>
          </div>
        </div>

        <div class="col-sm-6">
          <div class="goal">
            <div class="d-flex justify-content-between mb-2">
              <span>Vacation</span><strong>41%</strong>
            </div>
            <div class="progress"><div class="progress-bar" style="width:41%"></div></div>
          </div>
        </div>

        <div class="col-sm-6">
          <div class="goal">
            <div class="d-flex justify-content-between mb-2">
              <span>New Laptop</span><strong>88%</strong>
            </div>
            <div class="progress"><div class="progress-bar" style="width:88%"></div></div>
          </div>
        </div>

        <div class="col-sm-6">
          <div class="goal">
            <div class="d-flex justify-content-between mb-2">
              <span>Investments</span><strong>54%</strong>
            </div>
            <div class="progress"><div class="progress-bar" style="width:54%"></div></div>
          </div>
        </div>
      </div>

      <div class="glass p-3 mt-4 d-flex justify-content-between align-items-center">
        <div>
          <div class="text-secondary small">Monthly savings target</div>
          <strong>₹ 25,000</strong>
        </div>
        <span class="badge text-bg-success">On track</span>
      </div>
    </div>
  </div>
</div>
```

  </div>
</section>

<section id="goals" class="py-5">
  <div class="container">
    <div class="section-title">
      <h2>Built for real financial goals</h2>
      <p>Whether you’re saving for an emergency fund, a trip, or a new gadget, ExpenseTracker helps you stay focused and motivated.</p>
    </div>

```
<div class="row g-4">
  <div class="col-md-4">
    <div class="glass feature text-center">
      <div class="icon mx-auto"><i class="bi bi-heart-pulse"></i></div>
      <h5>Emergency Fund</h5>
      <p class="text-secondary">Build a safety net with automated progress tracking.</p>
    </div>
  </div>

  <div class="col-md-4">
    <div class="glass feature text-center">
      <div class="icon mx-auto"><i class="bi bi-airplane"></i></div>
      <h5>Travel Planning</h5>
      <p class="text-secondary">Set destination budgets and monitor savings growth.</p>
    </div>
  </div>

  <div class="col-md-4">
    <div class="glass feature text-center">
      <div class="icon mx-auto"><i class="bi bi-cpu"></i></div>
      <h5>Tech Purchases</h5>
      <p class="text-secondary">Plan for laptops, phones, and other expensive upgrades.</p>
    </div>
  </div>
</div>
```

  </div>
</section>

<section id="testimonials" class="py-5">
  <div class="container">
    <div class="section-title">
      <h2>Loved by students and professionals</h2>
      <p>People use ExpenseTracker every day to reduce overspending and improve savings habits.</p>
    </div>

```
<div class="row g-4">
  <div class="col-md-4">
    <div class="glass testimonial">
      <div class="d-flex align-items-center gap-3 mb-3">
        <div class="avatar">MP</div>
        <div>
          <strong>Mansi Patil</strong>
          <div class="text-secondary small">Computer Engineering Student</div>
        </div>
      </div>
      <p class="mb-0">“The dashboard feels like a real banking app. I can finally see where my money goes every month.”</p>
    </div>
  </div>

  <div class="col-md-4">
    <div class="glass testimonial">
      <div class="d-flex align-items-center gap-3 mb-3">
        <div class="avatar">AR</div>
        <div>
          <strong>Aditya Rao</strong>
          <div class="text-secondary small">Freelance Designer</div>
        </div>
      </div>
      <p class="mb-0">“Budget alerts and category reports helped me cut unnecessary expenses within the first week.”</p>
    </div>
  </div>

  <div class="col-md-4">
    <div class="glass testimonial">
      <div class="d-flex align-items-center gap-3 mb-3">
        <div class="avatar">SK</div>
        <div>
          <strong>Sneha Kulkarni</strong>
          <div class="text-secondary small">Software Engineer</div>
        </div>
      </div>
      <p class="mb-0">“The UI is beautiful, responsive, and much easier to use than spreadsheet-based tracking.”</p>
    </div>
  </div>
</div>
```

  </div>
</section>

<section class="py-5">
  <div class="container">
    <div class="glass cta">
      <div class="position-relative">
        <h2 class="fw-bold display-6">Ready to transform your financial habits?</h2>
        <p class="text-secondary mt-3 mb-4">Join ExpenseTracker today and start tracking your money with clarity, confidence, and beautiful analytics.</p>

```
    <div class="d-flex flex-wrap justify-content-center gap-3">
      <a href="register.php" class="btn btn-primary btn-lg px-4">
        <i class="bi bi-person-plus me-2"></i>Create Free Account
      </a>

      <a href="login.php" class="btn btn-outline-light btn-lg px-4">
        <i class="bi bi-box-arrow-in-right me-2"></i>Login
      </a>
    </div>
  </div>
</div>
```

  </div>
</section>

<footer class="py-4">
  <div class="container d-flex flex-column flex-lg-row justify-content-between align-items-center gap-3">
    <div>
      <strong class="text-white">ExpenseTracker</strong>
      <div class="small">Smart personal finance manager built with PHP, MySQL, Bootstrap, and Chart.js.</div>
    </div>

```
<div class="d-flex gap-3 fs-5">
  <a href="#" class="text-decoration-none text-light"><i class="bi bi-github"></i></a>
  <a href="#" class="text-decoration-none text-light"><i class="bi bi-linkedin"></i></a>
  <a href="#" class="text-decoration-none text-light"><i class="bi bi-envelope"></i></a>
</div>
```

  </div>

  <!-- ===========================
        CONTACT US
=========================== -->

<section id="contact" class="py-5">

    <div class="container">

        <div class="text-center mb-5">

            <h2 class="fw-bold text-white">
                <i class="bi bi-envelope-fill text-warning me-2"></i>
                Contact Us
            </h2>

            <p class="text-secondary">
                Have questions or feedback? We'd love to hear from you.
            </p>

        </div>

        <div class="row g-4"> 

            <!-- Contact Information -->

            <div class="col-lg-4">

                <div class="glass h-100 p-4">

                    <h4 class="fw-bold mb-4 text-warning">

                        Get In Touch

                 <h4 class="fw-bold mb-4 text-warning">
    <i class="bi bi-person-badge-fill me-2"></i>
    Developer
</h4>

<div class="mb-4">

    <div class="d-flex align-items-center">

        <div class="icon-circle bg-warning me-3">

            <i class="bi bi-person-fill text-dark"></i>

        </div>

        <div>

            <strong>Developer</strong>

            <p class="mb-0 text-secondary">
                Mansi Patil
            </p>

        </div>

    </div>

</div>

<div class="mb-4">

    <div class="d-flex align-items-center">

        <div class="icon-circle bg-success me-3">

            <i class="bi bi-envelope-fill"></i>

        </div>

        <div>

            <strong>Email</strong>

            <p class="mb-0 text-secondary">
                finorasupport@gmail.com
            </p>

        </div>

    </div>

</div>

<div class="mb-4">

    <div class="d-flex align-items-center">

        <div class="icon-circle bg-primary me-3">

            <i class="bi bi-telephone-fill"></i>

        </div>

        <div>

            <strong>Phone</strong>

            <p class="mb-0 text-secondary">
                +91 8983901511
            </p>

        </div>

    </div>

</div>

<div>

    <div class="d-flex align-items-center">

        <div class="icon-circle bg-danger me-3">

            <i class="bi bi-geo-alt-fill"></i>

        </div>

        <div>

            <strong>Location</strong>

            <p class="mb-0 text-secondary">
                Pune, Maharashtra, India
            </p>

        </div>

    </div>

</div>
</div>
</div>
            <!-- Contact Form -->

            <div class="col-lg-8">

                <div class="glass p-4">

                    <h4 class="fw-bold mb-4 text-warning">

                        Send us a Message

                    </h4>

                    <form method="POST">

                        <div class="row">

                            <div class="col-md-6 mb-3">

                                <label class="form-label text-light">
                                    Full Name
                                </label>

                                <input
                                    type="text"
                                    name="name"
                                    class="form-control"
                                    placeholder="Enter your name"
                                    required>

                            </div>

                            <div class="col-md-6 mb-3">

                                <label class="form-label text-light">
                                    Email Address
                                </label>

                                <input
                                    type="email"
                                    name="email"
                                    class="form-control"
                                    placeholder="Enter your email"
                                    required>

                            </div>

                        </div>

                        <div class="mb-3">

                            <label class="form-label text-light">
                                Subject
                            </label>

                            <input
                                type="text"
                                name="subject"
                                class="form-control"
                                placeholder="Enter subject"
                                required>

                        </div>

                        <div class="mb-4">

                            <label class="form-label text-light">
                                Message
                            </label>

                            <textarea
                                name="message"
                                rows="6"
                                class="form-control"
                                placeholder="Write your message here..."
                                required></textarea>

                        </div>

                        <button
                            type="submit"
                            class="btn btn-warning px-5">

                            <i class="bi bi-send-fill me-2"></i>

                            Send Message

                        </button>

                    </form>

                </div>

            </div>

        </div>

    </div>

</section>

  <div class="container text-center small mt-3">
    © 2026  Finora-ExpenseTracker. All rights reserved.
  </div>
</footer>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>

</body>
</html>