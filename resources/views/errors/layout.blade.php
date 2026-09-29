<!DOCTYPE html>
<html lang="id">
<head>
<meta charset="utf-8">
<meta name="viewport" content="width=device-width, initial-scale=1">
<meta name="theme-color" content="#1a5336">
<meta name="robots" content="noindex">
<title>@yield('code', 'Error') · An-Nuur 2</title>
<style>
:root {
  --green: #2d6a4f;
  --green-dark: #1a5336;
  --gold: #c09849;
  --ink: #1a2420;
  --muted: #4d5f55;
  --cream: #f7f5ef;
  --line: #e0e8dc;
}
* { box-sizing: border-box; margin: 0; padding: 0; }
html { height: 100%; }
body {
  min-height: 100%;
  background: var(--cream);
  color: var(--ink);
  font-family: 'Segoe UI', system-ui, -apple-system, sans-serif;
  display: flex;
  flex-direction: column;
  -webkit-font-smoothing: antialiased;
}
.topbar {
  height: 4px;
  background: linear-gradient(90deg, var(--green-dark), var(--gold), var(--green));
}
.err-header {
  background: #fff;
  border-bottom: 1px solid var(--line);
  padding: 18px 0;
}
.err-header-inner {
  width: min(1180px, calc(100% - 48px));
  margin: 0 auto;
  display: flex;
  align-items: center;
  gap: 11px;
}
.brand-mark {
  height: 40px;
  width: 36px;
  border: 1.5px solid var(--green);
  background: linear-gradient(135deg, #f2f8f0, #e2efe0);
  color: var(--green);
  border-radius: 14px 14px 5px 5px;
  display: grid;
  place-items: center;
  font-size: 24px;
  font-weight: 700;
  text-decoration: none;
}
.brand-text strong {
  display: block;
  font-size: 17px;
  letter-spacing: .12em;
  color: var(--green-dark);
}
.brand-text small {
  display: block;
  font-size: 7.5px;
  letter-spacing: .16em;
  color: var(--muted);
  margin-top: 3px;
}
.err-main {
  flex: 1;
  display: flex;
  align-items: center;
  justify-content: center;
  padding: 60px 24px;
}
.err-card {
  background: #fff;
  border: 1px solid var(--line);
  border-radius: 20px;
  padding: 56px 52px;
  max-width: 600px;
  width: 100%;
  text-align: center;
  box-shadow: 0 8px 32px -6px rgba(24,82,55,.10), 0 2px 8px rgba(0,0,0,.04);
  position: relative;
  overflow: hidden;
}
.err-card::before {
  content: '';
  position: absolute;
  top: 0;
  left: 0;
  right: 0;
  height: 4px;
  background: var(--accent-color, linear-gradient(90deg, var(--green), var(--gold)));
}
.err-code {
  font-family: Georgia, serif;
  font-size: 96px;
  font-weight: 400;
  line-height: 1;
  color: var(--green);
  opacity: .1;
  position: absolute;
  top: 20px;
  left: 50%;
  transform: translateX(-50%);
  white-space: nowrap;
  pointer-events: none;
  user-select: none;
  letter-spacing: -4px;
}
.err-icon {
  width: 68px;
  height: 68px;
  border-radius: 50%;
  display: grid;
  place-items: center;
  margin: 0 auto 22px;
  position: relative;
  z-index: 1;
  background: var(--icon-bg, linear-gradient(135deg, #eef7ea, #d8edcf));
  border: 1.5px solid var(--icon-border, #c2dec0);
  color: var(--icon-color, var(--green));
  box-shadow: 0 4px 12px rgba(24,82,55,.10);
}
.err-icon svg {
  width: 30px;
  height: 30px;
}
.err-badge {
  display: inline-flex;
  align-items: center;
  gap: 6px;
  font-size: 10px;
  font-weight: 700;
  letter-spacing: .12em;
  padding: 4px 12px;
  border-radius: 99px;
  margin-bottom: 18px;
  position: relative;
  z-index: 1;
  background: var(--badge-bg, #edf7e8);
  border: 1px solid var(--badge-border, #c5dfc2);
  color: var(--badge-text, var(--green));
}
.err-title {
  font-family: Georgia, serif;
  font-size: 32px;
  font-weight: 400;
  color: var(--ink);
  line-height: 1.25;
  margin-bottom: 14px;
  letter-spacing: -.5px;
  position: relative;
  z-index: 1;
}
.err-desc {
  font-size: 15px;
  line-height: 1.75;
  color: var(--muted);
  max-width: 430px;
  margin: 0 auto 32px;
  position: relative;
  z-index: 1;
}
.err-actions {
  display: flex;
  gap: 12px;
  justify-content: center;
  flex-wrap: wrap;
  position: relative;
  z-index: 1;
}
.btn {
  display: inline-flex;
  align-items: center;
  gap: 8px;
  min-height: 48px;
  padding: 11px 24px;
  border-radius: 8px;
  font-size: 14px;
  font-weight: 600;
  text-decoration: none;
  transition: all .18s ease;
  border: 1px solid transparent;
}
.btn-primary {
  background: linear-gradient(180deg, #3a8a64, var(--green));
  border-color: var(--green-dark);
  color: #fff;
  box-shadow: 0 2px 6px rgba(24,82,55,.25);
}
.btn-primary:hover {
  background: var(--green-dark);
  box-shadow: 0 4px 12px rgba(24,82,55,.30);
  transform: translateY(-1px);
  color: #fff;
}
.btn-outline {
  background: #fff;
  border-color: #cdd9ca;
  color: var(--green-dark);
}
.btn-outline:hover {
  background: #f0f7ee;
  border-color: var(--green);
  transform: translateY(-1px);
}
.err-footer {
  text-align: center;
  padding: 20px 24px;
  font-size: 11px;
  color: #7a8a7e;
  border-top: 1px solid var(--line);
}
.err-footer a {
  color: var(--green);
  text-decoration: none;
}
.err-footer a:hover {
  text-decoration: underline;
}
@media (max-width: 600px) {
  .err-card { padding: 40px 24px; border-radius: 16px; }
  .err-title { font-size: 26px; }
  .err-desc { font-size: 14px; }
  .err-code { font-size: 72px; }
  .btn { flex: 1; justify-content: center; }
}
</style>
</head>
<body>
<div class="topbar"></div>
<header class="err-header">
  <div class="err-header-inner">
    <a href="/" class="brand-mark" aria-label="An-Nuur 2, halaman utama">&#x646;</a>
    <div class="brand-text"><strong>AN-NUUR 2</strong><small>PANTI ASUHAN &middot; KEDIRI</small></div>
  </div>
</header>
<main class="err-main">
  <div class="err-card" style="@yield('card_style', '')">
    <span class="err-code" aria-hidden="true">@yield('code', 'Err')</span>
    <div class="err-icon" style="@yield('icon_style', '')">@yield('icon')</div>
    <span class="err-badge" style="@yield('badge_style', '')">
      <svg width="10" height="10" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round"><circle cx="12" cy="12" r="10"/><line x1="12" y1="8" x2="12" y2="12"/><line x1="12" y1="16" x2="12.01" y2="16"/></svg>
      @yield('badge_text', 'ERROR')
    </span>
    <h1 class="err-title">@yield('title', 'Terjadi kesalahan')</h1>
    <p class="err-desc">@yield('description', 'Maaf, ada yang tidak beres.')</p>
    <div class="err-actions">
      @yield('extra_action')
      <a href="/" class="btn btn-primary">
        <svg width="16" height="16" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M3 12l2-2m0 0l7-7 7 7M5 10v10a1 1 0 001 1h3m10-11l2 2m-2-2v10a1 1 0 01-1 1h-3m-6 0a1 1 0 001-1v-4a1 1 0 011-1h2a1 1 0 011 1v4a1 1 0 001 1m-6 0h6"/></svg>
        Kembali ke beranda
      </a>
    </div>
  </div>
</main>
<footer class="err-footer">&copy; {{ date('Y') }} Panti Asuhan NU An-Nuur 2 &nbsp;&middot;&nbsp; <a href="/">Beranda</a> &nbsp;&middot;&nbsp; <a href="/kontak">Kontak</a></footer>
</body>
</html>
