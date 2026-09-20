<style>

/* ==========================================================
   Stardena Pay — shared layout styles
   Palette is unchanged from the original landing page.
   ========================================================== */

:root{
  /* core palette */
  --ink:#1B0F33;          /* text on light surfaces      */
  --deep:#20123A;         /* page background             */
  --shelf:#321C64;        /* hero top / nav bg           */
  --shelf-lit:#7542E5;    /* hero bottom glow            */
  --panel:#3B2371;        /* inset panel                 */
  --violet:#6E3FE7;       /* primary button              */
  --violet-hi:#8253F0;    /* primary button hover        */
  --lav:#F4F1FA;          /* light card surface          */
  --lav-2:#E5DDF7;        /* light card divider          */
  --text:#FFFFFF;
  --muted:#CFC2EC;
  --accent-soft:#C0A9F5;  /* eyebrows, tags              */
  --line:rgba(255,255,255,.16);

  /* type */
  --sans:"Inter",system-ui,-apple-system,"Segoe UI",Roboto,sans-serif;
  --display:"Archivo","Inter",system-ui,sans-serif;

  /* radii */
  --r-lg:28px;
  --r-xl:48px;
}

*{box-sizing:border-box}
html{-webkit-text-size-adjust:100%;scroll-behavior:smooth}

body{
  margin:0;
  background:var(--deep);
  color:var(--text);
  font-family:var(--sans);
  font-size:16px;
  line-height:1.55;
  overflow-x:hidden;
}

img{max-width:100%;display:block}
a{color:inherit}

h1,h2,h3{
  font-family:var(--display);
  font-weight:700;
  letter-spacing:-.02em;
  line-height:1.08;
  margin:0;
}
p{margin:0}

:focus-visible{
  outline:3px solid #C6B4FF;
  outline-offset:3px;
  border-radius:6px;
}

/* ---------- layout helpers ---------- */
.wrap{width:min(1120px,100% - 48px);margin-inline:auto}

.eyebrow{
  font-size:13px;font-weight:600;letter-spacing:.06em;
  color:var(--accent-soft);margin:0 0 14px;
}
.lede{
  color:var(--muted);max-width:46ch;margin-top:16px;font-size:16.5px;
}

/* ---------- buttons ---------- */
.btn{
  display:inline-flex;align-items:center;gap:8px;
  background:var(--violet);color:#fff;text-decoration:none;
  font-weight:600;font-size:15px;
  padding:12px 22px;border-radius:999px;border:0;cursor:pointer;
  transition:background .15s ease;
}
.btn:hover{background:var(--violet-hi)}

.btn--ghost{background:transparent;border:1px solid var(--line);color:#fff}
.btn--ghost:hover{background:rgba(255,255,255,.08)}

.btn--light{background:rgba(255,255,255,.1);border:1px solid var(--line)}
.btn--light:hover{background:rgba(255,255,255,.18)}

/* ---------- nav (shared across all pages) ---------- */
.nav{position:relative;z-index:50;background:var(--deep);padding:16px 0}
.nav-inner{display:flex;align-items:center;justify-content:space-between;gap:24px}

.brand{
  display:flex;align-items:center;gap:10px;
  text-decoration:none;flex:none;
}

/* Hard ceiling on the logo mark — an oversized source image is clipped down
   to this box no matter what pay.png actually measures. */
.mark{
  width:28px;height:28px;flex:none;
  object-fit:contain;border-radius:8px;display:block;
}
.mark--lg{width:38px;height:38px}

/* Gradient wordmark, matching the mark's own violet gradient rather than
   sitting as plain white text next to it. */
.brand-word{
  font-family:var(--display);font-weight:700;font-size:19px;
  letter-spacing:-.01em;line-height:1;

  color:#fff; /* fallback if background-clip text isn't supported */
  background:linear-gradient(120deg,#E7DDFF 0%,#B79CFF 45%,#8253F0 100%);
  -webkit-background-clip:text;
  background-clip:text;
  -webkit-text-fill-color:transparent;
}
.foot-brand .brand-word{font-size:28px}

.nav-links{display:flex;align-items:center;gap:30px;font-size:14.5px;font-weight:500}
.nav-links a{display:inline-flex;align-items:center;gap:6px;text-decoration:none;color:#EDE6FF;opacity:.85}
.nav-links a:hover{opacity:1}
.nav-icon{width:15px;height:15px}

.nav-actions{display:flex;align-items:center;gap:10px}

.nav-toggle{
  display:none;flex-direction:column;justify-content:center;gap:5px;
  width:34px;height:34px;background:none;border:0;cursor:pointer;padding:0;flex:none;
}
.nav-toggle span{display:block;width:100%;height:2px;background:#fff;border-radius:2px;transition:transform .2s ease,opacity .2s ease}
.nav-toggle.is-open span:nth-child(1){transform:translateY(7px) rotate(45deg)}
.nav-toggle.is-open span:nth-child(2){opacity:0}
.nav-toggle.is-open span:nth-child(3){transform:translateY(-7px) rotate(-45deg)}

.mobile-menu{display:none;flex-direction:column;gap:2px;padding:14px 24px 24px}
.mobile-menu[data-open="true"]{display:flex}
.mobile-menu a{
  padding:13px 4px;color:#fff;text-decoration:none;
  font-size:15px;font-weight:500;border-bottom:1px solid var(--line);
}
.mobile-menu hr{border:0;border-top:1px solid var(--line);margin:10px 0}
.mobile-menu .btn{margin-top:8px;justify-content:center}

/* ---------- hero ---------- */
.hero{
  margin:0 12px;
  padding:88px 0 96px;
  background:linear-gradient(178deg,var(--shelf) 34%,#5C32BC 76%,var(--shelf-lit) 100%);
  border-radius:0 0 var(--r-xl) var(--r-xl);
}
.hero-grid{
  display:grid;
  grid-template-columns:minmax(0,.9fr) minmax(0,1.1fr);
  gap:56px;align-items:center;
}
.hero h1{font-size:clamp(34px,4.6vw,52px)}
.cta-row{display:flex;flex-wrap:wrap;gap:12px;margin-top:28px}

/* checkout mock */
.stage{
  background:linear-gradient(150deg,#CDBDF7,#EDE4FF 58%,#F7F1FF);
  border-radius:20px;padding:34px 30px;
  position:relative;overflow:hidden;min-height:280px;
  display:flex;align-items:center;justify-content:center;
}
.stage::after{
  content:"";position:absolute;right:-70px;bottom:-90px;
  width:230px;height:230px;
  background:radial-gradient(circle,#B69BF5,transparent 68%);
  opacity:.75;
}
.paycard{
  position:relative;z-index:2;width:min(330px,100%);
  background:#fff;border-radius:16px;padding:22px;
  box-shadow:0 18px 40px rgba(38,16,86,.28);
  color:var(--ink);font-size:14px;
}
.paycard .row{display:flex;justify-content:space-between;align-items:baseline}
.paycard .co{font-weight:600;font-size:13px;color:#6B5C8C}
.amount{
  font-family:var(--display);font-weight:700;font-size:34px;
  letter-spacing:-.02em;margin:10px 0 2px;color:var(--ink);
}
.paycard .sub{color:#6B5C8C;font-size:13px}
.field{
  margin-top:16px;border:1px solid #E1D9F2;border-radius:10px;
  padding:10px 12px;color:#8A7BAB;font-size:13px;
  display:flex;justify-content:space-between;
}
.paybtn{
  margin-top:12px;background:var(--violet);color:#fff;text-align:center;
  font-weight:600;padding:11px;border-radius:10px;font-size:14px;
}
.chip{
  position:absolute;z-index:3;right:16px;top:14px;
  background:#0E9F6E;color:#fff;font-size:12px;font-weight:600;
  padding:6px 12px;border-radius:999px;
  box-shadow:0 8px 20px rgba(14,80,55,.35);
}

/* mobile money confirmation toast, floating over the stage */
.momo-toast{
  position:absolute;z-index:3;left:16px;bottom:16px;
  background:#fff;color:var(--ink);
  display:flex;align-items:center;gap:10px;
  padding:10px 14px;border-radius:12px;
  box-shadow:0 10px 24px rgba(20,10,50,.22);
  font-size:12.5px;max-width:220px;
}
.momo-toast strong{display:block;font-size:13px}
.momo-toast span{color:#6B5C8C}
.dot-pulse{position:relative;width:8px;height:8px;border-radius:50%;background:#0E9F6E;flex:none}
.dot-pulse::after{
  content:"";position:absolute;inset:-4px;border-radius:50%;
  background:#0E9F6E;opacity:.35;animation:pulse 1.8s ease-out infinite;
}
@keyframes pulse{0%{transform:scale(.6);opacity:.55}100%{transform:scale(2.4);opacity:0}}

/* ---------- payment methods section ---------- */
.methods{padding:100px 0 20px}
.methods h2{font-size:clamp(28px,3.6vw,40px);max-width:16ch}
.method-grid{
  display:grid;grid-template-columns:1fr 1fr;gap:32px;
  margin-top:48px;
}
.method-col{
  background:var(--panel);border:1px solid var(--line);
  border-radius:20px;padding:30px;
}
.method-col h3{font-size:19px}
.method-col > p{color:var(--muted);font-size:14.5px;margin-top:10px}
.badge-row{display:flex;flex-wrap:wrap;gap:8px;margin-top:18px}
.badge{
  background:var(--lav);color:var(--ink);
  font-size:13.5px;font-weight:600;
  padding:8px 14px;border-radius:999px;
}
.flag-row{margin-top:16px;font-size:18px;letter-spacing:2px}

/* ---------- split section ---------- */
.split{padding:110px 0}
.split-grid{display:grid;grid-template-columns:1fr 1fr;gap:64px;align-items:center}
.split h2{font-size:clamp(28px,3.4vw,38px)}
.split .btn{margin-top:26px}

.code{
  background:#160C2B;
  border:1px solid rgba(255,255,255,.1);
  border-radius:14px;overflow:hidden;
  box-shadow:0 24px 50px rgba(0,0,0,.35);
}
.code header{
  display:flex;gap:6px;align-items:center;
  padding:12px 14px;background:rgba(255,255,255,.05);
}
.dot{width:10px;height:10px;border-radius:50%;background:#4C3A78}
.code .tab{margin-left:10px;font-size:12px;color:#B7A6DE}
.code pre{
  margin:0;padding:20px;font-size:12.5px;line-height:1.85;overflow-x:auto;
  font-family:ui-monospace,SFMono-Regular,Menlo,monospace;color:#D9CDF5;
}
.k{color:#FF9E64}
.s{color:#9BE7A8}
.c{color:#8574B5}
.n{color:#7FD3FF}

/* ---------- feature cards ---------- */
.features{padding:40px 0 20px;text-align:center}
.features h2{font-size:clamp(30px,4.4vw,46px)}
.features > .wrap > p{color:var(--muted);margin-top:14px}

.cards{
  display:grid;grid-template-columns:repeat(4,1fr);gap:22px;
  margin-top:52px;text-align:left;
}
.card .art{
  background:var(--lav);border-radius:14px;height:150px;
  display:flex;align-items:center;justify-content:center;
}
.card .tag{
  display:inline-block;margin:18px 0 8px;font-size:13px;
  color:var(--accent-soft);text-decoration:underline;text-underline-offset:3px;
}
.card h3{font-size:18px;line-height:1.25}
.card p{color:var(--muted);font-size:14px;margin-top:10px}
.features .btn{margin-top:48px}

@media (max-width:1000px){
  .cards{grid-template-columns:repeat(2,1fr)}
}
@media (max-width:560px){
  .cards{grid-template-columns:1fr}
}

/* ---------- inset panel ---------- */
.panel-sec{padding:110px 0 0}
.panel{
  background:linear-gradient(160deg,#3E2479,#2E1A5C);
  border-radius:var(--r-lg);padding:56px;
  display:grid;grid-template-columns:1fr 1fr;gap:56px;align-items:center;
}
.panel h2{font-size:clamp(28px,3.4vw,38px)}

.invoice{background:var(--lav);border-radius:14px;padding:26px;color:var(--ink)}
.invoice .head{
  display:flex;justify-content:space-between;align-items:center;
  font-size:13px;color:#6B5C8C;
}
.invoice .total{
  font-family:var(--display);font-weight:700;font-size:30px;margin:14px 0 18px;
}
.line{
  display:flex;justify-content:space-between;font-size:13.5px;
  padding:9px 0;border-top:1px solid var(--lav-2);
}
.invoice .send{
  margin-top:18px;background:var(--violet);color:#fff;border-radius:10px;
  padding:10px;text-align:center;font-weight:600;font-size:13.5px;
}

.mid-cta{text-align:center;padding:90px 0 120px}

/* ---------- bottom shelf + signup ---------- */
.shelf-b{
  margin:0 12px;height:120px;
  background:linear-gradient(0deg,var(--deep) 12%,#4A2A96 88%,#6B3FD4);
  border-radius:var(--r-xl) var(--r-xl) 0 0;
}

.signup{padding:80px 0 100px;text-align:center}
.signup h2{font-size:clamp(26px,3.2vw,34px)}
.signup p{color:var(--muted);margin:14px auto 0;max-width:42ch}

.form{max-width:420px;margin:32px auto 0;text-align:left}
.form label{font-size:13.5px;color:var(--muted);display:block;margin-bottom:8px}
.form input{
  width:100%;background:rgba(255,255,255,.07);
  border:1px solid var(--line);color:#fff;
  border-radius:10px;padding:13px 16px;font:inherit;font-size:15px;
}
.form input::placeholder{color:#9B8AC4}
.form input.is-invalid{border-color:#FF8FA3}
.form button{width:100%;margin-top:14px;justify-content:center}
.form-msg{margin-top:12px;font-size:13.5px;color:var(--muted);min-height:20px}
.form-msg.is-error{color:#FF8FA3}
.form-msg.is-ok{color:#8FE3B8}

/* ---------- footer ---------- */
footer{padding:60px 0 40px}
.foot-top{display:flex;justify-content:space-between;gap:40px;flex-wrap:wrap}
.legal{
  color:#A899CD;font-size:13.5px;max-width:52ch;
  margin-bottom:26px;line-height:1.6;
}
.foot-links{display:flex;flex-direction:column;gap:12px;font-size:14.5px}
.foot-links a{text-decoration:none;color:#E4DCF7}
.foot-links a:hover{text-decoration:underline}

.foot-brand{
  display:flex;align-items:center;gap:12px;
}
.rule{border:0;border-top:1px solid var(--line);margin:44px 0 28px}
.foot-bottom{
  display:flex;justify-content:space-between;align-items:flex-end;
  gap:24px;flex-wrap:wrap;
}
.foot-bottom h3{font-family:var(--display);font-size:24px}
.foot-bottom span{color:#A899CD;font-size:14px}

/* ---------- floating action stack (scroll-top, WhatsApp, sandbox widget) ---------- */
.fab-stack{
  position:fixed;right:20px;bottom:20px;z-index:40;
  display:flex;flex-direction:column;align-items:flex-end;gap:12px;
}

.fab{
  width:52px;height:52px;flex:none;border-radius:50%;border:0;
  display:flex;align-items:center;justify-content:center;
  box-shadow:0 14px 30px rgba(12,4,32,.4);
  cursor:pointer;transition:opacity .2s ease,transform .2s ease,background .15s ease;
}

.fab--top{
  background:#fff;color:var(--ink);
  opacity:0;transform:translateY(8px);pointer-events:none;
}
.fab--top.is-visible{opacity:1;transform:translateY(0);pointer-events:auto}
.fab--top:hover{background:var(--lav)}
.fab--top svg{width:20px;height:20px}

.fab--whatsapp{background:#25D366}
.fab--whatsapp:hover{background:#1EBE5A}
.fab--whatsapp svg{width:26px;height:26px}

.widget{
  width:min(300px,calc(100vw - 40px));
  background:#fff;color:var(--ink);
  border-radius:14px;box-shadow:0 14px 40px rgba(12,4,32,.45);
  overflow:hidden;
}
.widget > button{
  width:100%;display:flex;align-items:center;gap:10px;
  background:none;border:0;cursor:pointer;
  font:inherit;font-size:14.5px;font-weight:500;color:var(--ink);
  padding:14px 16px;text-align:left;
}
.widget > button span{flex:1}
.widget .plus{
  font-size:20px;line-height:1;color:#5B4B85;
  transition:transform .2s ease;font-style:normal;
}
.widget[data-open="true"] .plus{transform:rotate(45deg)}

.widget .body{display:none;padding:0 16px 18px;font-size:13.5px;color:#5B4B85}
.widget[data-open="true"] .body{display:block}

.widget .key{
  margin-top:10px;background:var(--lav);border-radius:8px;padding:10px 12px;
  font-family:ui-monospace,Menlo,monospace;font-size:12px;color:#3A2A64;
  display:flex;justify-content:space-between;gap:10px;align-items:center;
}
.widget .key button{
  width:auto;padding:0;border:0;background:none;cursor:pointer;
  font:inherit;font-size:12px;font-weight:600;color:var(--violet);
}

/* ---------- generic docs / inner pages ---------- */
.docs-hero{padding:72px 0 48px;text-align:left}
.docs-hero h1{font-size:clamp(30px,4vw,44px)}
.docs-hero .lede{max-width:58ch}
.docs-body{padding:10px 0 120px}
.docs-grid{display:grid;grid-template-columns:repeat(2,1fr);gap:20px}
.doc-card{
  display:block;background:var(--panel);border:1px solid var(--line);
  border-radius:16px;padding:26px;text-decoration:none;color:#fff;
  transition:background .15s ease;
}
.doc-card:hover{background:#46297f}
.doc-card h3{font-size:18px;margin-bottom:8px}
.doc-card p{color:var(--muted);font-size:14px}

/* ---------- responsive ---------- */
@media (max-width:900px){
  .hero{padding:64px 0 72px}
  .hero-grid,
  .split-grid,
  .panel,
  .method-grid,
  .docs-grid{grid-template-columns:1fr;gap:32px}
  .panel{padding:32px}
  .split{padding:80px 0}
  .panel-sec{padding:80px 0 0}
  .methods{padding:72px 0 10px}
  .mid-cta{padding:64px 0 90px}

  .nav-links,.nav-actions{display:none}
  .nav-toggle{display:flex}
}

@media (prefers-reduced-motion:reduce){
  html{scroll-behavior:auto}
  *{transition:none !important;animation:none !important}
  .dot-pulse::after{display:none}
}
</style>