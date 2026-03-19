<?php
// pages/configuracoes.php
require_once __DIR__ . '/../config.php';
verificarAutenticacao();

$prof = professorLogado();
$db   = getDB();
$pid  = $prof['id'];

// ─── AJAX handlers ───────────────────────────────────────────
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action'])) {
    $action = $_POST['action'];

    /* ── TURMAS ── */
    if ($action === 'criar_turma') {
        $nome = trim($_POST['nome'] ?? '');
        $desc = trim($_POST['descricao'] ?? '');
        $ano  = (int)($_POST['ano_letivo'] ?? date('Y'));
        if (!$nome) jsonResponse(['ok'=>false,'msg'=>'Nome obrigatório.']);
        $st = $db->prepare('INSERT INTO turmas (professor_id,nome,descricao,ano_letivo) VALUES (?,?,?,?)');
        $st->execute([$pid,$nome,$desc,$ano]);
        jsonResponse(['ok'=>true,'id'=>$db->lastInsertId(),'msg'=>'Turma criada!']);
    }
    if ($action === 'editar_turma') {
        $id=$_POST['id']; $nome=trim($_POST['nome']??''); $desc=trim($_POST['descricao']??''); $ano=(int)$_POST['ano_letivo'];
        if (!$nome) jsonResponse(['ok'=>false,'msg'=>'Nome obrigatório.']);
        $st=$db->prepare('UPDATE turmas SET nome=?,descricao=?,ano_letivo=? WHERE id=? AND professor_id=?');
        $st->execute([$nome,$desc,$ano,$id,$pid]);
        jsonResponse(['ok'=>true,'msg'=>'Turma actualizada!']);
    }
    if ($action === 'apagar_turma') {
        $st=$db->prepare('DELETE FROM turmas WHERE id=? AND professor_id=?');
        $st->execute([$_POST['id'],$pid]);
        jsonResponse(['ok'=>true,'msg'=>'Turma removida!']);
    }

    /* ── DISCIPLINAS ── */
    if ($action === 'criar_disciplina') {
        $tid=trim($_POST['turma_id']??''); $nome=trim($_POST['nome']??''); $ch=(int)($_POST['carga_horaria']??0);
        if (!$tid||!$nome) jsonResponse(['ok'=>false,'msg'=>'Campos obrigatórios em falta.']);
        // verifica posse da turma
        $chk=$db->prepare('SELECT id FROM turmas WHERE id=? AND professor_id=?');
        $chk->execute([$tid,$pid]);
        if (!$chk->fetch()) jsonResponse(['ok'=>false,'msg'=>'Turma inválida.']);
        $st=$db->prepare('INSERT INTO disciplinas (turma_id,nome,carga_horaria) VALUES (?,?,?)');
        $st->execute([$tid,$nome,$ch]);
        jsonResponse(['ok'=>true,'id'=>$db->lastInsertId(),'msg'=>'Disciplina criada!']);
    }
    if ($action === 'editar_disciplina') {
        $id=$_POST['id']; $nome=trim($_POST['nome']??''); $ch=(int)($_POST['carga_horaria']??0);
        $st=$db->prepare('UPDATE disciplinas d JOIN turmas t ON d.turma_id=t.id SET d.nome=?,d.carga_horaria=? WHERE d.id=? AND t.professor_id=?');
        $st->execute([$nome,$ch,$id,$pid]);
        jsonResponse(['ok'=>true,'msg'=>'Disciplina actualizada!']);
    }
    if ($action === 'apagar_disciplina') {
        $st=$db->prepare('DELETE d FROM disciplinas d JOIN turmas t ON d.turma_id=t.id WHERE d.id=? AND t.professor_id=?');
        $st->execute([$_POST['id'],$pid]);
        jsonResponse(['ok'=>true,'msg'=>'Disciplina removida!']);
    }

    /* ── ALUNOS ── */
    if ($action === 'criar_aluno') {
        $tid=trim($_POST['turma_id']??''); $nome=trim($_POST['nome']??''); $num=trim($_POST['numero']??''); $email=trim($_POST['email']??'');
        if (!$tid||!$nome) jsonResponse(['ok'=>false,'msg'=>'Campos obrigatórios em falta.']);
        $chk=$db->prepare('SELECT id FROM turmas WHERE id=? AND professor_id=?');
        $chk->execute([$tid,$pid]);
        if (!$chk->fetch()) jsonResponse(['ok'=>false,'msg'=>'Turma inválida.']);
        $st=$db->prepare('INSERT INTO alunos (turma_id,nome,numero,email) VALUES (?,?,?,?)');
        $st->execute([$tid,$nome,$num,$email]);
        jsonResponse(['ok'=>true,'id'=>$db->lastInsertId(),'msg'=>'Aluno adicionado!']);
    }
    if ($action === 'editar_aluno') {
        $id=$_POST['id']; $nome=trim($_POST['nome']??''); $num=trim($_POST['numero']??''); $email=trim($_POST['email']??'');
        $st=$db->prepare('UPDATE alunos a JOIN turmas t ON a.turma_id=t.id SET a.nome=?,a.numero=?,a.email=? WHERE a.id=? AND t.professor_id=?');
        $st->execute([$nome,$num,$email,$id,$pid]);
        jsonResponse(['ok'=>true,'msg'=>'Aluno actualizado!']);
    }
    if ($action === 'apagar_aluno') {
        $st=$db->prepare('DELETE a FROM alunos a JOIN turmas t ON a.turma_id=t.id WHERE a.id=? AND t.professor_id=?');
        $st->execute([$_POST['id'],$pid]);
        jsonResponse(['ok'=>true,'msg'=>'Aluno removido!']);
    }

    jsonResponse(['ok'=>false,'msg'=>'Ação desconhecida.'], 400);
}

// ─── GET: buscar disciplinas/alunos por turma para modais ────
if ($_SERVER['REQUEST_METHOD'] === 'GET' && isset($_GET['fetch'])) {
    if ($_GET['fetch'] === 'disciplinas') {
        $tid=$_GET['turma_id']??0;
        $st=$db->prepare('SELECT d.* FROM disciplinas d JOIN turmas t ON d.turma_id=t.id WHERE d.turma_id=? AND t.professor_id=? ORDER BY d.nome');
        $st->execute([$tid,$pid]);
        jsonResponse(['data'=>$st->fetchAll()]);
    }
    if ($_GET['fetch'] === 'alunos') {
        $tid=$_GET['turma_id']??0;
        $st=$db->prepare('SELECT a.* FROM alunos a JOIN turmas t ON a.turma_id=t.id WHERE a.turma_id=? AND t.professor_id=? ORDER BY a.nome');
        $st->execute([$tid,$pid]);
        jsonResponse(['data'=>$st->fetchAll()]);
    }
}

// ─── Carregar todas as turmas para a view ────────────────────
$stTurmas=$db->prepare('SELECT * FROM turmas WHERE professor_id=? ORDER BY ano_letivo DESC, nome');
$stTurmas->execute([$pid]);
$turmas=$stTurmas->fetchAll();

$paginaTitulo='Configurações';
$paginaAtiva='configuracoes';
require_once __DIR__ . '/../includes/header.php';
require_once __DIR__ . '/../includes/sidebar.php';
?>

<div class="page-header">
  <h2><i class="fa fa-gear"></i> Configurações</h2>
  <p>Gerencie as suas turmas, disciplinas e alunos.</p>
</div>

<!-- TABS -->
<div class="tabs-nav">
  <button class="tab-btn active" data-tab="turmas"><i class="fa fa-users-rectangle"></i> Turmas</button>
  <button class="tab-btn" data-tab="disciplinas"><i class="fa fa-book"></i> Disciplinas</button>
  <button class="tab-btn" data-tab="alunos"><i class="fa fa-user-graduate"></i> Alunos</button>
</div>

<!-- TAB: TURMAS -->
<div class="tab-content active" id="tab-turmas">
  <div class="card">
    <div class="card-header">
      <h3><i class="fa fa-users-rectangle"></i> Turmas</h3>
      <button class="btn-primary btn-sm" onclick="abrirModal('modalTurma')">
        <i class="fa fa-plus"></i> Nova Turma
      </button>
    </div>
    <div class="card-body">
      <?php if (empty($turmas)): ?>
        <div class="empty-state"><i class="fa fa-users-rectangle fa-2x"></i><p>Nenhuma turma criada.</p></div>
      <?php else: ?>
        <div class="table-responsive">
          <table class="data-table" id="tabelaTurmas">
            <thead><tr><th>#</th><th>Nome</th><th>Ano Letivo</th><th>Descrição</th><th>Ações</th></tr></thead>
            <tbody>
              <?php foreach ($turmas as $i => $t): ?>
                <tr>
                  <td><?= $i+1 ?></td>
                  <td><?= h($t['nome']) ?></td>
                  <td><?= h($t['ano_letivo']) ?></td>
                  <td><?= h($t['descricao']) ?></td>
                  <td class="actions">
                    <button class="btn-icon btn-edit" title="Editar"
                      onclick="editarTurma(<?= $t['id'] ?>, '<?= h(addslashes($t['nome'])) ?>', '<?= h(addslashes($t['descricao'])) ?>', <?= $t['ano_letivo'] ?>)">
                      <i class="fa fa-pen"></i>
                    </button>
                    <button class="btn-icon btn-delete" title="Apagar"
                      onclick="apagarTurma(<?= $t['id'] ?>, '<?= h(addslashes($t['nome'])) ?>')">
                      <i class="fa fa-trash"></i>
                    </button>
                  </td>
                </tr>
              <?php endforeach; ?>
            </tbody>
          </table>
        </div>
      <?php endif; ?>
    </div>
  </div>
</div>

<!-- TAB: DISCIPLINAS -->
<div class="tab-content" id="tab-disciplinas">
  <div class="card">
    <div class="card-header">
      <h3><i class="fa fa-book"></i> Disciplinas</h3>
      <button class="btn-primary btn-sm" onclick="abrirModal('modalDisciplina')">
        <i class="fa fa-plus"></i> Nova Disciplina
      </button>
    </div>
    <div class="card-body">
      <div class="form-inline mb-3">
        <label><i class="fa fa-users-rectangle"></i> Filtrar por turma:</label>
        <select id="filtroDisciplinaTurma" onchange="carregarDisciplinas()">
          <option value="">— Selecione —</option>
          <?php foreach ($turmas as $t): ?>
            <option value="<?= $t['id'] ?>"><?= h($t['nome']) ?> (<?= $t['ano_letivo'] ?>)</option>
          <?php endforeach; ?>
        </select>
      </div>
      <div id="disciplinasLista">
        <div class="empty-state"><i class="fa fa-book fa-2x"></i><p>Selecione uma turma.</p></div>
      </div>
    </div>
  </div>
</div>

<!-- TAB: ALUNOS -->
<div class="tab-content" id="tab-alunos">
  <div class="card">
    <div class="card-header">
      <h3><i class="fa fa-user-graduate"></i> Alunos</h3>
      <button class="btn-primary btn-sm" onclick="abrirModal('modalAluno')">
        <i class="fa fa-plus"></i> Novo Aluno
      </button>
    </div>
    <div class="card-body">
      <div class="form-inline mb-3">
        <label><i class="fa fa-users-rectangle"></i> Filtrar por turma:</label>
        <select id="filtroAlunoTurma" onchange="carregarAlunos()">
          <option value="">— Selecione —</option>
          <?php foreach ($turmas as $t): ?>
            <option value="<?= $t['id'] ?>"><?= h($t['nome']) ?> (<?= $t['ano_letivo'] ?>)</option>
          <?php endforeach; ?>
        </select>
      </div>
      <div id="alunosLista">
        <div class="empty-state"><i class="fa fa-user-graduate fa-2x"></i><p>Selecione uma turma.</p></div>
      </div>
    </div>
  </div>
</div>

<!-- ════════════ MODAIS ════════════ -->

<!-- Modal Turma -->
<div class="modal-overlay" id="modalTurma">
  <div class="modal-box">
    <div class="modal-header">
      <h3 id="modalTurmaTitle"><i class="fa fa-users-rectangle"></i> Nova Turma</h3>
      <button class="modal-close" onclick="fecharModal('modalTurma')"><i class="fa fa-xmark"></i></button>
    </div>
    <form id="formTurma" onsubmit="submitTurma(event)">
      <input type="hidden" id="turmaId" name="id" value="">
      <input type="hidden" name="action" id="turmaAction" value="criar_turma">
      <div class="modal-body">
        <div class="form-group">
          <label>Nome da Turma *</label>
          <input type="text" name="nome" id="turmaNome" required placeholder="Ex: 10ª Classe A">
        </div>
        <div class="form-group">
          <label>Ano Letivo *</label>
          <input type="number" name="ano_letivo" id="turmaAno" required
                 value="<?= date('Y') ?>" min="2000" max="2100">
        </div>
        <div class="form-group">
          <label>Descrição</label>
          <input type="text" name="descricao" id="turmaDesc" placeholder="Opcional">
        </div>
      </div>
      <div class="modal-footer">
        <button type="button" class="btn-secondary" onclick="fecharModal('modalTurma')">Cancelar</button>
        <button type="submit" class="btn-primary"><i class="fa fa-save"></i> Salvar</button>
      </div>
    </form>
  </div>
</div>

<!-- Modal Disciplina -->
<div class="modal-overlay" id="modalDisciplina">
  <div class="modal-box">
    <div class="modal-header">
      <h3 id="modalDisciplinaTitle"><i class="fa fa-book"></i> Nova Disciplina</h3>
      <button class="modal-close" onclick="fecharModal('modalDisciplina')"><i class="fa fa-xmark"></i></button>
    </div>
    <form id="formDisciplina" onsubmit="submitDisciplina(event)">
      <input type="hidden" name="id" id="disciplinaId" value="">
      <input type="hidden" name="action" id="disciplinaAction" value="criar_disciplina">
      <div class="modal-body">
        <div class="form-group">
          <label>Turma *</label>
          <select name="turma_id" id="disciplinaTurmaId" required>
            <option value="">— Selecione —</option>
            <?php foreach ($turmas as $t): ?>
              <option value="<?= $t['id'] ?>"><?= h($t['nome']) ?> (<?= $t['ano_letivo'] ?>)</option>
            <?php endforeach; ?>
          </select>
        </div>
        <div class="form-group">
          <label>Nome da Disciplina *</label>
          <input type="text" name="nome" id="disciplinaNome" required placeholder="Ex: Matemática">
        </div>
        <div class="form-group">
          <label>Carga Horária (h)</label>
          <input type="number" name="carga_horaria" id="disciplinaCH" value="0" min="0">
        </div>
      </div>
      <div class="modal-footer">
        <button type="button" class="btn-secondary" onclick="fecharModal('modalDisciplina')">Cancelar</button>
        <button type="submit" class="btn-primary"><i class="fa fa-save"></i> Salvar</button>
      </div>
    </form>
  </div>
</div>

<!-- Modal Aluno -->
<div class="modal-overlay" id="modalAluno">
  <div class="modal-box">
    <div class="modal-header">
      <h3 id="modalAlunoTitle"><i class="fa fa-user-graduate"></i> Novo Aluno</h3>
      <button class="modal-close" onclick="fecharModal('modalAluno')"><i class="fa fa-xmark"></i></button>
    </div>
    <form id="formAluno" onsubmit="submitAluno(event)">
      <input type="hidden" name="id" id="alunoId" value="">
      <input type="hidden" name="action" id="alunoAction" value="criar_aluno">
      <div class="modal-body">
        <div class="form-group">
          <label>Turma *</label>
          <select name="turma_id" id="alunoTurmaId" required>
            <option value="">— Selecione —</option>
            <?php foreach ($turmas as $t): ?>
              <option value="<?= $t['id'] ?>"><?= h($t['nome']) ?> (<?= $t['ano_letivo'] ?>)</option>
            <?php endforeach; ?>
          </select>
        </div>
        <div class="form-group">
          <label>Nome Completo *</label>
          <input type="text" name="nome" id="alunoNome" required placeholder="Nome do aluno">
        </div>
        <div class="form-group">
          <label>Número / BI</label>
          <input type="text" name="numero" id="alunoNum" placeholder="Nº do aluno (opcional)">
        </div>
        <div class="form-group">
          <label>E-mail</label>
          <input type="email" name="email" id="alunoEmail" placeholder="Opcional">
        </div>
      </div>
      <div class="modal-footer">
        <button type="button" class="btn-secondary" onclick="fecharModal('modalAluno')">Cancelar</button>
        <button type="submit" class="btn-primary"><i class="fa fa-save"></i> Salvar</button>
      </div>
    </form>
  </div>
</div>

<!-- Modal Confirmação -->
<div class="modal-overlay" id="modalConfirm">
  <div class="modal-box modal-sm">
    <div class="modal-header">
      <h3><i class="fa fa-triangle-exclamation text-orange"></i> Confirmar</h3>
      <button class="modal-close" onclick="fecharModal('modalConfirm')"><i class="fa fa-xmark"></i></button>
    </div>
    <div class="modal-body">
      <p id="confirmMsg">Tem certeza?</p>
    </div>
    <div class="modal-footer">
      <button class="btn-secondary" onclick="fecharModal('modalConfirm')">Cancelar</button>
      <button class="btn-danger" id="confirmBtn">Confirmar</button>
    </div>
  </div>
</div>

<script>
const BASE = '/sistema_escolar/pages/configuracoes.php';

// ── TABS ──────────────────────────────────────────────────────
document.querySelectorAll('.tab-btn').forEach(btn => {
  btn.addEventListener('click', () => {
    document.querySelectorAll('.tab-btn').forEach(b => b.classList.remove('active'));
    document.querySelectorAll('.tab-content').forEach(c => c.classList.remove('active'));
    btn.classList.add('active');
    document.getElementById('tab-' + btn.dataset.tab).classList.add('active');
  });
});

// ── MODAIS ────────────────────────────────────────────────────
function abrirModal(id) { document.getElementById(id).classList.add('show'); }
function fecharModal(id) { document.getElementById(id).classList.remove('show'); }
document.querySelectorAll('.modal-overlay').forEach(m => {
  m.addEventListener('click', e => { if (e.target === m) m.classList.remove('show'); });
});

// ── TURMAS ────────────────────────────────────────────────────
function editarTurma(id, nome, desc, ano) {
  document.getElementById('turmaId').value     = id;
  document.getElementById('turmaNome').value   = nome;
  document.getElementById('turmaDesc').value   = desc;
  document.getElementById('turmaAno').value    = ano;
  document.getElementById('turmaAction').value = 'editar_turma';
  document.getElementById('modalTurmaTitle').innerHTML = '<i class="fa fa-pen"></i> Editar Turma';
  abrirModal('modalTurma');
}

async function submitTurma(e) {
  e.preventDefault();
  const res = await postForm(BASE, new FormData(e.target));
  if (res.ok) { fecharModal('modalTurma'); toast(res.msg, 'success'); setTimeout(()=>location.reload(),800); }
  else toast(res.msg, 'error');
}

function apagarTurma(id, nome) {
  document.getElementById('confirmMsg').textContent = `Apagar a turma "${nome}" e todos os dados associados?`;
  document.getElementById('confirmBtn').onclick = async () => {
    const fd = new FormData(); fd.append('action','apagar_turma'); fd.append('id',id);
    const res = await postForm(BASE, fd);
    fecharModal('modalConfirm');
    if (res.ok) { toast(res.msg,'success'); setTimeout(()=>location.reload(),800); }
    else toast(res.msg,'error');
  };
  abrirModal('modalConfirm');
}

// ── DISCIPLINAS ───────────────────────────────────────────────
async function carregarDisciplinas() {
  const tid = document.getElementById('filtroDisciplinaTurma').value;
  const el  = document.getElementById('disciplinasLista');
  if (!tid) { el.innerHTML = '<div class="empty-state"><i class="fa fa-book fa-2x"></i><p>Selecione uma turma.</p></div>'; return; }
  const r = await fetch(`${BASE}?fetch=disciplinas&turma_id=${tid}`);
  const data = await r.json();
  if (!data.data.length) { el.innerHTML='<div class="empty-state"><i class="fa fa-book fa-2x"></i><p>Nenhuma disciplina.</p></div>'; return; }
  el.innerHTML = `<div class="table-responsive"><table class="data-table"><thead><tr><th>#</th><th>Disciplina</th><th>C.H.</th><th>Ações</th></tr></thead><tbody>
    ${data.data.map((d,i)=>`<tr>
      <td>${i+1}</td><td>${esc(d.nome)}</td><td>${d.carga_horaria}h</td>
      <td class="actions">
        <button class="btn-icon btn-edit" title="Editar" onclick="editarDisciplina(${d.id},'${esc(d.nome)}',${d.carga_horaria},${tid})"><i class="fa fa-pen"></i></button>
        <button class="btn-icon btn-delete" title="Apagar" onclick="apagarDisciplina(${d.id},'${esc(d.nome)}')"><i class="fa fa-trash"></i></button>
      </td></tr>`).join('')}
  </tbody></table></div>`;
}

function editarDisciplina(id,nome,ch,tid) {
  document.getElementById('disciplinaId').value        = id;
  document.getElementById('disciplinaNome').value      = nome;
  document.getElementById('disciplinaCH').value        = ch;
  document.getElementById('disciplinaTurmaId').value   = tid;
  document.getElementById('disciplinaAction').value    = 'editar_disciplina';
  document.getElementById('modalDisciplinaTitle').innerHTML='<i class="fa fa-pen"></i> Editar Disciplina';
  abrirModal('modalDisciplina');
}

async function submitDisciplina(e) {
  e.preventDefault();
  const tid = document.getElementById('filtroDisciplinaTurma').value || document.getElementById('disciplinaTurmaId').value;
  document.getElementById('disciplinaTurmaId').value = tid;
  const res = await postForm(BASE, new FormData(e.target));
  if (res.ok) { fecharModal('modalDisciplina'); toast(res.msg,'success'); carregarDisciplinas(); }
  else toast(res.msg,'error');
}

function apagarDisciplina(id,nome) {
  document.getElementById('confirmMsg').textContent = `Apagar a disciplina "${nome}"?`;
  document.getElementById('confirmBtn').onclick = async () => {
    const fd = new FormData(); fd.append('action','apagar_disciplina'); fd.append('id',id);
    const res = await postForm(BASE,fd);
    fecharModal('modalConfirm');
    if (res.ok) { toast(res.msg,'success'); carregarDisciplinas(); }
    else toast(res.msg,'error');
  };
  abrirModal('modalConfirm');
}

// ── ALUNOS ────────────────────────────────────────────────────
async function carregarAlunos() {
  const tid = document.getElementById('filtroAlunoTurma').value;
  const el  = document.getElementById('alunosLista');
  if (!tid) { el.innerHTML='<div class="empty-state"><i class="fa fa-user-graduate fa-2x"></i><p>Selecione uma turma.</p></div>'; return; }
  const r = await fetch(`${BASE}?fetch=alunos&turma_id=${tid}`);
  const data = await r.json();
  if (!data.data.length) { el.innerHTML='<div class="empty-state"><i class="fa fa-user-graduate fa-2x"></i><p>Nenhum aluno.</p></div>'; return; }
  el.innerHTML = `<div class="table-responsive"><table class="data-table"><thead><tr><th>#</th><th>Nome</th><th>Número</th><th>E-mail</th><th>Ações</th></tr></thead><tbody>
    ${data.data.map((a,i)=>`<tr>
      <td>${i+1}</td><td>${esc(a.nome)}</td><td>${esc(a.numero||'—')}</td><td>${esc(a.email||'—')}</td>
      <td class="actions">
        <button class="btn-icon btn-edit" title="Editar" onclick="editarAluno(${a.id},'${esc(a.nome)}','${esc(a.numero||'')}','${esc(a.email||'')}',${tid})"><i class="fa fa-pen"></i></button>
        <button class="btn-icon btn-delete" title="Apagar" onclick="apagarAluno(${a.id},'${esc(a.nome)}')"><i class="fa fa-trash"></i></button>
      </td></tr>`).join('')}
  </tbody></table></div>`;
}

function editarAluno(id,nome,num,email,tid) {
  document.getElementById('alunoId').value       = id;
  document.getElementById('alunoNome').value     = nome;
  document.getElementById('alunoNum').value      = num;
  document.getElementById('alunoEmail').value    = email;
  document.getElementById('alunoTurmaId').value  = tid;
  document.getElementById('alunoAction').value   = 'editar_aluno';
  document.getElementById('modalAlunoTitle').innerHTML='<i class="fa fa-pen"></i> Editar Aluno';
  abrirModal('modalAluno');
}

async function submitAluno(e) {
  e.preventDefault();
  const tid = document.getElementById('filtroAlunoTurma').value || document.getElementById('alunoTurmaId').value;
  document.getElementById('alunoTurmaId').value = tid;
  const res = await postForm(BASE, new FormData(e.target));
  if (res.ok) { fecharModal('modalAluno'); toast(res.msg,'success'); carregarAlunos(); }
  else toast(res.msg,'error');
}

function apagarAluno(id,nome) {
  document.getElementById('confirmMsg').textContent = `Apagar o aluno "${nome}"?`;
  document.getElementById('confirmBtn').onclick = async () => {
    const fd=new FormData(); fd.append('action','apagar_aluno'); fd.append('id',id);
    const res=await postForm(BASE,fd);
    fecharModal('modalConfirm');
    if (res.ok) { toast(res.msg,'success'); carregarAlunos(); }
    else toast(res.msg,'error');
  };
  abrirModal('modalConfirm');
}

// ── HELPERS ───────────────────────────────────────────────────
async function postForm(url, fd) {
  const r = await fetch(url, { method:'POST', body:fd });
  return r.json();
}
function esc(str) { return String(str).replace(/[&<>"']/g, c => ({'&':'&amp;','<':'&lt;','>':'&gt;','"':'&quot;',"'":'&#39;'}[c])); }
</script>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>
