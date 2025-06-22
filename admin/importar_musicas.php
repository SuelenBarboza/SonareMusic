<?php
require_once('../includes/conexao.php');

$conn = getDBConnection();

function obterIdArtista(PDO $conn, $nomeArtista = 'Artista Desconhecido') {
    $sql = "SELECT id FROM artistas WHERE nome = ?";
    $stmt = $conn->prepare($sql);
    $stmt->execute([$nomeArtista]);
    $resultado = $stmt->fetch(PDO::FETCH_ASSOC);

    if ($resultado) {
        return $resultado['id'];
    }

    $sqlInsert = "INSERT INTO artistas (nome) VALUES (?)";
    $stmtInsert = $conn->prepare($sqlInsert);
    $stmtInsert->execute([$nomeArtista]);

    return $conn->lastInsertId();
}

function obterIdGeneroSemGenero(PDO $conn) {
    $sql = "SELECT id FROM generos WHERE nome = ?";
    $stmt = $conn->prepare($sql);
    $stmt->execute(['sem genero']);
    $resultado = $stmt->fetch(PDO::FETCH_ASSOC);

    if ($resultado) {
        return $resultado['id'];
    }

    
    $sqlInsert = "INSERT INTO generos (nome) VALUES (?)";
    $stmtInsert = $conn->prepare($sqlInsert);
    $stmtInsert->execute(['sem genero']);

    return $conn->lastInsertId();
}

function musicaExiste(PDO $conn, $nomeArquivo) {
    $sql = "SELECT COUNT(*) FROM musicas WHERE arquivo = ?";
    $stmt = $conn->prepare($sql);
    $stmt->execute([$nomeArquivo]);
    return $stmt->fetchColumn() > 0;
}

$artista_id = obterIdArtista($conn); 
$genero_id = obterIdGeneroSemGenero($conn); 

$diretorio = '../uploads/musicas';
$arquivos = scandir($diretorio);

foreach ($arquivos as $arquivo) {
    if ($arquivo !== '.' && $arquivo !== '..' && pathinfo($arquivo, PATHINFO_EXTENSION) === 'mp3') {
        if (!musicaExiste($conn, $arquivo)) {
            $sql = "INSERT INTO musicas 
                (titulo, artista_id, genero_id, arquivo, capa, duracao, data_upload, upload_por, tipo_upload, album_id, letra, visualizacoes, ativo)
                VALUES (?, ?, ?, ?, NULL, NULL, NOW(), NULL, 'admin', NULL, NULL, 0, 1)";
            $stmt = $conn->prepare($sql);
            $stmt->execute([
                pathinfo($arquivo, PATHINFO_FILENAME), 
                $artista_id,
                $genero_id,
                $arquivo
            ]);
            echo "Música '$arquivo' importada com sucesso.<br>";
        } else {
            echo "Música '$arquivo' já existe no banco.<br>";
        }
    }
}
?>
