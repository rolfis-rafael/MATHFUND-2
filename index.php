<?php
header("Content-Security-Policy: default-src 'self'; script-src 'self'; style-src 'self' 'unsafe-inline'; img-src 'self' data: blob:; connect-src 'self'; font-src 'self'; object-src 'none'; base-uri 'self'; frame-ancestors 'none'; form-action 'self'");
header('X-Content-Type-Options: nosniff');
header('Referrer-Policy: same-origin');
?>
<!doctype html>
<html lang="pt-BR">
<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <meta name="theme-color" content="#302f2f">
  <meta name="description" content="Uma aventura de matemática com desafios, conquistas e espaço para pensar. Do 7º ano à 3ª série do Ensino Médio.">
  <title>MathFund • Aprenda jogando</title>
  <link rel="icon" type="image/webp" href="assets/img/logo.webp">
  <script src="assets/js/theme.js?v=3.2.0"></script>
  <link rel="stylesheet" href="assets/css/app.css?v=3.2.0">
  <link rel="stylesheet" href="assets/css/original.css?v=3.2.0">
  <link rel="stylesheet" href="assets/css/game-fx.css?v=3.2.0">
  <link rel="stylesheet" href="assets/css/aero.css?v=3.2.0">
  <link rel="stylesheet" href="assets/css/theme.css?v=3.2.0">
  <link rel="stylesheet" href="assets/css/adventure.css?v=3.2.0">
  <link rel="stylesheet" href="assets/css/playful.css?v=3.2.0">
  <link rel="stylesheet" href="assets/css/collection.css?v=3.2.0">
  <script src="assets/js/app.js?v=3.2.0" type="module"></script>
</head>
<body>
  <a class="skip-link" href="#main">Pular para o conteúdo</a>
  <div class="mobile-shade" id="nav-shade" hidden></div>
  <aside class="sidebar" id="sidebar" aria-label="Menu principal">
    <a href="#perfil" class="sidebar-profile" id="sidebar-profile" title="Meu perfil"></a>
    <nav id="navigation" aria-label="Navegação"></nav>
    <a href="#ajuda" class="nav-item sidebar-help" title="Como jogar"><span aria-hidden="true">?</span><span>Como jogar</span></a>
  </aside>
  <div class="shell">
    <header class="topbar">
      <button id="menu-toggle" class="icon-button" aria-label="Abrir menu" aria-expanded="false" aria-controls="sidebar">☰</button>
      <div class="topbar-title"><strong id="page-name">Menu de jogos</strong></div>
      <div class="topbar-right">
        <label class="grade-control"><span class="grade-label">Sua série</span><select id="grade-select" aria-label="Escolher ano ou série"><option value="7">7º ano</option><option value="9">9º ano</option><option value="em1">1ª série EM</option><option value="em2">2ª série EM</option><option value="em3">3ª série EM</option></select></label>
        <span class="resource flame" id="streak-stat" title="Dias seguidos de estudo">♨ <b>0</b></span>
        <span class="resource coin" id="coin-stat" title="Moedas">◉ <b>30</b></span>
        <button id="theme-toggle" class="icon-button" aria-label="Usar modo claro" aria-pressed="false" title="Modo claro"><span aria-hidden="true">☀</span></button>
      </div>
    </header>
    <main id="main" tabindex="-1"><div id="view"><div class="loading-state"><img src="assets/img/fox.webp" alt="Raposinha do MathFund" width="130"><div class="loading-spinner" aria-hidden="true"><span class="loading-ring"></span></div><h1>Carregando MathFund…</h1><p>Um instante para organizar os desafios.</p></div></div></main>
    
  </div>
  <div id="toast" class="toast" role="status" aria-live="polite" hidden></div>
  <dialog id="modal" aria-labelledby="modal-title"></dialog>
  <noscript><div class="noscript">Ative o JavaScript para jogar MathFund. Abra esta pasta pelo Apache do XAMPP, em http://localhost/mathfund/.</div></noscript>
</body>
</html>
