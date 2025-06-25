
<?php
require_once __DIR__ . '/includes/getID3-master/getid3/getid3.php';




$pastaMusicas = 'uploads/musicas/';
$pastaCapas = 'uploads/capas/';

$arquivos = scandir($pastaMusicas);
$getID3 = new getID3;

foreach ($arquivos as $arquivo) {
    if (pathinfo($arquivo, PATHINFO_EXTENSION) === 'mp3') {
        $caminhoArquivo = $pastaMusicas . $arquivo;
        $info = $getID3->analyze($caminhoArquivo);

        // Carrega os comentários para acessar a imagem embutida
        getid3_lib::CopyTagsToComments($info);

        if (!empty($info['comments']['picture'][0])) {
            $capa = $info['comments']['picture'][0];

            // Detecta extensão da imagem
            $mime = $capa['image_mime'];
            $extensao = ($mime === 'image/jpeg') ? '.jpg' : (($mime === 'image/png') ? '.png' : '');

            if ($extensao) {
                $nomeBase = pathinfo($arquivo, PATHINFO_FILENAME);
                $caminhoImagem = $pastaCapas . $nomeBase . $extensao;

                // Salva a imagem extraída
                file_put_contents($caminhoImagem, $capa['data']);

                echo "✅ Capa extraída: $arquivo → $nomeBase$extensao<br>";
            } else {
                echo "⚠️ Tipo de imagem não suportado em: $arquivo<br>";
            }
        } else {
            echo "❌ Nenhuma capa embutida encontrada em: $arquivo<br>";
        }
    }
}
?>
