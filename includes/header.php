<?php
require_once __DIR__ . '/../config/db.php';
$cartCount   = cartCount();
?>
<!DOCTYPE html>
<html lang="id">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title><?= $pageTitle ?? 'HanzelStore - Digital Product Store' ?></title>
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
html{scroll-behavior:smooth}
body{font-family:'Segoe UI',system-ui,-apple-system,sans-serif;background:var(--bg);color:var(--text);min-height:100vh;line-height:1.6}
a{color:var(--alight);text-decoration:none;transition:color var(--t);cursor:pointer}
a:hover{color:var(--text)}
h1,h2,h3{line-height:1.3;font-weight:700}
h1{font-size:clamp(1.8rem,4vw,2.8rem);letter-spacing:-.02em}
h2{font-size:clamp(1.25rem,3vw,1.8rem);letter-spacing:-.01em}
.container{max-width:1200px;margin:0 auto;padding:0 24px}
.section{padding:48px 0}
code,.mono{font-family:'Cascadia Code','Fira Code',monospace;font-size:.85em}
img{max-width:100%;display:block}

/* ACCESSIBILITY & FOCUS */
a:focus-visible, button:focus-visible, input:focus-visible, select:focus-visible, textarea:focus-visible{
    outline:2px solid var(--accent);outline-offset:2px;
}
@media (prefers-reduced-motion: reduce){
    *,*::before,*::after{
        animation-duration:.01ms!important;
        animation-iteration-count:1!important;
        transition-duration:.01ms!important;
        scroll-behavior:auto!important;
    }
}

/* NAV */
.nav{position:sticky;top:0;z-index:100;background:rgba(13,17,23,.94);border-bottom:1px solid var(--border)}
.nav-inner{display:flex;align-items:center;justify-content:space-between;height:64px;max-width:1200px;margin:0 auto;padding:0 24px}
.nav-brand{font-family:'Cascadia Code','Fira Code',monospace;font-size:1.2rem;font-weight:700;color:var(--text);display:flex;align-items:center;gap:8px}
.brand-dot{color:var(--accent);font-size:1.4rem}
.nav-links{display:flex;align-items:center;gap:8px}
.nav-links a{color:var(--muted);padding:7px 14px;border-radius:var(--rs);font-size:.9rem;font-weight:500;transition:all var(--t)}
.nav-links a:hover{color:var(--text);background:var(--hover)}
.cart-btn{display:flex;align-items:center;gap:6px;background:var(--card)!important;border:1px solid var(--borderl)!important;color:var(--text)!important;border-radius:var(--rs)!important;position:relative}
.cart-badge{background:var(--accent);color:#fff;font-size:.68rem;font-weight:700;border-radius:999px;width:18px;height:18px;display:flex;align-items:center;justify-content:center;position:absolute;top:-5px;right:-6px}

/* HERO */
.hero{padding:64px 0 44px;position:relative;text-align:center}
.hero-inner{position:relative;z-index:1}
.hero h1{margin-bottom:14px}
.hero h1 span{color:var(--accent)}
.hero-sub{color:var(--muted);font-size:1.05rem;max-width:540px;margin:0 auto 28px;line-height:1.7}
.search-row{display:flex;gap:10px;max-width:480px;margin:0 auto}

/* GRID */
.product-grid{display:grid;grid-template-columns:repeat(auto-fill,minmax(280px,1fr));gap:20px}

/* CARD */
.card{background:var(--card);border:1px solid var(--border);border-radius:var(--r);overflow:hidden;transition:border-color var(--t),box-shadow var(--t),transform var(--t);display:flex;flex-direction:column;position:relative}
.card:hover{border-color:var(--borderl);box-shadow:0 6px 20px rgba(0,0,0,.35);transform:translateY(-2px)}
.card-head{padding:20px 20px 12px;display:flex;align-items:flex-start;justify-content:space-between}
.p-icon{font-size:2rem;background:rgba(108,99,255,.08);border:1px solid rgba(108,99,255,.2);border-radius:var(--rs);width:54px;height:54px;display:flex;align-items:center;justify-content:center;overflow:hidden}
.p-icon img{width:100%;height:100%;object-fit:cover;border-radius:var(--rs)}
.badge{font-size:.68rem;font-weight:700;letter-spacing:.04em;padding:3px 8px;border-radius:4px;text-transform:uppercase}
.b-bestseller{background:rgba(63,185,80,.12);color:var(--green);border:1px solid rgba(63,185,80,.3)}
.b-hot{background:rgba(248,81,73,.12);color:var(--red);border:1px solid rgba(248,81,73,.3)}
.b-new,.b-featured{background:rgba(108,99,255,.12);color:var(--alight);border:1px solid rgba(108,99,255,.3)}
.b-popular{background:rgba(210,153,34,.12);color:var(--yellow);border:1px solid rgba(210,153,34,.3)}
.b-sale{background:rgba(248,81,73,.12);color:var(--red);border:1px solid rgba(248,81,73,.3)}
.card-body{padding:0 20px 18px;flex:1}
.p-cat{font-size:.73rem;color:var(--dim);text-transform:uppercase;letter-spacing:.08em;margin-bottom:5px;display:block}
.p-name{font-size:1.05rem;font-weight:700;margin-bottom:6px}
.p-desc{font-size:.86rem;color:var(--muted);line-height:1.6;margin-bottom:14px}
.p-meta{display:flex;align-items:center;gap:10px;font-size:.78rem;color:var(--dim)}
.card-foot{padding:14px 20px 20px;border-top:1px solid var(--border)}
.price-row{display:flex;align-items:center;justify-content:space-between;margin-bottom:12px}
.price-main{font-size:1.25rem;font-weight:800}
.price-ori{font-size:.82rem;color:var(--dim);text-decoration:line-through}
.price-disc{background:rgba(63,185,80,.12);color:var(--green);font-size:.72rem;font-weight:700;padding:2px 7px;border-radius:4px}
.card-actions{display:flex;gap:8px}

/* BUTTONS */
.btn{display:inline-flex;align-items:center;justify-content:center;gap:6px;padding:10px 18px;border-radius:var(--rs);font-size:.875rem;font-weight:600;cursor:pointer;border:none;transition:all var(--t);line-height:1;text-decoration:none;font-family:inherit}
.btn-primary{background:var(--accent);color:#fff;flex:1}
.btn-primary:hover{background:var(--accent-hover);color:#fff;transform:translateY(-1px)}
.btn-outline{background:transparent;border:1px solid var(--borderl);color:var(--muted)}
.btn-outline:hover{border-color:var(--accent);color:var(--text);background:rgba(108,99,255,.05)}
.btn-ghost{background:transparent;color:var(--muted)}
.btn-ghost:hover{color:var(--text);background:var(--hover)}
.btn-danger{background:rgba(248,81,73,.12);color:var(--red);border:1px solid rgba(248,81,73,.3)}
.btn-danger:hover{background:rgba(248,81,73,.22)}
.btn-success{background:var(--green);color:#0D1117}
.btn-success:hover{background:#56d364}
.btn-lg{padding:13px 26px;font-size:1rem;border-radius:var(--rs)}
.btn-sm{padding:6px 12px;font-size:.8rem}
.btn-block{width:100%}

/* FORM */
.form-section{background:var(--card);border:1px solid var(--border);border-radius:var(--r);padding:24px;margin-bottom:18px}
.form-title{font-size:.82rem;font-weight:700;text-transform:uppercase;letter-spacing:.07em;color:var(--muted);margin-bottom:16px;padding-bottom:10px;border-bottom:1px solid var(--border)}
.form-group{margin-bottom:16px}
.form-label{display:block;font-size:.84rem;font-weight:600;color:var(--muted);margin-bottom:7px}
.form-control{width:100%;background:var(--bg);border:1px solid var(--borderl);color:var(--text);border-radius:var(--rs);padding:10px 14px;font-size:.9rem;transition:border-color var(--t);font-family:inherit}
.form-control:focus{outline:none;border-color:var(--accent)}
.form-control::placeholder{color:var(--dim)}
.pay-opt{border:1px solid var(--borderl);border-radius:var(--rs);padding:13px 15px;cursor:pointer;transition:all var(--t);display:flex;align-items:center;gap:12px;margin-bottom:8px}
.pay-opt:has(input:checked){border-color:var(--accent);background:rgba(108,99,255,.06)}
.pay-opt input{accent-color:var(--accent);cursor:pointer}
.pay-label{font-size:.88rem;font-weight:600}
.pay-desc{font-size:.78rem;color:var(--muted)}

/* CART */
.page-head{padding:32px 0 20px;border-bottom:1px solid var(--border);margin-bottom:32px}
.two-col{display:grid;grid-template-columns:1fr 340px;gap:24px;align-items:start}
.three-col{display:grid;grid-template-columns:1fr 340px;gap:24px;align-items:start}
.cart-item{background:var(--card);border:1px solid var(--border);border-radius:var(--r);padding:18px;display:flex;align-items:center;gap:14px;margin-bottom:10px;transition:border-color var(--t)}
.cart-item:hover{border-color:var(--borderl)}
.ci-icon{font-size:1.7rem;background:rgba(108,99,255,.08);border:1px solid rgba(108,99,255,.2);border-radius:var(--rs);width:50px;height:50px;display:flex;align-items:center;justify-content:center;flex-shrink:0;overflow:hidden}
.ci-icon img{width:100%;height:100%;object-fit:cover}
.ci-info{flex:1}
.ci-name{font-weight:600;margin-bottom:3px}
.ci-price{font-size:1.05rem;font-weight:700;color:var(--alight)}

/* SUMMARY */
.summary{background:var(--card);border:1px solid var(--border);border-radius:var(--r);padding:22px;position:sticky;top:80px}
.s-title{font-size:.95rem;font-weight:700;margin-bottom:16px}
.s-row{display:flex;justify-content:space-between;align-items:center;padding:9px 0;border-bottom:1px solid var(--border);font-size:.88rem}
.s-row:last-of-type{border-bottom:none}
.s-row.total{font-size:1.05rem;font-weight:800;padding-top:14px;margin-top:4px;border-top:1px solid var(--borderl);border-bottom:none}
.s-label{color:var(--muted)}

/* CHECKOUT ITEMS */
.co-item{display:flex;align-items:center;gap:10px;padding:11px 0;border-bottom:1px solid var(--border);font-size:.86rem}
.co-item:last-child{border-bottom:none}
.co-name{flex:1;color:var(--muted)}

/* BANK INFO */
.bank-box{background:rgba(108,99,255,.06);border:1px solid rgba(108,99,255,.2);border-radius:var(--r);padding:18px;margin:14px 0}
.bank-box h4{font-size:.82rem;text-transform:uppercase;letter-spacing:.06em;color:var(--alight);margin-bottom:12px}
.bank-row{display:flex;justify-content:space-between;padding:5px 0;font-size:.86rem}
.bank-label{color:var(--muted)}
.bank-val{font-weight:700;font-family:monospace}

/* SUCCESS */
.success-wrap{max-width:540px;margin:56px auto;background:var(--card);border:1px solid var(--border);border-radius:var(--r);padding:40px 32px;text-align:center}
.success-ico{font-size:3.5rem;margin-bottom:16px;display:block}
.order-code{background:var(--bg);border:1px solid var(--borderl);border-radius:var(--rs);padding:12px;font-family:monospace;font-size:1.15rem;font-weight:700;color:var(--alight);letter-spacing:.1em;margin:18px 0}

/* ALERTS */
.alert{padding:13px 16px;border-radius:var(--rs);margin-bottom:18px;font-size:.875rem}
.alert-success{background:rgba(63,185,80,.1);border:1px solid rgba(63,185,80,.3);color:var(--green)}
.alert-danger{background:rgba(248,81,73,.1);border:1px solid rgba(248,81,73,.3);color:var(--red)}
.alert-info{background:rgba(108,99,255,.1);border:1px solid rgba(108,99,255,.3);color:var(--alight)}

/* EMPTY */
.empty{text-align:center;padding:64px 24px;color:var(--muted)}
.empty-ico{font-size:3.5rem;margin-bottom:14px;display:block}
.empty h3{color:var(--text);margin-bottom:7px}

/* FOOTER */
footer{border-top:1px solid var(--border);padding:28px 0;margin-top:64px;text-align:center;color:var(--dim);font-size:.85rem}
footer span{color:var(--accent)}

.sep{color:var(--borderl)}
.tag-green{color:var(--green)}
.tag-yellow{color:var(--yellow)}

@media(max-width:768px){
    .two-col,.three-col{grid-template-columns:1fr}
    .nav-hide{display:none}
}
</style>
</head>
<body>
<nav class="nav">
    <div class="nav-inner">
        <a href="<?= SITE_URL ?>/index.php" class="nav-brand">
            <span class="brand-dot">‖</span> HanzelStore
        </a>
        <div class="nav-links">
            <a href="<?= SITE_URL ?>/index.php" class="nav-hide">Katalog Produk</a>
            <a href="<?= SITE_URL ?>/track.php" class="nav-hide">Lacak Pesanan</a>
            <a href="<?= SITE_URL ?>/cart.php" class="cart-btn" aria-label="Lihat Keranjang Belanja">
                🛒 Keranjang
                <?php if ($cartCount > 0): ?>
                    <span class="cart-badge"><?= $cartCount ?></span>
                <?php endif; ?>
            </a>
        </div>
    </div>
</nav>
