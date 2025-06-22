<?php
function getDBConnection() {
    $dns = "mysql:host=localhost;dbname=sonare_db;charset=utf8mb4";
    $user = "root";
    $pass = "";

    try {
        $pdo = new PDO($dns, $user, $pass);
        $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
        return $pdo;
    } catch (PDOException $erro) {
        echo "Erro na conexão com o banco de dados:<br>";
        echo "Código: " . $erro->getCode() . "<br>";
        echo "Mensagem: " . $erro->getMessage();
        exit;
    }
}
?>
