<?php
// Hardcoded local credentials for CLI execution
define('DB_HOST', 'localhost');
define('DB_USER', 'root');
define('DB_PASS', '');
define('DB_NAME', 'sistema_parceiro_murilo');

$conn = new mysqli(DB_HOST, DB_USER, DB_PASS, DB_NAME);

if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

// Add columns to templates_certificados
$columns = [
    "ADD COLUMN exibir_nome TINYINT(1) DEFAULT 1",
    "ADD COLUMN tamanho_fonte_nome INT DEFAULT 24",
    "ADD COLUMN cor_nome VARCHAR(7) DEFAULT '#000000'",
    "ADD COLUMN exibir_curso TINYINT(1) DEFAULT 1",
    "ADD COLUMN tamanho_fonte_curso INT DEFAULT 16",
    "ADD COLUMN cor_curso VARCHAR(7) DEFAULT '#000000'",
    "ADD COLUMN exibir_data TINYINT(1) DEFAULT 1",
    "ADD COLUMN tamanho_fonte_data INT DEFAULT 14",
    "ADD COLUMN cor_data VARCHAR(7) DEFAULT '#000000'"
];

foreach ($columns as $col) {
    try {
        $sql = "ALTER TABLE templates_certificados $col";
        if ($conn->query($sql) === TRUE) {
            echo "Column added successfully: $col\n";
        } else {
            // Ignore duplicate column error
            if ($conn->errno == 1060) {
                echo "Column already exists: $col\n";
            } else {
                echo "Error adding column: " . $conn->error . "\n";
            }
        }
    } catch (Exception $e) {
        echo "Exception: " . $e->getMessage() . "\n";
    }
}

// Add column to template_campos_customizados
try {
    $sql = "ALTER TABLE template_campos_customizados ADD COLUMN exibir TINYINT(1) DEFAULT 1";
    if ($conn->query($sql) === TRUE) {
        echo "Column added successfully: exibir to template_campos_customizados\n";
    } else {
        if ($conn->errno == 1060) {
            echo "Column already exists: exibir\n";
        } else {
            echo "Error adding column: " . $conn->error . "\n";
        }
    }
} catch (Exception $e) {
    echo "Exception: " . $e->getMessage() . "\n";
}

$conn->close();
echo "Migration completed.\n";
?>