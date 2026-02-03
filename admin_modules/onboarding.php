<?php
// admin_modules/onboarding.php — онбординг: вибір гравця → начитка по біо, здібностям, мед-карті; всі пункти редаговані

$statusLabels = ['healthy' => 'Здоровий', 'infected' => 'Заражений', 'unknown' => 'Невідомо'];

$allPlayers = getJson(USERS_FILE);
$playable = array_filter($allPlayers, function($u) {
    return ($u['role'] ?? '') !== 'GAMEMASTER' && ($u['chapter'] ?? '') !== 'admin';
});

// --- Збереження змін (редагування всіх пунктів + зміна пароля/коду входу) ---
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['save_onboarding']) && !empty($_POST['player_code'])) {
    $code = trim($_POST['player_code']);
    $users = getJson(USERS_FILE);
    foreach ($users as $i => $u) {
        if (($u['access_code'] ?? '') === $code || ($u['id'] ?? '') === $code) {
            $users[$i]['history'] = $_POST['history'] ?? $users[$i]['history'] ?? '';
            $users[$i]['abilities'] = $_POST['abilities'] ?? $users[$i]['abilities'] ?? '';
            // Зміна пароля (коду входу)
            $newCode = trim((string)($_POST['new_access_code'] ?? ''));
            if ($newCode !== '') {
                $users[$i]['access_code'] = $newCode;
                $code = $newCode; // для редиректу з оновленим кодом
            }
            $lvl = isset($_POST['level']) ? (int)$_POST['level'] : (isset($users[$i]['level']) ? (int)$users[$i]['level'] : 1);
            $users[$i]['level'] = max(1, min(5, $lvl));
            if (!isset($users[$i]['stats']) || !is_array($users[$i]['stats'])) $users[$i]['stats'] = [];
            $users[$i]['stats']['status'] = $_POST['med_status'] ?? $users[$i]['stats']['status'] ?? 'unknown';
            $psy = isset($_POST['psy']) ? (int)$_POST['psy'] : (isset($users[$i]['stats']['psy']) ? (int)$users[$i]['stats']['psy'] : 0);
            $users[$i]['stats']['psy'] = max(0, min(100, $psy));
            saveJson(USERS_FILE, $users);
            header('Location: admin.php?view=onboarding&player=' . urlencode($code) . '&saved=1');
            exit;
        }
    }
}

$selectedCode = $_GET['player'] ?? '';
$player = null;
if ($selectedCode) {
    foreach ($allPlayers as $u) {
        if (($u['access_code'] ?? '') === $selectedCode || ($u['id'] ?? '') === $selectedCode) {
            $player = $u;
            break;
        }
    }
}
?>
<div style="border-bottom:1px solid #333; padding-bottom:10px; margin-bottom:20px; color:#666;">
    <h2 style="color:#00f0ff; margin:0;">ONBOARDING</h2>
    <p style="margin:5px 0 0 0;">Оберіть гравця — далі по пунктах: біо, здібності, відмітка по мед-карті. Усі поля можна редагувати та зберегти.</p>
    <?php if (!empty($_GET['saved'])): ?><p style="color:#0f0; margin-top:10px;">✓ Зміни збережено.</p><?php endif; ?>
</div>

<form method="GET" style="margin-bottom:25px;">
    <input type="hidden" name="view" value="onboarding">
    <label style="color:#888; font-size:0.8rem;">ГРАВЕЦЬ:</label>
    <select name="player" onchange="this.form.submit()" style="width:100%; max-width:400px; background:#111; border:1px solid #00f0ff; color:#fff; padding:10px; margin-top:5px;">
        <option value="">— оберіть —</option>
        <?php foreach ($playable as $u): ?>
            <option value="<?= htmlspecialchars($u['access_code'] ?? $u['id']) ?>" <?= ($u['access_code'] ?? '') === $selectedCode ? 'selected' : '' ?>>
                <?= htmlspecialchars($u['name'] ?? $u['access_code']) ?> (<?= htmlspecialchars($u['role'] ?? '—') ?>)
            </option>
        <?php endforeach; ?>
    </select>
</form>

<?php if ($player):
    $stats = $player['stats'] ?? [];
    $psy = isset($stats['psy']) ? (int)$stats['psy'] : 0;
    $statusRaw = $stats['status'] ?? 'unknown';
    if (!isset($statusLabels[$statusRaw])) {
        if (in_array($statusRaw, ['OK', 'STABLE'])) $statusRaw = 'healthy';
        elseif (in_array($statusRaw, ['INJURED', 'CRITICAL'])) $statusRaw = 'infected';
        else $statusRaw = 'unknown';
    }
?>
<form method="POST">
    <input type="hidden" name="view" value="onboarding">
    <input type="hidden" name="save_onboarding" value="1">
    <input type="hidden" name="player_code" value="<?= htmlspecialchars($player['access_code'] ?? $player['id']) ?>">

    <div style="display:grid; gap:20px;">
        <div class="editor" style="border-left:4px solid #0f0;">
            <h3 style="color:#0f0; margin-top:0;">0. Пароль (код входу) та рівень допуску</h3>
            <p style="color:#888; font-size:0.8rem; margin:0 0 10px 0;">Поточний код: <code style="color:#0f0;"><?= htmlspecialchars($player['access_code'] ?? '—') ?></code>. Щоб змінити — введіть новий код нижче; листіть порожнім, щоб не змінювати.</p>
            <input type="text" name="new_access_code" placeholder="Новий код входу (пароль) — залишити порожнім = не змінювати" style="width:100%; max-width:400px; background:#0a0a0a; border:1px solid #333; color:#fff; padding:10px; font-family:monospace; box-sizing:border-box;" value="" autocomplete="off">
            <label style="color:#888; font-size:0.8rem; display:block; margin-top:12px;">Рівень допуску (LEVEL):</label>
            <select name="level" style="width:100%; max-width:120px; background:#111; border:1px solid #444; color:#fff; padding:10px; margin-top:5px;">
                <?php $pl = max(1, min(5, (int)($player['level'] ?? 1))); for ($i = 1; $i <= 5; $i++): ?>
                    <option value="<?= $i ?>" <?= $pl === $i ? 'selected' : '' ?>>LVL <?= $i ?></option>
                <?php endfor; ?>
            </select>
        </div>
        <div class="editor" style="border-left:4px solid #00f0ff;">
            <h3 style="color:#00f0ff; margin-top:0;">1. Біо / Історія</h3>
            <textarea name="history" rows="12" style="width:100%; background:#0a0a0a; border:1px solid #333; color:#fff; padding:12px; font-family:inherit; box-sizing:border-box;"><?= htmlspecialchars($player['history'] ?? '') ?></textarea>
        </div>
        <div class="editor" style="border-left:4px solid #ffd700;">
            <h3 style="color:#ffd700; margin-top:0;">2. Здібності</h3>
            <textarea name="abilities" rows="6" style="width:100%; background:#0a0a0a; border:1px solid #333; color:#fff; padding:12px; font-family:inherit; box-sizing:border-box;"><?= htmlspecialchars($player['abilities'] ?? '') ?></textarea>
        </div>
        <div class="editor" style="border-left:4px solid #f55;">
            <h3 style="color:#f55; margin-top:0;">3. Мед-карта</h3>
            <label style="color:#888; font-size:0.8rem;">Статус:</label>
            <select name="med_status" style="width:100%; max-width:300px; background:#111; border:1px solid #444; color:#fff; padding:10px; margin-top:5px;">
                <option value="healthy" <?= $statusRaw === 'healthy' ? 'selected' : '' ?>>Здоровий</option>
                <option value="infected" <?= $statusRaw === 'infected' ? 'selected' : '' ?>>Заражений</option>
                <option value="unknown" <?= $statusRaw === 'unknown' ? 'selected' : '' ?>>Невідомо</option>
            </select>
            <label style="color:#888; font-size:0.8rem; display:block; margin-top:12px;">Психічне здоров'я (PSY), %:</label>
            <input type="number" name="psy" min="0" max="100" value="<?= $psy ?>" style="width:80px; background:#111; border:1px solid #444; color:#fff; padding:8px;">
        </div>
        <div>
            <button type="submit" class="btn-act" style="background:#00f0ff; color:#000;">ЗБЕРЕГТИ ВСІ ЗМІНИ</button>
        </div>
    </div>
</form>
<?php else: ?>
    <p style="color:#666;">Оберіть гравця зі списку вище.</p>
<?php endif; ?>
