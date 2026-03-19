<?php
// includes/sidebar.php
$paginaAtiva = $paginaAtiva ?? 'dashboard';
$prof = professorLogado();

$menu = [
  ['id' => 'dashboard',      'href' => '/sistema_escolar/dashboard.php',            'icon' => 'fa-gauge-high',      'label' => 'Dashboard'],
  ['id' => 'configuracoes',  'href' => '/sistema_escolar/pages/configuracoes.php',   'icon' => 'fa-gear',            'label' => 'Configurações'],
  ['id' => 'sumario',        'href' => '/sistema_escolar/pages/sumario.php',         'icon' => 'fa-book',            'label' => 'Registo de Sumário'],
  ['id' => 'presenca',       'href' => '/sistema_escolar/pages/presenca.php',        'icon' => 'fa-clipboard-check', 'label' => 'Marcar Presença'],
  ['id' => 'relatorio',      'href' => '/sistema_escolar/pages/relatorio.php',       'icon' => 'fa-chart-bar',       'label' => 'Relatórios'],
];
?>
<div class="wrapper" id="wrapper">
  <!-- SIDEBAR -->
  <nav class="sidebar" id="sidebar">
    <div class="sidebar-header">
      <a href="/sistema_escolar/dashboard.php" class="sidebar-brand">
        <i class="fa-solid fa-book-open-reader"></i>
        <span class="brand-text"><?= SITE_NAME ?></span>
      </a>
      <button class="sidebar-toggle" id="sidebarToggle" title="Recolher menu">
        <i class="fa fa-bars"></i>
      </button>
    </div>

    <div class="sidebar-user">
      <div class="user-avatar"><i class="fa fa-user-tie"></i></div>
      <div class="user-info">
        <span class="user-name"><?= h($prof['nome']) ?></span>
        <span class="user-role">Professor</span>
      </div>
    </div>

    <ul class="sidebar-menu">
      <?php foreach ($menu as $item): ?>
        <li class="<?= $paginaAtiva === $item['id'] ? 'active' : '' ?>">
          <a href="<?= $item['href'] ?>" title="<?= $item['label'] ?>">
            <i class="fa <?= $item['icon'] ?>"></i>
            <span><?= $item['label'] ?></span>
          </a>
        </li>
      <?php endforeach; ?>
    </ul>

    <div class="sidebar-footer">
      <a href="/sistema_escolar/logout.php" class="sidebar-logout" title="Sair">
        <i class="fa fa-right-from-bracket"></i>
        <span>Sair</span>
      </a>
    </div>
  </nav>

  <!-- CONTEÚDO PRINCIPAL -->
  <div class="main-content" id="mainContent">
    <!-- Topbar -->
    <header class="topbar">
      <div class="topbar-left">
        <button class="topbar-toggle" id="topbarToggle">
          <i class="fa fa-bars"></i>
        </button>
        <nav class="breadcrumb-nav">
          <span><i class="fa fa-house"></i></span>
          <span class="bc-sep">/</span>
          <span class="bc-current"><?= h($paginaTitulo) ?></span>
        </nav>
      </div>
      <div class="topbar-right">
        <span class="topbar-date"><i class="fa fa-calendar-days"></i> <?= date('d/m/Y') ?></span>
        <a href="/sistema_escolar/logout.php" class="topbar-logout" title="Sair">
          <i class="fa fa-right-from-bracket"></i>
        </a>
      </div>
    </header>

    <!-- ÁREA DE CONTEÚDO -->
    <main class="page-content">
