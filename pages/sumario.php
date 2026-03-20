<?php
// pages/sumario.php
require_once __DIR__ . '/../config.php';
verificarAutenticacao();

$prof = professorLogado();
$db   = getDB();
$pid  = $prof['id'];

// ─── AJAX ────────────────────────────────────────────────────
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action'])) {
    $action = $_POST['action'];

    if ($action === 'get_disciplinas') {
        $tid = $_POST['turma_id'] ?? 0;
        $st  = $db->prepare('SELECT d.id,d.nome FROM disciplinas d JOIN turmas t ON d.turma_id=t.id WHERE d.turma_id=? AND t.professor_id=? ORDER BY d.nome');
        $st->execute([$tid, $pid]);
        jsonResponse(['data' => $st->fetchAll()]);
    }

    if ($action === 'criar_sumario') {
        $tid  = trim($_POST['turma_id']      ?? '');
        $did  = trim($_POST['disciplina_id'] ?? '');
        $dh   = trim($_POST['data_hora']     ?? '');
        $cont = trim($_POST['conteudo']      ?? '');
        $naul = (int)($_POST['numero_aula']  ?? 1);
        if (!$tid || !$did || !$dh || !$cont)
            jsonResponse(['ok'=>false,'msg'=>'Preencha todos os campos obrigatórios.']);
        // valida posse
        $chk = $db->prepare('SELECT id FROM turmas WHERE id=? AND professor_id=?');
        $chk->execute([$tid,$pid]);
        if (!$chk->fetch()) jsonResponse(['ok'=>false,'msg'=>'Turma inválida.']);
        $st = $db->prepare('INSERT INTO sumarios (professor_id,turma_id,disciplina_id,data_hora,conteudo,numero_aula) VALUES (?,?,?,?,?,?)');
        $st->execute([$pid,$tid,$did,$dh,$cont,$naul]);
        jsonResponse(['ok'=>true,'id'=>$db->lastInsertId(),'msg'=>'Sumário registado com sucesso!']);
    }

    if ($action === 'editar_sumario') {
        $id   = $_POST['id'];
        $did  = $_POST['disciplina_id'];
        $dh   = $_POST['data_hora'];
        $cont = trim($_POST['conteudo'] ?? '');
        $naul = (int)($_POST['numero_aula'] ?? 1);
        $st = $db->prepare('UPDATE sumarios SET disciplina_id=?,data_hora=?,conteudo=?,numero_aula=? WHERE id=? AND professor_id=?');
        $st->execute([$did,$dh,$cont,$naul,$id,$pid]);
        jsonResponse(['ok'=>true,'msg'=>'Sumário actualizado!']);
    }

    if ($action === 'apagar_sumario') {
        $st = $db->prepare('DELETE FROM sumarios WHERE id=? AND professor_id=?');
        $st->execute([$_POST['id'],$pid]);
        jsonResponse(['ok'=>true,'msg'=>'Sumário removido!']);
    }

    jsonResponse(['ok'=>false,'msg'=>'Ação desconhecida.'],400);
}

// ─── Listar sumários do professor com filtros ─────────────────
$filtroTurma = $_GET['turma_id'] ?? '';
$filtroData  = $_GET['data']     ?? '';

$sql = 'SELECT s.id,s.data_hora,s.conteudo,s.numero_aula,
               t.nome AS turma,t.id AS turma_id,
               d.nome AS disciplina,d.id AS disciplina_id
        FROM sumarios s
        JOIN turmas t      ON s.turma_id=t.id
        JOIN disciplinas d ON s.disciplina_id=d.id
        WHERE s.professor_id=?';
$params = [$pid];
if ($filtroTurma) { $sql.=' AND s.turma_id=?'; $params[]=$filtroTurma; }
if ($filtroData)  { $sql.=' AND DATE(s.data_hora)=?'; $params[]=$filtroData; }
$sql .= ' ORDER BY s.data_hora DESC';

$stS = $db->prepare($sql);
$stS->execute($params);
$sumarios = $stS->fetchAll();

$stTurmas = $db->prepare('SELECT * FROM turmas WHERE professor_id=? ORDER BY nome');
$stTurmas->execute([$pid]);
$turmas = $stTurmas->fetchAll();

$paginaTitulo = 'Registo de Sumário';
$paginaAtiva  = 'sumario';
require_once __DIR__ . '/../includes/header.php';
require_once __DIR__ . '/../includes/sidebar.php';
?>

<div class="page-header">
  <h2><i class="fa fa-book"></i> Registo de Sumário</h2>
  <button class="btn-primary" onclick="abrirModal('modalSumario')">
    <i class="fa fa-plus"></i> Novo Sumário
  </button>
</div>

<!-- FILTROS -->
<div class="card mb-3">
  <div class="card-body">
    <form method="GET" class="filter-form">
      <div class="form-group">
        <label><i class="fa fa-users-rectangle"></i> Turma</label>
        <select name="turma_id">
          <option value="">Todas</option>
          <?php foreach ($turmas as $t): ?>
            <option value="<?= $t['id'] ?>" <?= $filtroTurma==$t['id']?'selected':'' ?>>
              <?= h($t['nome']) ?>
            </option>
          <?php endforeach; ?>
        </select>
      </div>
      <div class="form-group">
        <label><i class="fa fa-calendar"></i> Data</label>
        <input type="date" name="data" value="<?= h($filtroData) ?>">
      </div>
      <button type="submit" class="btn-primary"><i class="fa fa-magnifying-glass"></i> Filtrar</button>
      <a href="sumario.php" class="btn-secondary">Limpar</a>
    </form>
  </div>
</div>

<!-- LISTA DE SUMÁRIOS -->
<div class="card">
  <div class="card-header">
    <h3><i class="fa fa-list"></i> Sumários Registados <span class="badge badge-blue"><?= count($sumarios) ?></span></h3>
  </div>
  <div class="card-body">
    <?php if (empty($sumarios)): ?>
      <div class="empty-state">
        <i class="fa fa-book-open fa-2x"></i>
        <p>Nenhum sumário encontrado.</p>
        <button class="btn-primary" onclick="abrirModal('modalSumario')">Registar Sumário</button>
      </div>
    <?php else: ?>
      <div class="table-responsive">
        <table class="data-table" id="tabelaSumarios">
          <thead>
            <tr><th>#</th><th>Data / Hora</th><th>Turma</th><th>Disciplina</th><th>Aula Nº</th><th>Conteúdo</th><th>Ações</th></tr>
          </thead>
          <tbody>
            <?php foreach ($sumarios as $i => $s): ?>
              <tr>
                <td><?= $i+1 ?></td>
                <td><span class="badge badge-blue"><?= date('d/m/Y', strtotime($s['data_hora'])) ?></span><br>
                    <small><?= date('H:i', strtotime($s['data_hora'])) ?></small></td>
                <td><?= h($s['turma']) ?></td>
                <td><?= h($s['disciplina']) ?></td>
                <td><span class="badge badge-purple">Aula <?= $s['numero_aula'] ?></span></td>
                <td class="text-truncate" style="max-width:250px" title="<?= h($s['conteudo']) ?>">
                  <?= h(mb_substr($s['conteudo'],0,80)).(mb_strlen($s['conteudo'])>80?'…':'') ?>
                </td>
                <td class="actions">
                  <button class="btn-icon btn-view" title="Ver conteúdo"
                    onclick="verSumario(<?= htmlspecialchars(json_encode($s), ENT_QUOTES) ?>)">
                    <i class="fa fa-eye"></i>
                  </button>
                  <button class="btn-icon btn-edit" title="Editar"
                    onclick="editarSumario(<?= htmlspecialchars(json_encode($s), ENT_QUOTES) ?>)">
                    <i class="fa fa-pen"></i>
                  </button>
                  <button class="btn-icon btn-delete" title="Apagar"
                    onclick="apagarSumario(<?= $s['id'] ?>, '<?= date('d/m/Y',strtotime($s['data_hora'])) ?>')">
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

<!-- ════════ MODAIS ════════ -->

<!-- Modal Criar/Editar Sumário -->
<div class="modal-overlay" id="modalSumario">
  <div class="modal-box modal-lg">
    <div class="modal-header">
      <h3 id="modalSumarioTitle"><i class="fa fa-book"></i> Novo Sumário</h3>
      <button class="modal-close" onclick="fecharModal('modalSumario')"><i class="fa fa-xmark"></i></button>
    </div>
    <form id="formSumario" onsubmit="submitSumario(event)">
      <input type="hidden" name="id" id="sumarioId">
      <input type="hidden" name="action" id="sumarioAction" value="criar_sumario">
      <div class="modal-body">
        <div class="form-row">
          <div class="form-group">
            <label>Turma *</label>
            <select name="turma_id" id="sumarioTurma" required onchange="getDisciplinas(this.value)">
              <option value="">— Selecione —</option>
              <?php foreach ($turmas as $t): ?>
                <option value="<?= $t['id'] ?>"><?= h($t['nome']) ?> (<?= $t['ano_letivo'] ?>)</option>
              <?php endforeach; ?>
            </select>
          </div>
          <div class="form-group">
            <label>Disciplina *</label>
            <select name="disciplina_id" id="sumarioDisciplina" required>
              <option value="">— Selecione a turma primeiro —</option>
            </select>
          </div>
        </div>
        <div class="form-row">
          <div class="form-group">
            <label>Data e Hora *</label>
            <input type="datetime-local" name="data_hora" id="sumarioDataHora" required>
          </div>
          <div class="form-group">
            <label>Número da Aula</label>
            <input type="number" name="numero_aula" id="sumarioNumAula" value="1" min="1">
          </div>
        </div>
        <div class="form-group">
          <label>Conteúdo da Aula *</label>
          <textarea name="conteudo" id="sumarioConteudo" rows="5"
                    placeholder="Descreva o conteúdo leccionado nesta aula..." required></textarea>
        </div>
      </div>
      <div class="modal-footer">
        <button type="button" class="btn-secondary" onclick="fecharModal('modalSumario')">Cancelar</button>
        <button type="submit" class="btn-primary"><i class="fa fa-save"></i> Guardar</button>
      </div>
    </form>
  </div>
</div>

<!-- Modal Ver Sumário -->
<div class="modal-overlay" id="modalVerSumario">
  <div class="modal-box modal-lg">
    <div class="modal-header">
      <h3><i class="fa fa-eye"></i> Detalhes do Sumário</h3>
      <button class="modal-close" onclick="fecharModal('modalVerSumario')"><i class="fa fa-xmark"></i></button>
    </div>
    <div class="modal-body" id="verSumarioBody"></div>
    <div class="modal-footer">
      <button class="btn-secondary" onclick="fecharModal('modalVerSumario')">Fechar</button>
    </div>
  </div>
</div>

<!-- Modal Confirmação -->
<div class="modal-overlay" id="modalConfirm">
  <div class="modal-box modal-sm">
    <div class="modal-header">
      <h3><i class="fa fa-triangle-exclamation text-orange"></i> Confirmar</h3>
      <button class="modal-close" onclick="fecharModal('modalConfirm')"><i class="fa fa-xmark"></i></button>
    </div>
    <div class="modal-body"><p id="confirmMsg">Tem certeza?</p></div>
    <div class="modal-footer">
      <button class="btn-secondary" onclick="fecharModal('modalConfirm')">Cancelar</button>
      <button class="btn-danger" id="confirmBtn">Confirmar</button>
    </div>
  </div>
</div>

<script>
const BASE = '/sistema_escolar/pages/sumario.php';

function abrirModal(id) { document.getElementById(id).classList.add('show'); }
function fecharModal(id) { document.getElementById(id).classList.remove('show'); }
document.querySelectorAll('.modal-overlay').forEach(m => {
  m.addEventListener('click', e => { if(e.target===m) m.classList.remove('show'); });
});

// Abrir modal com datetime local atual
document.getElementById('modalSumario').addEventListener('transitionend', () => {});
function resetForm() {
  document.getElementById('sumarioId').value = '';
  document.getElementById('sumarioAction').value = 'criar_sumario';
  document.getElementById('modalSumarioTitle').innerHTML = '<i class="fa fa-book"></i> Novo Sumário';
  document.getElementById('formSumario').reset();
  const now = new Date(); now.setSeconds(0,0);
  document.getElementById('sumarioDataHora').value = now.toISOString().slice(0,16);
}

document.querySelector('[onclick="abrirModal(\'modalSumario\')"]')?.addEventListener('click', resetForm);

async function getDisciplinas(tid, selId) {
  const sel = document.getElementById('sumarioDisciplina');
  sel.innerHTML = '<option value="">Carregando...</option>';
  if (!tid) { sel.innerHTML = '<option value="">— Selecione a turma —</option>'; return; }
  const fd = new FormData(); fd.append('action','get_disciplinas'); fd.append('turma_id',tid);
  const r = await fetch(BASE,{method:'POST',body:fd});
  const d = await r.json();
  if (!d.data.length) { sel.innerHTML='<option value="">Sem disciplinas nesta turma</option>'; return; }
  sel.innerHTML = '<option value="">— Selecione —</option>' +
    d.data.map(x=>`<option value="${x.id}" ${selId==x.id?'selected':''}>${esc(x.nome)}</option>`).join('');
}

async function submitSumario(e) {
  e.preventDefault();
  const res = await postForm(BASE, new FormData(e.target));
  if (res.ok) { fecharModal('modalSumario'); toast(res.msg,'success'); setTimeout(()=>location.reload(),800); }
  else toast(res.msg,'error');
}

function editarSumario(s) {
  document.getElementById('sumarioId').value       = s.id;
  document.getElementById('sumarioAction').value   = 'editar_sumario';
  document.getElementById('sumarioNumAula').value  = s.numero_aula;
  document.getElementById('sumarioConteudo').value = s.conteudo;
  document.getElementById('sumarioDataHora').value = s.data_hora.replace(' ','T').slice(0,16);
  document.getElementById('sumarioTurma').value    = s.turma_id;
  document.getElementById('modalSumarioTitle').innerHTML = '<i class="fa fa-pen"></i> Editar Sumário';
  getDisciplinas(s.turma_id, s.disciplina_id);
  abrirModal('modalSumario');
}

function verSumario(s) {
  document.getElementById('verSumarioBody').innerHTML = `
    <dl class="detail-list">
      <dt>Turma</dt><dd>${esc(s.turma)}</dd>
      <dt>Disciplina</dt><dd>${esc(s.disciplina)}</dd>
      <dt>Data / Hora</dt><dd>${new Date(s.data_hora).toLocaleString('pt-AO')}</dd>
      <dt>Nº da Aula</dt><dd>${s.numero_aula}</dd>
      <dt>Conteúdo</dt><dd class="pre-wrap">${esc(s.conteudo)}</dd>
    </dl>`;
  abrirModal('modalVerSumario');
}

function apagarSumario(id, data) {
  document.getElementById('confirmMsg').textContent = `Apagar o sumário de ${data}?`;
  document.getElementById('confirmBtn').onclick = async () => {
    const fd = new FormData(); fd.append('action','apagar_sumario'); fd.append('id',id);
    const res = await postForm(BASE,fd);
    fecharModal('modalConfirm');
    if (res.ok) { toast(res.msg,'success'); setTimeout(()=>location.reload(),800); }
    else toast(res.msg,'error');
  };
  abrirModal('modalConfirm');
}

async function postForm(url,fd){ const r=await fetch(url,{method:'POST',body:fd}); return r.json(); }
function esc(s){return String(s).replace(/[&<>"']/g,c=>({'&':'&amp;','<':'&lt;','>':'&gt;','"':'&quot;',"'":'&#39;'}[c]));}
</script>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>
