<?php
require 'includes/conexao.php';

$pdo = getDBConnection();
session_start();

if (isset($_SESSION['usuario_id']) || isset($_SESSION['admin_id'])) {
    header('Location: index.php');
    exit();
}

$mensagem = '';

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $nome = trim($_POST['nome']);
    $username = trim($_POST['username']);
    $email = trim($_POST['email']);
    $senha = $_POST['senha'];
    $confirmar_senha = $_POST['confirmar_senha'];

    if (empty($nome) || empty($email) || empty($senha) || empty($username)) {
        $mensagem = 'error:Por favor, preencha todos os campos.';
    } elseif ($senha !== $confirmar_senha) {
        $mensagem = 'error:As senhas não coincidem.';
    } elseif (strlen($senha) < 3) {
        $mensagem = 'error:A senha deve ter pelo menos 3 caracteres.';
    } else {
        // Verificar se username já existe
        $sql = "SELECT id FROM usuarios WHERE username = ?";
        $stmt = $pdo->prepare($sql);
        $stmt->execute([$username]);

        if ($stmt->rowCount() > 0) {
            $mensagem = 'error:Este nome de usuário já está em uso.';
        } else {
            // Verificar se email já existe em ambas as tabelas
            $sql = "SELECT id FROM usuarios WHERE email = ? UNION SELECT id FROM administradores WHERE email = ?";
            $stmt = $pdo->prepare($sql);
            $stmt->execute([$email, $email]);

            if ($stmt->rowCount() > 0) {
                $mensagem = 'error:Este email já está em uso.';
            } else {
                // Criptografar a senha
                $senha_hash = password_hash($senha, PASSWORD_DEFAULT);

                // Inserir usuário
                $sql = "INSERT INTO usuarios (nome, username, email, senha, data_cadastro) VALUES (?, ?, ?, ?, NOW())";
                $stmt = $pdo->prepare($sql);

                if ($stmt->execute([$nome, $username, $email, $senha_hash])) {
                    $mensagem = 'success:Cadastro realizado com sucesso! <a href="login.php">Faça login aqui</a>.';
                } else {
                    $mensagem = 'error:Erro ao cadastrar. Por favor, tente novamente.';
                }
            }
        }
    }
}
?>

<!DOCTYPE html>
<html lang="pt-br">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Cadastro - Sonare</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link rel="stylesheet" href="css/cadastro.css">
</head>
<body>
    <div class="particles"></div>
    <div class="container">
        <div class="header">
            <div class="music-icon">🎵</div>
            <h1>Cadastro de Usuário</h1>
            <p>Crie sua conta gratuita</p>
        </div>
        
        <div class="form-container">
            <?php 
            if (!empty($mensagem)) {
                $parts = explode(":", $mensagem, 2);
                $tipo = $parts[0];
                $msg = $parts[1] ?? '';
                echo '<div class="mensagem '.$tipo.'">'.$msg.'</div>';
            }
            ?>
            
            <form method="POST" action="cadastro.php">
                <div class="form-group">
                    <label for="nome">Nome Completo</label>
                    <input type="text" id="nome" name="nome" class="form-control" value="<?php echo htmlspecialchars($_POST['nome'] ?? ''); ?>" required>
                </div>

                <div class="form-group">
                    <label for="username">Nome de Usuário(Username)</label>
                    <input type="text" id="username" name="username" class="form-control" value="<?php echo htmlspecialchars($_POST['username'] ?? ''); ?>" required>
                </div>

                <div class="form-group">
                    <label for="email">Email</label>
                    <input type="email" id="email" name="email" class="form-control" value="<?php echo htmlspecialchars($_POST['email'] ?? ''); ?>" required>
                </div>
                
                <div class="form-group">
                    <label for="senha">Senha</label>
                    <div class="password-toggle">
                        <input type="password" id="senha" name="senha" class="form-control" required minlength="3">
                        <i class="toggle-icon fas fa-eye" onclick="togglePassword('senha', this)"></i>
                    </div>
                </div>

                <div class="form-group">
                    <label for="confirmar_senha">Confirmar Senha</label>
                    <div class="password-toggle">
                        <input type="password" id="confirmar_senha" name="confirmar_senha" class="form-control" required minlength="3">
                        <i class="toggle-icon fas fa-eye" onclick="togglePassword('confirmar_senha', this)"></i>
                    </div>
                </div>
                
                <button type="submit" class="btn">Cadastrar Usuário</button>
                <a href="login.php" class="btn btn-outline">Voltar</a>
            </form>
            
            <div class="login-link">
                Já tem uma conta? <a href="login.php">Faça login</a>
            </div>
        </div>
    </div>

<script>
    document.addEventListener('DOMContentLoaded', function() {
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
            
            const duration = Math.random() * 20 + 10;
            particle.style.animationDuration = `${duration}s`;
            particle.style.animationDelay = `${Math.random() * 5}s`;
            
            particlesContainer.appendChild(particle);
        }
    });

    function togglePassword(inputId, icon) {
        const input = document.getElementById(inputId);
        const isPassword = input.type === 'password';
        input.type = isPassword ? 'text' : 'password';
        icon.classList.toggle('fa-eye');
        icon.classList.toggle('fa-eye-slash');
    }
</script>
</body>
</html>
