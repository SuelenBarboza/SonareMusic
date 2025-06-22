<?php
require 'includes/conexao.php';
session_start();

$mensagem = '';
$tipo_login = $_GET['tipo'] ?? 'usuario'; 

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $username = trim($_POST['username']);
    $senha = $_POST['senha'];
    $tipo = $_POST['tipo']; 

    if (empty($username) || empty($senha)) {
        $mensagem = 'Por favor, preencha todos os campos.';
    } else {
        
        if ($tipo === 'admin') {
            $_SESSION['admin_logado'] = true;
            $tabela = 'administradores';
            $painel = 'admin/painel_admin.php';
            $id_sessao = 'admin_id';
            $nome_sessao = 'admin_nome';
            $user_sessao = 'admin_username';
            $foto_sessao = 'admin_foto';
        } else {
            $tabela = 'usuarios';
            $painel = 'painel_usuario.php';
            $id_sessao = 'usuario_id';
            $nome_sessao = 'usuario_nome';
            $user_sessao = 'usuario_username';
            $foto_sessao = 'usuario_foto';
        }

        // Consulta genérica
        $pdo = getDBConnection();
        $sql = "SELECT * FROM $tabela WHERE username = ?";
        $stmt = $pdo->prepare($sql);
        $stmt->execute([$username]);
        $dados = $stmt->fetch(PDO::FETCH_ASSOC);

        if ($dados && password_verify($senha, $dados['senha'])) {
            if ($dados['status'] === 'ativo') {
                $sql = "UPDATE $tabela SET ultimo_login = NOW() WHERE id = ?";
                $stmt = $pdo->prepare($sql);
                $stmt->execute([$dados['id']]);

                $_SESSION[$id_sessao] = $dados['id'];
                $_SESSION[$nome_sessao] = $dados['nome'];
                $_SESSION[$user_sessao] = $dados['username'];
                $_SESSION[$foto_sessao] = $dados['foto'];
                $_SESSION['usuario_logado'] = true;

                if ($tipo === 'admin') {
                    $_SESSION['nivel_acesso'] = $dados['nivel_acesso'];
                }

                
                header("Location: $painel");
                exit();
            } else {
                $mensagem = 'Sua conta está inativa.';
            }
        } else {
            $mensagem = 'Usuário ou senha incorretos.';
        }
    }
}
?>


<!DOCTYPE html>
<html lang="pt-br">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Login - Sonare</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link rel="stylesheet" href="css/login.css">
</head>
<body>
    <div class="particles"></div>
    <div class="container">
        <div class="header">
            <div class="music-icon">🎵</div>
            <h1>Login</h1>
            <p>Acesse sua conta</p>
        </div>
        
        <div class="form-container">
            <?php if (!empty($mensagem)): ?>
                <div class="mensagem error"><?php echo $mensagem; ?></div>
            <?php endif; ?>
            
            <div class="login-type-buttons">
                <button class="login-type-btn <?php echo $tipo_login == 'usuario' ? 'active' : ''; ?>" 
                        onclick="setLoginType('usuario', event)">
                    Usuário
                </button>
                <button class="login-type-btn <?php echo $tipo_login == 'admin' ? 'active' : ''; ?>" 
                        onclick="setLoginType('admin', event)">
                    Administrador
                </button>
            </div>
            
            <form method="POST" action="login.php<?php echo '?tipo=' . $tipo_login; ?>">
                <input type="hidden" name="tipo" value="<?php echo $tipo_login; ?>">
                
                <div class="form-group">
                    <label for="username">Nome de Usuário</label>
                    <input type="text" id="username" name="username" class="form-control" required>
                </div>
                
                <div class="form-group">
                    <label for="senha">Senha</label>
                    <div class="password-toggle">
                        <input type="password" id="senha" name="senha" class="form-control" required>
                        <i class="toggle-icon fas fa-eye" onclick="togglePassword()"></i>
                    </div>
                </div>

                <div class="reset-password">
                    <a href="redefinir_senha.php">Redefinir senha</a>
                </div>

                <button type="submit" class="btn">Entrar</button>
                <a href="index.php" class="btn btn-outline">Voltar para Início</a>

            </form>
            
            <div class="login-link">
                <span id="cadastro-text"><?php echo $tipo_login == 'usuario' ? 'Não tem uma conta?' : 'Precisa de uma conta de administrador?'; ?></span> 
                <a href="<?php echo $tipo_login == 'usuario' ? 'cadastro.php' : 'admin/cadastro_admin.php'; ?>" id="cadastro-link">Cadastre-se</a> | 
                <a href="recuperar_senha.php">Esqueceu a senha?</a>
            </div>
        </div>
    </div>

    <script>
        function setLoginType(tipo, event) {
            event.preventDefault();
            document.querySelectorAll('.login-type-btn').forEach(btn => {
                btn.classList.remove('active');
            });
            event.currentTarget.classList.add('active');

            document.querySelector('input[name="tipo"]').value = tipo;

            const cadastroText = document.getElementById('cadastro-text');
            const cadastroLink = document.getElementById('cadastro-link');

            if (tipo === 'usuario') {
                cadastroText.textContent = 'Não tem uma conta?';
                cadastroLink.textContent = 'Cadastre-se';
                cadastroLink.href = 'cadastro.php';
            } else {
                cadastroText.textContent = 'Precisa de uma conta de administrador?';
                cadastroLink.textContent = 'Cadastre-se';
                cadastroLink.href = 'admin/cadastro_admin.php';
            }

            history.pushState(null, null, '?tipo=' + tipo);
        }

        function togglePassword() {
            const senhaInput = document.getElementById('senha');
            const icon = document.querySelector('.toggle-icon');
            if (senhaInput.type === 'password') {
                senhaInput.type = 'text';
                icon.classList.replace('fa-eye', 'fa-eye-slash');
            } else {
                senhaInput.type = 'password';
                icon.classList.replace('fa-eye-slash', 'fa-eye');
            }
        }

        document.addEventListener('DOMContentLoaded', function () {
            const particlesContainer = document.querySelector('.particles');
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
        });
    </script>
</body>
</html>
