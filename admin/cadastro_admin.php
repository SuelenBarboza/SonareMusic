<?php
require('../includes/conexao.php');
$pdo = getDBConnection();

session_start();

$mensagem = '';

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $nome = trim($_POST['nome']);
    $username = trim($_POST['username']);
    $email = trim($_POST['email']);
    $senha = $_POST['senha'];
    $confirmar_senha = $_POST['confirmar_senha'];
    $nivel_acesso = $_POST['nivel_acesso'];

    
    if (empty($nome) || empty($email) || empty($senha)) {
        $mensagem = 'Por favor, preencha todos os campos.';
    } elseif ($senha !== $confirmar_senha) {
        $mensagem = 'As senhas não coincidem.';
    } elseif (strlen($senha) < 3) {
        $mensagem = 'A senha deve ter pelo menos 3 caracteres.';
    } else {
        
        $sql = "SELECT id FROM administradores WHERE email = ? UNION SELECT id FROM usuarios WHERE email = ?";
        $stmt = $pdo->prepare($sql);
        $stmt->execute([$email, $email]);
        
        if ($stmt->rowCount() > 0) {
            $mensagem = 'Este email já está em uso.';
        } else {
            
            $senha_hash = password_hash($senha, PASSWORD_DEFAULT);

            $sql = "INSERT INTO administradores (nome, username, email, senha, nivel_acesso, data_cadastro) 
                    VALUES (?, ?, ?, ?, ?, NOW())";
            $stmt = $pdo->prepare($sql);

            if ($stmt->execute([$nome, $username, $email, $senha_hash, $nivel_acesso])) {
                $mensagem = 'success:Administrador cadastrado com sucesso!';
            } else {
                $mensagem = 'Erro ao cadastrar. Por favor, tente novamente.';
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
    <title>Cadastrar Administrador - Sonare</title>
    <link rel="stylesheet" href="../css/cadastro_admin.css">


</head>
<body>
    <div class="particles"></div>
    <div class="container">
        <div class="header">
            <div class="music-icon">🎵</div>
            <h1>Cadastro de Administrador</h1>
            <p>Crie sua conta </p>
        </div>
        
        <?php if (!empty($mensagem)): ?>
            <div class="mensagem <?php echo strpos($mensagem, 'success:') === 0 ? 'success' : 'error'; ?>">
                <?php echo str_replace('success:', '', $mensagem); ?>
            </div>
        <?php endif; ?>
        
        <form method="POST" action="cadastro_admin.php">
            <div class="form-group">
                <label for="nome">Nome Completo</label>
                <input type="text" id="nome" name="nome" class="form-control" required>
            </div>

                <div class="form-group">
                    <label for="username">Nome de Usuário(Username)</label>
                    <input type="text" id="username" name="username" class="form-control" required>
                </div>
                    
            
            <div class="form-group">
                <label for="email">Email</label>
                <input type="email" id="email" name="email" class="form-control" required>
            </div>
            
            <div class="form-group">
                <label for="senha">Senha </label>
                <input type="password" id="senha" name="senha" class="form-control" required minlength="3">
            </div>
            
            <div class="form-group">
                <label for="confirmar_senha">Confirmar Senha</label>
                <input type="password" id="confirmar_senha" name="confirmar_senha" class="form-control" required minlength="3">
            </div>
            
            <!-- <div class="form-group">
                <label for="nivel_acesso">Nível de Acesso</label>
                <select id="nivel_acesso" name="nivel_acesso" class="form-control" required>
                    <option value="basico">Básico</option>
                    <option value="medio">Médio</option>
                    <option value="alto">Alto</option>
                </select>
            </div> -->
            
            <button type="submit" class="btn">Cadastrar Administrador</button>
            <a href="../login.php" class="btn btn-outline">Voltar</a>

            <div class="login-link">
                Já tem uma conta? <a href="../login.php">Faça login</a>
            </div>
        </form>
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
                particle.style.animationDuration = `${Math.random() * 20 + 10}s`;
                particle.style.animationDelay = `${Math.random() * 5}s`;
                
                particlesContainer.appendChild(particle);
            }
        });
    </script>
</body>
</html>