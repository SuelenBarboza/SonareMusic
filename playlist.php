<?php include 'includes/conexao.php'; ?>
<?php include 'includes/header.php'; ?>

<div class="container">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <h2>Minhas Playlists</h2>
        <button class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#newPlaylistModal">
            <i class="fas fa-plus me-2"></i>Nova Playlist
        </button>
    </div>
    
    <div class="row">
        <?php
        $stmt = $pdo->prepare("
            SELECT p.*, COUNT(pm.musica_id) AS total_musicas
            FROM playlists p
            LEFT JOIN playlist_musicas pm ON p.id = pm.playlist_id
            WHERE p.usuario_id = ?
            GROUP BY p.id
            ORDER BY p.ultima_atualizacao DESC, p.data_criacao DESC
        ");
        $stmt->execute([$usuario_id]);
        $playlists = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        if (count($playlists) > 0) {
            foreach ($playlists as $playlist) {
                $capa = $playlist['capa'] ? '../uploads/' . $playlist['capa'] : 'https://via.placeholder.com/300x300.png?text=Sem+Capa';
                
                echo '<div class="col-md-4 mb-4">
                    <div class="card h-100">
                        <img src="' . $capa . '" class="card-img-top" alt="Capa da playlist">
                        <div class="card-body">
                            <h5 class="card-title">' . htmlspecialchars($playlist['nome']) . '</h5>
                            <p class="card-text">' . htmlspecialchars($playlist['descricao'] ?? 'Sem descrição') . '</p>
                            <p class="text-muted"><small>' . $playlist['total_musicas'] . ' música(s)</small></p>
                        </div>
                        <div class="card-footer bg-white">
                            <div class="d-flex justify-content-between">
                                <a href="playlist.php?id=' . $playlist['id'] . '" class="btn btn-sm btn-primary">
                                    <i class="fas fa-eye"></i> Ver
                                </a>
                                <button class="btn btn-sm btn-outline-secondary edit-playlist" data-playlist-id="' . $playlist['id'] . '">
                                    <i class="fas fa-edit"></i> Editar
                                </button>
                                <button class="btn btn-sm btn-outline-danger delete-playlist" data-playlist-id="' . $playlist['id'] . '">
                                    <i class="fas fa-trash"></i>
                                </button>
                            </div>
                        </div>
                    </div>
                </div>';
            }
        } else {
            echo '<div class="col-12">
                <div class="alert alert-info">Você ainda não criou nenhuma playlist.</div>
            </div>';
        }
        ?>
    </div>
</div>

<!-- Modal para nova playlist -->
<div class="modal fade" id="newPlaylistModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Nova Playlist</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                <form id="newPlaylistForm">
                    <div class="mb-3">
                        <label for="playlistName" class="form-label">Nome da playlist*</label>
                        <input type="text" class="form-control" id="playlistName" name="name" required>
                    </div>
                    <div class="mb-3">
                        <label for="playlistDesc" class="form-label">Descrição</label>
                        <textarea class="form-control" id="playlistDesc" name="description" rows="3"></textarea>
                    </div>
                    <div class="mb-3">
                        <label for="playlistCover" class="form-label">Capa (opcional)</label>
                        <input type="file" class="form-control" id="playlistCover" name="cover" accept="image/*">
                    </div>
                    <div class="mb-3 form-check">
                        <input type="checkbox" class="form-check-input" id="playlistPublic" name="public">
                        <label class="form-check-label" for="playlistPublic">Playlist pública</label>
                    </div>
                </form>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancelar</button>
                <button type="button" id="savePlaylistBtn" class="btn btn-primary">Salvar</button>
            </div>
        </div>
    </div>
</div>

<!-- Modal para editar playlist -->
<div class="modal fade" id="editPlaylistModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Editar Playlist</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                <form id="editPlaylistForm">
                    <input type="hidden" id="editPlaylistId" name="id">
                    <div class="mb-3">
                        <label for="editPlaylistName" class="form-label">Nome da playlist*</label>
                        <input type="text" class="form-control" id="editPlaylistName" name="name" required>
                    </div>
                    <div class="mb-3">
                        <label for="editPlaylistDesc" class="form-label">Descrição</label>
                        <textarea class="form-control" id="editPlaylistDesc" name="description" rows="3"></textarea>
                    </div>
                    <div class="mb-3">
                        <label for="editPlaylistCover" class="form-label">Capa (opcional)</label>
                        <input type="file" class="form-control" id="editPlaylistCover" name="cover" accept="image/*">
                        <div class="mt-2">
                            <img id="currentPlaylistCover" src="" class="img-thumbnail" style="max-width: 100px;">
                        </div>
                    </div>
                    <div class="mb-3 form-check">
                        <input type="checkbox" class="form-check-input" id="editPlaylistPublic" name="public">
                        <label class="form-check-label" for="editPlaylistPublic">Playlist pública</label>
                    </div>
                </form>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancelar</button>
                <button type="button" id="updatePlaylistBtn" class="btn btn-primary">Salvar Alterações</button>
            </div>
        </div>
    </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', function() {
    // Criar nova playlist
    document.getElementById('savePlaylistBtn').addEventListener('click', function() {
        const form = document.getElementById('newPlaylistForm');
        const formData = new FormData(form);
        
        fetch('ajax/create_playlist.php', {
            method: 'POST',
            body: formData
        })
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                alert('Playlist criada com sucesso!');
                bootstrap.Modal.getInstance(document.getElementById('newPlaylistModal')).hide();
                location.reload();
            } else {
                alert('Erro: ' + data.message);
            }
        });
    });
    
    // Editar playlist
    document.querySelectorAll('.edit-playlist').forEach(btn => {
        btn.addEventListener('click', function() {
            const playlistId = this.getAttribute('data-playlist-id');
            
            fetch('ajax/get_playlist.php?id=' + playlistId)
                .then(response => response.json())
                .then(data => {
                    if (data.success) {
                        document.getElementById('editPlaylistId').value = data.playlist.id;
                        document.getElementById('editPlaylistName').value = data.playlist.nome;
                        document.getElementById('editPlaylistDesc').value = data.playlist.descricao || '';
                        document.getElementById('editPlaylistPublic').checked = data.playlist.publica == 1;
                        
                        if (data.playlist.capa) {
                            document.getElementById('currentPlaylistCover').src = '../uploads/' + data.playlist.capa;
                        } else {
                            document.getElementById('currentPlaylistCover').src = 'https://via.placeholder.com/300x300.png?text=Sem+Capa';
                        }
                        
                        const editModal = new bootstrap.Modal(document.getElementById('editPlaylistModal'));
                        editModal.show();
                    }
                });
        });
    });
    
    // Atualizar playlist
    document.getElementById('updatePlaylistBtn').addEventListener('click', function() {
        const form = document.getElementById('editPlaylistForm');
        const formData = new FormData(form);
        
        fetch('ajax/update_playlist.php', {
            method: 'POST',
            body: formData
        })
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                alert('Playlist atualizada com sucesso!');
                bootstrap.Modal.getInstance(document.getElementById('editPlaylistModal')).hide();
                location.reload();
            } else {
                alert('Erro: ' + data.message);
            }
        });
    });
    
    // Excluir playlist
    document.querySelectorAll('.delete-playlist').forEach(btn => {
        btn.addEventListener('click', function() {
            if (confirm('Tem certeza que deseja excluir esta playlist?')) {
                const playlistId = this.getAttribute('data-playlist-id');
                
                fetch('ajax/delete_playlist.php', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/x-www-form-urlencoded',
                    },
                    body: 'id=' + playlistId
                })
                .then(response => response.json())
                .then(data => {
                    if (data.success) {
                        alert('Playlist excluída com sucesso!');
                        location.reload();
                    } else {
                        alert('Erro: ' + data.message);
                    }
                });
            }
        });
    });
});
</script>

<?php include 'includes/footer.php'; ?>