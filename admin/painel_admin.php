<?php
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

require_once(__DIR__ . "/../includes/conexao.php");
require_once(__DIR__ . "/../includes/funcoes.php");

session_start();

if (!isset($_SESSION['admin_logado'])) {
  header('Location: login.php');
  exit();
}


if (isset($_GET['excluir'])) {
  if (excluirMusica($_GET['excluir'])) {
    echo "<script>Swal.fire('Sucesso!', 'Música excluída com sucesso!', 'success');</script>";
  } else {
    echo "<script>Swal.fire('Erro!', 'Falha ao excluir música.', 'error');</script>";
  }
}


$musicas = listarMusicasAdmin();
$artistas = listarArtistas();
$generos = listarGeneros();

// Preparar dados para o JavaScript
$songsData = [];
foreach ($musicas as $musica) {

  $capaPath = $musica['capa'] ?
    '../uploads/capas/' . rawurlencode(basename($musica['capa'])) :
    '../uploads/capas/default-cover.jpg';


  $audioPath = '../uploads/musicas/' . rawurlencode(basename($musica['arquivo']));

  $songsData[] = [
    'id' => $musica['id'],
    'title' => htmlspecialchars($musica['titulo'], ENT_QUOTES, 'UTF-8'),
    'artist' => htmlspecialchars($musica['artista_nome'], ENT_QUOTES, 'UTF-8'),
    'artistId' => $musica['artista_id'],
    'genre' => htmlspecialchars($musica['genero_nome'], ENT_QUOTES, 'UTF-8'),
    'genreId' => $musica['genero_id'],
    'album' => isset($musica['album_nome']) ? htmlspecialchars($musica['album_nome'], ENT_QUOTES, 'UTF-8') : '',
    'cover' => $capaPath,
    'audioUrl' => $audioPath,
    'duration' => !empty($musica['duracao']) ? substr($musica['duracao'], 0, 5) : '3:45'
  ];
}

?>
<!DOCTYPE html>
<html lang="pt-br">

<head>
  <meta charset="UTF-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1" />
  <title>SonareMusic - Painel Administrativo</title>
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/limonte-sweetalert2/11.4.24/sweetalert2.min.css">
  <link rel="stylesheet" href="../css/painel_admin.css">
</head>

<body>

  <div class="sidebar" id="sidebar">
    <h1>SonareMusic</h1>
    <ul class="nav-links">
      <li><a href="#" class="active"><i class="fas fa-home icon"></i> Home</a></li>
      <li><a href="#"><i class="fas fa-music icon"></i> Minhas Músicas</a></li>
      <li><a href="#"><i class="fas fa-cog icon"></i> Configurações</a></li>
      <li><a href="../logout.php"><i class="fas fa-sign-out-alt icon"></i> Sair</a></li>

    </ul>

    <div class="current-playing">
      <div class="title" id="sidebar-track-title">Nenhuma música selecionada</div>
      <div class="artist" id="sidebar-track-artist">—</div>
    </div>
  </div>

  <div class="main-content">
    <div class="header">
      <div class="welcome-container">
        <div class="welcome">Bem-vindo Administrador!</div>
        <div class="subtitle">Gerencie sua biblioteca musical</div>
      </div>

      <div class="user-menu">
        <div class="user-avatar" id="user-avatar">
          <i class="fas fa-user"></i>
        </div>
      </div>
    </div>

    <div class="content-header">
      <h2>Minhas Músicas</h2>
      <div class="header-buttons">
        <button class="btn-add-artist" id="add-artist-btn">
          <i class="fas fa-user-plus"></i> Adicionar Artista
        </button>

        <button type="button" class="btn-add-genero" id="add-genero-btn">
          <i class="fas fa-plus"></i> Novo Gênero
        </button>

        <button class="btn-add-music" id="add-music-btn">
          <i class="fas fa-plus"></i> Adicionar Música
        </button>
      </div>
    </div>



    <!--filtros -->
    <div class="filter-section">
      <div class="search-box">
        <i class="fas fa-search"></i>
        <input type="text" id="search-input" placeholder="Buscar música, artista ou álbum...">
      </div>

      <div class="filter-controls">
        <div class="filter-group">
          <label for="genre-filter">Filtrar por Gênero:</label>
          <select id="genre-filter" class="filter-select">
            <option value="">Todos os gêneros</option>
            <?php foreach ($generos as $genero): ?>
              <option value="<?= htmlspecialchars($genero['id'], ENT_QUOTES, 'UTF-8') ?>">
                <?= htmlspecialchars($genero['nome'], ENT_QUOTES, 'UTF-8') ?>
              </option>
            <?php endforeach; ?>
          </select>
        </div>

        <div class="filter-group">
          <label for="sort-by">Ordenar por:</label>
          <select id="sort-by" class="filter-select">
            <option value="title-asc">Título (A-Z)</option>
            <option value="title-desc">Título (Z-A)</option>
            <option value="artist-asc">Artista (A-Z)</option>
            <option value="artist-desc">Artista (Z-A)</option>
            <option value="genre-asc">Gênero (A-Z)</option>
            <option value="genre-desc">Gênero (Z-A)</option>
            <option value="id-asc">Mais antigas</option>
            <option value="id-desc">Mais recentes</option>
          </select>
        </div>
      </div>
    </div>

    <div class="music-grid" id="music-grid">
      <?php if (empty($musicas)): ?>
        <div class="empty-state">
          <i class="fas fa-music"></i>
          <p>Nenhuma música cadastrada ainda</p>
          <button class="btn-add-music" id="add-first-music-btn">
            <i class="fas fa-plus"></i> Adicionar Primeira Música
          </button>
        </div>
      <?php endif; ?>
    </div>

    <!-- Reprodutor/Player -->
    <div class="player" id="player">
      <div class="cover">
        <div class="default-cover">
          <i class="fas fa-music"></i>
        </div>
        <div class="cover-overlay">
          <i class="fas fa-expand"></i>
        </div>
      </div>
      <div class="info">
        <div class="title" id="track-title">Nenhuma música selecionada</div>
        <div class="artist" id="track-artist">—</div>
      </div>
      <div class="controls">
        <button class="btn-control" id="prev-btn" aria-label="Anterior">
          <i class="fas fa-step-backward"></i>
        </button>

        <button class="btn-control" id="shuffle-btn" aria-label="Embaralhar">
          <i class="fas fa-random"></i>
        </button>

        <button class="btn-play" id="play-pause" aria-label="Play/Pause">
          <i class="fas fa-play" id="play-icon"></i>
          <i class="fas fa-pause" id="pause-icon" style="display: none;"></i>
        </button>

        <button class="btn-control" id="repeat-btn" aria-label="Repetir">
          <i class="fas fa-redo"></i>
        </button>

        <button class="btn-control" id="next-btn" aria-label="Próxima">
          <i class="fas fa-step-forward"></i>
        </button>

        <div class="progress-wrapper">
          <div class="progress-container" id="progress-container" title="Clique para avançar">
            <div class="progress" id="progress"></div>
          </div>
          <div class="time" id="current-time">0:00</div>
          <div class="time" id="duration">0:00</div>
        </div>
      </div>

      <div class="volume-control">
        <i class="fas fa-volume-down"></i>
        <input type="range" class="volume-slider" id="volume" min="0" max="1" step="0.01" value="0.7">
        <i class="fas fa-volume-up"></i>
      </div>
    </div>
  </div>

  <!-- Modal para adicionar/editar música -->
  <div class="modal" id="music-modal">
    <div class="modal-content">
      <div class="modal-header">
        <h3 class="modal-title" id="modal-title">Adicionar Música</h3>
        <button class="close-modal" id="close-modal">&times;</button>
      </div>


      <div class="modal-body">
        <form id="music-form" enctype="multipart/form-data">
          <input type="hidden" id="song-id" name="id">

          <div class="form-row">
            <div class="cover-column">
              <div class="cover-preview" id="cover-preview">
                <i class="fas fa-music"></i>
              </div>
              <label for="cover-upload" class="cover-upload-label">
                <i class="fas fa-image"></i> Selecionar Capa (Opcional)
              </label>
              <input type="file" id="cover-upload" name="capa" accept="image/*" class="cover-upload">
            </div>


            <div class="form-column">
              <div class="form-group">
                <label for="song-title" class="form-label">Nome da Música *</label>
                <input type="text" id="song-title" name="titulo" class="form-input" required
                  placeholder="Digite o nome da música">
              </div>

              <div class="form-group">
                <label for="song-artist" class="form-label">Artista *</label>
                <select id="song-artist" name="artista_id" class="form-input" required>
                  <option value="">Selecione um artista</option>
                  <?php foreach ($artistas as $artista): ?>
                    <option value="<?= htmlspecialchars($artista['id'], ENT_QUOTES, 'UTF-8') ?>">
                      <?= htmlspecialchars($artista['nome'], ENT_QUOTES, 'UTF-8') ?>
                    </option>
                  <?php endforeach; ?>
                </select>
              </div>

              <div class="form-group">
                <label for="song-genre" class="form-label">Gênero *</label>
                <select id="song-genre" name="genero_id" class="form-input" required>
                  <option value="">Selecione um gênero</option>
                  <?php foreach ($generos as $genero): ?>
                    <option value="<?= htmlspecialchars($genero['id'], ENT_QUOTES, 'UTF-8') ?>">
                      <?= htmlspecialchars($genero['nome'], ENT_QUOTES, 'UTF-8') ?>
                    </option>
                  <?php endforeach; ?>
                </select>
              </div>

              <div class="form-group">
                <label for="song-album" class="form-label">Álbum (Opcional)</label>
                <input type="text" id="song-album" name="album" class="form-input" placeholder="Digite o nome do álbum">
              </div>

              <div class="form-group">
                <label for="song-file" class="form-label">Arquivo da Música *</label>
                <input type="file" id="song-file" name="arquivo_audio" accept="audio/*" class="form-input"
                  <?= empty($musicas) ? 'required' : '' ?>>
                <small class="form-hint">Formatos suportados: MP3, WAV, OGG</small>
              </div>
            </div>
          </div>
        </form>
      </div>

      <div class="modal-footer">
        <button type="button" class="btn btn-secondary" id="cancel-btn">Cancelar</button>
        <button type="submit" class="btn btn-primary" form="music-form">Salvar Música</button>
      </div>
    </div>
  </div>


  <!-- Modal para adicionar artista -->
  <div class="modal" id="artist-modal">
    <div class="modal-content">
      <div class="modal-header">
        <h3 class="modal-title">Adicionar Artista</h3>
        <button class="close-modal" id="close-artist-modal">&times;</button>
      </div>

      <div class="modal-body">
        <form id="artist-form" enctype="multipart/form-data">
          <div class="form-group">
            <label for="artist-name" class="form-label">Nome do Artista *</label>
            <input type="text" id="artist-name" name="nome" class="form-input" required
              placeholder="Digite o nome do artista">
          </div>

          <div class="form-group">
            <label for="artist-bio" class="form-label">Biografia (Opcional)</label>
            <textarea id="artist-bio" name="biografia" class="form-input"
              placeholder="Digite uma breve biografia"></textarea>
          </div>

          <div class="form-group">
            <label for="artist-photo" class="form-label">Foto (Opcional)</label>
            <input type="file" id="artist-photo" name="foto" accept="image/*" class="form-input">
          </div>

          <div class="modal-footer">
            <button type="button" class="btn btn-secondary" id="cancel-artist-btn">Cancelar</button>
            <button type="submit" class="btn btn-primary">Salvar Artista</button>
          </div>
        </form>
      </div>
    </div>
  </div>


  <!-- Modal para adicionar gênero -->
  <div class="modal" id="genre-modal">
    <div class="modal-content">
      <div class="modal-header">
        <h3 class="modal-title">Adicionar Gênero</h3>
        <button class="close-modal" id="close-genre-modal">&times;</button>
      </div>

      <div class="modal-body">
        <form id="genre-form">
          <div class="form-group">
            <label for="genre-name" class="form-label">Nome do Gênero *</label>
            <input type="text" id="genre-name" name="nome" class="form-input" required
              placeholder="Digite o nome do gênero">
          </div>

          <div class="form-group">
            <label for="genre-description" class="form-label">Descrição (Opcional)</label>
            <textarea id="genre-description" name="descricao" class="form-input"
              placeholder="Digite uma breve descrição"></textarea>
          </div>

          <div class="modal-footer">
            <button type="button" class="btn btn-secondary" id="cancel-genre-btn">Cancelar</button>
            <button type="submit" class="btn btn-primary">Salvar Gênero</button>
          </div>
        </form>
      </div>
    </div>
  </div>

  <div class="menu-toggle" id="menu-toggle">
    <i class="fas fa-bars"></i>
  </div>

  
  <script src="https://cdnjs.cloudflare.com/ajax/libs/limonte-sweetalert2/11.4.24/sweetalert2.min.js"></script>

  <script>
    // Elementos do DOM
    const playPauseBtn = document.getElementById('play-pause');
    const playIcon = document.getElementById('play-icon');
    const pauseIcon = document.getElementById('pause-icon');
    const prevBtn = document.getElementById('prev-btn');
    const nextBtn = document.getElementById('next-btn');
    const shuffleBtn = document.getElementById('shuffle-btn');
    const repeatBtn = document.getElementById('repeat-btn');
    const progress = document.getElementById('progress');
    const progressContainer = document.getElementById('progress-container');
    const currentTimeEl = document.getElementById('current-time');
    const durationEl = document.getElementById('duration');
    const volumeSlider = document.getElementById('volume');
    const player = document.getElementById('player');
    const cover = document.querySelector('.player .cover');
    const menuToggle = document.getElementById('menu-toggle');
    const sidebar = document.getElementById('sidebar');
    const musicGrid = document.getElementById('music-grid');
    const sidebarTrackTitle = document.getElementById('sidebar-track-title');
    const sidebarTrackArtist = document.getElementById('sidebar-track-artist');

    // Modais
    const musicModal = document.getElementById('music-modal');
    const modalTitle = document.getElementById('modal-title');
    const musicForm = document.getElementById('music-form');
    const songIdInput = document.getElementById('song-id');
    const songTitleInput = document.getElementById('song-title');
    const songArtistInput = document.getElementById('song-artist');
    const songGenreInput = document.getElementById('song-genre');
    const songAlbumInput = document.getElementById('song-album');
    const songFileInput = document.getElementById('song-file');
    const coverUpload = document.getElementById('cover-upload');
    const coverPreview = document.getElementById('cover-preview');
    const closeModalBtn = document.getElementById('close-modal');
    const cancelBtn = document.getElementById('cancel-btn');
    const addMusicBtn = document.getElementById('add-music-btn');
    const addFirstMusicBtn = document.getElementById('add-first-music-btn');
    const addGeneroBtn = document.getElementById('add-genero-btn');

    const artistModal = document.getElementById('artist-modal');
    const artistForm = document.getElementById('artist-form');
    const artistNameInput = document.getElementById('artist-name');
    const artistBioInput = document.getElementById('artist-bio');
    const artistPhotoInput = document.getElementById('artist-photo');
    const closeArtistModalBtn = document.getElementById('close-artist-modal');
    const cancelArtistBtn = document.getElementById('cancel-artist-btn');
    const addArtistBtn = document.getElementById('add-artist-btn');

    const genreModal = document.getElementById('genre-modal');
    const genreForm = document.getElementById('genre-form');
    const genreNameInput = document.getElementById('genre-name');
    const genreDescriptionInput = document.getElementById('genre-description');
    const closeGenreModalBtn = document.getElementById('close-genre-modal');
    const cancelGenreBtn = document.getElementById('cancel-genre-btn');

    // Estado do player
    const audio = new Audio();
    let songs = <?php echo json_encode($songsData); ?>;
    let currentSongIndex = 0;
    let isShuffle = false;
    let isRepeat = false;
    let isPlaying = false;
    let isEditMode = false;


    console.log('Lista de músicas:', songs);
    // Inicializar a aplicação
    function init() {
      console.log('Player inicializado'); 
      
      setupEventListeners();
      updateMusicGrid();

      
      if (songs.length > 0) {
        loadSong(0);
      }

      audio.volume = volumeSlider.value;
    }


    audio.addEventListener('error', function (e) {
      console.error('Erro no elemento de áudio:', e);
      console.error('Código de erro:', audio.error.code);
      console.error('Mensagem:', audio.error.message);

      setTimeout(() => {
        nextSong();
      }, 2000);
    });

    
    function formatTime(seconds) {
      const min = Math.floor(seconds / 60);
      const sec = Math.floor(seconds % 60);
      return `${min}:${sec < 10 ? '0' + sec : sec}`;
    }

    
    function updateMusicGrid() {
      musicGrid.innerHTML = '';

      if (songs.length === 0) {
        musicGrid.innerHTML = `
              <div class="empty-state">
                  <i class="fas fa-music"></i>
                  <p>Nenhuma música cadastrada ainda</p>
                  <button class="btn-add-music" id="add-first-music-btn">
                      <i class="fas fa-plus"></i> Adicionar Primeira Música
                  </button>
              </div>
          `;
        document.getElementById('add-first-music-btn').addEventListener('click', () => openModal());
        return;
      }

      songs.forEach((song, index) => {
        const card = document.createElement('div');
        card.className = 'music-card';
        if (index === currentSongIndex && isPlaying) {
          card.classList.add('active');
        }

        
        const coverPath = getCorrectCoverPath(song.cover);

        card.innerHTML = `
              <div class="music-cover" style="background-image: url('${coverPath}')">
                  <div class="music-overlay">
                      <div class="play-btn">
                          <i class="fas fa-play"></i>
                      </div>
                  </div>
                  <div class="card-actions">
                      <button class="action-btn edit-btn" data-id="${song.id}">
                          <i class="fas fa-edit"></i>
                      </button>
                      <button class="action-btn delete-btn" data-id="${song.id}">
                          <i class="fas fa-trash"></i>
                      </button>
                  </div>
              </div>
              <div class="music-info">
                  <div class="music-title">${song.title}</div>
                  <div class="music-artist">${song.artist}</div>
                  <div class="music-genre">${song.genre}</div>
                  ${song.album ? `<div class="music-album">${song.album}</div>` : ''}
              </div>
          `;

        card.addEventListener('click', (e) => {
          if (!e.target.closest('.action-btn')) {
            playSong(index);
          }
        });

        musicGrid.appendChild(card);
      });

      
      setupCardActions();
    }


    function getCorrectCoverPath(originalPath) {
      if (!originalPath) {
        return '../uploads/capas/default-cover.jpg';
      }


      const cleanPath = originalPath.replace(/^\.\.\//, '');


      if (/^https?:\/\//.test(cleanPath)) {
        return cleanPath;
      }


      return cleanPath.startsWith('uploads/') ? `../${cleanPath}` : cleanPath;
    }

    
    function setupCardActions() {
      document.querySelectorAll('.edit-btn').forEach(btn => {
        btn.addEventListener('click', (e) => {
          e.stopPropagation();
          const id = btn.getAttribute('data-id');
          const index = songs.findIndex(song => song.id == id);
          if (index !== -1) {
            openModal(index);
          }
        });
      });

      document.querySelectorAll('.delete-btn').forEach(btn => {
        btn.addEventListener('click', (e) => {
          e.stopPropagation();
          const id = btn.getAttribute('data-id');
          deleteSong(id);
        });
      });
    }

    
    document.querySelectorAll('.edit-btn').forEach(btn => {
      btn.addEventListener('click', (e) => {
        e.stopPropagation();
        const id = btn.getAttribute('data-id');
        const index = songs.findIndex(song => song.id == id);
        if (index !== -1) {
          openModal(index);
        }
      });
    });

    document.querySelectorAll('.delete-btn').forEach(btn => {
      btn.addEventListener('click', (e) => {
        e.stopPropagation();
        const id = btn.getAttribute('data-id');
        deleteSong(id);
      });
    });


    function loadSong(index) {
      if (songs.length === 0 || index < 0 || index >= songs.length) return;

      currentSongIndex = index;
      const song = songs[currentSongIndex];

      audio.src = song.audioUrl;
      audio.load(); 

      console.log('Carregando música:', song.title, 'Caminho:', song.audioUrl);

      document.getElementById('track-title').textContent = song.title;
      document.getElementById('track-artist').textContent = song.artist;
      sidebarTrackTitle.textContent = song.title;
      sidebarTrackArtist.textContent = song.artist;

      let coverPath;

      if (song.cover) {
        if (song.cover.startsWith('http')) {
          coverPath = song.cover;
        }
        else if (song.cover.startsWith('uploads/')) {
          coverPath = '../' + song.cover;
        }
        else if (song.cover.startsWith('../')) {
          coverPath = song.cover;
        }
        else {
          coverPath = '../uploads/capas/' + song.cover;
        }
      } else {
        coverPath = '../uploads/capas/default-cover.jpg';
      }

      cover.style.backgroundImage = `url('${coverPath}')`;
      cover.querySelector('.default-cover').style.display = 'none';

      updateMusicGrid();

      audio.addEventListener('error', (e) => {
        console.error('Erro ao carregar áudio:', e);
        Swal.fire('Erro', 'Não foi possível carregar o arquivo de áudio: ' + song.audioUrl, 'error');
        setTimeout(nextSong, 2000);
      }, { once: true });

      audio.addEventListener('canplaythrough', () => {
        durationEl.textContent = formatTime(audio.duration);
        if (isPlaying) {
          audio.play().catch(e => {
            console.error('Erro ao reproduzir:', e);
            Swal.fire('Erro', 'Não foi possível iniciar a reprodução', 'error');
          });
        }
      }, { once: true });
    }

    
    function normalizeCoverPath(coverPath) {
      if (!coverPath) return '../uploads/capas/default-cover.jpg';

      if (coverPath.startsWith('http')) return coverPath;

      let normalized = coverPath.replace(/^(\.\.\/)+/, '../');

      if (normalized.includes('uploads/') && !normalized.startsWith('../')) {
        normalized = '../' + normalized;
      }

      return normalized;
    }


    
    function playSong(index = currentSongIndex) {
      console.log('Tentando tocar música, índice:', index);
      if (songs.length === 0) return;

      if (index !== currentSongIndex) {
        loadSong(index);
      }

      audio.play()
        .then(() => {
          console.log('Música tocando');
          player.classList.add('playing');
          playIcon.style.display = 'none';
          pauseIcon.style.display = 'block';
          isPlaying = true;
        })
        .catch(e => {
          console.error('Erro ao reproduzir:', e);
          Swal.fire('Erro', 'Não foi possível iniciar a reprodução: ' + e.message, 'error');
        });
    }

    function pauseSong() {
      console.log('Pausando música');
      player.classList.remove('playing');
      audio.pause();
      playIcon.style.display = 'block';
      pauseIcon.style.display = 'none';
      isPlaying = false;
    }

    
    function nextSong() {
      if (songs.length === 0) return;

      if (isShuffle) {
        currentSongIndex = Math.floor(Math.random() * songs.length);
      } else {
        currentSongIndex = (currentSongIndex + 1) % songs.length;
      }

      loadSong(currentSongIndex);
      if (isPlaying) {
        playSong();
      }
    }

    
    function prevSong() {
      if (songs.length === 0) return;

      currentSongIndex = (currentSongIndex - 1 + songs.length) % songs.length;
      loadSong(currentSongIndex);
      if (isPlaying) {
        playSong();
      }
    }

    
    function updateProgress(e) {
      const { duration, currentTime } = e.srcElement;
      const progressPercent = (currentTime / duration) * 100;
      progress.style.width = `${progressPercent}%`;
      currentTimeEl.textContent = formatTime(currentTime);
    }

    
    function setProgress(e) {
      const width = this.clientWidth;
      const clickX = e.offsetX;
      const duration = audio.duration;
      audio.currentTime = (clickX / width) * duration;
    }

    
    function setVolume() {
      audio.volume = this.value;
    }

    
    function openModal(editIndex = null) {
      isEditMode = editIndex !== null;

      if (isEditMode) {
        modalTitle.textContent = 'Editar Música';
        const song = songs[editIndex];

        songIdInput.value = song.id;
        songTitleInput.value = song.title;
        songArtistInput.value = song.artistId;
        songGenreInput.value = song.genreId;
        songAlbumInput.value = song.album || '';

        if (song.cover) {
          coverPreview.style.backgroundImage = `url('${song.cover}')`;
          coverPreview.innerHTML = '';
        } else {
          coverPreview.style.backgroundImage = '';
          coverPreview.innerHTML = '<i class="fas fa-music"></i>';
        }

        
        songFileInput.required = false;
      } else {
        modalTitle.textContent = 'Adicionar Música';
        musicForm.reset();
        coverPreview.style.backgroundImage = '';
        coverPreview.innerHTML = '<i class="fas fa-music"></i>';
        songFileInput.required = true;
      }

      musicModal.classList.add('active');
    }

    
    function closeModal() {
      musicModal.classList.remove('active');
      musicForm.reset();
    }

    
    function openArtistModal() {
      artistModal.classList.add('active');
    }

    
    function closeArtistModal() {
      artistModal.classList.remove('active');
      artistForm.reset();
    }

    
    function openGenreModal() {
      genreModal.classList.add('active');
    }

    
    function closeGenreModal() {
      genreModal.classList.remove('active');
      genreForm.reset();
    }

    
    function enviarMusica(formData) {
      fetch('../upload_musicas.php', {
        method: 'POST',
        body: formData
      })
        .then(response => response.text()) 
        .then(text => {
          console.log('Resposta bruta do servidor:', text); 
          const data = JSON.parse(text); 

          if (data.success) {
            Swal.fire('Sucesso!', data.message, 'success');
            closeModal();
            setTimeout(() => location.reload(), 1500);
          } else {
            let errorMsg = data.message;
            if (data.errors) {
              errorMsg += '<br><br>' + data.errors.join('<br>');
            }
            Swal.fire('Erro!', errorMsg, 'error');
          }
        });
    }

    
    function enviarArtista(formData) {
      fetch('../cadastrar_artista.php', {
        method: 'POST',
        body: formData
      })
        .then(response => response.json())
        .then(data => {
          if (data.success) {
            Swal.fire('Sucesso!', 'Artista cadastrado com sucesso!', 'success');
            closeArtistModal();
            setTimeout(() => location.reload(), 1500);
          } else {
            Swal.fire('Erro!', data.message || 'Erro ao cadastrar artista', 'error');
          }
        })
        .catch(error => {
          Swal.fire('Erro!', 'Falha na comunicação com o servidor', 'error');
          console.error('Erro:', error);
        });
    }

    
    function enviarGenero(formData) {
      fetch('../cadastrar_genero.php', {
        method: 'POST',
        body: formData
      })
        .then(response => response.json())
        .then(data => {
          if (data.success) {
            Swal.fire('Sucesso!', 'Gênero cadastrado com sucesso!', 'success');
            closeGenreModal();
            setTimeout(() => location.reload(), 1500);
          } else {
            Swal.fire('Erro!', data.message || 'Erro ao cadastrar gênero', 'error');
          }
        })
        .catch(error => {
          Swal.fire('Erro!', 'Falha na comunicação com o servidor', 'error');
          console.error('Erro:', error);
        });
    }

    
    function deleteSong(id) {
      Swal.fire({
        title: 'Tem certeza?',
        text: "Você não poderá reverter isso!",
        icon: 'warning',
        showCancelButton: true,
        confirmButtonColor: '#1DB954',
        cancelButtonColor: '#d33',
        confirmButtonText: 'Sim, excluir!',
        cancelButtonText: 'Cancelar'
      }).then((result) => {
        if (result.isConfirmed) {
          fetch(`painel_admin.php?excluir=${id}`)
            .then(response => {
              if (response.ok) {
                location.reload();
              } else {
                Swal.fire('Erro!', 'Falha ao excluir música', 'error');
              }
            })
            .catch(error => {
              Swal.fire('Erro!', 'Falha na comunicação com o servidor', 'error');
            });
        }
      });
    }


    
    function setupEventListeners() {
      
      playPauseBtn.addEventListener('click', () => {
        if (songs.length === 0) return;

        if (isPlaying) {
          pauseSong();
        } else {
          playSong();
        }
      });

      prevBtn.addEventListener('click', prevSong);
      nextBtn.addEventListener('click', nextSong);

      shuffleBtn.addEventListener('click', () => {
        isShuffle = !isShuffle;
        shuffleBtn.style.color = isShuffle ? 'var(--primary)' : 'var(--text-secondary)';
      });

      repeatBtn.addEventListener('click', () => {
        isRepeat = !isRepeat;
        repeatBtn.style.color = isRepeat ? 'var(--primary)' : 'var(--text-secondary)';
      });

      progressContainer.addEventListener('click', setProgress);
      volumeSlider.addEventListener('input', setVolume);

      
      menuToggle.addEventListener('click', () => {
        sidebar.classList.toggle('active');
      });

      
      audio.addEventListener('ended', () => {
        if (isRepeat) {
          audio.currentTime = 0;
          audio.play();
        } else {
          nextSong();
        }
      });

      
      audio.addEventListener('timeupdate', updateProgress);

      
      document.addEventListener('keydown', (e) => {
        
        const isModalOpen = document.querySelector('.modal.active') !== null;

        if (isModalOpen) {
          return;
        }

        
        if (e.code === 'Space') {
          e.preventDefault();
          playPauseBtn.click();
        } else if (e.code === 'ArrowRight') {
          nextBtn.click();
        } else if (e.code === 'ArrowLeft') {
          prevBtn.click();
        }
      });
    }

    
    addMusicBtn.addEventListener('click', () => openModal());
    if (addFirstMusicBtn) {
      addFirstMusicBtn.addEventListener('click', () => openModal());
    }
    closeModalBtn.addEventListener('click', closeModal);
    cancelBtn.addEventListener('click', closeModal);

    addArtistBtn.addEventListener('click', openArtistModal);
    closeArtistModalBtn.addEventListener('click', closeArtistModal);
    cancelArtistBtn.addEventListener('click', closeArtistModal);

    addGeneroBtn.addEventListener('click', (e) => {
      e.preventDefault();
      openGenreModal();
    });
    closeGenreModalBtn.addEventListener('click', closeGenreModal);
    cancelGenreBtn.addEventListener('click', closeGenreModal);

    musicForm.addEventListener('submit', function (e) {
      e.preventDefault();

      document.querySelectorAll('.form-group').forEach(group => {
        group.classList.remove('invalid');
        const errorMsg = group.querySelector('.error-message');
        if (errorMsg) errorMsg.remove();
      });

      let isValid = true;
      let errorMessage = '';

      if (!songTitleInput.value.trim()) {
        markFieldAsInvalid(songTitleInput, 'Por favor, informe o nome da música');
        isValid = false;
        errorMessage += '• Nome da música é obrigatório<br>';
      }

      if (!songArtistInput.value) {
        markFieldAsInvalid(songArtistInput, 'Por favor, selecione um artista');
        isValid = false;
        errorMessage += '• Artista é obrigatório<br>';
      }

      if (!songGenreInput.value) {
        markFieldAsInvalid(songGenreInput, 'Por favor, selecione um gênero');
        isValid = false;
        errorMessage += '• Gênero é obrigatório<br>';
      }

      if (!isEditMode && !songFileInput.files[0]) {
        markFieldAsInvalid(songFileInput, 'Por favor, selecione um arquivo de música');
        isValid = false;
        errorMessage += '• Arquivo de música é obrigatório<br>';
      }

      if (!isValid) {
        Swal.fire({
          title: 'Erro!',
          html: 'Por favor, corrija os seguintes campos:<br><br>' + errorMessage,
          icon: 'error',
          confirmButtonText: 'Entendi'
        });
        return;
      }

      const formData = new FormData(this);
      formData.append('action', isEditMode ? 'update' : 'insert');
      enviarMusica(formData);
    });

    function markFieldAsInvalid(inputElement, message) {
      const formGroup = inputElement.closest('.form-group');
      formGroup.classList.add('invalid');

      const existingError = formGroup.querySelector('.error-message');
      if (existingError) existingError.remove();

      const errorElement = document.createElement('div');
      errorElement.className = 'error-message';
      errorElement.textContent = message;
      formGroup.appendChild(errorElement);

      inputElement.focus();
    }

    artistForm.addEventListener('submit', function (e) {
      e.preventDefault();

      if (!artistNameInput.value.trim()) {
        Swal.fire('Erro!', 'Por favor, informe o nome do artista', 'error');
        return;
      }

      const formData = new FormData(this);
      enviarArtista(formData);
    });

    genreForm.addEventListener('submit', function (e) {
      e.preventDefault();

      if (!genreNameInput.value.trim()) {
        Swal.fire('Erro!', 'Por favor, informe o nome do gênero', 'error');
        return;
      }

      const formData = new FormData(this);
      enviarGenero(formData);
    });

    coverUpload.addEventListener('change', function (e) {
      const file = e.target.files[0];
      if (file) {
        const reader = new FileReader();
        reader.onload = function (event) {
          coverPreview.style.backgroundImage = `url('${event.target.result}')`;
          coverPreview.innerHTML = '';
        };
        reader.readAsDataURL(file);
      }
    });


    window.addEventListener('click', (e) => {
      if (e.target === musicModal) {
        closeModal();
      }
      if (e.target === artistModal) {
        closeArtistModal();
      }
      if (e.target === genreModal) {
        closeGenreModal();
      }
    });

    songTitleInput.addEventListener('input', function () {
      if (this.value.trim()) {
        this.closest('.form-group').classList.remove('invalid');
        const errorMsg = this.closest('.form-group').querySelector('.error-message');
        if (errorMsg) errorMsg.remove();
      }
    });

    [songArtistInput, songGenreInput].forEach(select => {
      select.addEventListener('change', function () {
        if (this.value) {
          this.closest('.form-group').classList.remove('invalid');
          const errorMsg = this.closest('.form-group').querySelector('.error-message');
          if (errorMsg) errorMsg.remove();
        }
      });
    });

    songFileInput.addEventListener('change', function () {
      if (this.files[0]) {
        this.closest('.form-group').classList.remove('invalid');
        const errorMsg = this.closest('.form-group').querySelector('.error-message');
        if (errorMsg) errorMsg.remove();
      }
    });




    
    function filterAndSortSongs() {
      const searchTerm = document.getElementById('search-input').value.toLowerCase();
      const genreFilter = document.getElementById('genre-filter').value;
      const sortBy = document.getElementById('sort-by').value;

      
      let filteredSongs = <?php echo json_encode($songsData); ?>;

      
      if (searchTerm) {
        filteredSongs = filteredSongs.filter(song =>
          song.title.toLowerCase().includes(searchTerm) ||
          song.artist.toLowerCase().includes(searchTerm) ||
          (song.album && song.album.toLowerCase().includes(searchTerm))
        );
      }

      
      if (genreFilter) {
        filteredSongs = filteredSongs.filter(song => song.genreId == genreFilter);
      }

      
      filteredSongs.sort((a, b) => {
        switch (sortBy) {
          case 'title-asc':
            return a.title.localeCompare(b.title);
          case 'title-desc':
            return b.title.localeCompare(a.title);
          case 'artist-asc':
            return a.artist.localeCompare(b.artist);
          case 'artist-desc':
            return b.artist.localeCompare(a.artist);
          case 'genre-asc':
            return a.genre.localeCompare(b.genre);
          case 'genre-desc':
            return b.genre.localeCompare(a.genre);
          case 'id-asc':
            return a.id - b.id;
          case 'id-desc':
            return b.id - a.id;
          default:
            return 0;
        }
      });

      return filteredSongs;
    }

    
    function updateGridWithFilters() {
      const filteredSongs = filterAndSortSongs();

      
      if (filteredSongs.length === 0) {
        musicGrid.innerHTML = `
                <div class="empty-state">
                    <i class="fas fa-search"></i>
                    <p>Nenhuma música encontrada com os filtros atuais</p>
                    <button class="btn-clear-filters" id="clear-filters-btn">
                        Limpar filtros
                    </button>
                </div>
            `;

        document.getElementById('clear-filters-btn').addEventListener('click', () => {
          document.getElementById('search-input').value = '';
          document.getElementById('genre-filter').value = '';
          document.getElementById('sort-by').value = 'title-asc';
          updateGridWithFilters();
        });

        return;
      }

      
      songs = filteredSongs;
      updateMusicGrid();
    }


    
    function init() {
      updateMusicGrid();
      setupEventListeners();

      if (songs.length > 0) {
        loadSong(0);
      }
      
      updateGridWithFilters();
      
      document.getElementById('search-input').addEventListener('input', updateGridWithFilters);
      document.getElementById('genre-filter').addEventListener('change', updateGridWithFilters);
      document.getElementById('sort-by').addEventListener('change', updateGridWithFilters);
    }

    
    audio.volume = volumeSlider.value;

  
    document.getElementById('search-input').addEventListener('input', function () {
      updateGridWithFilters();
    });

    
    document.getElementById('genre-filter').addEventListener('change', function () {
      updateGridWithFilters();
    });

    
    document.getElementById('sort-by').addEventListener('change', function () {
      updateGridWithFilters();
    });

    
    document.addEventListener('DOMContentLoaded', init);

  </script>

</body>

</html>