<?php
/**
 * MIGRAÇÃO 002: Criar tabelas do EAD no banco principal
 * Data: 29/10/2025
 */

require_once __DIR__ . '/../config/config.php';

$conn = getDBConnection();

try {
    echo "Iniciando migração 002...\n";
    
    // 1. Criar tabela ead_aulas
    echo "1. Criando tabela 'ead_aulas'...\n";
    $sql_aulas = "
    CREATE TABLE IF NOT EXISTS ead_aulas (
        id INT(11) PRIMARY KEY AUTO_INCREMENT,
        curso_id BIGINT(20) NOT NULL,
        titulo VARCHAR(255) NOT NULL,
        descricao TEXT,
        ordem INT(11),
        duracao_minutos INT(11),
        ativa TINYINT(1) DEFAULT 1,
        criado_em TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        atualizado_em TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
        FOREIGN KEY (curso_id) REFERENCES cursos(id) ON DELETE CASCADE,
        KEY idx_curso (curso_id),
        KEY idx_ordem (ordem)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
    ";
    $conn->query($sql_aulas);
    echo "   ✓ Tabela 'ead_aulas' criada\n";
    
    // 2. Criar tabela ead_conteudo_aulas
    echo "2. Criando tabela 'ead_conteudo_aulas'...\n";
    $sql_conteudo = "
    CREATE TABLE IF NOT EXISTS ead_conteudo_aulas (
        id INT(11) PRIMARY KEY AUTO_INCREMENT,
        aula_id INT(11) NOT NULL,
        tipo ENUM('video','material','exercicio','texto') NOT NULL,
        titulo VARCHAR(255) NOT NULL,
        descricao TEXT,
        url_arquivo VARCHAR(255),
        duracao_minutos INT(11),
        ordem INT(11),
        criado_em TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        FOREIGN KEY (aula_id) REFERENCES ead_aulas(id) ON DELETE CASCADE,
        KEY idx_aula (aula_id)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
    ";
    $conn->query($sql_conteudo);
    echo "   ✓ Tabela 'ead_conteudo_aulas' criada\n";
    
    // 3. Criar tabela ead_progresso_aluno
    echo "3. Criando tabela 'ead_progresso_aluno'...\n";
    $sql_progresso = "
    CREATE TABLE IF NOT EXISTS ead_progresso_aluno (
        id INT(11) PRIMARY KEY AUTO_INCREMENT,
        inscricao_id BIGINT(20) NOT NULL,
        aula_id INT(11) NOT NULL,
        conteudo_id INT(11),
        visualizado TINYINT(1) DEFAULT 0,
        tempo_gasto_minutos INT(11) DEFAULT 0,
        data_visualizacao DATETIME,
        data_conclusao DATETIME,
        FOREIGN KEY (inscricao_id) REFERENCES inscricoes_alunos(id) ON DELETE CASCADE,
        FOREIGN KEY (aula_id) REFERENCES ead_aulas(id) ON DELETE CASCADE,
        FOREIGN KEY (conteudo_id) REFERENCES ead_conteudo_aulas(id) ON DELETE SET NULL,
        KEY idx_inscricao (inscricao_id),
        KEY idx_aula (aula_id)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
    ";
    $conn->query($sql_progresso);
    echo "   ✓ Tabela 'ead_progresso_aluno' criada\n";
    
    // 4. Criar tabela ead_exercicios
    echo "4. Criando tabela 'ead_exercicios'...\n";
    $sql_exercicios = "
    CREATE TABLE IF NOT EXISTS ead_exercicios (
        id INT(11) PRIMARY KEY AUTO_INCREMENT,
        aula_id INT(11) NOT NULL,
        titulo VARCHAR(255) NOT NULL,
        descricao TEXT,
        tipo ENUM('multipla_escolha','dissertativa','pratica') DEFAULT 'multipla_escolha',
        pontuacao_maxima INT(11) DEFAULT 10,
        criado_em TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        FOREIGN KEY (aula_id) REFERENCES ead_aulas(id) ON DELETE CASCADE,
        KEY idx_aula (aula_id)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
    ";
    $conn->query($sql_exercicios);
    echo "   ✓ Tabela 'ead_exercicios' criada\n";
    
    // 5. Criar tabela ead_respostas_exercicios
    echo "5. Criando tabela 'ead_respostas_exercicios'...\n";
    $sql_respostas = "
    CREATE TABLE IF NOT EXISTS ead_respostas_exercicios (
        id INT(11) PRIMARY KEY AUTO_INCREMENT,
        exercicio_id INT(11) NOT NULL,
        aluno_id BIGINT(20) NOT NULL,
        resposta TEXT,
        pontuacao INT(11),
        data_resposta TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        data_correcao DATETIME,
        FOREIGN KEY (exercicio_id) REFERENCES ead_exercicios(id) ON DELETE CASCADE,
        FOREIGN KEY (aluno_id) REFERENCES alunos(id) ON DELETE CASCADE,
        KEY idx_exercicio (exercicio_id),
        KEY idx_aluno (aluno_id)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
    ";
    $conn->query($sql_respostas);
    echo "   ✓ Tabela 'ead_respostas_exercicios' criada\n";
    
    echo "\n✅ Migração 002 concluída com sucesso!\n";
    
} catch (Exception $e) {
    echo "\n❌ Erro na migração: " . $e->getMessage() . "\n";
    exit(1);
}

$conn->close();
?>

