<?php
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

$_SERVER['HTTP_HOST'] = 'localhost';

require_once __DIR__ . '/../config/config.php';

echo "<h2>Debug Pagar Mais Tarde</h2>";

if (php_sapi_name() === 'cli') {
    echo "Running in CLI mode.\n";
    // Mock user for CLI
    $parceiro_id = 1;
    echo "Mocking parceiro_id = $parceiro_id\n";
} else {
    if (!isAuthenticated()) {
        die("Not authenticated. Please login first.");
    }
    $user = getCurrentUser();
    $parceiro_id = $user['parceiro_id'] ?? $user['id'];
    echo "Authenticated as parceiro_id = $parceiro_id<br>";
}

$conn = getDBConnection();

try {
    echo "Attempting update...<br>";
    $sql = "UPDATE parceiros SET pagamento_pendente = 1, updated_at = NOW() WHERE id = ?";
    echo "Query: $sql<br>";

    $stmt = $conn->prepare($sql);
    if (!$stmt) {
        throw new Exception("Prepare failed: " . $conn->error);
    }

    $stmt->bind_param("i", $parceiro_id);

    if (!$stmt->execute()) {
        throw new Exception("Execute failed: " . $stmt->error);
    }

    echo "Update successful! Affected rows: " . $stmt->affected_rows . "<br>";
    $stmt->close();

} catch (Exception $e) {
    echo "<h3>Error Caught:</h3>";
    echo "Message: " . $e->getMessage() . "<br>";
    echo "Trace: <pre>" . $e->getTraceAsString() . "</pre>";
}
?>