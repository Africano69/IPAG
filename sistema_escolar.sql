-- ============================================================
--  SISTEMA DE LIVRO DE SUMÁRIO E CONTROLE DE PRESENÇA
--  Script de criação do banco de dados MySQL
-- ============================================================

CREATE DATABASE IF NOT EXISTS sistema_escolar
  CHARACTER SET utf8mb4
  COLLATE utf8mb4_unicode_ci;

USE sistema_escolar;

-- ------------------------------------------------------------
-- Tabela: professores
-- ------------------------------------------------------------
CREATE TABLE IF NOT EXISTS professores (
  id          INT AUTO_INCREMENT PRIMARY KEY,
  nome        VARCHAR(150) NOT NULL,
  email       VARCHAR(150) NOT NULL UNIQUE,
  senha       VARCHAR(255) NOT NULL,
  criado_em   DATETIME DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB;

-- ------------------------------------------------------------
-- Tabela: turmas
-- ------------------------------------------------------------
CREATE TABLE IF NOT EXISTS turmas (
  id             INT AUTO_INCREMENT PRIMARY KEY,
  professor_id   INT NOT NULL,
  nome           VARCHAR(100) NOT NULL,
  descricao      VARCHAR(255),
  ano_letivo     YEAR NOT NULL,
  criado_em      DATETIME DEFAULT CURRENT_TIMESTAMP,
  FOREIGN KEY (professor_id) REFERENCES professores(id) ON DELETE CASCADE
) ENGINE=InnoDB;

-- ------------------------------------------------------------
-- Tabela: disciplinas
-- ------------------------------------------------------------
CREATE TABLE IF NOT EXISTS disciplinas (
  id           INT AUTO_INCREMENT PRIMARY KEY,
  turma_id     INT NOT NULL,
  nome         VARCHAR(100) NOT NULL,
  carga_horaria INT DEFAULT 0,
  criado_em    DATETIME DEFAULT CURRENT_TIMESTAMP,
  FOREIGN KEY (turma_id) REFERENCES turmas(id) ON DELETE CASCADE
) ENGINE=InnoDB;

-- ------------------------------------------------------------
-- Tabela: alunos
-- ------------------------------------------------------------
CREATE TABLE IF NOT EXISTS alunos (
  id           INT AUTO_INCREMENT PRIMARY KEY,
  turma_id     INT NOT NULL,
  nome         VARCHAR(150) NOT NULL,
  numero       VARCHAR(20),
  email        VARCHAR(150),
  criado_em    DATETIME DEFAULT CURRENT_TIMESTAMP,
  FOREIGN KEY (turma_id) REFERENCES turmas(id) ON DELETE CASCADE
) ENGINE=InnoDB;

-- ------------------------------------------------------------
-- Tabela: sumarios
-- ------------------------------------------------------------
CREATE TABLE IF NOT EXISTS sumarios (
  id             INT AUTO_INCREMENT PRIMARY KEY,
  professor_id   INT NOT NULL,
  turma_id       INT NOT NULL,
  disciplina_id  INT NOT NULL,
  data_hora      DATETIME NOT NULL,
  conteudo       TEXT NOT NULL,
  numero_aula    INT DEFAULT 1,
  criado_em      DATETIME DEFAULT CURRENT_TIMESTAMP,
  FOREIGN KEY (professor_id)  REFERENCES professores(id) ON DELETE CASCADE,
  FOREIGN KEY (turma_id)      REFERENCES turmas(id)      ON DELETE CASCADE,
  FOREIGN KEY (disciplina_id) REFERENCES disciplinas(id) ON DELETE CASCADE
) ENGINE=InnoDB;

-- ------------------------------------------------------------
-- Tabela: presencas
-- ------------------------------------------------------------
CREATE TABLE IF NOT EXISTS presencas (
  id          INT AUTO_INCREMENT PRIMARY KEY,
  sumario_id  INT NOT NULL,
  aluno_id    INT NOT NULL,
  presente    TINYINT(1) DEFAULT 0,
  criado_em   DATETIME DEFAULT CURRENT_TIMESTAMP,
  UNIQUE KEY uk_presenca (sumario_id, aluno_id),
  FOREIGN KEY (sumario_id) REFERENCES sumarios(id) ON DELETE CASCADE,
  FOREIGN KEY (aluno_id)   REFERENCES alunos(id)   ON DELETE CASCADE
) ENGINE=InnoDB;
