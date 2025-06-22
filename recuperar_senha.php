<?php
require 'includes/conexao.php';
session_start();

$mensagem = '';
$tipo_usuario = isset($_GET['tipo']) ? $_GET['tipo'] : 'usuario';

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $email = filter_input(INPUT_POST, 'email', FILTER_SANITIZE_EMAIL);
    $tipo_usuario = isset($_POST['tipo']) ? $_POST['tipo'] : 'usuario';

    if (empty($email)) {
        $mensagem = 'Por favor, informe seu e-mail.';
    } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $mensagem = 'E-mail inválido.';
    } else {
        try {
            if ($tipo_usuario == 'admin') {
                $sql = "SELECT id, nome FROM administradores WHERE email = ?";
            } else {
                $sql = "SELECT id, nome FROM usuarios WHERE email = ?";
            }

            $stmt = $pdo->prepare($sql);
            $stmt->execute([$email]);

            if ($stmt->rowCount() > 0) {
                $usuario = $stmt->fetch(PDO::FETCH_ASSOC);
                
                $token = bin2hex(random_bytes(32));
                $expiracao = date("Y-m-d H:i:s", strtotime('+1 hour'));
                
                if ($tipo_usuario == 'admin') {
                    $sql_token = "UPDATE administradores SET token_senha = ?, token_expiracao = ? WHERE id = ?";
                } else {
                    $sql_token = "UPDATE usuarios SET token_senha = ?, token_expiracao = ? WHERE id = ?";
                }
                
                $stmt_token = $pdo->prepare($sql_token);
                $stmt_token->execute([$token, $expiracao, $usuario['id']]);
                
                
                $assunto = "Recuperação de Senha - Sonare";
                $mensagem_email = "Olá " . $usuario['nome'] . ",\n\n";
                $mensagem_email .= "Você solicitou a recuperação de senha. Clique no link abaixo para redefinir sua senha:\n\n";
                $mensagem_email .= "http://sonare.com/redefinir_senha.php?token=" . $token . "&tipo=" . $tipo_usuario . "\n\n";
                $mensagem_email .= "Este link expira em 1 hora.\n";
                $mensagem_email .= "Se você não solicitou esta alteração, ignore este e-mail.\n\n";
                $mensagem_email .= "Atenciosamente,\nEquipe Sonare";
                
                
                
                $mensagem = "success:Um e-mail com instruções foi enviado para $email. Verifique sua caixa de entrada.";
            } else {
                $mensagem = 'error:E-mail não encontrado em nosso sistema.';
            }
        } catch (PDOException $e) {
            $mensagem = 'error:Erro ao processar sua solicitação.';
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
    <title>Recuperar Senha - Sonare</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link rel="stylesheet" href="css/login.css">
    <link rel="stylesheet" href="css/recuperar_senha.css">
</head>
<body>
    <div class="particles"></div>
    <div class="container">
        <div class="header">
            <div class="music-icon">🎵</div>
            <h1>Recuperar Senha</h1>
            <p>Redefina sua senha</p>
        </div>
        
        <div class="form-container">
            <?php if (!empty($mensagem_texto)): ?>
                <div class="mensagem <?php echo $mensagem_tipo; ?>"><?php echo $mensagem_texto; ?></div>
            <?php endif; ?>
            
            <div class="login-type-buttons">
                <button class="login-type-btn <?php echo $tipo_usuario == 'usuario' ? 'active' : ''; ?>" 
                        onclick="setLoginType('usuario')">
                    Usuário
                </button>
                <button class="login-type-btn <?php echo $tipo_usuario == 'admin' ? 'active' : ''; ?>" 
                        onclick="setLoginType('admin')">
                    Administrador
                </button>
            </div>
            
            <div class="instructions">
                Digite seu e-mail cadastrado para receber um link de redefinição de senha.
            </div>
            
            <form method="POST" action="recuperar_senha.php">
                <input type="hidden" name="tipo" value="<?php echo $tipo_usuario; ?>">
                
                <div class="form-group">
                    <label for="email">E-mail</label>
                    <input type="email" id="email" name="email" class="form-control" required>
                </div>
                
                <button type="submit" class="btn">Enviar Link</button>
            </form>
            
            <div class="login-link">
                <a href="login.php">Voltar para o login</a>
            </div>
        </div>
    </div>

    <script>
        function setLoginType(tipo) {
            // Atualizar botões ativos
            document.querySelectorAll('.login-type-btn').forEach(btn => {
                btn.classList.remove('active');
            });
            event.currentTarget.classList.add('active');
            
            // Atualizar o tipo no formulário
            document.querySelector('input[name="tipo"]').value = tipo;
            
            // Atualizar URL sem recarregar
            history.pushState(null, null, '?tipo=' + tipo);
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