<?php
// Аналітика для ГМ: хто заходив, коли; K.I.R.A., бункер, взяті місії — агреговано, без PII (імён у таблиці активності немає)
$usersFile = dirname(__DIR__) . '/users.json';
$missionsFile = dirname(__DIR__) . '/missions_state.json';
$questsFile = dirname(__DIR__) . '/quests.json';

$users = file_exists($usersFile) ? (json_decode(file_get_contents($usersFile), true) ?: []) : [];
if (!is_array($users)) $users = [];

// Тільки гравці (не адмін/ГМ)
$players = array_filter($users, function ($u) {
    $role = $u['role'] ?? '';
    $chapter = $u['chapter'] ?? '';
    return $role !== 'GAMEMASTER' && $chapter !== 'admin';
});

// ——— Активність: ID, роль, сектор, остання активність (без імені — без PII) ———
$activity = [];
foreach ($players as $u) {
    $activity[] = [
        'id' => $u['id'] ?? '',
        'role' => $u['role'] ?? '',
        'chapter' => $u['chapter'] ?? '',
        'faction' => $u['faction'] ?? '',
        'last_active' => $u['last_active'] ?? null,
    ];
}
usort($activity, function ($a, $b) {
    $ta = $a['last_active'] ? strtotime($a['last_active']) : 0;
    $tb = $b['last_active'] ? strtotime($b['last_active']) : 0;
    return $tb - $ta;
});

$now = time();
$last24 = 0;
$last7d = 0;
foreach ($activity as $a) {
    if (empty($a['last_active'])) continue;
    $t = strtotime($a['last_active']);
    if ($now - $t <= 86400) $last24++;
    if ($now - $t <= 7 * 86400) $last7d++;
}

// ——— K.I.R.A. (агреговано) ———
$kiraCounts = [];
foreach ($players as $u) {
    $type = isset($u['kira_result']['type']) ? trim((string)$u['kira_result']['type']) : null;
    if ($type !== null && $type !== '') {
        $kiraCounts[$type] = ($kiraCounts[$type] ?? 0) + 1;
    }
}
ksort($kiraCounts);

// ——— Протокол бункера (агреговано) ———
$bunkerCounts = [];
foreach ($players as $u) {
    $bt = $u['bunker_test'] ?? null;
    $type = ($bt && isset($bt['result_type'])) ? trim((string)$bt['result_type']) : null;
    if ($type !== null && $type !== '') {
        $bunkerCounts[$type] = ($bunkerCounts[$type] ?? 0) + 1;
    }
}
ksort($bunkerCounts);

// ——— Взяті місії (агреговано: mission_id -> кількість гравців) ———
$missionsState = [];
if (file_exists($missionsFile)) {
    $raw = @file_get_contents($missionsFile);
    $missionsState = $raw ? (json_decode($raw, true) ?: []) : [];
}
if (!is_array($missionsState)) $missionsState = [];

$missionCounts = [];
foreach ($missionsState as $code => $list) {
    if (!is_array($list)) continue;
    foreach ($list as $mid) {
        $mid = trim((string)$mid);
        if ($mid !== '') {
            $missionCounts[$mid] = ($missionCounts[$mid] ?? 0) + 1;
        }
    }
}
ksort($missionCounts);

// Заголовки місій з quests.json (фракційні + персональні)
$questTitles = [];
if (file_exists($questsFile)) {
    $quests = json_decode(file_get_contents($questsFile), true) ?: [];
    foreach (['faction_goals', 'personal_goals'] as $key) {
        if (!isset($quests[$key]) || !is_array($quests[$key])) continue;
        foreach ($quests[$key] as $groupKey => $list) {
            if (!is_array($list)) continue;
            foreach ($list as $q) {
                if (isset($q['id'], $q['title'])) {
                    $questTitles[$q['id']] = $q['title'];
                }
            }
        }
    }
}
?>
<style>
.analytics-section { margin-bottom: 28px; }
.analytics-section h3 { color: #00f0ff; margin: 0 0 12px 0; font-size: 0.95rem; text-transform: uppercase; letter-spacing: 1px; }
.analytics-summary { color: #888; font-size: 0.85rem; margin-bottom: 10px; }
.analytics-table { width: 100%; border-collapse: collapse; margin-top: 8px; }
.analytics-table th, .analytics-table td { border-bottom: 1px solid #222; padding: 10px 12px; text-align: left; font-size: 0.8rem; }
.analytics-table th { color: #666; font-weight: bold; text-transform: uppercase; }
.analytics-table td { color: #ccc; }
.analytics-table tr:hover { background: #111; }
.analytics-bar { display: inline-block; background: #00f0ff; height: 6px; margin-right: 8px; vertical-align: middle; min-width: 4px; }
</style>

<h2 style="color:#ffd700; margin-top:0;">АНАЛІТИКА ДЛЯ ГМ</h2>
<p style="color:#888; font-size:0.85rem;">Агреговані дані: активність, K.I.R.A., Протокол бункера, взяті місії. Без PII (імён у таблиці активності немає).</p>

<div class="analytics-section">
    <h3>Активність (хто заходив, коли)</h3>
    <p class="analytics-summary">За останні 24 год: <strong style="color:#0f0;"><?= $last24 ?></strong> заходів &nbsp;//&nbsp; За останні 7 днів: <strong style="color:#0f0;"><?= $last7d ?></strong></p>
    <table class="analytics-table data-table">
        <thead>
            <tr>
                <th>ID</th>
                <th>Роль</th>
                <th>Фракція</th>
                <th>Сектор</th>
                <th>Остання активність</th>
            </tr>
        </thead>
        <tbody>
        <?php foreach ($activity as $a): ?>
            <tr>
                <td><code><?= h($a['id']) ?></code></td>
                <td><?= h($a['role']) ?></td>
                <td><?= h($a['faction']) ?></td>
                <td><?= h($a['chapter']) ?></td>
                <td style="color:#888;"><?= $a['last_active'] ? h(date('Y-m-d H:i', strtotime($a['last_active']))) : '—' ?></td>
            </tr>
        <?php endforeach; ?>
        <?php if (empty($activity)): ?>
            <tr><td colspan="5" style="color:#666;">Немає даних (пінг при заході в кабінет ще не викликався).</td></tr>
        <?php endif; ?>
        </tbody>
    </table>
</div>

<div class="analytics-section">
    <h3>K.I.R.A. (агреговано)</h3>
    <?php if (empty($kiraCounts)): ?>
    <p class="analytics-summary">Поки немає збережених результатів K.I.R.A.</p>
    <?php else: ?>
    <table class="analytics-table data-table">
        <thead>
            <tr>
                <th>Тип</th>
                <th>Кількість</th>
            </tr>
        </thead>
        <tbody>
        <?php foreach ($kiraCounts as $type => $cnt): ?>
            <tr>
                <td><?= h($type) ?></td>
                <td><?= (int)$cnt ?></td>
            </tr>
        <?php endforeach; ?>
        </tbody>
    </table>
    <?php endif; ?>
</div>

<div class="analytics-section">
    <h3>Протокол бункера (агреговано)</h3>
    <?php if (empty($bunkerCounts)): ?>
    <p class="analytics-summary">Поки немає збережених результатів Протоколу бункера.</p>
    <?php else: ?>
    <table class="analytics-table data-table">
        <thead>
            <tr>
                <th>Тип</th>
                <th>Кількість</th>
            </tr>
        </thead>
        <tbody>
        <?php foreach ($bunkerCounts as $type => $cnt): ?>
            <tr>
                <td><?= h($type) ?></td>
                <td><?= (int)$cnt ?></td>
            </tr>
        <?php endforeach; ?>
        </tbody>
    </table>
    <?php endif; ?>
</div>

<div class="analytics-section">
    <h3>Взяті місії (агреговано)</h3>
    <?php if (empty($missionCounts)): ?>
    <p class="analytics-summary">Поки немає взятих місій.</p>
    <?php else: ?>
    <table class="analytics-table data-table">
        <thead>
            <tr>
                <th>ID місії</th>
                <th>Назва (якщо є)</th>
                <th>Кількість гравців</th>
            </tr>
        </thead>
        <tbody>
        <?php foreach ($missionCounts as $mid => $cnt): ?>
            <tr>
                <td><code><?= h($mid) ?></code></td>
                <td style="color:#888; font-size:0.75rem;"><?= h($questTitles[$mid] ?? '—') ?></td>
                <td><?= (int)$cnt ?></td>
            </tr>
        <?php endforeach; ?>
        </tbody>
    </table>
    <?php endif; ?>
</div>
