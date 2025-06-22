<?php
require_once(__DIR__ . "/conexao.php");

//
// ===============================
// SEÇÃO: Funções de Utilidade 
// ===============================
//

function obterOuCriarArtista($nomeArtista) {
    $pdo = getDBConnection();
    $stmt = $pdo->prepare("SELECT id FROM artistas WHERE nome = ?");
    $stmt->execute([$nomeArtista]);
    if ($stmt->rowCount() > 0) return $stmt->fetchColumn();

    $stmt = $pdo->prepare("INSERT INTO artistas (nome) VALUES (?)");
    $stmt->execute([$nomeArtista]);
    return $pdo->lastInsertId();
}

function obterOuCriarGenero($nomeGenero) {
    $pdo = getDBConnection();
    $stmt = $pdo->prepare("SELECT id FROM generos WHERE nome = ?");
    $stmt->execute([$nomeGenero]);
    if ($stmt->rowCount() > 0) return $stmt->fetchColumn();

    $stmt = $pdo->prepare("INSERT INTO generos (nome) VALUES (?)");
    $stmt->execute([$nomeGenero]);
    return $pdo->lastInsertId();
}

function obterOuCriarAlbum($nomeAlbum, $artistaId) {
    $pdo = getDBConnection();
    if (empty($nomeAlbum)) return null;

    $stmt = $pdo->prepare("SELECT id FROM albuns WHERE titulo = ? AND artista_id = ?");
    $stmt->execute([$nomeAlbum, $artistaId]);
    if ($stmt->rowCount() > 0) return $stmt->fetchColumn();

    $stmt = $pdo->prepare("INSERT INTO albuns (titulo, artista_id) VALUES (?, ?)");
    $stmt->execute([$nomeAlbum, $artistaId]);
    return $pdo->lastInsertId();
}

//
// ===============================
// SEÇÃO: CRUD - Adição e Edição
// ===============================
//

function adicionarMusica($dados, $arquivoAudio, $arquivoCapa) {
    $pdo = getDBConnection();
    
    try {
        $pdo->beginTransaction();

        $artistaId = obterOuCriarArtista($dados['artista']);
        $generoId = obterOuCriarGenero($dados['genero']);
        $albumId  = !empty($dados['album']) ? obterOuCriarAlbum($dados['album'], $artistaId) : null;

        // Upload do áudio
        $diretorioAudio = '../uploads/audios/';
        $nomeAudio = uniqid() . '_' . basename($arquivoAudio['name']);
        $caminhoAudio = $diretorioAudio . $nomeAudio;
        if (!move_uploaded_file($arquivoAudio['tmp_name'], $caminhoAudio)) {
            throw new Exception("Falha no upload do áudio");
        }

        // Upload da capa
        $caminhoCapa = null;
        if ($arquivoCapa && $arquivoCapa['error'] == UPLOAD_ERR_OK) {
            $diretorioCapa = '../uploads/capas/';
            $nomeCapa = uniqid() . '_' . basename($arquivoCapa['name']);
            $caminhoCapa = $diretorioCapa . $nomeCapa;
            move_uploaded_file($arquivoCapa['tmp_name'], $caminhoCapa);
        }

        $sql = "INSERT INTO musicas 
                (titulo, artista_id, genero_id, arquivo, capa, album_id, duracao, upload_por, tipo_upload) 
                VALUES 
                (:titulo, :artista_id, :genero_id, :arquivo, :capa, :album_id, '00:00:00', :upload_por, 'admin')";
        $stmt = $pdo->prepare($sql);
        $stmt->execute([
            ':titulo' => $dados['titulo'],
            ':artista_id' => $artistaId,
            ':genero_id' => $generoId,
            ':arquivo' => $caminhoAudio,
            ':capa' => $caminhoCapa,
            ':album_id' => $albumId,
            ':upload_por' => $_SESSION['admin_id']
        ]);

        $pdo->commit();
        return true;
    } catch (Exception $e) {
        $pdo->rollBack();
        error_log("Erro ao adicionar música: " . $e->getMessage());
        return false;
    }
}

function editarMusica($id, $dados, $arquivoAudio = null, $arquivoCapa = null) {
    $pdo = getDBConnection();

    try {
        $pdo->beginTransaction();

        $artistaId = obterOuCriarArtista($dados['artista']);
        $generoId = obterOuCriarGenero($dados['genero']);
        $albumId  = !empty($dados['album']) ? obterOuCriarAlbum($dados['album'], $artistaId) : null;

        $sql = "UPDATE musicas SET 
                    titulo = :titulo,
                    artista_id = :artista_id,
                    genero_id = :genero_id,
                    album_id = :album_id
                WHERE id = :id";
        $stmt = $pdo->prepare($sql);
        $stmt->execute([
            ':titulo' => $dados['titulo'],
            ':artista_id' => $artistaId,
            ':genero_id' => $generoId,
            ':album_id' => $albumId,
            ':id' => $id
        ]);

        // Se novo áudio foi enviado
        if ($arquivoAudio && $arquivoAudio['error'] == UPLOAD_ERR_OK) {
            $nomeAudio = uniqid() . '_' . basename($arquivoAudio['name']);
            $caminhoAudio = '../uploads/audios/' . $nomeAudio;
            if (move_uploaded_file($arquivoAudio['tmp_name'], $caminhoAudio)) {
                $stmt = $pdo->prepare("UPDATE musicas SET arquivo = ? WHERE id = ?");
                $stmt->execute([$caminhoAudio, $id]);
            }
        }

        // Se nova capa foi enviada
        if ($arquivoCapa && $arquivoCapa['error'] == UPLOAD_ERR_OK) {
            $nomeCapa = uniqid() . '_' . basename($arquivoCapa['name']);
            $caminhoCapa = '../uploads/capas/' . $nomeCapa;
            if (move_uploaded_file($arquivoCapa['tmp_name'], $caminhoCapa)) {
                $stmt = $pdo->prepare("UPDATE musicas SET capa = ? WHERE id = ?");
                $stmt->execute([$caminhoCapa, $id]);
            }
        }

        $pdo->commit();
        return true;
    } catch (Exception $e) {
        $pdo->rollBack();
        error_log("Erro ao editar música: " . $e->getMessage());
        return false;
    }
}

function uploadArquivo($arquivo, $tipo) {
    $extensoesPermitidas = [
        'musicas' => ['mp3', 'wav', 'ogg'],
        'capas' => ['jpg', 'jpeg', 'png', 'gif']
    ];

    $diretorio = __DIR__ . "/../uploads/{$tipo}/";
    if (!is_dir($diretorio)) {
        mkdir($diretorio, 0755, true);
    }

    $nomeOriginal = $arquivo['name'];
    $ext = strtolower(pathinfo($nomeOriginal, PATHINFO_EXTENSION));

    if (!in_array($ext, $extensoesPermitidas[$tipo])) {
        throw new Exception("Extensão de arquivo não permitida para {$tipo}");
    }

    $nomeArquivo = uniqid() . '.' . $ext;
    $caminhoDestino = $diretorio . $nomeArquivo;

    if (move_uploaded_file($arquivo['tmp_name'], $caminhoDestino)) {
        return "uploads/{$tipo}/" . $nomeArquivo; // Retorna caminho relativo sem ../
    } else {
        throw new Exception("Erro ao mover arquivo para {$caminhoDestino}");
    }
}

//
// ===============================
// SEÇÃO: Listagem
// ===============================
//

function listarMusicasAdmin() {
    $conn = getDBConnection();
    $query = "SELECT m.id, m.titulo, 
              m.arquivo,
              m.capa,
              m.duracao, m.ativo,
              m.artista_id, m.genero_id, m.album_id,
              a.nome AS artista_nome, g.nome AS genero_nome,
              al.titulo AS album_nome
          FROM musicas m
          JOIN artistas a ON m.artista_id = a.id
          JOIN generos g ON m.genero_id = g.id
          LEFT JOIN albuns al ON m.album_id = al.id
          ORDER BY m.data_upload DESC";
    $stmt = $conn->prepare($query);
    $stmt->execute();
    return $stmt->fetchAll(PDO::FETCH_ASSOC);
}

function listarMusicasParaUsuario($filtroGenero = null, $termoBusca = null) {
    $conn = getDBConnection();
    $query = "SELECT m.id, m.titulo, m.arquivo, m.capa, m.duracao, 
                     a.nome AS artista_nome, g.nome AS genero_nome 
              FROM musicas m
              JOIN artistas a ON m.artista_id = a.id
              JOIN generos g ON m.genero_id = g.id
              WHERE m.ativo = 1";
    $params = [];

    if ($filtroGenero) {
        $query .= " AND g.id = ?";
        $params[] = $filtroGenero;
    }

    if ($termoBusca) {
        $query .= " AND (m.titulo LIKE ? OR a.nome LIKE ?)";
        $termoBuscaLike = "%$termoBusca%";
        $params[] = $termoBuscaLike;
        $params[] = $termoBuscaLike;
    }

    $stmt = $conn->prepare($query);
    $stmt->execute($params);
    return $stmt->fetchAll(PDO::FETCH_ASSOC);
}

//
// ===============================
// SEÇÃO: Exclusão 
// ===============================
//

function excluirMusica($id) {
    $pdo = getDBConnection();
    $stmt = $pdo->prepare("DELETE FROM musicas WHERE id = ?");
    $stmt->execute([$id]);
}

//
// ===============================
// SEÇÃO: Listagens Simples 
// ===============================
//

function listarArtistas() {
    $conn = getDBConnection();
    $stmt = $conn->query("SELECT id, nome FROM artistas ORDER BY nome");
    return $stmt->fetchAll(PDO::FETCH_ASSOC);
}

function listarGeneros() {
    $conn = getDBConnection();
    $stmt = $conn->query("SELECT id, nome FROM generos ORDER BY nome");
    return $stmt->fetchAll(PDO::FETCH_ASSOC);
}

function listarAlbuns() {
    $pdo = getDBConnection();
    try {
        $stmt = $pdo->query("SELECT * FROM albuns");
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    } catch (PDOException $e) {
        echo "Erro ao listar álbuns: " . $e->getMessage();
        return [];
    }
}

function listarArtistasParaDatalist() {
    $pdo = getDBConnection();
    $stmt = $pdo->query("SELECT nome FROM artistas ORDER BY nome");
    return $stmt->fetchAll(PDO::FETCH_COLUMN);
}

function listarGenerosParaDatalist() {
    $pdo = getDBConnection();
    $stmt = $pdo->query("SELECT nome FROM generos ORDER BY nome");
    return $stmt->fetchAll(PDO::FETCH_COLUMN);
}

function listarAlbunsParaDatalist() {
    $pdo = getDBConnection();
    $stmt = $pdo->query("SELECT titulo FROM albuns ORDER BY titulo");
    return $stmt->fetchAll(PDO::FETCH_COLUMN);
}

function corrigirCaminho($caminho) {
    return str_replace('\\', '/', $caminho);
}

function buscarOuCriarArtista($nome, $pdo) {
    $stmt = $pdo->prepare("SELECT id FROM artistas WHERE nome = ?");
    $stmt->execute([$nome]);
    $id = $stmt->fetchColumn();

    if (!$id) {
        $stmt = $pdo->prepare("INSERT INTO artistas (nome) VALUES (?)");
        $stmt->execute([$nome]);
        $id = $pdo->lastInsertId();
    }

    return $id;
}

function buscarOuCriarGenero($nome, $pdo) {
    $stmt = $pdo->prepare("SELECT id FROM generos WHERE nome = ?");
    $stmt->execute([$nome]);
    $id = $stmt->fetchColumn();

    if (!$id) {
        $stmt = $pdo->prepare("INSERT INTO generos (nome) VALUES (?)");
        $stmt->execute([$nome]);
        $id = $pdo->lastInsertId();
    }

    return $id;
}

