<?php
ob_start(); 
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);


require_once(__DIR__ . "/includes/conexao.php");
require_once(__DIR__ . "/includes/funcoes.php");

header('Content-Type: application/json');


ob_start();

$pdo = getDBConnection(); 

// Criar pastas se não existirem
$uploadDirs = ['../uploads/musicas', '../uploads/capas'];
foreach ($uploadDirs as $dir) {
    if (!file_exists($dir)) {
        mkdir($dir, 0755, true);
    }
}

// Verificar se é POST
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    ob_end_clean();
    echo json_encode(['success' => false, 'message' => 'Método não permitido']);
    exit;
}

// Obter dados do formulário 
$dados = [
    'id' => $_POST['id'] ?? null,
    'titulo' => $_POST['titulo'] ?? '',
    'artista_id' => $_POST['artista_id'] ?? '',
    'genero_id' => $_POST['genero_id'] ?? '',
    'album_id' => $_POST['album_id'] ?? null,
    'action' => $_POST['action'] ?? 'insert'
];

// Verificação dos campos obrigatórios
$errors = [];

if (empty($dados['titulo'])) {
    $errors[] = 'O título da música é obrigatório';
}

if (empty($dados['artista_id'])) {
    $errors[] = 'O artista é obrigatório';
}

if (empty($dados['genero_id'])) {
    $errors[] = 'O gênero é obrigatório';
}

if ($dados['action'] === 'insert' && (empty($_FILES['arquivo_audio']['name']) || $_FILES['arquivo_audio']['error'] !== UPLOAD_ERR_OK)) {
    $errors[] = 'O arquivo de áudio é obrigatório e deve ser válido';
}

if (!empty($errors)) {
    ob_end_clean();
    echo json_encode([
        'success' => false,
        'message' => 'Por favor, corrija os seguintes erros:',
        'errors' => $errors
    ]);
    exit;
}

try {
    $pdo->beginTransaction();

    // Upload do áudio
    $arquivo_musica = '';
    if (!empty($_FILES['arquivo_audio']['name']) && $_FILES['arquivo_audio']['error'] === UPLOAD_ERR_OK) {
        $arquivo_musica = uploadArquivo($_FILES['arquivo_audio'], 'musicas');
        $arquivo_musica = corrigirCaminho($arquivo_musica);
    }

    // Upload da capa
    $capa = null;
    if (!empty($_FILES['capa']['name']) && $_FILES['capa']['error'] === UPLOAD_ERR_OK) {
        $capa = uploadArquivo($_FILES['capa'], 'capas');
        $capa = corrigirCaminho($capa);
    }

    if ($dados['action'] === 'insert') {
        $sql = "INSERT INTO musicas (titulo, artista_id, genero_id, album_id, arquivo, capa, ativo)
        VALUES (:titulo, :artista_id, :genero_id, :album_id, :arquivo, :capa, 1)";
    $stmt = $pdo->prepare($sql);
    $stmt->execute([
        ':titulo' => $dados['titulo'],
        ':artista_id' => $dados['artista_id'],
        ':genero_id' => $dados['genero_id'],
        ':album_id' => $dados['album_id'] ?: null,
        ':arquivo' => $arquivo_musica,
        ':capa' => $capa
    ]);
    } else {
        // Atualização
        $sql = "UPDATE musicas SET 
                titulo = :titulo,
                artista_id = :artista_id,
                genero_id = :genero_id,
                album_id = :album_id";

        if (!empty($capa)) {
            $sql .= ", capa = :capa";
        }
        if (!empty($arquivo_musica)) {
            $sql .= ", arquivo = :arquivo";
        }

        $sql .= " WHERE id = :id";

        $params = [
            ':titulo' => $dados['titulo'],
            ':artista_id' => $dados['artista_id'],
            ':genero_id' => $dados['genero_id'],
            ':album_id' => $dados['album_id'] ?: null,
            ':id' => $dados['id']
        ];

        if (!empty($capa)) {
            $params[':capa'] = $capa;
        }
        if (!empty($arquivo_musica)) {
            $params[':arquivo'] = $arquivo_musica;
        }

        $stmt = $pdo->prepare($sql);
        $stmt->execute($params);
    }

    $pdo->commit();

    ob_end_clean();
    echo json_encode(['success' => true, 'message' => 'Música salva com sucesso!']);
} catch (Exception $e) {
    $pdo->rollBack();

    ob_end_clean();
    echo json_encode([
        'success' => false,
        'message' => 'Erro ao processar a música',
        'error' => $e->getMessage()
    ]);
}
?>