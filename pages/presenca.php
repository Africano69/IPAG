<?php
// pages/presenca.php
require_once __DIR__ . '/../config.php';
verificarAutenticacao();

$prof = professorLogado();
$db   = getDB();
$pid  = $prof['id'];

// ─── AJAX: salvar presenças ───────────────────────────────────
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action'])) {
    if ($_POST['action'] === 'get_sumarios') {
        $tid = $_POST['turma_id'] ?? 0;
        $did = $_POST['disciplina_id'] ?? 0;
        $st  = $db->prepare('
            SELECT s.id,s.data_hora,s.numero_aula,s.conteudo
            FROM sumarios s
            WHERE s.professor_id=? AND s.turma_id=? AND s.disciplina_id=?
            ORDER BY s.data_hora DESC LIMIT 50');
        $st->execute([$pid,$tid,$did]);
        jsonResponse(['data'=>$st->fetchAll()]);
    }

    if ($_POST['action'] === 'get_disciplinas') {
        $tid = $_POST['turma_id'] ?? 0;
        $st  = $db->prepare('SELECT d.id,d.nome FROM disciplinas d JOIN turmas t ON d.turma_id=t.id WHERE d.turma_id=? AND t.professor_id=? ORDER BY d.nome');
        $st->execute([$tid,$pid]);
        jsonResponse(['data'=>$st->fetchAll()]);
    }

    if ($_POST['action'] === 'get_alunos_sumario') {
        $sid = $_POST['sumario_id'] ?? 0;
        // buscar alunos da turma do sumário
        $stS = $db->prepare('SELECT turma_id FROM sumarios WHERE id=? AND professor_id=?');
        $stS->execute([$sid,$pid]);
        $sum = $stS->fetch();
        if (!$sum) jsonResponse(['ok'=>false,'msg'=>'Sumário inválido.']);
        $stA = $db->prepare('SELECT a.id,a.nome,a.numero FROM alunos a WHERE a.turma_id=? ORDER BY a.nome');
        $stA->execute([$sum['turma_id']]);
        $alunos = $stA->fetchAll();
        // Presenças já marcadas
        $stP = $db->prepare('SELECT aluno_id,presente FROM presencas WHERE sumario_id=?');
        $stP->execute([$sid]);
        $marcadas = [];
        foreach ($stP->fetchAll() as $p) $marcadas[$p['aluno_id']] = (bool)$p['presente'];
        jsonResponse(['alunos'=>$alunos,'marcadas'=>$marcadas]);
    }

    if ($_POST['action'] === 'salvar_presencas') {
        $sid    = $_POST['sumario_id'] ?? 0;
        $presentes = $_POST['presentes'] ?? [];   // array de aluno_ids presentes
        $todos  = $_POST['todos_alunos'] ?? [];   // array com todos os aluno_ids da listagem
        // valida posse
        $chk = $db->prepare('SELECT id FROM sumarios WHERE id=? AND professor_id=?');
        $chk->execute([$sid,$pid]);
        if (!$chk->fetch()) jsonResponse(['ok'=>false,'msg'=>'Sumário inválido.']);
        $ins = $db->prepare('INSERT INTO presencas (sumario_id,aluno_id,presente) VALUES (?,?,?) ON DUPLICATE KEY UPDATE presente=VALUES(presente)');
        foreach ($todos as $aid) {
            $ins->execute([$sid, $aid, in_array($aid, $presentes) ? 1 : 0]);
        }
        jsonResponse(['ok'=>true,'msg'=>'Presenças guardadas com sucesso!']);
    }

    jsonResponse(['ok'=>false,'msg'=>'Ação desconhecida.'],400);
}

$stTurmas = $db->prepare('SELECT * FROM turmas WHERE professor_id=? ORDER BY nome');
$stTurmas->execute([$pid]);
$turmas = $stTurmas->fetchAll();

$paginaTitulo = 'Marcar Presença';
$paginaAtiva  = 'presenca';
require_once __DIR__ . '/../includes/header.php';
require_once __DIR__ . '/../includes/sidebar.php';
?>

<div class="page-header">
  <h2><i class="fa fa-clipboard-check"></i> Marcar Presença</h2>
  <p>Selecione a turma, disciplina e sumário para registar as presenças.</p>
</div>

<!-- PASSO 1: Selecionar Sumário -->
<div class="card mb-3">
  <div class="card-header"><h3><i class="fa fa-filter"></i> Passo 1 — Selecionar Sumário</h3></div>
  <div class="card-body">
    <div class="form-row">
      <div class="form-group">
        <label><i class="fa fa-users-rectangle"></i> Turma *</label>
        <select id="selTurma" onchange="carregarDisciplinas()">
          <option value="">— Selecione —</option>
          <?php foreach ($turmas as $t): ?>
            <option value="<?= $t['id'] ?>"><?= h($t['nome']) ?> (<?= $t['ano_letivo'] ?>)</option>
          <?php endforeach; ?>
        </select>
      </div>
      <div class="form-group">
        <label><i class="fa fa-book"></i> Disciplina *</label>
        <select id="selDisciplina" onchange="carregarSumarios()">
          <option value="">— Selecione a turma —</option>
        </select>
      </div>
      <div class="form-group">
        <label><i class="fa fa-book-open"></i> Sumário *</label>
        <select id="selSumario" onchange="carregarAlunos()">
          <option value="">— Selecione a disciplina —</option>
        </select>
      </div>
    </div>
  </div>
</div>

<!-- PASSO 2: Lista de Alunos -->
<div id="areaPresenca" style="display:none">
  <div class="card">
    <div class="card-header">
      <h3><i class="fa fa-users"></i> Passo 2 — Marcar Presenças</h3>
      <div class="header-actions">
        <button class="btn-sm btn-green" onclick="marcarTodos(true)"><i class="fa fa-check-double"></i> Todos Presentes</button>
        <button class="btn-sm btn-orange" onclick="marcarTodos(false)"><i class="fa fa-xmark"></i> Todos Ausentes</button>
      </div>
    </div>
    <div class="card-body">
      <div id="infoSumario" class="sumario-info mb-3"></div>

      <!-- Barra de pesquisa de aluno -->
      <div class="form-group mb-3">
        <input type="text" id="searchAluno" placeholder="🔍 Pesquisar aluno..." oninput="filtrarAlunos()" class="search-input">
      </div>

      <div id="listaAlunos"></div>

      <div class="presenca-footer mt-3">
        <div class="presenca-stats" id="presencaStats"></div>
        <button class="btn-primary btn-lg" id="btnSalvarPresenca" onclick="salvarPresencas()">
          <i class="fa fa-save"></i> Guardar Presenças
        </button>
      </div>
    </div>
  </div>
</div>

<script>
const BASE = '/sistema_escolar/pages/presenca.php';
let todosAlunos = [];

async function carregarDisciplinas() {
  const tid = document.getElementById('selTurma').value;
  const selD = document.getElementById('selDisciplina');
  selD.innerHTML = '<option value="">Carregando...</option>';
  document.getElementById('selSumario').innerHTML = '<option value="">—</option>';
  document.getElementById('areaPresenca').style.display = 'none';
  if (!tid) { selD.innerHTML='<option value="">— Selecione a turma —</option>'; return; }
  const fd=new FormData(); fd.append('action','get_disciplinas'); fd.append('turma_id',tid);
  const r=await fetch(BASE,{method:'POST',body:fd}); const d=await r.json();
  selD.innerHTML='<option value="">— Selecione —</option>'+d.data.map(x=>`<option value="${x.id}">${esc(x.nome)}</option>`).join('');
}

async function carregarSumarios() {
  const tid=document.getElementById('selTurma').value;
  const did=document.getElementById('selDisciplina').value;
  const selS=document.getElementById('selSumario');
  selS.innerHTML='<option value="">Carregando...</option>';
  document.getElementById('areaPresenca').style.display='none';
  if (!tid||!did){selS.innerHTML='<option value="">—</option>';return;}
  const fd=new FormData(); fd.append('action','get_sumarios'); fd.append('turma_id',tid); fd.append('disciplina_id',did);
  const r=await fetch(BASE,{method:'POST',body:fd}); const d=await r.json();
  if (!d.data.length){selS.innerHTML='<option value="">Sem sumários para esta disciplina</option>';return;}
  selS.innerHTML='<option value="">— Selecione —</option>'+
    d.data.map(s=>`<option value="${s.id}">[Aula ${s.numero_aula}] ${formatDate(s.data_hora)} — ${esc(s.conteudo).substring(0,40)}…</option>`).join('');
}

async function carregarAlunos() {
  const sid=document.getElementById('selSumario').value;
  if (!sid){document.getElementById('areaPresenca').style.display='none';return;}
  const fd=new FormData(); fd.append('action','get_alunos_sumario'); fd.append('sumario_id',sid);
  const r=await fetch(BASE,{method:'POST',body:fd}); const d=await r.json();
  todosAlunos=d.alunos;
  renderAlunos(d.alunos, d.marcadas);
  document.getElementById('areaPresenca').style.display='block';
  // Info do sumário
  const opt = document.getElementById('selSumario').selectedOptions[0];
  document.getElementById('infoSumario').innerHTML=`
    <div class="alert alert-info"><i class="fa fa-circle-info"></i> Sumário: <strong>${opt.text}</strong></div>`;
  atualizarStats();
}

function renderAlunos(alunos, marcadas={}) {
  const el=document.getElementById('listaAlunos');
  if(!alunos.length){el.innerHTML='<div class="empty-state"><i class="fa fa-users fa-2x"></i><p>Nenhum aluno nesta turma.</p></div>';return;}
  el.innerHTML=`<div class="attendance-grid">
    ${alunos.map((a,i)=>{
      const pres = marcadas[a.id] !== undefined ? marcadas[a.id] : true;
      return `<div class="att-card ${pres?'presente':'ausente'}" id="card-${a.id}">
        <div class="att-num">${a.numero||i+1}</div>
        <div class="att-info">
          <span class="att-name">${esc(a.nome)}</span>
        </div>
        <div class="att-btns">
          <button class="att-btn pres-btn ${pres?'active':''}" onclick="setPresenca(${a.id},true)" title="Presente">
            <i class="fa fa-check"></i>
          </button>
          <button class="att-btn aus-btn ${!pres?'active':''}" onclick="setPresenca(${a.id},false)" title="Ausente">
            <i class="fa fa-xmark"></i>
          </button>
        </div>
      </div>`;
    }).join('')}
  </div>`;
  atualizarStats();
}

function setPresenca(aid, presente) {
  const card=document.getElementById('card-'+aid);
  card.className='att-card '+(presente?'presente':'ausente');
  card.querySelector('.pres-btn').classList.toggle('active',presente);
  card.querySelector('.aus-btn').classList.toggle('active',!presente);
  atualizarStats();
}

function marcarTodos(pres) {
  todosAlunos.forEach(a=>setPresenca(a.id,pres));
}

function filtrarAlunos() {
  const q=document.getElementById('searchAluno').value.toLowerCase();
  document.querySelectorAll('.att-card').forEach(c=>{
    c.style.display=c.querySelector('.att-name').textContent.toLowerCase().includes(q)?'':'none';
  });
}

function atualizarStats() {
  const total=document.querySelectorAll('.att-card').length;
  const presentes=document.querySelectorAll('.att-card.presente').length;
  document.getElementById('presencaStats').innerHTML=
    `<span class="badge badge-green"><i class="fa fa-check"></i> ${presentes} presentes</span>
     <span class="badge badge-red"><i class="fa fa-xmark"></i> ${total-presentes} ausentes</span>
     <span class="badge badge-blue"><i class="fa fa-users"></i> ${total} total</span>`;
}

async function salvarPresencas() {
  const sid=document.getElementById('selSumario').value;
  const todos=todosAlunos.map(a=>a.id);
  const presentes=[];
  document.querySelectorAll('.att-card.presente').forEach(c=>{
    presentes.push(parseInt(c.id.replace('card-','')));
  });
  const fd=new FormData();
  fd.append('action','salvar_presencas');
  fd.append('sumario_id',sid);
  todos.forEach(id=>fd.append('todos_alunos[]',id));
  presentes.forEach(id=>fd.append('presentes[]',id));
  const res=await fetch(BASE,{method:'POST',body:fd}).then(r=>r.json());
  if(res.ok) toast(res.msg,'success');
  else toast(res.msg,'error');
}

function formatDate(dt){
  const d=new Date(dt);
  return d.toLocaleDateString('pt-AO',{day:'2-digit',month:'2-digit',year:'numeric'})+' '+d.toLocaleTimeString('pt-AO',{hour:'2-digit',minute:'2-digit'});
}
function esc(s){return String(s).replace(/[&<>"']/g,c=>({'&':'&amp;','<':'&lt;','>':'&gt;','"':'&quot;',"'":'&#39;'}[c]));}
</script>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>
