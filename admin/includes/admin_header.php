<?php
require_once __DIR__ . '/../../config/db.php';
requireAdmin();
$currentPage = basename($_SERVER['PHP_SELF'], '.php');

// Ambil jumlah pending order awal untuk badge sidebar
$pdo_header = getDB();
$pendingBadge = (int)$pdo_header->query("SELECT COUNT(*) FROM orders WHERE payment_status = 'pending'")->fetchColumn();
$initialMaxId = (int)$pdo_header->query("SELECT COALESCE(MAX(id), 0) FROM orders")->fetchColumn();
?>
<!DOCTYPE html>
<html lang="id">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title><?= $pageTitle ?? 'Admin - HanzelStore' ?></title>
<style>
*,*::before,*::after{box-sizing:border-box;margin:0;padding:0}
:root{
    --bg:#0D1117;--card:#161B22;--hover:#1F242C;
    --accent:#6C63FF;--accent-hover:#7E76FF;--alight:#8B85FF;
    --text:#F0F0F5;--muted:#9BA1B6;--dim:#7E84A3;
    --border:#262B34;--borderl:#353B47;
    --green:#3FB950;--red:#F85149;--yellow:#D29922;
    --r:8px;--rs:6px;--t:.15s ease;
}
body{font-family:'Segoe UI',system-ui,-apple-system,sans-serif;background:var(--bg);color:var(--text);min-height:100vh;line-height:1.6}
a{color:var(--alight);text-decoration:none;cursor:pointer}
a:focus-visible, button:focus-visible, input:focus-visible, select:focus-visible, textarea:focus-visible{
    outline:2px solid var(--accent);outline-offset:2px;
}
@media (prefers-reduced-motion: reduce){
    *,*::before,*::after{
        animation-duration:.01ms!important;
        animation-iteration-count:1!important;
        transition-duration:.01ms!important;
    }
}
h1,h2,h3{line-height:1.3;font-weight:700}
.container{max-width:1280px;margin:0 auto;padding:0 24px}
img{max-width:100%;display:block}
code,.mono{font-family:'Cascadia Code','Fira Code',monospace;font-size:.85em}

/* LAYOUT */
.admin-layout{display:flex;min-height:100vh}
.sidebar{width:230px;background:var(--card);border-right:1px solid var(--border);flex-shrink:0;position:sticky;top:0;height:100vh;display:flex;flex-direction:column}
.sidebar-brand{padding:22px 20px;font-family:'Cascadia Code',monospace;font-weight:700;font-size:1.1rem;border-bottom:1px solid var(--border);display:flex;align-items:center;gap:8px}
.sidebar-brand span{color:var(--accent);font-size:1.3rem}
.sidebar-nav{flex:1;padding:16px 12px}
.sidebar-nav a{display:flex;align-items:center;justify-content:space-between;gap:10px;padding:10px 14px;border-radius:var(--rs);color:var(--muted);font-size:.88rem;font-weight:500;margin-bottom:4px;transition:all var(--t)}
.sidebar-nav a:hover{background:var(--hover);color:var(--text)}
.sidebar-nav a.active{background:rgba(108,99,255,.12);color:var(--alight);border:1px solid rgba(108,99,255,.25)}
.sidebar-badge{background:var(--yellow);color:#0D1117;font-size:.72rem;font-weight:800;padding:2px 7px;border-radius:999px;line-height:1}
.sidebar-foot{padding:16px;border-top:1px solid var(--border)}
.main{flex:1;padding:32px 36px;max-width:calc(100% - 230px)}

/* TOPBAR */
.topbar{display:flex;align-items:center;justify-content:space-between;margin-bottom:28px}
.topbar h1{font-size:1.5rem}
.topbar p{color:var(--muted);font-size:.88rem;margin-top:4px}

/* STAT CARDS */
.stat-grid{display:grid;grid-template-columns:repeat(auto-fit,minmax(200px,1fr));gap:16px;margin-bottom:28px}
.stat-card{background:var(--card);border:1px solid var(--border);border-radius:var(--r);padding:20px}
.stat-card .ico{font-size:1.6rem;margin-bottom:10px}
.stat-card .num{font-size:1.6rem;font-weight:800}
.stat-card .lbl{font-size:.8rem;color:var(--muted);margin-top:2px}

/* TABLE */
.table-wrap{background:var(--card);border:1px solid var(--border);border-radius:var(--r);overflow:hidden}
table{width:100%;border-collapse:collapse}
th{text-align:left;font-size:.75rem;text-transform:uppercase;letter-spacing:.06em;color:var(--dim);padding:14px 18px;border-bottom:1px solid var(--border);background:rgba(255,255,255,.02)}
td{padding:14px 18px;border-bottom:1px solid var(--border);font-size:.87rem}
tr:last-child td{border-bottom:none}
tr:hover td{background:rgba(255,255,255,.015)}

/* BADGES / STATUS */
.status{font-size:.72rem;font-weight:700;padding:4px 10px;border-radius:999px;text-transform:uppercase;letter-spacing:.04em}
.status-pending{background:rgba(210,153,34,.15);color:var(--yellow);border:1px solid rgba(210,153,34,.3)}
.status-confirmed{background:rgba(63,185,80,.15);color:var(--green);border:1px solid rgba(63,185,80,.3)}
.status-rejected{background:rgba(248,81,73,.15);color:var(--red);border:1px solid rgba(248,81,73,.3)}

/* BUTTONS */
.btn{display:inline-flex;align-items:center;justify-content:center;gap:6px;padding:9px 18px;border-radius:var(--rs);font-size:.85rem;font-weight:600;cursor:pointer;border:none;transition:all var(--t);line-height:1;text-decoration:none;font-family:inherit}
.btn-primary{background:var(--accent);color:#fff}
.btn-primary:hover{background:var(--accent-hover);transform:translateY(-1px)}
.btn-outline{background:transparent;border:1px solid var(--borderl);color:var(--muted)}
.btn-outline:hover{border-color:var(--accent);color:var(--text);background:rgba(108,99,255,.05)}
.btn-danger{background:rgba(248,81,73,.12);color:var(--red);border:1px solid rgba(248,81,73,.3)}
.btn-danger:hover{background:rgba(248,81,73,.22)}
.btn-success{background:var(--green);color:#0D1117}
.btn-success:hover{background:#56d364}
.btn-sm{padding:6px 13px;font-size:.78rem}
.btn-block{width:100%}

/* FORM */
.form-card{background:var(--card);border:1px solid var(--border);border-radius:var(--r);padding:26px;max-width:640px}
.form-group{margin-bottom:16px}
.form-label{display:block;font-size:.84rem;font-weight:600;color:var(--muted);margin-bottom:7px}
.form-control{width:100%;background:var(--bg);border:1px solid var(--borderl);color:var(--text);border-radius:var(--rs);padding:10px 14px;font-size:.9rem;font-family:inherit}
.form-control:focus{outline:none;border-color:var(--accent)}
.form-row{display:grid;grid-template-columns:1fr 1fr;gap:14px}
.form-hint{font-size:.76rem;color:var(--dim);margin-top:5px}

/* ALERT */
.alert{padding:13px 16px;border-radius:var(--rs);margin-bottom:18px;font-size:.875rem}
.alert-success{background:rgba(63,185,80,.1);border:1px solid rgba(63,185,80,.3);color:var(--green)}
.alert-danger{background:rgba(248,81,73,.1);border:1px solid rgba(248,81,73,.3);color:var(--red)}

/* MISC */
.empty{text-align:center;padding:60px 24px;color:var(--muted)}
.thumb{width:42px;height:42px;border-radius:var(--rs);background:rgba(108,99,255,.08);border:1px solid rgba(108,99,255,.2);display:flex;align-items:center;justify-content:center;font-size:1.3rem;overflow:hidden;flex-shrink:0}
.thumb img{width:100%;height:100%;object-fit:cover}
.flex-g{display:flex;align-items:center;gap:10px}

@media(max-width:900px){
    .admin-layout{flex-direction:column}
    .sidebar{width:100%;height:auto;position:relative}
    .main{max-width:100%;padding:20px}
    .form-row{grid-template-columns:1fr}
}

/* NOTIFICATION POPUP */
#notif-container{position:fixed;bottom:24px;right:24px;z-index:99999;display:flex;flex-direction:column;gap:12px;max-width:380px;width:100%;pointer-events:none}
.notif-toast{pointer-events:auto;background:#161B22;border:1px solid var(--borderl);box-shadow:0 8px 24px rgba(0,0,0,.6);border-radius:var(--r);padding:16px 18px;color:#F0F0F5;animation:notifIn .3s ease forwards}
@keyframes notifIn{from{opacity:0;transform:translateY(30px) scale(.9)}to{opacity:1;transform:translateY(0) scale(1)}}
.notif-head{display:flex;align-items:center;justify-content:space-between;font-size:.82rem;font-weight:700;color:#3FB950;margin-bottom:6px}
.notif-body{font-size:.9rem;line-height:1.4}
.notif-body strong{color:#8B85FF}
.notif-foot{display:flex;align-items:center;justify-content:space-between;margin-top:8px;padding-top:8px;border-top:1px solid #21262D}
.notif-close{background:none;border:none;color:#8B8FA8;cursor:pointer;font-size:1.1rem;line-height:1}
.notif-close:hover{color:#F0F0F5}
</style>
</head>
<body>
<div class="admin-layout">
    <aside class="sidebar">
        <div class="sidebar-brand"><span>‖</span> HanzelStore Admin</div>
        <nav class="sidebar-nav">
            <a href="dashboard.php" class="<?= $currentPage === 'dashboard' ? 'active' : '' ?>">
                <span>📊 Dashboard</span>
            </a>
            <a href="orders.php" class="<?= in_array($currentPage, ['orders','order_detail']) ? 'active' : '' ?>">
                <span>🧾 Pesanan</span>
                <span class="sidebar-badge" id="pending-badge" style="<?= $pendingBadge > 0 ? '' : 'display:none' ?>"><?= $pendingBadge ?></span>
            </a>
            <a href="products.php" class="<?= in_array($currentPage, ['products','product_form']) ? 'active' : '' ?>">
                <span>📦 Produk</span>
            </a>
            <a href="../index.php" target="_blank">
                <span>🌐 Lihat Toko</span>
            </a>
        </nav>
        <div class="sidebar-foot">
            <a href="logout.php" class="btn btn-outline btn-block">🚪 Logout</a>
        </div>
    </aside>
    <main class="main">

    <!-- Notifikasi Popup -->
    <div id="notif-container"></div>

    <script>
    (function(){
        let lastId = <?= $initialMaxId ?>;
        const pendingBadge = document.getElementById('pending-badge');

        /* ── Suara notifikasi (Web Audio API) ── */
        function playNotifSound(){
            try {
                const ctx = new (window.AudioContext || window.webkitAudioContext)();
                const osc  = ctx.createOscillator();
                const gain = ctx.createGain();
                osc.type = 'sine';
                osc.frequency.setValueAtTime(587.33, ctx.currentTime);
                osc.frequency.setValueAtTime(880, ctx.currentTime + 0.12);
                gain.gain.setValueAtTime(0.25, ctx.currentTime);
                gain.gain.exponentialRampToValueAtTime(0.01, ctx.currentTime + 0.35);
                osc.connect(gain); gain.connect(ctx.destination);
                osc.start(); osc.stop(ctx.currentTime + 0.35);
            } catch(e){}
        }

        /* ── Helper Escape HTML (Mencegah XSS) ── */
        function escapeHtml(str){
            if(str === null || str === undefined) return '';
            var div = document.createElement('div');
            div.textContent = String(str);
            return div.innerHTML;
        }

        /* ── Tampilkan popup toast ── */
        function showToast(o){
            playNotifSound();
            const c = document.getElementById('notif-container');
            const t = document.createElement('div');
            t.className = 'notif-toast';

            const safeName  = escapeHtml(o.customer_name);
            const safeTotal = escapeHtml(o.total_formatted);
            const safeCode  = escapeHtml(o.order_code);
            const safeTime  = escapeHtml(o.created_at);

            t.innerHTML =
                '<div class="notif-head"><span>🎉 ORDERAN BARU MASUK!</span><button class="notif-close" onclick="this.closest(\'.notif-toast\').remove()">&times;</button></div>' +
                '<div class="notif-body"><strong>'+safeName+'</strong> baru saja membuat pesanan sebesar <strong>'+safeTotal+'</strong></div>' +
                '<div class="notif-foot">' +
                '<span style="font-size:.78rem;color:#8B8FA8;font-family:monospace">'+safeCode+' &bull; '+safeTime+'</span>' +
                '<a href="order_detail.php?code='+encodeURIComponent(o.order_code)+'" class="btn btn-primary btn-sm" style="padding:4px 10px;font-size:.75rem">Lihat Detail</a></div>';
            c.appendChild(t);

            // Browser Notification
            if("Notification" in window && Notification.permission === "granted"){
                new Notification("🛒 Orderan Baru!", { body: String(o.customer_name)+' ('+String(o.order_code)+') - '+String(o.total_formatted) });
            }
            // Auto-hapus 10 detik
            setTimeout(function(){ if(t.parentNode){ t.style.opacity='0'; t.style.transform='translateY(20px)'; t.style.transition='.3s ease'; setTimeout(function(){t.remove();},300); }}, 10000);
        }

        // Minta izin Notifikasi
        if("Notification" in window && Notification.permission === "default") Notification.requestPermission();

        /* ── Polling ke server setiap 6 detik ── */
        function poll(){
            fetch('check_new_orders.php?last_id='+lastId)
                .then(function(r){return r.json()})
                .then(function(d){
                    if(!d.success) return;
                    if(d.max_id > lastId) lastId = d.max_id;

                    // Update badge pending
                    if(pendingBadge){
                        if(d.pending_count > 0){ pendingBadge.textContent = d.pending_count; pendingBadge.style.display='inline-block'; }
                        else { pendingBadge.style.display='none'; }
                    }
                    // Tampilkan popup
                    if(d.has_new && d.new_orders) d.new_orders.forEach(showToast);
                })
                .catch(function(){});
        }
        setInterval(poll, 6000);
    })();
    </script>
