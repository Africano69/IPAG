<?php
// pages/relatorio.php
require_once __DIR__ . '/../config.php';
verificarAutenticacao();

$prof = professorLogado();
$db   = getDB();
$pid  = $prof['id'];

// ─── AJAX ────────────────────────────────────────────────────
if (isset($_GET['fetch'])) {
    if ($_GET['fetch'] === 'disciplinas') {
        $tid=$_GET['turma_id']??0;
        $st=$db->prepare('SELECT d.id,d.nome FROM disciplinas d JOIN turmas t ON d.turma_id=t.id WHERE d.turma_id=? AND t.professor_id=? ORDER BY d.nome');
        $st->execute([$tid,$pid]);
        jsonResponse(['data'=>$st->fetchAll()]);
    }
}

// ─── EXPORTAÇÃO EXCEL ────────────────────────────────────────
if (isset($_GET['export'])) {
    $tipo  = $_GET['export'];
    $tid   = $_GET['turma_id']   ?? 0;
    $did   = $_GET['disciplina_id'] ?? 0;
    $dtIni = $_GET['data_ini']   ?? '';
    $dtFim = $_GET['data_fim']   ?? '';

    header('Content-Type: application/vnd.ms-excel; charset=utf-8');
    header('Content-Disposition: attachment; filename="relatorio_' . $tipo . '_' . date('Ymd_His') . '.xls"');
    header('Cache-Control: max-age=0');
    echo "\xEF\xBB\xBF"; // BOM UTF-8

    if ($tipo === 'sumarios') {
        $sql='SELECT s.data_hora,s.numero_aula,t.nome AS turma,d.nome AS disciplina,s.conteudo
              FROM sumarios s JOIN turmas t ON s.turma_id=t.id JOIN disciplinas d ON s.disciplina_id=d.id
              WHERE s.professor_id=?';
        $params=[$pid];
        if ($tid) {$sql.=' AND s.turma_id=?';$params[]=$tid;}
        if ($did) {$sql.=' AND s.disciplina_id=?';$params[]=$did;}
        if ($dtIni){$sql.=' AND DATE(s.data_hora)>=?';$params[]=$dtIni;}
        if ($dtFim){$sql.=' AND DATE(s.data_hora)<=?';$params[]=$dtFim;}
        $sql.=' ORDER BY s.data_hora DESC';
        $st=$db->prepare($sql); $st->execute($params); $rows=$st->fetchAll();

        echo "<table border='1'>";
        echo "<tr style='background:#1e3a5f;color:white;font-weight:bold'>
                <th>Data/Hora</th><th>Turma</th><th>Disciplina</th><th>Nº Aula</th><th>Conteúdo</th>
              </tr>";
        foreach ($rows as $r) {
            echo "<tr>
                    <td>" . date('d/m/Y H:i',strtotime($r['data_hora'])) . "</td>
                    <td>" . htmlspecialchars($r['turma']) . "</td>
                    <td>" . htmlspecialchars($r['disciplina']) . "</td>
                    <td>" . $r['numero_aula'] . "</td>
                    <td>" . htmlspecialchars($r['conteudo']) . "</td>
                  </tr>";
        }
        echo "</table>";
    }

    if ($tipo === 'presencas') {
        $sql='SELECT a.nome AS aluno,a.numero,t.nome AS turma,d.nome AS disciplina,
                     s.data_hora,s.numero_aula,
                     CASE WHEN p.presente=1 THEN "Presente" ELSE "Ausente" END AS estado
              FROM presencas p
              JOIN alunos a    ON p.aluno_id=a.id
              JOIN sumarios s  ON p.sumario_id=s.id
              JOIN turmas t    ON s.turma_id=t.id
              JOIN disciplinas d ON s.disciplina_id=d.id
              WHERE s.professor_id=?';
        $params=[$pid];
        if ($tid){$sql.=' AND s.turma_id=?';$params[]=$tid;}
        if ($did){$sql.=' AND s.disciplina_id=?';$params[]=$did;}
        if ($dtIni){$sql.=' AND DATE(s.data_hora)>=?';$params[]=$dtIni;}
        if ($dtFim){$sql.=' AND DATE(s.data_hora)<=?';$params[]=$dtFim;}
        $sql.=' ORDER BY t.nome,d.nome,s.data_hora,a.nome';
        $st=$db->prepare($sql); $st->execute($params); $rows=$st->fetchAll();

        echo "<table border='1'>";
        echo "<tr style='background:#1e3a5f;color:white;font-weight:bold'>
                <th>Aluno</th><th>Nº</th><th>Turma</th><th>Disciplina</th><th>Data/Hora</th><th>Nº Aula</th><th>Estado</th>
              </tr>";
        foreach ($rows as $r) {
            $cor = $r['estado']==='Presente' ? '#d4edda' : '#f8d7da';
            echo "<tr style='background:{$cor}'>
                    <td>".htmlspecialchars($r['aluno'])."</td>
                    <td>".htmlspecialchars($r['numero'])."</td>
                    <td>".htmlspecialchars($r['turma'])."</td>
                    <td>".htmlspecialchars($r['disciplina'])."</td>
                    <td>".date('d/m/Y H:i',strtotime($r['data_hora']))."</td>
                    <td>".$r['numero_aula']."</td>
                    <td><b>".$r['estado']."</b></td>
                  </tr>";
        }
        echo "</table>";
    }

    if ($tipo === 'resumo') {
        $sql='SELECT a.nome AS aluno,a.numero,t.nome AS turma,d.nome AS disciplina,
                     COUNT(p.id) AS total_aulas,
                     SUM(p.presente) AS presentes,
                     (COUNT(p.id)-SUM(p.presente)) AS ausentes,
                     ROUND(SUM(p.presente)/COUNT(p.id)*100,1) AS taxa
              FROM presencas p
              JOIN alunos a    ON p.aluno_id=a.id
              JOIN sumarios s  ON p.sumario_id=s.id
              JOIN turmas t    ON s.turma_id=t.id
              JOIN disciplinas d ON s.disciplina_id=d.id
              WHERE s.professor_id=?';
        $params=[$pid];
        if ($tid){$sql.=' AND s.turma_id=?';$params[]=$tid;}
        if ($did){$sql.=' AND s.disciplina_id=?';$params[]=$did;}
        if ($dtIni){$sql.=' AND DATE(s.data_hora)>=?';$params[]=$dtIni;}
        if ($dtFim){$sql.=' AND DATE(s.data_hora)<=?';$params[]=$dtFim;}
        $sql.=' GROUP BY a.id,d.id ORDER BY t.nome,d.nome,a.nome';
        $st=$db->prepare($sql); $st->execute($params); $rows=$st->fetchAll();

        echo "<table border='1'>";
        echo "<tr style='background:#1e3a5f;color:white;font-weight:bold'>
                <th>Aluno</th><th>Nº</th><th>Turma</th><th>Disciplina</th>
                <th>Total Aulas</th><th>Presenças</th><th>Faltas</th><th>Taxa (%)</th>
              </tr>";
        foreach ($rows as $r) {
            $cor = $r['taxa']>=75 ? '#d4edda' : ($r['taxa']>=50 ? '#fff3cd' : '#f8d7da');
            echo "<tr style='background:{$cor}'>
                    <td>".htmlspecialchars($r['aluno'])."</td>
                    <td>".htmlspecialchars($r['numero'])."</td>
                    <td>".htmlspecialchars($r['turma'])."</td>
                    <td>".htmlspecialchars($r['disciplina'])."</td>
                    <td>{$r['total_aulas']}</td>
                    <td>{$r['presentes']}</td>
                    <td>{$r['ausentes']}</td>
                    <td><b>{$r['taxa']}%</b></td>
                  </tr>";
        }
        echo "</table>";
    }
    exit;
}

// ─── Dados para a view ───────────────────────────────────────
$stTurmas = $db->prepare('SELECT * FROM turmas WHERE professor_id=? ORDER BY nome');
$stTurmas->execute([$pid]);
$turmas = $stTurmas->fetchAll();

$paginaTitulo = 'Relatórios';
$paginaAtiva  = 'relatorio';
require_once __DIR__ . '/../includes/header.php';
require_once __DIR__ . '/../includes/sidebar.php';
?>

<div class="page-header">
  <h2><i class="fa fa-chart-bar"></i> Relatórios</h2>
  <p>Exporte relatórios das suas turmas e presenças em formato Excel.</p>
</div>

<!-- FILTROS -->
<div class="card mb-4">
  <div class="card-header"><h3><i class="fa fa-filter"></i> Filtros</h3></div>
  <div class="card-body">
    <div class="form-row">
      <div class="form-group">
        <label><i class="fa fa-users-rectangle"></i> Turma</label>
        <select id="relTurma" onchange="carregarDisciplinas()">
          <option value="">Todas as turmas</option>
          <?php foreach ($turmas as $t): ?>
            <option value="<?= $t['id'] ?>"><?= h($t['nome']) ?> (<?= $t['ano_letivo'] ?>)</option>
          <?php endforeach; ?>
        </select>
      </div>
      <div class="form-group">
        <label><i class="fa fa-book"></i> Disciplina</label>
        <select id="relDisciplina">
          <option value="">Todas as disciplinas</option>
        </select>
      </div>
      <div class="form-group">
        <label><i class="fa fa-calendar"></i> Data Inicial</label>
        <input type="date" id="relDtIni">
      </div>
      <div class="form-group">
        <label><i class="fa fa-calendar"></i> Data Final</label>
        <input type="date" id="relDtFim">
      </div>
    </div>
  </div>
</div>

<!-- CARTÕES DE RELATÓRIO -->
<div class="report-grid">

  <div class="report-card">
    <div class="report-icon report-icon-blue">
      <i class="fa fa-book"></i>
    </div>
    <div class="report-info">
      <h3>Sumários</h3>
      <p>Todos os sumários registados com conteúdos de aula.</p>
    </div>
    <button class="btn-primary" onclick="exportar('sumarios')">
      <i class="fa fa-file-excel"></i> Exportar Excel
    </button>
  </div>

  <div class="report-card">
    <div class="report-icon report-icon-green">
      <i class="fa fa-clipboard-check"></i>
    </div>
    <div class="report-info">
      <h3>Lista de Presenças</h3>
      <p>Presenças detalhadas por aluno, aula e disciplina.</p>
    </div>
    <button class="btn-primary" onclick="exportar('presencas')">
      <i class="fa fa-file-excel"></i> Exportar Excel
    </button>
  </div>

  <div class="report-card">
    <div class="report-icon report-icon-purple">
      <i class="fa fa-chart-pie"></i>
    </div>
    <div class="report-info">
      <h3>Resumo de Presenças</h3>
      <p>Resumo com total de presenças e taxa por aluno/disciplina.</p>
    </div>
    <button class="btn-primary" onclick="exportar('resumo')">
      <i class="fa fa-file-excel"></i> Exportar Excel
    </button>
  </div>

</div>

<!-- PRÉVIA DOS DADOS -->
<div class="card mt-4">
  <div class="card-header">
    <h3><i class="fa fa-table"></i> Pré-visualização — Resumo de Presenças</h3>
    <button class="btn-sm btn-secondary" onclick="carregarPrevia()">
      <i class="fa fa-rotate"></i> Actualizar
    </button>
  </div>
  <div class="card-body" id="previaArea">
    <div class="empty-state"><i class="fa fa-table fa-2x"></i><p>Clique em "Actualizar" para carregar a pré-visualização.</p></div>
  </div>
</div>

<script>
const BASE_REL = '/sistema_escolar/pages/relatorio.php';

async function carregarDisciplinas() {
  const tid = document.getElementById('relTurma').value;
  const sel = document.getElementById('relDisciplina');
  sel.innerHTML = '<option value="">Todas as disciplinas</option>';
  if (!tid) return;
  const r = await fetch(`${BASE_REL}?fetch=disciplinas&turma_id=${tid}`);
  const d = await r.json();
  sel.innerHTML = '<option value="">Todas</option>' + d.data.map(x=>`<option value="${x.id}">${esc(x.nome)}</option>`).join('');
}

function buildParams() {
  const p = new URLSearchParams();
  const t=document.getElementById('relTurma').value;
  const d=document.getElementById('relDisciplina').value;
  const i=document.getElementById('relDtIni').value;
  const f=document.getElementById('relDtFim').value;
  if(t) p.set('turma_id',t);
  if(d) p.set('disciplina_id',d);
  if(i) p.set('data_ini',i);
  if(f) p.set('data_fim',f);
  return p.toString();
}

function exportar(tipo) {
  const params=buildParams();
  window.location.href=`${BASE_REL}?export=${tipo}&${params}`;
}

async function carregarPrevia() {
  const el=document.getElementById('previaArea');
  el.innerHTML='<div class="loading"><i class="fa fa-spinner fa-spin"></i> Carregando...</div>';
  const params=buildParams();
  const tid=document.getElementById('relTurma').value;
  const did=document.getElementById('relDisciplina').value;
  const ini=document.getElementById('relDtIni').value;
  const fim=document.getElementById('relDtFim').value;
  const r=await fetch(`${BASE_REL}?preview=resumo&${params}`);
  // Faz consulta inline via AJAX separado
  const fd=new FormData();
  fd.append('action','preview_resumo');
  if(tid) fd.append('turma_id',tid);
  if(did) fd.append('disciplina_id',did);
  if(ini) fd.append('data_ini',ini);
  if(fim) fd.append('data_fim',fim);
  const res=await fetch(BASE_REL,{method:'POST',body:fd}).then(r=>r.json()).catch(()=>null);
  if(!res||!res.data||!res.data.length){
    el.innerHTML='<div class="empty-state"><i class="fa fa-table fa-2x"></i><p>Nenhum dado encontrado para os filtros seleccionados.</p></div>';
    return;
  }
  el.innerHTML=`<div class="table-responsive"><table class="data-table">
    <thead><tr><th>Aluno</th><th>Nº</th><th>Turma</th><th>Disciplina</th><th>Total</th><th>Presenças</th><th>Faltas</th><th>Taxa</th></tr></thead>
    <tbody>${res.data.map(r=>`<tr>
      <td>${esc(r.aluno)}</td><td>${esc(r.numero||'—')}</td><td>${esc(r.turma)}</td><td>${esc(r.disciplina)}</td>
      <td>${r.total_aulas}</td><td><span class="badge badge-green">${r.presentes}</span></td>
      <td><span class="badge badge-red">${r.ausentes}</span></td>
      <td><span class="badge ${r.taxa>=75?'badge-green':r.taxa>=50?'badge-orange':'badge-red'}">${r.taxa}%</span></td>
    </tr>`).join('')}</tbody>
  </table></div>`;
}

function esc(s){return String(s||'').replace(/[&<>"']/g,c=>({'&':'&amp;','<':'&lt;','>':'&gt;','"':'&quot;',"'":'&#39;'}[c]));}
</script>

<?php
// Handler AJAX para pré-visualização
if ($_SERVER['REQUEST_METHOD']==='POST' && ($_POST['action']??'')==='preview_resumo') {
    $tid2=$_POST['turma_id']??0; $did2=$_POST['disciplina_id']??0;
    $ini2=$_POST['data_ini']??''; $fim2=$_POST['data_fim']??'';
    $s2='SELECT a.nome AS aluno,a.numero,t.nome AS turma,d.nome AS disciplina,
                COUNT(p.id) AS total_aulas,SUM(p.presente) AS presentes,
                (COUNT(p.id)-SUM(p.presente)) AS ausentes,
                ROUND(SUM(p.presente)/COUNT(p.id)*100,1) AS taxa
         FROM presencas p JOIN alunos a ON p.aluno_id=a.id
         JOIN sumarios s ON p.sumario_id=s.id JOIN turmas t ON s.turma_id=t.id
         JOIN disciplinas d ON s.disciplina_id=d.id
         WHERE s.professor_id=?';
    $p2=[$pid];
    if($tid2){$s2.=' AND s.turma_id=?';$p2[]=$tid2;}
    if($did2){$s2.=' AND s.disciplina_id=?';$p2[]=$did2;}
    if($ini2){$s2.=' AND DATE(s.data_hora)>=?';$p2[]=$ini2;}
    if($fim2){$s2.=' AND DATE(s.data_hora)<=?';$p2[]=$fim2;}
    $s2.=' GROUP BY a.id,d.id ORDER BY t.nome,d.nome,a.nome LIMIT 100';
    $st2=$db->prepare($s2);$st2->execute($p2);
    jsonResponse(['data'=>$st2->fetchAll()]);
}

require_once __DIR__ . '/../includes/footer.php';
?>
