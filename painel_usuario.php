<?php
require_once(__DIR__ . "/includes/conexao.php");
require_once(__DIR__ . "/includes/funcoes_usuario.php");

session_start();

if (!isset($_SESSION['usuario_logado'])) {
  header('Location: login.php');
  exit();
}

// Filtros
$filtroGenero = isset($_GET['genero']) ? (int) $_GET['genero'] : null;
$termoBusca = isset($_GET['busca']) ? trim($_GET['busca']) : null;

$musicas = listarMusicasDisponiveis($filtroGenero, $termoBusca);

$songsData = [];
$basePath = '/SonareMusic/';

foreach ($musicas as $musica) {

  $safeTitle = htmlspecialchars($musica['titulo'] ?? '', ENT_QUOTES, 'UTF-8');
  $safeArtist = htmlspecialchars($musica['artista'] ?? '', ENT_QUOTES, 'UTF-8');
  $safeGenre = htmlspecialchars($musica['genero'] ?? '', ENT_QUOTES, 'UTF-8');


  // Tratamento da capa
  
  $capaFile = $musica['capa'] ?? '';
if (!empty($capaFile) && strpos($capaFile, 'http') !== 0) {
  
  $capaPath = $basePath . 'uploads/capas/' . rawurlencode(basename($capaFile));

  
  $caminhoFisico = $_SERVER['DOCUMENT_ROOT'] . $basePath . 'uploads/capas/' . basename($capaFile);

  if (!file_exists($caminhoFisico)) {
    $capaPath = $basePath . 'uploads/capas/default-cover.jpg';
  }
} else {
  $capaPath = $capaFile ?: $basePath . 'uploads/capas/default-cover.jpg';
}



  // Tratamento do áudio
  $audioFile = $musica['arquivo'] ?? '';
  if (strpos($audioFile, 'http') !== 0) {
    $audioPath = $basePath . 'uploads/musicas/' . basename($audioFile);

    if (!file_exists($_SERVER['DOCUMENT_ROOT'] . $audioPath)) {
      continue;
    }
  } else {
    $audioPath = $audioFile;
  }

  // Formatação da duração
  $duration = '3:45';
  if (!empty($musica['duracao'])) {
    $timeParts = explode(':', $musica['duracao']);
    $duration = sprintf('%02d:%02d', $timeParts[0] ?? 0, $timeParts[1] ?? 0);
  }

  $songsData[] = [
    'id' => (int) $musica['id'],
    'title' => $safeTitle,
    'artist' => $safeArtist,
    'genre' => $safeGenre,
    'generoId' => (int) $musica['genero_id'],
    'album' => 'Álbum',
    'cover' => $capaPath,
    'audioUrl' => $audioPath,
    'duration' => $duration,
    'exists' => file_exists($_SERVER['DOCUMENT_ROOT'] . $audioPath)
  ];
}
?>

<!DOCTYPE html>
<html lang="pt-br">

<head>
  <meta charset="UTF-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1" />
  <title>SonareMusic - Painel do Usuário</title>
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/limonte-sweetalert2/11.4.24/sweetalert2.min.css">
  <link rel="stylesheet" href="./css/painel_usuario.css">
</head>

<body>

  <div class="sidebar" id="sidebar">
    <h1>SonareMusic</h1>
    <ul class="nav-links">
      <li><a href="#" class="active"><i class="fas fa-home icon"></i> Início</a></li>
      <li><a href="#"><i class="fas fa-music icon"></i> Minhas Músicas</a></li>
      <li><a href="#"><i class="fas fa-heart icon"></i> Favoritas</a></li>
      <li><a href="#"><i class="fas fa-list icon"></i> Playlists</a></li>
      <li><a href="#"><i class="fas fa-history icon"></i> Histórico</a></li>
      <li><a href="#"><i class="fas fa-cog icon"></i> Configurações</a></li>
      <li><a href="logout.php"><i class="fas fa-sign-out-alt icon"></i> Sair</a></li>
    </ul>

    <div class="current-playing">
      <div class="title" id="sidebar-track-title">Nenhuma música selecionada</div>
      <div class="artist" id="sidebar-track-artist">—</div>
    </div>
  </div>

  <div class="main-content">
    <div class="header">
      <div class="welcome-container">
        <div class="welcome">Bem-vindo de volta!</div>
        <div class="subtitle">O que vamos ouvir hoje?</div>
      </div>

      <div class="user-menu">
        <div class="user-avatar" id="user-avatar">
          <i class="fas fa-user"></i>
        </div>
      </div>
    </div>

    <!-- Filtros e busca -->
    <div class="filters-container">
      <div class="filter-group">
        <label class="filter-label">Gênero</label>
        <select class="filter-select" id="genre-filter">
          <option value="">Todos os gêneros</option>
          <?php
          $generos = listarGenerosDisponiveis();
          foreach ($generos as $genero) {
            echo '<option value="' . htmlspecialchars($genero['id']) . '">' .
              htmlspecialchars($genero['nome']) . '</option>';
          }
          ?>
        </select>
      </div>

      <div class="filter-group search-box">
        <label class="filter-label"></label>
        <i class="fas fa-search search-icon"></i>
        <input type="text" class="search-input" id="search-input" placeholder="Pesquisar músicas...">
      </div>
    </div>

    <!-- Grade de músicas -->
    <div class="music-grid" id="music-grid">
      <?php if (empty($songsData)): ?>
        <div class="empty-state">
          <i class="fas fa-music"></i>
          <p>Nenhuma música disponível no momento</p>
        </div>
      <?php else: ?>
        <?php foreach ($songsData as $song): ?>
          <div class="music-card" data-id="<?= $song['id'] ?>">
            <div class="music-cover" style="background-image: url('<?= $song['cover'] ?>')">
              <div class="music-overlay">
                <div class="play-btn">
                  <i class="fas fa-play"></i>
                </div>
              </div>
              <div class="card-actions">
                <button class="action-btn favorite-btn" data-id="<?= $song['id'] ?>">
                  <i class="far fa-heart"></i>
                </button>
                <button class="action-btn playlist-btn" data-id="<?= $song['id'] ?>">
                  <i class="fas fa-plus"></i>
                </button>
              </div>
            </div>
            <div class="music-info">
              <div class="music-title"><?= $song['title'] ?></div>
              <div class="music-artist"><?= $song['artist'] ?></div>
              <div class="music-genre"><?= $song['genre'] ?></div>
            </div>
          </div>
        <?php endforeach; ?>
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
    const userAvatar = document.getElementById('user-avatar');
    const musicGrid = document.getElementById('music-grid');
    const sidebarTrackTitle = document.getElementById('sidebar-track-title');
    const sidebarTrackArtist = document.getElementById('sidebar-track-artist');
    const genreFilter = document.getElementById('genre-filter');
    const searchInput = document.getElementById('search-input');

    // Estado do player
    const audio = new Audio();
    let songs = <?php echo json_encode($songsData); ?>;
    let currentSongIndex = 0;
    let isShuffle = false;
    let isRepeat = false;
    let isPlaying = false;


    // Monitorar erros do áudio
    audio.addEventListener('error', (e) => {
      console.error('Erro no elemento de áudio:', e);
      showToast('Erro ao carregar a música');
    });

    // Inicializar a aplicação
    function init() {
      updateMusicGrid();
      setupEventListeners();
      if (songs.length > 0) {
        loadSong(0);
      }
    }

    // Atualizar a grade de músicas
    function updateMusicGrid(filteredSongs = songs) {
      if (filteredSongs.length === 0) {
        musicGrid.innerHTML = `
        <div class="empty-state">
          <i class="fas fa-music"></i>
          <p>Nenhuma música encontrada</p>
        </div>
      `;
        return;
      }

      musicGrid.innerHTML = '';

      filteredSongs.forEach((song, index) => {
        const card = document.createElement('div');
        card.className = 'music-card';
        if (index === currentSongIndex && isPlaying) {
          card.classList.add('active');
        }

        card.innerHTML = `
        <div class="music-cover" style="background-image: url('${getCorrectPath(song.cover)}')">
          <div class="music-overlay">
            <div class="play-btn">
              <i class="fas fa-play"></i>
            </div>
          </div>
          <div class="card-actions">
            <button class="action-btn favorite-btn" data-id="${song.id}">
              <i class="far fa-heart"></i>
            </button>
            <button class="action-btn playlist-btn" data-id="${song.id}">
              <i class="fas fa-plus"></i>
            </button>
          </div>
        </div>
        <div class="music-info">
          <div class="music-title">${song.title}</div>
          <div class="music-artist">${song.artist}</div>
          <div class="music-genre">${song.genre}</div>
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





    // Filtrar músicas
    function filterSongs() {
      const genre = genreFilter.value;
      const search = searchInput.value.toLowerCase();

      let filtered = songs;
      if (genre) {
        filtered = filtered.filter(song => song.genreId == genre);
      }
      if (search) {
        filtered = filtered.filter(song =>
          song.title.toLowerCase().includes(search) ||
          song.artist.toLowerCase().includes(search)
        );
      }
      updateMusicGrid(filtered);
    }

    // Exibir notificação 
    function showToast(message) {
      const Toast = Swal.mixin({
        toast: true,
        position: 'bottom-end',
        showConfirmButton: false,
        timer: 3000,
        timerProgressBar: true,
        didOpen: (toast) => {
          toast.addEventListener('mouseenter', Swal.stopTimer);
          toast.addEventListener('mouseleave', Swal.resumeTimer);
        }
      });

      Toast.fire({
        icon: 'success',
        title: message
      });
    }

    // Funções do player
    function formatTime(seconds) {
      if (isNaN(seconds)) return '0:00';
      const min = Math.floor(seconds / 60);
      const sec = Math.floor(seconds % 60);
      return `${min}:${sec < 10 ? '0' + sec : sec}`;
    }

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
        } else if (song.cover.startsWith('uploads/')) {
          coverPath = song.cover;
        } else if (song.cover.startsWith('./uploads/')) {
          coverPath = song.cover;
        } else if (song.cover.startsWith('/')) {
          coverPath = song.cover;
        } else {
          coverPath = 'uploads/capas/' + song.cover;
        }
      } else {
        coverPath = 'uploads/capas/default-cover.jpg';
      }


      const caminhoBase = coverPath.substring(0, coverPath.lastIndexOf('/') + 1);
      const nomeArquivo = coverPath.substring(coverPath.lastIndexOf('/') + 1);
      const encodedCoverPath = caminhoBase + encodeURIComponent(nomeArquivo);

      console.log('Caminho da capa:', encodedCoverPath);

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

    const song = songs[currentSongIndex];

    console.log('Dados da música:', song); // Mostra tudo: título, artista, capa, etc.
    console.log('Capa original (song.cover):', song.cover); // Mostra só a string do caminho da capa



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

      // Atualizar a barra de progresso
      const progressPercent = (currentTime / duration) * 100;
      progress.style.width = `${progressPercent}%`;

      // Atualizar o tempo atual
      currentTimeEl.textContent = formatTime(currentTime);

      // Atualizar a duração total 
      if (durationEl.textContent === '0:00' && !isNaN(duration)) {
        durationEl.textContent = formatTime(duration);
      }
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

    // Configurar os listeners de eventos
    function setupEventListeners() {
      playPauseBtn.addEventListener('click', () => {
        if (songs.length === 0) return;
        isPlaying ? pauseSong() : playSong();
      });
      prevBtn.addEventListener('click', prevSong);
      nextBtn.addEventListener('click', nextSong);
      shuffleBtn.addEventListener('click', () => {
        isShuffle = !isShuffle;
        shuffleBtn.style.color = isShuffle ? 'var(--primary)' : 'var(--text-secondary)';
        showToast(isShuffle ? 'Modo embaralhar ativado' : 'Modo embaralhar desativado');
      });
      repeatBtn.addEventListener('click', () => {
        isRepeat = !isRepeat;
        repeatBtn.style.color = isRepeat ? 'var(--primary)' : 'var(--text-secondary)';
        showToast(isRepeat ? 'Modo repetir ativado' : 'Modo repetir desativado');
      });
      progressContainer.addEventListener('click', setProgress);
      volumeSlider.addEventListener('input', setVolume);
      genreFilter.addEventListener('change', filterSongs);
      searchInput.addEventListener('input', filterSongs);

      document.addEventListener('keydown', (e) => {
        if (e.code === 'Space') {
          e.preventDefault();
          playPauseBtn.click();
        } else if (e.code === 'ArrowRight') {
          nextBtn.click();
        } else if (e.code === 'ArrowLeft') {
          prevBtn.click();
        }
      });

      audio.addEventListener('timeupdate', () => {
        const { duration, currentTime } = audio;
        const progressPercent = (currentTime / duration) * 100;
        progress.style.width = `${progressPercent}%`;
        currentTimeEl.textContent = formatTime(currentTime);

        // Atualiza a duração total apenas se for válida
        if (!isNaN(duration) && duration > 0) {
          durationEl.textContent = formatTime(duration);
        }
      });


      audio.addEventListener('timeupdate', updateProgress);
    }

    // Função para corrigir caminhos duplicados
    function getCorrectPath(path) {
      return path.replace(/([^:]\/)\/+/g, '$1');
    }

    function setupCardActions() {
      document.querySelectorAll('.favorite-btn').forEach(btn => {
        btn.addEventListener('click', (e) => {
          e.stopPropagation();
          const id = btn.getAttribute('data-id');
          toggleFavorite(id, btn);
        });
      });
    }

    function toggleFavorite(id, btn) {
      const icon = btn.querySelector('i');
      const isFavorite = icon.classList.contains('fas');
      setTimeout(() => {
        if (isFavorite) {
          icon.classList.remove('fas');
          icon.classList.add('far');
          showToast('Música removida dos favoritos');
        } else {
          icon.classList.remove('far');
          icon.classList.add('fas');
          showToast('Música adicionada aos favoritos');
        }
      }, 300);
    }

    document.addEventListener('DOMContentLoaded', init);
  </script>


</body>

</html>