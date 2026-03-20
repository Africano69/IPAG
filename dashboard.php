<?php
// dashboard.php
require_once __DIR__ . '/config.php';
verificarAutenticacao();

$prof = professorLogado();
$db   = getDB();
$pid  = $prof['id'];

// Estatísticas
$totalTurmas     = $db->prepare('SELECT COUNT(*) FROM turmas WHERE professor_id = ?');
$totalTurmas->execute([$pid]);
$numTurmas = (int)$totalTurmas->fetchColumn();

$totalDisc = $db->prepare('SELECT COUNT(d.id) FROM disciplinas d JOIN turmas t ON d.turma_id=t.id WHERE t.professor_id = ?');
$totalDisc->execute([$pid]);
$numDisc = (int)$totalDisc->fetchColumn();

$totalAlunos = $db->prepare('SELECT COUNT(a.id) FROM alunos a JOIN turmas t ON a.turma_id=t.id WHERE t.professor_id = ?');
$totalAlunos->execute([$pid]);
$numAlunos = (int)$totalAlunos->fetchColumn();

$totalSumarios = $db->prepare('SELECT COUNT(*) FROM sumarios WHERE professor_id = ?');
$totalSumarios->execute([$pid]);
$numSumarios = (int)$totalSumarios->fetchColumn();

// Últimos 5 sumários
$ultSumarios = $db->prepare('
  SELECT s.id, s.data_hora, s.conteudo, t.nome AS turma, d.nome AS disciplina
  FROM sumarios s
  JOIN turmas t ON s.turma_id = t.id
  JOIN disciplinas d ON s.disciplina_id = d.id
  WHERE s.professor_id = ?
  ORDER BY s.data_hora DESC
  LIMIT 5
');
$ultSumarios->execute([$pid]);
$sumarios = $ultSumarios->fetchAll();

// Taxa de presença geral (último mês)
$taxaStmt = $db->prepare('
  SELECT
    COUNT(*) AS total,
    SUM(presente) AS presentes
  FROM presencas p
  JOIN sumarios s ON p.sumario_id = s.id
  WHERE s.professor_id = ?
    AND s.data_hora >= DATE_SUB(NOW(), INTERVAL 30 DAY)
');
$taxaStmt->execute([$pid]);
$taxaRow  = $taxaStmt->fetch();
$taxaPresenca = ($taxaRow['total'] > 0)
  ? round(($taxaRow['presentes'] / $taxaRow['total']) * 100, 1)
  : 0;

$paginaTitulo = 'Dashboard';
$paginaAtiva  = 'dashboard';
require_once __DIR__ . '/includes/header.php';
require_once __DIR__ . '/includes/sidebar.php';
?>

<div class="page-header">
  <h2><i class="fa fa-gauge-high"></i> Dashboard</h2>
  <p>Bem-vindo, <strong><?= h($prof['nome']) ?></strong>! Aqui está o resumo da sua atividade.</p>
</div>

<!-- CARDS DE ESTATÍSTICAS -->
<div class="stats-grid">
  <div class="stat-card stat-blue">
    <div class="stat-icon"><i class="fa fa-users-rectangle"></i></div>
    <div class="stat-info">
      <h3><?= $numTurmas ?></h3>
      <p>Turmas</p>
    </div>
  </div>
  <div class="stat-card stat-green">
    <div class="stat-icon"><i class="fa fa-book"></i></div>
    <div class="stat-info">
      <h3><?= $numDisc ?></h3>
      <p>Disciplinas</p>
    </div>
  </div>
  <div class="stat-card stat-orange">
    <div class="stat-icon"><i class="fa fa-user-graduate"></i></div>
    <div class="stat-info">
      <h3><?= $numAlunos ?></h3>
      <p>Alunos</p>
    </div>
  </div>
  <div class="stat-card stat-purple">
    <div class="stat-icon"><i class="fa fa-book-open"></i></div>
    <div class="stat-info">
      <h3><?= $numSumarios ?></h3>
      <p>Sumários</p>
    </div>
  </div>
  <div class="stat-card stat-teal">
    <div class="stat-icon"><i class="fa fa-circle-check"></i></div>
    <div class="stat-info">
      <h3><?= $taxaPresenca ?>%</h3>
      <p>Presença (30 dias)</p>
    </div>
  </div>
</div>

<!-- ÚLTIMOS SUMÁRIOS -->
<div class="card mt-4">
  <div class="card-header">
    <h3><i class="fa fa-clock-rotate-left"></i> Últimos Sumários Registados</h3>
    <a href="/sistema_escolar/pages/sumario.php" class="btn-sm btn-primary">
      <i class="fa fa-plus"></i> Novo Sumário
    </a>
  </div>
  <div class="card-body">
    <?php if (empty($sumarios)): ?>
      <div class="empty-state">
        <i class="fa fa-book-open fa-2x"></i>
        <p>Nenhum sumário registado ainda.</p>
        <a href="/sistema_escolar/pages/sumario.php" class="btn-primary">Registar Sumário</a>
      </div>
    <?php else: ?>
      <div class="table-responsive">
        <table class="data-table">
          <thead>
            <tr>
              <th>Data / Hora</th>
              <th>Turma</th>
              <th>Disciplina</th>
              <th>Conteúdo</th>
            </tr>
          </thead>
          <tbody>
            <?php foreach ($sumarios as $s): ?>
              <tr>
                <td><span class="badge badge-blue"><?= date('d/m/Y H:i', strtotime($s['data_hora'])) ?></span></td>
                <td><?= h($s['turma']) ?></td>
                <td><?= h($s['disciplina']) ?></td>
                <td class="text-truncate" style="max-width:300px"><?= h($s['conteudo']) ?></td>
              </tr>
            <?php endforeach; ?>
          </tbody>
        </table>
      </div>
    <?php endif; ?>
  </div>
</div>

<!-- ATALHOS RÁPIDOS -->
<div class="quick-actions mt-4">
  <h3><i class="fa fa-bolt"></i> Ações Rápidas</h3>
  <div class="qa-grid">
    <a href="/sistema_escolar/pages/configuracoes.php" class="qa-card">
      <i class="fa fa-gear"></i><span>Configurações</span>
    </a>
    <a href="/sistema_escolar/pages/sumario.php" class="qa-card">
      <i class="fa fa-book"></i><span>Registo de Sumário</span>
    </a>
    <a href="/sistema_escolar/pages/presenca.php" class="qa-card">
      <i class="fa fa-clipboard-check"></i><span>Marcar Presença</span>
    </a>
    <a href="/sistema_escolar/pages/relatorio.php" class="qa-card">
      <i class="fa fa-chart-bar"></i><span>Relatórios</span>
    </a>
  </div>
</div>

<?php require_once __DIR__ . '/includes/footer.php'; ?>
