<?php
require 'includes/conexao.php';
session_start();

$mensagem = '';
$token = isset($_GET['token']) ? $_GET['token'] : '';
$tipo_usuario = isset($_GET['tipo']) ? $_GET['tipo'] : 'usuario';

if (empty($token)) {
    header('Location: recuperar_senha.php');
    exit();
}

// Verificar se o token é válido
try {
    if ($tipo_usuario == 'admin') {
        $sql = "SELECT id FROM administradores WHERE token_senha = ? AND token_expiracao > NOW()";
    } else {
        $sql = "SELECT id FROM usuarios WHERE token_senha = ? AND token_expiracao > NOW()";
    }

    $stmt = $pdo->prepare($sql);
    $stmt->execute([$token]);

    if ($stmt->rowCount() == 0) {
        $mensagem = 'error:Link inválido ou expirado. Solicite um novo link.';
        $token_valido = false;
    } else {
        $usuario = $stmt->fetch(PDO::FETCH_ASSOC);
        $usuario_id = $usuario['id'];
        $token_valido = true;
    }
} catch (PDOException $e) {
    $mensagem = 'error:Erro ao verificar o token.';
    $token_valido = false;
}

if ($_SERVER['REQUEST_METHOD'] == 'POST' && $token_valido) {
    $nova_senha = $_POST['nova_senha'];
    $confirmar_senha = $_POST['confirmar_senha'];

    if (empty($nova_senha) || empty($confirmar_senha)) {
        $mensagem = 'error:Por favor, preencha todos os campos.';
    } elseif ($nova_senha != $confirmar_senha) {
        $mensagem = 'error:As senhas não coincidem.';
    } elseif (strlen($nova_senha) < 8) {
        $mensagem = 'error:A senha deve ter pelo menos 8 caracteres.';
    } else {
        try {
            $senha_hash = password_hash($nova_senha, PASSWORD_DEFAULT);
            
            if ($tipo_usuario == 'admin') {
                $sql = "UPDATE administradores SET senha = ?, token_senha = NULL, token_expiracao = NULL WHERE id = ?";
            } else {
                $sql = "UPDATE usuarios SET senha = ?, token_senha = NULL, token_expiracao = NULL WHERE id = ?";
            }
            
            $stmt = $pdo->prepare($sql);
            $stmt->execute([$senha_hash, $usuario_id]);
            
            $mensagem = 'success:Senha redefinida com sucesso! Você já pode fazer login com sua nova senha.';
            $token_valido = false; 
        } catch (PDOException $e) {
            $mensagem = 'error:Erro ao redefinir a senha.';
        }
    }
}


$mensagem_tipo = '';
$mensagem_texto = '';
if (!empty($mensagem)) {
    $parts = explode(':', $mensagem, 2);
    $mensagem_tipo = count($parts) > 1 ? $parts[0] : 'error';
    $mensagem_texto = count($parts) > 1 ? $parts[1] : $mensagem;
}
?>

<!DOCTYPE html>
<html lang="pt-br">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Redefinir Senha - Sonare</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link rel="stylesheet" href="css/login.css">
    <style>
        .password-toggle {
            position: relative;
        }
        
        .toggle-icon {
            position: absolute;
            right: 10px;
            top: 35px;
            cursor: pointer;
            color: #666;
        }
    </style>
</head>
<body>
    <div class="particles"></div>
    <div class="container">
        <div class="header">
            <div class="music-icon">🎵</div>
            <h1>Redefinir Senha</h1>
            <p>Crie uma nova senha</p>
        </div>
        
        <div class="form-container">
            <?php if (!empty($mensagem_texto)): ?>
                <div class="mensagem <?php echo $mensagem_tipo; ?>"><?php echo $mensagem_texto; ?></div>
            <?php endif; ?>
            
            <?php if ($token_valido): ?>
                <form method="POST" action="redefinir_senha.php?token=<?php echo $token; ?>&tipo=<?php echo $tipo_usuario; ?>">
                    <div class="form-group password-toggle">
                        <label for="nova_senha">Nova Senha</label>
                        <input type="password" id="nova_senha" name="nova_senha" class="form-control" required minlength="8">
                        <i class="toggle-icon fas fa-eye" onclick="togglePassword('nova_senha')"></i>
                    </div>
                    
                    <div class="form-group password-toggle">
                        <label for="confirmar_senha">Confirmar Senha</label>
                        <input type="password" id="confirmar_senha" name="confirmar_senha" class="form-control" required minlength="8">
                        <i class="toggle-icon fas fa-eye" onclick="togglePassword('confirmar_senha')"></i>
                    </div>
                    
                    <button type="submit" class="btn">Redefinir Senha</button>
                </form>
            <?php else: ?>
                <div class="login-link">
                    <a href="recuperar_senha.php" class="btn">Solicitar novo link</a>
                </div>
            <?php endif; ?>
            
            <div class="login-link">
                <a href="login.php">Voltar para o login</a>
            </div>
        </div>
    </div>

    <script>
        function togglePassword(inputId) {
            const input = document.getElementById(inputId);
            const icon = input.nextElementSibling;
            if (input.type === 'password') {
                input.type = 'text';
                icon.classList.replace('fa-eye', 'fa-eye-slash');
            } else {
                input.type = 'password';
                icon.classList.replace('fa-eye-slash', 'fa-eye');
            }
        }
        
        // Efeito de partículas 
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
                particle.style.animationDuration = `${Math.random() * 20 + 10}s`;
                particle.style.animationDelay = `${Math.random() * 5}s`;
                
                particlesContainer.appendChild(particle);
            }
        });
    </script>
</body>
</html>