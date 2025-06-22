<?php
session_start();
require 'includes/conexao.php';

$pdo = getDBConnection();

// Verificar se está logado (usuário ou admin)
$logado = false;
$tipo_usuario = '';
$nome_usuario = '';
$foto_usuario = 'default.jpg';

if (isset($_SESSION['usuario_id'])) {
    $logado = true;
    $tipo_usuario = 'usuario';
    $nome_usuario = $_SESSION['usuario_nome'];
    $foto_usuario = !empty($_SESSION['usuario_foto']) ? $_SESSION['usuario_foto'] : 'default.jpg';
} elseif (isset($_SESSION['admin_id'])) {
    $logado = true;
    $tipo_usuario = 'admin';
    $nome_usuario = $_SESSION['admin_nome'];
    $foto_usuario = !empty($_SESSION['admin_foto']) ? $_SESSION['admin_foto'] : 'admin_default.jpg';
}

// Buscar as 6 músicas mais recentes
$sql_musicas = "SELECT m.id, m.titulo, m.capa, m.duracao, 
                a.id AS artista_id, a.nome AS artista_nome,
                g.nome AS genero_nome
                FROM musicas m
                JOIN artistas a ON m.artista_id = a.id
                JOIN generos g ON m.genero_id = g.id
                WHERE m.ativo = 1
                ORDER BY m.data_upload DESC 
                LIMIT 6";
$result_musicas = $pdo->query($sql_musicas);
$musicas_recentes = $result_musicas->fetchAll(PDO::FETCH_ASSOC);

// Buscar os 4 artistas mais populares (com mais músicas)
$sql_artistas = "SELECT a.id, a.nome, a.foto, 
                COUNT(m.id) AS total_musicas
                FROM artistas a
                LEFT JOIN musicas m ON a.id = m.artista_id
                WHERE m.ativo = 1 OR m.id IS NULL
                GROUP BY a.id
                ORDER BY total_musicas DESC, a.nome ASC
                LIMIT 4";
$result_artistas = $pdo->query($sql_artistas);
$artistas_populares = $result_artistas->fetchAll(PDO::FETCH_ASSOC);
?>

<!DOCTYPE html>
<html lang="pt-br">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Sonare - Sua Plataforma Musical</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/animate.css/4.1.1/animate.min.css">
    <link rel="stylesheet" href="css/index.css">
</head>

<body>
    <div id="page-loader">
        <div class="loader-content">
            <div class="logo"><i class="fas fa-music"></i> Sonare</div>
            <div class="typing-text" id="typing-text"></div>
        </div>
    </div>


    <!-- Header -->
    <header>
        <div class="container">
            <div class="header-content">
                <a href="index.php" class="logo">
                    <i class="fas fa-music"></i>
                    Sonare
                </a>

                <ul class="nav-links">
                    <li><a href="index.php">Início</a></li>
                    <li><a href="#features">Recursos</a></li>
                    <li><a href="#registration">Cadastro</a></li>
                    <li><a href="#contact">Contato</a></li>
                    <?php if ($logado && $tipo_usuario == 'admin'): ?>
                        <li><a href="admin/painel.php">Painel Admin</a></li>
                    <?php endif; ?>
                </ul>

                <div class="user-menu">
                    <?php if ($logado): ?>
                        <div class="dropdown">
                            <?php if ($foto_usuario == 'default.jpg' || $foto_usuario == 'admin_default.jpg'): ?>
                                <img src="https://api.dicebear.com/7.x/initials/svg?seed=<?php echo urlencode($nome_usuario); ?>"
                                    alt="Avatar" class="user-avatar">
                            <?php else: ?>
                                <img src="uploads/avatars/<?php echo $foto_usuario; ?>" alt="Avatar" class="user-avatar">
                            <?php endif; ?>
                            <span><?php echo $nome_usuario; ?></span>
                            <div class="dropdown-content">
                                <a
                                    href="<?php echo $tipo_usuario == 'admin' ? 'admin/perfil.php' : 'usuario/perfil.php'; ?>">
                                    <i class="fas fa-user"></i> Perfil
                                </a>
                                <?php if ($tipo_usuario == 'usuario'): ?>
                                    <a href="usuario/painel.php">
                                        <i class="fas fa-headphones"></i> Minha Conta
                                    </a>
                                <?php endif; ?>
                                <a href="logout.php">
                                    <i class="fas fa-sign-out-alt"></i> Sair
                                </a>
                            </div>
                        </div>
                    <?php else: ?>
                        <a href="login.php" class="btn">Entrar</a>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </header>

    <section class="hero">
        <div class="particles" id="particles"></div>
        <div class="container hero-content">
            <h1>Descubra sua próxima música favorita</h1>
            <p>Sonare é a plataforma perfeita para explorar, gerenciar e compartilhar suas músicas favoritas</p>
            <div>
                <a href="#registration" class="btn btn-large">Comece Agora</a>
                <a href="#features" class="btn btn-outline btn-large">Saiba Mais</a>
            </div>
        </div>
    </section>

    <section id="features" class="section">
        <div class="container">
            <div class="section-title">
                <h2>Recursos Incríveis</h2>
                <p>Descubra tudo o que oferecemos para sua experiência musical</p>
            </div>

            <div class="features-grid">
                <div class="feature-card animate__animated animate__fadeIn">
                    <div class="feature-icon">
                        <i class="fas fa-music"></i>
                    </div>
                    <h3>Catálogo Completo</h3>
                    <p>Acesso a milhares de músicas de diversos gêneros e artistas</p>
                </div>

                <div class="feature-card animate__animated animate__fadeIn">
                    <div class="feature-icon">
                        <i class="fas fa-headphones"></i>
                    </div>
                    <h3>Playlists Personalizadas</h3>
                    <p>Crie e compartilhe suas próprias playlists</p>
                </div>

                <div class="feature-card animate__animated animate__fadeIn">
                    <div class="feature-icon">
                        <i class="fas fa-search"></i>
                    </div>
                    <h3>Busca Inteligente</h3>
                    <p>Encontre exatamente o que procura com nosso sistema de busca avançado</p>
                </div>
            </div>
        </div>
    </section>

    <section id="registration" class="registration-section">
        <div class="container">
            <div class="section-title">
                <h2>Crie sua conta</h2>
                <p>Escolha o tipo de conta que melhor atende suas necessidades</p>
            </div>

            <div class="registration-cards">
                <div class="registration-card animate__animated animate__fadeIn">
                    <div class="registration-icon">
                        <i class="fas fa-user"></i>
                    </div>
                    <h3>Usuário</h3>
                    <p>Acesso completo ao catálogo de músicas e recursos de playlist</p>
                    <a href="cadastro.php" class="btn">Cadastre-se como Usuário</a>
                </div>

                <div class="registration-card animate__animated animate__fadeIn">
                    <div class="registration-icon">
                        <i class="fas fa-shield-alt"></i>
                    </div>
                    <h3>Administrador</h3>
                    <p>Gerencie o conteúdo da plataforma</p>
                    <a href="admin/cadastro_admin.php" class="btn btn-outline">Cadastre-se como Admin</a>
                </div>
            </div>
        </div>
    </section>

    <section class="music-section" style="background: #f9fafc;">
        <div class="container">
            <div class="section-title">
                <h2>Músicas Recentes</h2>
                <p>Veja as últimas adições ao nosso catálogo</p>
            </div>
        </div>
    </section>


    <section class="artists-section">
        <div class="container">
            <div class="section-title">
                <h2>Artistas Populares</h2>
                <p>Conheça os artistas mais ouvidos</p>
            </div>
        </div>
    </section>


    <footer id="contact">
        <div class="container">
            <div class="footer-content">
                <div class="footer-column">
                    <h3>Sonare</h3>
                    <p>Sua plataforma completa para descoberta e gerenciamento de músicas.</p>
                    <div class="social-links">
                        <a href="#"><i class="fab fa-facebook-f"></i></a>
                        <a href="#"><i class="fab fa-twitter"></i></a>
                        <a href="#"><i class="fab fa-instagram"></i></a>
                        <a href="#"><i class="fab fa-youtube"></i></a>
                    </div>
                </div>

                <div class="footer-column">
                    <h3>Links</h3>
                    <ul class="footer-links">
                        <li><a href="index.php">Início</a></li>
                        <li><a href="#features">Recursos</a></li>
                        <li><a href="#registration">Cadastro</a></li>
                        <li><a href="#contact">Contato</a></li>
                    </ul>
                </div>

                <div class="footer-column">
                    <h3>Contato</h3>
                    <ul class="footer-links">
                        <li><a href="mailto:contato@sonare.com">contato@sonare.com</a></li>
                        <li><a href="tel:+5511987654321">(18) 98765-4321</a></li>
                        <li><a href="#">Termos de Uso</a></li>
                        <li><a href="#">Política de Privacidade</a></li>
                    </ul>
                </div>
            </div>

            <div class="copyright">
                <p>&copy; <?php echo date('Y'); ?> Sonare. Todos os direitos reservados.</p>
            </div>
        </div>
    </footer>

    <script>
        // Efeito de partículas
        document.addEventListener('DOMContentLoaded', function () {
            const particlesContainer = document.getElementById('particles');
            const particleCount = 30;

            for (let i = 0; i < particleCount; i++) {
                const particle = document.createElement('div');
                particle.classList.add('particle');

                const size = Math.random() * 4 + 2;
                particle.style.width = `${size}px`;
                particle.style.height = `${size}px`;
                particle.style.left = `${Math.random() * 100}%`;
                particle.style.top = `${Math.random() * 100}%`;
                particle.style.animationDuration = `${Math.random() * 20 + 10}s`;
                particle.style.animationDelay = `${Math.random() * 5}s`;

                particlesContainer.appendChild(particle);
            }

            // Animar elementos quando aparecem na tela
            const animateOnScroll = function () {
                const elements = document.querySelectorAll('.music-card, .artist-card, .registration-card, .feature-card');

                elements.forEach(element => {
                    const elementPosition = element.getBoundingClientRect().top;
                    const screenPosition = window.innerHeight / 1.3;

                    if (elementPosition < screenPosition) {
                        element.classList.add('animate__fadeIn');
                    }
                });
            };

            window.addEventListener('scroll', animateOnScroll);
            animateOnScroll();
        });

        // Animação do começo
        document.addEventListener("DOMContentLoaded", function () {
            const text = "Carregando sua experiência sonora...";
            const typingEl = document.getElementById("typing-text");

            let i = 0;
            const typingSpeed = 60;

            function typeChar() {
                if (i < text.length) {
                    typingEl.textContent += text.charAt(i);
                    i++;
                    setTimeout(typeChar, typingSpeed);
                }
            }

            typeChar();

            
            setTimeout(() => {
                document.getElementById("page-loader").style.display = "none";
            }, 3500);
        });

    </script>
</body>

</html>