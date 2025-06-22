<?php
require_once(__DIR__ . "/conexao.php");

// Listar músicas ativas
function listarMusicasDisponiveis($filtroGenero = null, $termoBusca = null) {
    $pdo = getDBConnection();
    
    $query = "SELECT m.id, m.titulo, m.arquivo, m.capa, m.duracao, m.genero_id,
              a.nome AS artista, g.nome AS genero
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
        $params[] = "%$termoBusca%";
        $params[] = "%$termoBusca%";
    }

    $stmt = $pdo->prepare($query);
    $stmt->execute($params);
    return $stmt->fetchAll(PDO::FETCH_ASSOC);
}


// Buscar música por ID 
function buscarMusica($id) {
    $pdo = getDBConnection();
    
    $stmt = $pdo->prepare("SELECT m.*, a.nome AS artista, g.nome AS genero
                          FROM musicas m
                          JOIN artistas a ON m.artista_id = a.id
                          JOIN generos g ON m.genero_id = g.id
                          WHERE m.id = ? AND m.ativo = 1");
    $stmt->execute([$id]);
    return $stmt->fetch(PDO::FETCH_ASSOC);
}

// Funções de favoritos 
function adicionarFavorito($usuarioId, $musicaId) {
    $pdo = getDBConnection();
    
    try {
        $stmt = $pdo->prepare("INSERT INTO favoritos (usuario_id, musica_id) VALUES (?, ?)");
        return $stmt->execute([$usuarioId, $musicaId]);
    } catch (PDOException $e) {
        
        if ($e->getCode() == 23000) { 
            return true;
        }
        error_log("Erro ao favoritar: " . $e->getMessage());
        return false;
    }
}

function removerFavorito($usuarioId, $musicaId) {
    $pdo = getDBConnection();
    $stmt = $pdo->prepare("DELETE FROM favoritos WHERE usuario_id = ? AND musica_id = ?");
    return $stmt->execute([$usuarioId, $musicaId]);
}

function listarFavoritos($usuarioId) {
    $pdo = getDBConnection();
    
    $stmt = $pdo->prepare("SELECT m.*, a.nome AS artista 
                          FROM favoritos f
                          JOIN musicas m ON f.musica_id = m.id
                          JOIN artistas a ON m.artista_id = a.id
                          WHERE f.usuario_id = ? AND m.ativo = 1");
    $stmt->execute([$usuarioId]);
    return $stmt->fetchAll(PDO::FETCH_ASSOC);
}

//  Histórico de reprodução 
function registrarReproducao($usuarioId, $musicaId) {
    $pdo = getDBConnection();
    $stmt = $pdo->prepare("INSERT INTO historico (usuario_id, musica_id, data_reproducao) 
                          VALUES (?, ?, NOW())");
    return $stmt->execute([$usuarioId, $musicaId]);
}

function listarHistorico($usuarioId, $limite = 10) {
    $pdo = getDBConnection();
    
    $stmt = $pdo->prepare("SELECT m.*, a.nome AS artista, h.data_reproducao
                          FROM historico h
                          JOIN musicas m ON h.musica_id = m.id
                          JOIN artistas a ON m.artista_id = a.id
                          WHERE h.usuario_id = ? AND m.ativo = 1
                          ORDER BY h.data_reproducao DESC
                          LIMIT ?");
    $stmt->execute([$usuarioId, $limite]);
    return $stmt->fetchAll(PDO::FETCH_ASSOC);
}

// Busca segura (para preencher selects/datalists)
function listarGenerosDisponiveis() {
    $pdo = getDBConnection();
    $stmt = $pdo->query("SELECT id, nome FROM generos ORDER BY nome");
    return $stmt->fetchAll(PDO::FETCH_ASSOC);
}

function listarArtistasDisponiveis() {
    $pdo = getDBConnection();
    $stmt = $pdo->query("SELECT id, nome FROM artistas ORDER BY nome");
    return $stmt->fetchAll(PDO::FETCH_ASSOC);
}
?>