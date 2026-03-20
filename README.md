#  https://africano69.github.io/IPAG/
📚 SumárioPRO — Sistema de Livro de Sumário e Controle de Presença

Sistema web completo para gestão de sumários e presenças de turmas escolares.

---

📋 Requisitos

- PHP >= 7.4
- MySQL >= 5.7 (ou MariaDB >= 10.3)
- Servidor web: Apache (com mod_rewrite) ou Nginx
- Recomendado: XAMPP, WAMP, LAMP ou Laragon

---

🚀 Instalação

1. Copiar os ficheiros
Coloque a pasta `sistema_escolar/` dentro do diretório raiz do servidor web:
- **XAMPP**: `C:\xampp\htdocs\sistema_escolar\`
- **Linux/Apache**: `/var/www/html/sistema_escolar/`

2. Criar o banco de dados
Aceda ao phpMyAdmin ou use o terminal MySQL e execute:
```sql
SOURCE /caminho/para/sistema_escolar/database.sql;
```
Ou importe o ficheiro `database.sql` via phpMyAdmin.

3. Configurar a conexão
Edite o ficheiro `config.php` e ajuste as credenciais:
```php
define('DB_HOST', 'localhost');
define('DB_USER', 'root');       // seu utilizador MySQL
define('DB_PASS', '');           // sua senha MySQL
define('DB_NAME', 'sistema_escolar');
```

4. Acessar o sistema
Abra o navegador e aceda:
```
http://localhost/sistema_escolar/
```

---

🗂️ Estrutura de Ficheiros

```
sistema_escolar/
├── config.php              ← Configuração do banco de dados
├── database.sql            ← Script SQL (criar o BD aqui)
├── index.php               ← Redireciona para login/dashboard
├── login.php               ← Página de login
├── register.php            ← Página de cadastro de professor
├── logout.php              ← Encerra a sessão
├── dashboard.php           ← Painel principal com estatísticas
├── assets/
│   ├── css/
│   │   ├── auth.css        ← Estilos login/cadastro
│   │   └── main.css        ← Estilos sistema principal
│   └── js/
│       └── main.js         ← JavaScript global (sidebar, toasts)
├── includes/
│   ├── header.php          ← Cabeçalho HTML
│   ├── sidebar.php         ← Barra lateral + topbar
│   └── footer.php          ← Rodapé HTML
└── pages/
    ├── configuracoes.php   ← Gestão de turmas, disciplinas e alunos
    ├── sumario.php         ← Registo de sumários
    ├── presenca.php        ← Marcação de presenças
    └── relatorio.php       ← Relatórios e exportação Excel
```

---

🎯 Funcionalidades

🔐 Autenticação
- Cadastro de professores (nome, email, senha com hash bcrypt)
- Login seguro com sessões PHP
- Logout

📊 Dashboard
- Estatísticas: nº de turmas, disciplinas, alunos, sumários
- Taxa de presença dos últimos 30 dias
- Últimos sumários registados
- Atalhos rápidos para todas as secções

⚙️ Configurações
- **Turmas**: criar, editar e apagar turmas por ano letivo
- **Disciplinas**: associar disciplinas a cada turma com carga horária
- **Alunos**: gerir alunos por turma (nome, nº, email)

📖 Registo de Sumário
- Filtrar por turma e data
- Registar sumário: turma, disciplina, data/hora, nº de aula, conteúdo
- Editar e apagar sumários
- Visualizar conteúdo completo

✅ Marcar Presença
- Selecionar turma → disciplina → sumário
- Lista visual de alunos com botões Presente/Ausente
- Marcar todos presentes ou todos ausentes
- Pesquisa rápida de aluno
- Estatísticas em tempo real (presentes/ausentes/total)
- Guardar e actualizar presenças a qualquer momento

📈 Relatórios (Exportação Excel)
- **Sumários**: todos os conteúdos leccionados com filtros
- **Lista de presenças**: detalhada por aluno/aula
- **Resumo de presenças**: taxa de presença por aluno/disciplina (com código de cores)
- Filtros: turma, disciplina, data inicial e data final
- Pré-visualização antes de exportar

---

🎨 Design
- Interface moderna e responsiva
- Sidebar recolhível (desktop) / overlay (mobile)
- Notificações toast
- Modais de confirmação
- Paleta profissional azul-marinho

---

🔒 Segurança
- Senhas com `password_hash()` (bcrypt)
- Consultas SQL com PDO prepared statements (prevenção de SQL Injection)
- Verificação de sessão em todas as páginas protegidas
- Validação de propriedade (professor só vê os seus dados)
- Escape de HTML com `htmlspecialchars()`

---

📞 Suporte
Desenvolvido com PHP + MySQL + HTML + CSS + JavaScript puro.
Compatível com PHP 7.4+ e MySQL 5.7+.
