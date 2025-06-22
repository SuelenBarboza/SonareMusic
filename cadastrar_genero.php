<?php
require_once(__DIR__ . "/includes/conexao.php");
require_once(__DIR__ . "/includes/funcoes.php");

session_start();

if (!isset($_SESSION['admin_logado'])) {
    header('Location: login.php');
    exit();
}

$response = ['success' => false, 'message' => ''];

try {
    $nome = $_POST['nome'];
    $descricao = $_POST['descricao'] ?? null;

    if (empty($nome)) {
        throw new Exception("Nome do gênero é obrigatório!");
    }

    $pdo = getDBConnection();

    // Verifica se já existe um gênero com o mesmo nome
    $check = $pdo->prepare("SELECT COUNT(*) FROM generos WHERE nome = :nome");
    $check->bindParam(':nome', $nome, PDO::PARAM_STR);
    $check->execute();

    if ($check->fetchColumn() > 0) {
        throw new Exception("Este gênero já está cadastrado!");
    }

    // Insere o novo gênero
    $sql = "INSERT INTO generos (nome, descricao) VALUES (:nome, :descricao)";
    $stmt = $pdo->prepare($sql);
    $stmt->bindParam(':nome', $nome, PDO::PARAM_STR);
    $stmt->bindParam(':descricao', $descricao, PDO::PARAM_STR);
    $stmt->execute();

    $response['success'] = true;
    $response['message'] = 'Gênero cadastrado com sucesso!';
} catch (PDOException $e) {
    $response['message'] = "Erro no banco de dados: " . $e->getMessage();
} catch (Exception $e) {
    $response['message'] = $e->getMessage();
}

header('Content-Type: application/json');
echo json_encode($response);
?>