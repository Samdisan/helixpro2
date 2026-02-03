<?php
// AUDIO — завантаження музики та прив'язка до глав/фракцій
$audioDir = __DIR__ . '/../assets/audio';
$files = is_dir($audioDir) ? array_values(array_diff(scandir($audioDir), ['.', '..'])) : [];
$audioFiles = array_filter($files, function ($f) { return preg_match('/\.(mp3|ogg|wav)$/i', $f); });
sort($audioFiles);

$audioConfig = file_exists(AUDIO_CONFIG_FILE) ? getJson(AUDIO_CONFIG_FILE) : [];
$chapters = $audioConfig['chapters'] ?? ['ch1' => 'ambience.mp3', 'ch2' => 'ambience.mp3'];
$factions = $audioConfig['factions'] ?? ['OLYMPOS' => 'ambience_olympos.mp3', 'ORIGIN' => 'ambience_origin.mp3', 'THEMIS' => 'ambience_themis.mp3', 'MOIRAI' => ''];
$defaultVolume = isset($audioConfig['default_volume']) ? (float)$audioConfig['default_volume'] : 0.3;
$bunkerSound = isset($audioConfig['3']) ? trim((string)$audioConfig['3']) : '';
$applicationSound = isset($audioConfig['4']) ? trim((string)$audioConfig['4']) : '';
$backgroundTracks = isset($audioConfig['background_tracks']) && is_array($audioConfig['background_tracks']) ? $audioConfig['background_tracks'] : [];

?>
<div class="editor">
    <h2 style="color:#00f0ff; margin-top:0;">🔊 АУДІО — МУЗИКА ПІД РІЗНІ КОНТЕКСТИ</h2>
    <p style="color:#888; font-size:0.9rem;">Завантажуйте треки в <code>assets/audio/</code> і прив’язуйте їх до глав (ch1, ch2) та фракцій (OLYMPOS, ORIGIN, THEMIS, MOIRAI). Клієнт (<code>system.js</code>) підхоплює конфіг з <code>audio_config.json</code>.</p>

    <h3 style="color:#fff; margin-top:24px;">Завантажити музику</h3>
    <form id="audio-upload-form" style="display:flex; flex-wrap:wrap; gap:12px; align-items:flex-end; margin-bottom:20px;">
        <label style="display:flex; flex-direction:column; gap:4px;">
            <span style="color:#888; font-size:0.85rem;">Файл (mp3, ogg, wav, макс. 20 MB)</span>
            <input type="file" name="audio" accept=".mp3,.ogg,.wav" required style="color:#ccc; background:#111; border:1px solid #333; padding:8px;">
        </label>
        <label style="display:flex; flex-direction:column; gap:4px;">
            <span style="color:#888; font-size:0.85rem;">Зберегти як (необов’язково)</span>
            <input type="text" name="save_as" placeholder="наприклад ambience_olympos.mp3" style="color:#ccc; background:#111; border:1px solid #333; padding:8px; width:220px;">
        </label>
        <button type="submit" class="btn" style="background:#00f0ff22; color:#00f0ff; border:1px solid #00f0ff; padding:8px 16px; cursor:pointer;">Завантажити</button>
    </form>
    <p id="audio-upload-msg" style="color:#0a0; font-size:0.9rem; margin-top:4px;"></p>

    <h3 style="color:#fff; margin-top:24px;">Файли в assets/audio</h3>
    <ul style="list-style:none; padding:0; margin:0 0 24px 0;">
        <?php if (empty($audioFiles)): ?>
            <li style="color:#666;">— папка порожня. Завантажте файл вище.</li>
        <?php else: ?>
            <?php foreach ($audioFiles as $f): ?>
                <li style="display:flex; align-items:center; gap:10px; margin-bottom:8px;">
                    <button type="button" class="btn-play-audio" data-src="assets/audio/<?= h($f) ?>" style="background:#111; color:#0f0; border:1px solid #333; padding:4px 12px; cursor:pointer; font-size:0.85rem;">▶</button>
                    <code style="color:#ccc;"><?= h($f) ?></code>
                </li>
            <?php endforeach; ?>
        <?php endif; ?>
    </ul>

    <h3 style="color:#fff; margin-top:24px;">Прив’язка музики до контекстів</h3>
    <p style="color:#888; font-size:0.9rem;">Оберіть файл для кожної глави та фракції. Після змін натисніть «Зберегти конфіг».</p>
    <form id="audio-config-form" method="POST" action="save_audio_config.php" style="max-width:520px;">
        <input type="hidden" name="action" value="save">
        <table style="color:#ccc; border-collapse:collapse; width:100%;">
            <tr><td style="padding:6px 12px 6px 0; color:#888;">Глава ch1</td>
                <td><select name="chapters[ch1]" style="background:#111; color:#ccc; border:1px solid #333; padding:6px; width:100%;">
                    <?php foreach ($audioFiles as $f): ?>
                        <option value="<?= h($f) ?>" <?= ($chapters['ch1'] ?? '') === $f ? 'selected' : '' ?>><?= h($f) ?></option>
                    <?php endforeach; ?>
                    <?php if (empty($audioFiles) || !in_array($chapters['ch1'] ?? '', $audioFiles)): ?>
                        <option value="<?= h($chapters['ch1'] ?? 'ambience.mp3') ?>" selected><?= h($chapters['ch1'] ?? 'ambience.mp3') ?></option>
                    <?php endif; ?>
                </select></td></tr>
            <tr><td style="padding:6px 12px 6px 0; color:#888;">Глава ch2</td>
                <td><select name="chapters[ch2]" style="background:#111; color:#ccc; border:1px solid #333; padding:6px; width:100%;">
                    <?php foreach ($audioFiles as $f): ?>
                        <option value="<?= h($f) ?>" <?= ($chapters['ch2'] ?? '') === $f ? 'selected' : '' ?>><?= h($f) ?></option>
                    <?php endforeach; ?>
                    <?php if (empty($audioFiles) || !in_array($chapters['ch2'] ?? '', $audioFiles)): ?>
                        <option value="<?= h($chapters['ch2'] ?? 'ambience.mp3') ?>" selected><?= h($chapters['ch2'] ?? 'ambience.mp3') ?></option>
                    <?php endif; ?>
                </select></td></tr>
            <tr><td style="padding:6px 12px 6px 0; color:#888;">Фракція OLYMPOS</td>
                <td><select name="factions[OLYMPOS]" style="background:#111; color:#ccc; border:1px solid #333; padding:6px; width:100%;">
                    <?php foreach ($audioFiles as $f): ?>
                        <option value="<?= h($f) ?>" <?= ($factions['OLYMPOS'] ?? '') === $f ? 'selected' : '' ?>><?= h($f) ?></option>
                    <?php endforeach; ?>
                    <?php if (empty($audioFiles) || !in_array($factions['OLYMPOS'] ?? '', $audioFiles)): ?>
                        <option value="<?= h($factions['OLYMPOS'] ?? 'ambience_olympos.mp3') ?>" selected><?= h($factions['OLYMPOS'] ?? 'ambience_olympos.mp3') ?></option>
                    <?php endif; ?>
                </select></td></tr>
            <tr><td style="padding:6px 12px 6px 0; color:#888;">Фракція ORIGIN</td>
                <td><select name="factions[ORIGIN]" style="background:#111; color:#ccc; border:1px solid #333; padding:6px; width:100%;">
                    <?php foreach ($audioFiles as $f): ?>
                        <option value="<?= h($f) ?>" <?= ($factions['ORIGIN'] ?? '') === $f ? 'selected' : '' ?>><?= h($f) ?></option>
                    <?php endforeach; ?>
                    <?php if (empty($audioFiles) || !in_array($factions['ORIGIN'] ?? '', $audioFiles)): ?>
                        <option value="<?= h($factions['ORIGIN'] ?? 'ambience_origin.mp3') ?>" selected><?= h($factions['ORIGIN'] ?? 'ambience_origin.mp3') ?></option>
                    <?php endif; ?>
                </select></td></tr>
            <tr><td style="padding:6px 12px 6px 0; color:#888;">Фракція THEMIS</td>
                <td><select name="factions[THEMIS]" style="background:#111; color:#ccc; border:1px solid #333; padding:6px; width:100%;">
                    <?php foreach ($audioFiles as $f): ?>
                        <option value="<?= h($f) ?>" <?= ($factions['THEMIS'] ?? '') === $f ? 'selected' : '' ?>><?= h($f) ?></option>
                    <?php endforeach; ?>
                    <?php if (empty($audioFiles) || !in_array($factions['THEMIS'] ?? '', $audioFiles)): ?>
                        <option value="<?= h($factions['THEMIS'] ?? 'ambience_themis.mp3') ?>" selected><?= h($factions['THEMIS'] ?? 'ambience_themis.mp3') ?></option>
                    <?php endif; ?>
                </select></td></tr>
            <tr><td style="padding:6px 12px 6px 0; color:#888;">Фракція МОЙРИ</td>
                <td><select name="factions[MOIRAI]" style="background:#111; color:#ccc; border:1px solid #333; padding:6px; width:100%;">
                    <option value="" <?= ($factions['MOIRAI'] ?? '') === '' ? 'selected' : '' ?>>— без звуку</option>
                    <?php foreach ($audioFiles as $f): ?>
                        <option value="<?= h($f) ?>" <?= ($factions['MOIRAI'] ?? '') === $f ? 'selected' : '' ?>><?= h($f) ?></option>
                    <?php endforeach; ?>
                </select></td></tr>
            <tr><td style="padding:12px 12px 6px 0; color:#888;">Гучність BGM (0–1)</td>
                <td><input type="number" name="default_volume" min="0" max="1" step="0.1" value="<?= h((string)$defaultVolume) ?>" style="background:#111; color:#ccc; border:1px solid #333; padding:6px; width:80px;"></td></tr>
            <tr><td style="padding:12px 12px 6px 0; color:#888;">Звук: Протокол бункера (3)</td>
                <td><select name="3" style="background:#111; color:#ccc; border:1px solid #333; padding:6px; width:100%;">
                    <option value="" <?= $bunkerSound === '' ? 'selected' : '' ?>— без звуку</option>
                    <?php foreach ($audioFiles as $f): ?>
                        <option value="<?= h($f) ?>" <?= $bunkerSound === $f ? 'selected' : '' ?>><?= h($f) ?></option>
                    <?php endforeach; ?>
                </select></td></tr>
            <tr><td style="padding:12px 12px 6px 0; color:#888;">Звук: при подачі заявки (4)</td>
                <td><select name="4" style="background:#111; color:#ccc; border:1px solid #333; padding:6px; width:100%;">
                    <option value="" <?= $applicationSound === '' ? 'selected' : '' ?>— без звуку</option>
                    <?php foreach ($audioFiles as $f): ?>
                        <option value="<?= h($f) ?>" <?= $applicationSound === $f ? 'selected' : '' ?>><?= h($f) ?></option>
                    <?php endforeach; ?>
                </select></td></tr>
            <tr><td colspan="2" style="padding:16px 12px 8px 0; color:#00f0ff; font-size:0.85rem;">Фонова музика сайту (3–4 треки)</td></tr>
            <?php for ($i = 1; $i <= 4; $i++): $bt = $backgroundTracks[$i - 1] ?? ''; ?>
            <tr><td style="padding:6px 12px 6px 0; color:#888;">Фоновий трек <?= $i ?></td>
                <td><select name="background_track_<?= $i ?>" style="background:#111; color:#ccc; border:1px solid #333; padding:6px; width:100%;">
                    <option value="" <?= $bt === '' ? 'selected' : '' ?>— не використовувати</option>
                    <?php foreach ($audioFiles as $f): ?>
                        <option value="<?= h($f) ?>" <?= $bt === $f ? 'selected' : '' ?>><?= h($f) ?></option>
                    <?php endforeach; ?>
                </select></td></tr>
            <?php endfor; ?>
        </table>
        <button type="submit" style="margin-top:16px; background:#00f0ff22; color:#00f0ff; border:1px solid #00f0ff; padding:10px 20px; cursor:pointer;">Зберегти конфіг</button>
    </form>
    <p id="audio-config-msg" style="color:#0a0; font-size:0.9rem; margin-top:8px;"></p>
</div>

<script>
(function() {
    var uploadForm = document.getElementById('audio-upload-form');
    var uploadMsg = document.getElementById('audio-upload-msg');
    if (uploadForm) {
        uploadForm.addEventListener('submit', function(e) {
            e.preventDefault();
            uploadMsg.textContent = '';
            var fd = new FormData();
            fd.append('audio', uploadForm.querySelector('input[name="audio"]').files[0]);
            var saveAs = uploadForm.querySelector('input[name="save_as"]').value.trim();
            if (saveAs) fd.append('save_as', saveAs);
            fetch('upload_audio.php', { method: 'POST', body: fd, credentials: 'same-origin' })
                .then(function(r) { return r.json(); })
                .then(function(data) {
                    if (data.ok) {
                        uploadMsg.textContent = 'Завантажено: ' + data.file;
                        uploadMsg.style.color = '#0a0';
                        setTimeout(function() { location.reload(); }, 800);
                    } else {
                        uploadMsg.textContent = data.error || 'Помилка';
                        uploadMsg.style.color = '#a00';
                    }
                })
                .catch(function() {
                    uploadMsg.textContent = 'Помилка мережі';
                    uploadMsg.style.color = '#a00';
                });
        });
    }
    document.querySelectorAll('.btn-play-audio').forEach(function(btn) {
        btn.addEventListener('click', function() {
            var src = this.getAttribute('data-src');
            var audio = new Audio(src);
            if (this.textContent === '■') {
                window._adminPreviewAudio && window._adminPreviewAudio.pause();
                this.textContent = '▶';
                return;
            }
            window._adminPreviewAudio && window._adminPreviewAudio.pause();
            document.querySelectorAll('.btn-play-audio').forEach(function(b) { b.textContent = '▶'; });
            this.textContent = '■';
            window._adminPreviewAudio = audio;
            audio.play();
            audio.onended = audio.onpause = function() {
                btn.textContent = '▶';
            };
        });
    });
    var configForm = document.getElementById('audio-config-form');
    var configMsg = document.getElementById('audio-config-msg');
    if (configForm) {
        configForm.addEventListener('submit', function(e) {
            e.preventDefault();
            configMsg.textContent = '';
            var fd = new FormData(configForm);
            fetch('save_audio_config.php', { method: 'POST', body: fd, credentials: 'same-origin' })
                .then(function(r) { return r.json(); })
                .then(function(data) {
                    if (data.ok) {
                        configMsg.textContent = 'Конфіг збережено.';
                        configMsg.style.color = '#0a0';
                    } else {
                        configMsg.textContent = data.error || 'Помилка';
                        configMsg.style.color = '#a00';
                    }
                })
                .catch(function() {
                    configMsg.textContent = 'Помилка мережі';
                    configMsg.style.color = '#a00';
                });
        });
    }
})();
</script>
