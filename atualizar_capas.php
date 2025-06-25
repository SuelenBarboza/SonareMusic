<?php
require 'includes/conexao.php'; // ou ajuste o caminho conforme seu projeto
$pdo = getDBConnection();

$pastaMusicas = 'uploads/musicas/';
$pastaCapas = 'uploads/capas/';

// Buscar todas as músicas do banco
$sql = "SELECT id, arquivo FROM musicas";
$stmt = $pdo->query($sql);
$musicas = $stmt->fetchAll(PDO::FETCH_ASSOC);

foreach ($musicas as $musica) {
    $id = $musica['id'];
    $arquivo = $musica['arquivo'];

    // Obtém o nome da música sem a extensão
    $nomeBase = pathinfo($arquivo, PATHINFO_FILENAME);

    // Tenta encontrar a capa correspondente
    $capaEncontrada = null;
    if (file_exists($pastaCapas . $nomeBase . '.jpg')) {
        $capaEncontrada = $nomeBase . '.jpg';
    } elseif (file_exists($pastaCapas . $nomeBase . '.png')) {
        $capaEncontrada = $nomeBase . '.png';
    }

    // Se encontrou uma capa, atualiza no banco
    if ($capaEncontrada) {
        $update = $pdo->prepare("UPDATE musicas SET capa = :capa WHERE id = :id");
        $update->execute([
            ':capa' => $capaEncontrada,
            ':id' => $id
        ]);
        echo "✅ Capa atribuída para '{$arquivo}': {$capaEncontrada}<br>";
    } else {
        echo "⚠️ Nenhuma capa encontrada para '{$arquivo}'<br>";
    }
}
?>
