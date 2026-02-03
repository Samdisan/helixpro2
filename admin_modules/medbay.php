<?php
// MED-BAY: Статус (Здоровий/Заражений/Невідомо) + Психічне здоров'я (psy) + вкладка «Запити гравців»
$medbayTab = $_GET['medbay_tab'] ?? 'monitoring';
$filterChapter = $_GET['chapter_filter'] ?? 'all';

$statusOptions = [
    'healthy'   => 'Здоровий',
    'infected'  => 'Заражений',
    'unknown'   => 'Невідомо',
];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['medbay_action'] ?? 'update';
    if ($action === 'fulfill_request') {
        $reqId = trim((string)($_POST['request_id'] ?? ''));
        if ($reqId !== '') {
            $list = getJson(ANALYSIS_REQUESTS_FILE);
            if (is_array($list)) {
                foreach ($list as &$r) {
                    if (($r['id'] ?? '') === $reqId) {
                        $r['status'] = 'done';
                        break;
                    }
                }
                unset($r);
                saveJson(ANALYSIS_REQUESTS_FILE, $list);
            }
        }
        echo "<script>window.location.href='admin.php?view=medbay&medbay_tab=requests&chapter_filter=" . urlencode($filterChapter) . "';</script>";
        exit;
    }
    if ($action === 'roll_symptoms') {
        $act = max(1, min(10, (int)($_POST['act'] ?? 1)));
        $data = getJson(USERS_FILE);
        foreach ($data as $i => $u) {
            if (($u['role'] ?? '') === 'GAMEMASTER' || ($u['chapter'] ?? '') === 'admin') continue;
            if (($u['chapter'] ?? '') !== 'ch2') continue;
            $isOrigin = (($u['faction'] ?? '') === 'ORIGIN');
            $pct = $isOrigin ? (10 + 5 * $act) : (3 + 1 * $act);
            $pct = min(100, $pct);
            $data[$i]['black_capillaries_in_eyes'] = (mt_rand(1, 100) <= $pct);
            $data[$i]['hot_breathing'] = (mt_rand(1, 100) <= $pct);
        }
        saveJson(USERS_FILE, $data);
        echo "<script>window.location.href='admin.php?view=medbay&medbay_tab=monitoring&chapter_filter=" . urlencode($filterChapter) . "';</script>";
        exit;
    }
    $data = getJson(USERS_FILE);
    foreach ($data as &$user) {
        if ($user['id'] === $_POST['target_id']) {
            if (!isset($user['stats']) || !is_array($user['stats'])) {
                $user['stats'] = ['psy' => 100, 'status' => 'unknown'];
            }
            $user['stats']['psy'] = max(0, min(100, (int)($_POST['psy'] ?? 100)));
            $user['stats']['status'] = isset($statusOptions[$_POST['status']]) ? $_POST['status'] : 'unknown';
            $user['black_capillaries_in_eyes'] = !empty($_POST['black_capillaries_in_eyes']);
            $user['hot_breathing'] = !empty($_POST['hot_breathing']);
            break;
        }
    }
    saveJson(USERS_FILE, $data);
    echo "<script>window.location.href='admin.php?view=medbay&medbay_tab=monitoring&chapter_filter=" . urlencode($filterChapter) . "';</script>";
    exit;
}

$allPlayers = getJson(USERS_FILE);
$players = array_filter($allPlayers, function($u) use ($filterChapter) {
    if (($u['role'] ?? '') === 'GAMEMASTER' || ($u['chapter'] ?? '') === 'admin') return false;
    if ($filterChapter === 'all') return true;
    return ($u['chapter'] ?? '') === $filterChapter;
});
?>

<div class="filter-bar" style="margin: -20px -20px 20px -20px;">
    <span style="color:#666;">ВКЛАДКИ:</span>
    <a href="?view=medbay&medbay_tab=monitoring&chapter_filter=<?= urlencode($filterChapter) ?>" class="filter-btn <?= $medbayTab==='monitoring'?'active':'' ?>">МОНІТОРИНГ</a>
    <a href="?view=medbay&medbay_tab=requests&chapter_filter=<?= urlencode($filterChapter) ?>" class="filter-btn <?= $medbayTab==='requests'?'active':'' ?>">ЗАПИТИ ГРАВЦІВ</a>
</div>
<div class="filter-bar" style="margin: -20px -20px 20px -20px;">
    <span style="color:#666;">СЕКТОР:</span>
    <a href="?view=medbay&medbay_tab=<?= urlencode($medbayTab) ?>&chapter_filter=all" class="filter-btn <?= $filterChapter=='all'?'active':'' ?>">ВСІ</a>
    <a href="?view=medbay&medbay_tab=<?= urlencode($medbayTab) ?>&chapter_filter=ch1" class="filter-btn <?= $filterChapter=='ch1'?'active':'' ?>">ARCTIC</a>
    <a href="?view=medbay&medbay_tab=<?= urlencode($medbayTab) ?>&chapter_filter=ch2" class="filter-btn <?= $filterChapter=='ch2'?'active':'' ?>">PANDORA</a>
</div>

<?php if ($medbayTab === 'requests'): ?>
<?php
$analysisRequests = [];
if (file_exists(ANALYSIS_REQUESTS_FILE)) {
    $raw = @file_get_contents(ANALYSIS_REQUESTS_FILE);
    $analysisRequests = $raw ? (json_decode($raw, true) ?: []) : [];
}
if (!is_array($analysisRequests)) $analysisRequests = [];
$usersById = [];
foreach ($allPlayers as $u) {
    $c = $u['access_code'] ?? $u['id'] ?? '';
    if ($c !== '') $usersById[$c] = $u;
}
?>
<h2 style="color:#f55; margin-top:0;">MED-BAY — Запити гравців на дослідження</h2>
<p style="color:#888; font-size:0.85rem;">Запити на проведення аналізів (об'єкт дослідження), надіслані з кабінету гравця.</p>
<?php if (empty($analysisRequests)): ?>
<div class="editor" style="padding:30px; text-align:center; color:#666;">Запитів поки немає.</div>
<?php else: ?>
<table class="data-table">
    <thead>
        <tr>
            <td style="color:#666; font-size:0.7rem;">ХТО ЗАМОВИВ</td>
            <td style="color:#666; font-size:0.7rem;">ОБ'ЄКТ ДОСЛІДЖЕННЯ</td>
            <td style="color:#666; font-size:0.7rem;">ДАНІ АНАЛІЗУ (одразу)</td>
            <td style="color:#666; font-size:0.7rem;">ДАТА</td>
            <td style="color:#666; font-size:0.7rem;">СТАТУС</td>
            <td style="color:#666; font-size:0.7rem;">ДІЯ</td>
        </tr>
    </thead>
    <tbody>
    <?php
    $statusOpts = ['healthy' => 'Здоровий', 'infected' => 'Заражений', 'unknown' => 'Невідомо'];
    foreach (array_reverse($analysisRequests) as $req):
        $rCode = $req['requester_code'] ?? '';
        $tCode = $req['target_code'] ?? '';
        $ru = $usersById[$rCode] ?? null;
        $tu = $usersById[$tCode] ?? null;
        $rName = $ru ? (htmlspecialchars($ru['name'] ?? $rCode)) : $rCode;
        $tName = $tu ? (htmlspecialchars($tu['name'] ?? $tCode)) : $tCode;
        $tStat = $tu && isset($tu['stats']['status']) ? ($statusOpts[$tu['stats']['status']] ?? $tu['stats']['status']) : '—';
        $tPsy = $tu && isset($tu['stats']['psy']) ? (int)$tu['stats']['psy'] : '—';
        $tCap = $tu ? (!empty($tu['black_capillaries_in_eyes']) ? 'Так' : 'Ні') : '—';
        $tHot = $tu ? (!empty($tu['hot_breathing']) ? 'Так' : 'Ні') : '—';
        $analysisLine = $tu ? 'Статус: ' . $tStat . ' · PSY: ' . $tPsy . ' · Чорні капіляри: ' . $tCap . ' · Гаряче дихання: ' . $tHot : '—';
        $created = $req['created_at'] ?? '';
        if (preg_match('/^(\d{4}-\d{2}-\d{2})T(\d{2}:\d{2})/', $created, $m)) $created = $m[1] . ' ' . $m[2];
        $status = $req['status'] ?? 'pending';
        $statusLabel = $status === 'done' ? 'Виконано' : ($status === 'cancelled' ? 'Скасовано' : 'В очікуванні');
        $reqId = $req['id'] ?? '';
        $actionCell = '';
        if ($status === 'pending' && $reqId !== '') {
            $actionCell = '<form method="POST" style="margin:0; display:inline;"><input type="hidden" name="medbay_action" value="fulfill_request"><input type="hidden" name="request_id" value="' . htmlspecialchars($reqId) . '"><button type="submit" class="btn-act" style="border-color:#0f0; color:#0f0; font-size:0.75rem;">Віддати</button></form>';
        }
    ?>
        <tr>
            <td style="color:#fff;"><?= $rName ?></td>
            <td style="color:#00f0ff;"><?= $tName ?></td>
            <td style="color:#bbb; font-size:0.8rem;"><?= htmlspecialchars($analysisLine) ?></td>
            <td style="color:#888; font-size:0.85rem;"><?= htmlspecialchars($created) ?></td>
            <td style="color:#ffd700;"><?= $statusLabel ?></td>
            <td><?= $actionCell ?></td>
        </tr>
    <?php endforeach; ?>
    </tbody>
</table>
<?php endif; ?>
<?php else: ?>
<h2 style="color:#f55; margin-top:0;">MED-BAY — Статус та психічне здоров'я</h2>
<p style="color:#888; font-size:0.85rem; margin-top:-5px;">Клік по рядку або «Картка» — відкрити медкарту. Ймовірність симптомів: ORIGIN 10%+5%×акт, інші 3%+1%×акт.</p>
<form method="POST" style="margin-bottom:16px; display:flex; align-items:center; gap:12px;">
    <input type="hidden" name="medbay_action" value="roll_symptoms">
    <label style="color:#888;">Акт (1–10):</label>
    <input type="number" name="act" value="1" min="1" max="10" style="width:60px; background:#111; color:#fff; border:1px solid #444; padding:6px;">
    <button type="submit" class="btn-act" style="border-color:#f55; color:#f55;" onclick="return confirm('Розкинути симптоми (чорні капіляри, гаряче дихання) для всіх ch2 за ймовірністю?');">Розкинути симптоми за актом</button>
</form>
<table class="data-table med-list-table">
    <thead>
        <tr>
            <td style="color:#666; font-size:0.7rem;">ІМ'Я</td>
            <td style="color:#666; font-size:0.7rem;">РОЛЬ</td>
            <td style="color:#666; font-size:0.7rem;">ФРАКЦІЯ</td>
            <td style="color:#666; font-size:0.7rem;">СЕКТОР</td>
            <td style="color:#666; font-size:0.7rem;">СТАТУС</td>
            <td style="color:#666; font-size:0.7rem;">PSY</td>
            <td style="color:#666; font-size:0.7rem;">ЧОРН. КАП.</td>
            <td style="color:#666; font-size:0.7rem;">ГАРЯЧ. ДИХ.</td>
            <td style="color:#666; font-size:0.7rem;">БУНКЕР</td>
            <td style="color:#666; font-size:0.7rem;">ДІЯ</td>
        </tr>
    </thead>
    <tbody>
    <?php foreach ($players as $u):
        $stats = $u['stats'] ?? ['psy' => 100, 'status' => 'unknown'];
        $psy = isset($stats['psy']) ? (int)$stats['psy'] : 100;
        $statusRaw = $stats['status'] ?? 'unknown';
        if (!isset($statusOptions[$statusRaw])) {
            if (in_array($statusRaw, ['OK', 'STABLE'])) $statusRaw = 'healthy';
            elseif (in_array($statusRaw, ['INJURED', 'CRITICAL'])) $statusRaw = 'infected';
            else $statusRaw = 'unknown';
        }
        $bunkerTest = $u['bunker_test'] ?? null;
        $bunkerShort = ($bunkerTest && isset($bunkerTest['saved_at'])) ? ($bunkerTest['result_type'] ?? 'пройдено') : '—';
        $capillaries = !empty($u['black_capillaries_in_eyes']);
        $hotBreath = !empty($u['hot_breathing']);
    ?>
        <tr class="med-row" data-modal-id="med-modal-<?= htmlspecialchars($u['id']) ?>" style="cursor:pointer;">
            <td style="color:#fff;"><?= htmlspecialchars($u['name']) ?></td>
            <td style="color:#888;"><?= htmlspecialchars($u['role'] ?? '') ?></td>
            <td style="color:#00f0ff;"><?= htmlspecialchars($u['faction'] ?? '') ?></td>
            <td style="color:#888;"><?= htmlspecialchars($u['chapter'] ?? '') ?></td>
            <td style="color:#ffd700;"><?= $statusOptions[$statusRaw] ?? $statusRaw ?></td>
            <td style="color:#00f0ff;"><?= $psy ?></td>
            <td style="color:<?= $capillaries ? '#f55' : '#666' ?>; font-size:0.8rem;"><?= $capillaries ? 'Так' : '—' ?></td>
            <td style="color:<?= $hotBreath ? '#f55' : '#666' ?>; font-size:0.8rem;"><?= $hotBreath ? 'Так' : '—' ?></td>
            <td style="color:#888; font-size:0.8rem;"><?= $bunkerShort ?></td>
            <td><button type="button" class="btn-act med-open-btn" data-modal-id="med-modal-<?= htmlspecialchars($u['id']) ?>" onclick="event.stopPropagation();">Картка</button></td>
        </tr>
    <?php endforeach; ?>
    </tbody>
</table>

<div class="med-backdrop" id="med-backdrop" aria-hidden="true"></div>

<?php foreach ($players as $u):
    $stats = $u['stats'] ?? ['psy' => 100, 'status' => 'unknown'];
    $psy = isset($stats['psy']) ? (int)$stats['psy'] : 100;
    $statusRaw = $stats['status'] ?? 'unknown';
    if (!isset($statusOptions[$statusRaw])) {
        if (in_array($statusRaw, ['OK', 'STABLE'])) $statusRaw = 'healthy';
        elseif (in_array($statusRaw, ['INJURED', 'CRITICAL'])) $statusRaw = 'infected';
        else $statusRaw = 'unknown';
    }
    $bunkerTest = $u['bunker_test'] ?? null;
    $bunkerLabel = 'Протокол бункера: —';
    if ($bunkerTest && isset($bunkerTest['saved_at'])) {
        $bunkerLabel = 'Протокол бункера: пройдено';
        if (!empty($bunkerTest['result_type'])) {
            $bunkerLabel .= ' · ' . $bunkerTest['result_type'];
        }
        $ans = $bunkerTest['answers'] ?? [];
        if (is_array($ans) && !empty($ans)) {
            $points = ['A' => 4, 'B' => 3, 'C' => 2, 'D' => 1];
            $counts = ['A' => 0, 'B' => 0, 'C' => 0, 'D' => 0];
            $total = 0;
            foreach ($ans as $letter) {
                $letter = strtoupper(trim((string)$letter));
                if (isset($points[$letter])) {
                    $total += $points[$letter];
                    $counts[$letter]++;
                }
            }
            $maxScore = 8 * 4;
            $bunkerLabel .= ' · Оцінка ' . $total . '/' . $maxScore;
            $bunkerLabel .= ' (A:' . $counts['A'] . ' B:' . $counts['B'] . ' C:' . $counts['C'] . ' D:' . $counts['D'] . ')';
        }
    }
?>
<div class="med-modal" id="med-modal-<?= htmlspecialchars($u['id']) ?>" role="dialog" aria-labelledby="med-title-<?= htmlspecialchars($u['id']) ?>" aria-modal="true" style="display:none;">
    <div class="med-modal-inner" onclick="event.stopPropagation();">
        <div class="med-player-data">
            <h3 id="med-title-<?= htmlspecialchars($u['id']) ?>" style="color:#00f0ff; margin:0 0 12px 0; font-size:1rem;"><?= htmlspecialchars($u['name']) ?></h3>
            <table style="color:#888; font-size:0.8rem; width:100%; border-collapse:collapse;">
                <tr><td style="padding:4px 8px 4px 0;">Код доступу</td><td><code><?= htmlspecialchars($u['access_code'] ?? '') ?></code></td></tr>
                <tr><td style="padding:4px 8px 4px 0;">Роль</td><td><?= htmlspecialchars($u['role'] ?? '') ?></td></tr>
                <tr><td style="padding:4px 8px 4px 0;">Фракція</td><td><?= htmlspecialchars($u['faction'] ?? '') ?></td></tr>
                <tr><td style="padding:4px 8px 4px 0;">Сектор</td><td><?= htmlspecialchars($u['chapter'] ?? '') ?></td></tr>
                <tr><td style="padding:4px 8px 4px 0;">Рівень</td><td><?= htmlspecialchars($u['level'] ?? '') ?></td></tr>
                <tr><td style="padding:4px 8px 4px 0;">Бронь</td><td><?= htmlspecialchars($u['booking_status'] ?? '') ?></td></tr>
            </table>
        </div>
        <form method="POST" class="med-card" style="margin-top:16px;">
            <input type="hidden" name="target_id" value="<?= htmlspecialchars($u['id']) ?>">
            <label style="color:#666; font-size:0.7rem;">СТАТУС</label>
            <select name="status" style="width:100%; background:#000; color:#fff; padding:8px; margin-bottom:12px; border:1px solid #444;">
                <?php foreach ($statusOptions as $val => $label): ?>
                    <option value="<?= $val ?>" <?= $statusRaw === $val ? 'selected' : '' ?>><?= $label ?></option>
                <?php endforeach; ?>
            </select>
            <label style="color:#666; font-size:0.7rem;">ПСИХІЧНЕ ЗДОРОВ'Я (0–100)</label>
            <div class="stat-row psy" style="margin-bottom:8px;">
                <span class="stat-label">PSY</span>
                <div class="bar-bg"><div class="bar-fill" style="width:<?= $psy ?>%"></div></div>
                <input type="number" name="psy" value="<?= $psy ?>" class="stat-input" min="0" max="100">
            </div>
            <label style="color:#666; font-size:0.7rem; display:block; margin-top:12px;">СИМПТОМИ ЕТАПУ I</label>
            <label style="display:block; margin:6px 0;"><input type="checkbox" name="black_capillaries_in_eyes" value="1" <?= !empty($u['black_capillaries_in_eyes']) ? 'checked' : '' ?>> Чорні капіляри в очах</label>
            <label style="display:block; margin:6px 0;"><input type="checkbox" name="hot_breathing" value="1" <?= !empty($u['hot_breathing']) ? 'checked' : '' ?>> Гаряче дихання</label>
            <div style="color:#666; font-size:0.7rem; margin-bottom:8px;"><?= htmlspecialchars($bunkerLabel) ?></div>
            <button class="btn-act" style="width:100%; margin-top:10px;">ОНОВИТИ</button>
        </form>
        <button type="button" class="med-close-btn" onclick="window.medbayCloseModal()" aria-label="Закрити">×</button>
    </div>
</div>
<?php endforeach; ?>

<script>
(function() {
    var backdrop = document.getElementById('med-backdrop');
    function openModal(id) {
        var el = document.getElementById(id);
        if (el && backdrop) {
            backdrop.style.display = 'block';
            el.style.display = 'flex';
            document.body.style.overflow = 'hidden';
        }
    }
    function closeModal() {
        if (backdrop) backdrop.style.display = 'none';
        document.querySelectorAll('.med-modal').forEach(function(m) { m.style.display = 'none'; });
        document.body.style.overflow = '';
    }
    window.medbayCloseModal = closeModal;
    backdrop.addEventListener('click', closeModal);
    document.querySelectorAll('.med-row').forEach(function(row) {
        row.addEventListener('click', function() {
            var id = row.getAttribute('data-modal-id');
            if (id) openModal(id);
        });
    });
    document.querySelectorAll('.med-open-btn').forEach(function(btn) {
        btn.addEventListener('click', function() {
            var id = btn.getAttribute('data-modal-id');
            if (id) openModal(id);
        });
    });
    document.querySelectorAll('.med-modal').forEach(function(modal) {
        modal.addEventListener('click', function(e) {
            if (e.target === modal) closeModal();
        });
    });
})();
</script>
<?php endif; ?>
