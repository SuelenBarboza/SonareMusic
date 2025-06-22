# 🎶 Sonare

**Sonare** é um sistema de gerenciamento de músicas feito em PHP, com funcionalidades para cadastro, upload, reprodução e organização de músicas e playlists.

---

## 🚀 Funcionalidades

- Cadastro e listagem de músicas
- Upload de arquivos de áudio (.mp3)
- Player embutido para reprodução
- Cadastro de artistas e gêneros
- Organização por playlists
- Sistema simples e intuitivo

---

## 🖥 Tecnologias utilizadas

- PHP
- MySQL
- HTML5, CSS3, JavaScript
- FontAwesome para ícones

---

## 📂 Estrutura do projeto

/Sonare
├── admin/
├── css/
├── includes/
├── uploads/ # Pasta para arquivos de música (não versionada no Git)
│ ├── artistas/
│ ├── capas/
│ └── musicas/
├── SQL/ # Scripts SQL para banco de dados (não versionada)
├── index.php
├── login.php
├── painel_usuario.php
└── funcoes.php


---

## ⚙️ Como rodar o projeto

1. Clone o repositório:

   ```bash
git clone https://github.com/SuelenBarboza/SonareMusic.git
   
Coloque a pasta dentro do diretório htdocs do XAMPP.

Crie o banco de dados no MySQL e importe os scripts SQL (localmente).

Abra o XAMPP e inicie Apache e MySQL.

Acesse no navegador: http://localhost/SonareMusic

📌 Observações
A pasta uploads não está no repositório para evitar arquivos grandes.

Adicione suas proprias musicas manualmente ou crie uma api para adicionar musicas
🤝 Autor
Desenvolvido por Suélen Barboza
🔗 https://github.com/SuelenBarboza



