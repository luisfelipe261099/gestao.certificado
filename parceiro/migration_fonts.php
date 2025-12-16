<?php
// migration_fonts.php
// Script para adicionar colunas de fonte às tabelas de templates

// Configurações de banco de dados (ajuste conforme necessário)
$host = 'localhost';
$user = 'root';
$pass = '';
$db = 'sistema_parceiro_murilo';

$conn = new mysqli($host, $user, $pass, $db);

if ($conn->connect_error) {
    die("Falha na conexão: " . $conn->connect_error);
}

echo "Iniciando migração de fontes...\n";

// 1. Adicionar colunas na tabela templates_certificados
$cols = [
    'fonte_nome' => "VARCHAR(50) DEFAULT 'Arial'",
    'fonte_curso' => "VARCHAR(50) DEFAULT 'Arial'",
    'fonte_data' => "VARCHAR(50) DEFAULT 'Arial'"
];

foreach ($cols as $col => $def) {
    // Verificar se a coluna já existe
    $check = $conn->query("SHOW COLUMNS FROM templates_certificados LIKE '$col'");
    if ($check->num_rows == 0) {
        $sql = "ALTER TABLE templates_certificados ADD COLUMN $col $def";
        if ($conn->query($sql) === TRUE) {
            echo "Coluna '$col' adicionada com sucesso em templates_certificados.\n";
        } else {
            echo "Erro ao adicionar coluna '$col': " . $conn->error . "\n";
        }
    } else {
        echo "Coluna '$col' já existe em templates_certificados.\n";
    }
}

// 2. Adicionar coluna na tabela template_campos_customizados
$col_custom = 'fonte';
$def_custom = "VARCHAR(50) DEFAULT 'Arial'";

$check = $conn->query("SHOW COLUMNS FROM template_campos_customizados LIKE '$col_custom'");
if ($check->num_rows == 0) {
    $sql = "ALTER TABLE template_campos_customizados ADD COLUMN $col_custom $def_custom";
    if ($conn->query($sql) === TRUE) {
        echo "Coluna '$col_custom' adicionada com sucesso em template_campos_customizados.\n";
    } else {
        echo "Erro ao adicionar coluna '$col_custom': " . $conn->error . "\n";
    }
} else {
    echo "Coluna '$col_custom' já existe em template_campos_customizados.\n";
}

$conn->close();
echo "Migração concluída.\n";
?>