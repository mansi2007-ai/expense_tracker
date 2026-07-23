<!DOCTYPE html>

<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1">
<title>ExpenseTracker Notifications</title>

<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
<link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.css" rel="stylesheet">

<style>
body{
    background: radial-gradient(circle at top left,#1d4ed8 0%,#0b1020 35%,#070b16 100%);
    min-height:100vh;
    display:flex;
    justify-content:center;
    align-items:flex-start;
    padding:40px;
    font-family:'Segoe UI',sans-serif;
    color:white;
}

.glass{
    background:rgba(18,25,51,.92);
    border:1px solid rgba(255,255,255,.08);
    backdrop-filter: blur(18px);
    border-radius:22px;
    box-shadow:0 18px 50px rgba(0,0,0,.35);
}

.topbar{
    width:100%;
    max-width:900px;
    padding:18px 22px;
    display:flex;
    justify-content:space-between;
    align-items:center;
}

.dropdown-menu.glass{
    width:360px;
    padding:0;
    border:none;
}

.dropdown-item{
    color:white;
    padding:16px;
    border-bottom:1px solid rgba(255,255,255,.08);
    transition:all .2s ease;
}

.dropdown-item:hover{
    background:rgba(59,130,246,.12);
    transform:translateX(4px);
}

.dropdown-item:last-child{
    border-bottom:none;
}

.notif-icon{
    width:44px;
    height:44px;
    border-radius:14px;
    display:grid;
    place-items:center;
    background:rgba(255,255,255,.06);
}

.mark-read{
    font-size:13px;
    color:#7dd3fc;
    text-decoration:none;
}

.mark-read:hover{
    text-decoration:underline;
}

.badge-new{
    background:#2563eb;
}
</style>

</head>
<body>

<div class="glass topbar">

```
<div>
    <h4 class="mb-0">Dashboard</h4>
    <small class="text-secondary">Welcome back, Mansi 👋</small>
</div>

<!-- Notification Dropdown -->
<div class="dropdown">

    <button class="btn btn-dark position-relative border border-secondary"
            data-bs-toggle="dropdown" aria-expanded="false">

        <i class="bi bi-bell fs-5"></i>

        <!-- Unread count -->
        <span id="notifCount"
              class="position-absolute top-0 start-100 translate-middle badge rounded-pill bg-danger">
            3
        </span>
    </button>

    <div class="dropdown-menu dropdown-menu-end glass">

        <!-- Header -->
        <div class="d-flex justify-content-between align-items-center px-3 py-3 border-bottom border-secondary">
            <div>
                <h6 class="mb-0 text-white">Notifications</h6>
                <small class="text-secondary"><span id="unreadText">3 unread</span></small>
            </div>

            <a href="#" class="text-info text-decoration-none small">View all</a>
        </div>

        <!-- Notification list -->
        <div id="notifList" style="max-height:420px; overflow-y:auto;">

            <!-- Notification 1 -->
            <div class="dropdown-item" data-read="false">

                <div class="d-flex align-items-start gap-3">

                    <div class="notif-icon">
                        <i class="bi bi-exclamation-triangle text-danger fs-5"></i>
                    </div>

                    <div class="flex-grow-1">

                        <div class="d-flex justify-content-between align-items-start">
                            <strong>Budget Exceeded</strong>
                            <span class="badge badge-new rounded-pill">New</span>
                        </div>

                        <div class="small text-secondary mt-1">
                            Your expenses have exceeded your monthly budget.
                        </div>

                        <div class="small text-secondary mt-2">
                            <i class="bi bi-clock me-1"></i>21 Jul, 10:42 AM
                        </div>

                        <a href="#" class="mark-read" onclick="markRead(this)">Mark as read</a>
                    </div>
                </div>
            </div>

            <!-- Notification 2 -->
            <div class="dropdown-item" data-read="false">

                <div class="d-flex align-items-start gap-3">

                    <div class="notif-icon">
                        <i class="bi bi-wallet2 text-warning fs-5"></i>
                    </div>

                    <div class="flex-grow-1">

                        <div class="d-flex justify-content-between align-items-start">
                            <strong>Low Balance</strong>
                            <span class="badge badge-new rounded-pill">New</span>
                        </div>

                        <div class="small text-secondary mt-1">
                            Your available balance is below ₹5,000.
                        </div>

                        <div class="small text-secondary mt-2">
                            <i class="bi bi-clock me-1"></i>21 Jul, 10:40 AM
                        </div>

                        <a href="#" class="mark-read" onclick="markRead(this)">Mark as read</a>
                    </div>
                </div>
            </div>

            <!-- Notification 3 -->
            <div class="dropdown-item" data-read="false">

                <div class="d-flex align-items-start gap-3">

                    <div class="notif-icon">
                        <i class="bi bi-info-circle text-info fs-5"></i>
                    </div>

                    <div class="flex-grow-1">

                        <div class="d-flex justify-content-between align-items-start">
                            <strong>No Income Recorded</strong>
                            <span class="badge badge-new rounded-pill">New</span>
                        </div>

                        <div class="small text-secondary mt-1">
                            You have not added any income for this month.
                        </div>

                        <div class="small text-secondary mt-2">
                            <i class="bi bi-clock me-1"></i>21 Jul, 09:55 AM
                        </div>

                        <a href="#" class="mark-read" onclick="markRead(this)">Mark as read</a>
                    </div>
                </div>
            </div>

            <!-- Read notification -->
            <div class="dropdown-item" data-read="true">

                <div class="d-flex align-items-start gap-3">

                    <div class="notif-icon">
                        <i class="bi bi-check-circle text-success fs-5"></i>
                    </div>

                    <div class="flex-grow-1">

                        <div class="d-flex justify-content-between align-items-start">
                            <strong class="text-light">Expense Added</strong>
                        </div>

                        <div class="small text-secondary mt-1">
                            Grocery expense of ₹1,250 was added successfully.
                        </div>

                        <div class="small text-secondary mt-2">
                            <i class="bi bi-clock me-1"></i>20 Jul, 08:15 PM
                        </div>
                    </div>
                </div>
            </div>

        </div>

        <!-- Footer -->
        <div class="p-3 border-top border-secondary text-center">
            <button class="btn btn-outline-light btn-sm w-100" onclick="markAllRead()">
                <i class="bi bi-check2-all me-1"></i>Mark all as read
            </button>
        </div>

    </div>
</div>
```

</div>

<script>
function updateCounter(){
    const unread = document.querySelectorAll('#notifList .dropdown-item[data-read="false"]').length;

    const countBadge = document.getElementById('notifCount');
    const unreadText = document.getElementById('unreadText');

    unreadText.innerText = unread + ' unread';

    if(unread === 0){
        countBadge.style.display = 'none';
    }else{
        countBadge.style.display = 'inline-block';
        countBadge.innerText = unread;
    }
}

function markRead(link){
    event.preventDefault();

    const item = link.closest('.dropdown-item');

    if(item.dataset.read === 'false'){

        item.dataset.read = 'true';

        const badge = item.querySelector('.badge-new');
        if(badge) badge.remove();

        const title = item.querySelector('strong');
        title.classList.add('text-light');

        link.remove();

        updateCounter();
    }
}

function markAllRead(){

    document.querySelectorAll('#notifList .dropdown-item').forEach(item => {

        item.dataset.read = 'true';

        const badge = item.querySelector('.badge-new');
        if(badge) badge.remove();

        const link = item.querySelector('.mark-read');
        if(link) link.remove();

        const title = item.querySelector('strong');
        title.classList.add('text-light');
    });

    updateCounter();
}

// Initialize counter
updateCounter();
</script>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>

</body>
</html>