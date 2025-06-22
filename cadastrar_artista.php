<?php
require_once(__DIR__ . "/includes/conexao.php");
require_once(__DIR__ . "/includes/funcoes.php");


session_start();

if (!isset($_SESSION['admin_logado'])) {
    header('Location: login.php');
    exit();
}

$pdo = getDBConnection();

$response = ['success' => false, 'message' => ''];

try {
    $nome = $_POST['nome'];
    $biografia = $_POST['biografia'] ?? null;

    if (empty($nome)) {
        throw new Exception("Nome do artista é obrigatório!");
    }

    $sql = "INSERT INTO ARTISTAS (NOME, BIOGRAFIA) VALUES (:nome, :biografia)";
    $stmt = $pdo->prepare($sql);
    $stmt->bindParam(':nome', $nome, PDO::PARAM_STR);
    $stmt->bindParam(':biografia', $biografia, PDO::PARAM_STR);
    $stmt->execute();

    $response['success'] = true;
    $response['message'] = 'Artista cadastrado com sucesso!';
} catch (PDOException $e) {
    $response['message'] = "Erro no banco de dados: " . $e->getMessage();
} catch (Exception $e) {
    $response['message'] = $e->getMessage();
}

header('Content-Type: application/json');
echo json_encode($response);
?>