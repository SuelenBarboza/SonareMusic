<?php
require_once(__DIR__ . "/includes/funcoes_usuario.php");

echo "<h1>Teste do Sistema de Músicas</h1>";

//  Testar listagem de todas as músicas
echo "<h2>Todas as músicas disponíveis:</h2>";
$todasMusicas = listarMusicasDisponiveis();
echo "<pre>";
print_r($todasMusicas);
echo "</pre>";

//  Testar busca por ID
echo "<h2>Buscar música por ID (ID 5):</h2>";
$musica = buscarMusica(5);
print_r($musica);

//  Testar filtro por gênero
echo "<h2>Músicas do gênero Pop (ID 3):</h2>";
$musicasPop = listarMusicasDisponiveis(3);
print_r($musicasPop);

//  Testar busca por termo
echo "<h2>Músicas com 'teste' no título ou artista:</h2>";
$musicasTeste = listarMusicasDisponiveis(null, 'teste');
print_r($musicasTeste);

//  Testar listagem de gêneros
echo "<h2>Gêneros disponíveis:</h2>";
$generos = listarGenerosDisponiveis();
print_r($generos);

//  Testar listagem de artistas
echo "<h2>Artistas disponíveis:</h2>";
$artistas = listarArtistasDisponiveis();
print_r($artistas);

// Adicione isto ao final do teste_musicas.php
echo "<h2>Verificação de arquivos físicos:</h2>";
foreach ($todasMusicas as $musica) {
    $caminhoMusica = __DIR__ . '/' . $musica['arquivo'];
    echo "Música {$musica['id']} ({$musica['titulo']}): ";
    echo file_exists($caminhoMusica) ? "EXISTE" : "NÃO ENCONTRADO";
    echo " ($caminhoMusica)<br>";
    
    if (!empty($musica['capa'])) {
        $caminhoCapa = __DIR__ . '/' . $musica['capa'];
        echo "Capa: " . (file_exists($caminhoCapa) ? "EXISTE" : "NÃO ENCONTRADO");
        echo " ($caminhoCapa)<br>";
    }
    echo "<br>";
}
?>

echo '<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Teste do Sistema de Músicas</title>
    <style>
        body {
            font-family: \'Segoe UI\', Tahoma, Geneva, Verdana, sans-serif;
            line-height: 1.6;
            color: #333;
            background-color: #f5f5f5;
            margin: 0;
            padding: 20px;
        }
        
        h1 {
            color: #2c3e50;
            text-align: center;
            margin-bottom: 30px;
            padding-bottom: 10px;
            border-bottom: 2px solid #3498db;
        }
        
        h2 {
            color: #2980b9;
            margin-top: 30px;
            padding-left: 10px;
            border-left: 4px solid #3498db;
        }
        
        pre {
            background-color: #fff;
            border: 1px solid #ddd;
            border-radius: 4px;
            padding: 15px;
            overflow-x: auto;
            box-shadow: 0 2px 4px rgba(0,0,0,0.1);
            margin: 15px 0;
        }
        
        .file-test {
            background-color: #e8f4fc;
            padding: 10px 15px;
            margin: 10px 0;
            border-radius: 4px;
            border-left: 4px solid #3498db;
        }
        
        .file-exists {
            color: #27ae60;
            font-weight: bold;
        }
        
        .file-not-found {
            color: #e74c3c;
            font-weight: bold;
        }
        
        .path {
            font-family: Consolas, Monaco, \'Courier New\', monospace;
            font-size: 0.9em;
            color: #7f8c8d;
        }
        
        .music-item {
            background-color: white;
            border-radius: 6px;
            padding: 15px;
            margin: 15px 0;
            box-shadow: 0 2px 5px rgba(0,0,0,0.1);
        }
        
        .music-item h3 {
            margin-top: 0;
            color: #2c3e50;
        }
        
        .section {
            background-color: white;
            border-radius: 8px;
            padding: 20px;
            margin-bottom: 30px;
            box-shadow: 0 3px 10px rgba(0,0,0,0.1);
        }
    </style>
</head>
<body>';

echo "<div class='section'>";
echo "<h1>Teste do Sistema de Músicas</h1>";

// [Seu código PHP existente...]

echo "</div>"; // Fecha a div.section
echo '</body>
</html>';