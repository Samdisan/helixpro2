<?php
// admin_modules/quests.php — перегляд, додавання, редагування, видалення цілей (quests.json)

$quests = getJson(QUESTS_FILE);
$faction_goals = $quests['faction_goals'] ?? [];
$personal_goals = $quests['personal_goals'] ?? [];
$pushed_faction_goals = $quests['pushed_faction_goals'] ?? [];
$pushed_personal_goals = $quests['pushed_personal_goals'] ?? [];
if (!is_array($pushed_faction_goals)) $pushed_faction_goals = [];
if (!is_array($pushed_personal_goals)) $pushed_personal_goals = [];
$saveOk = false;
$saveError = null;

function saveQuests($faction_goals, $personal_goals, $pushed_faction_goals, $pushed_personal_goals) {
    saveJson(QUESTS_FILE, [
        'faction_goals' => $faction_goals,
        'personal_goals' => $personal_goals,
        'pushed_faction_goals' => $pushed_faction_goals,
        'pushed_personal_goals' => $pushed_personal_goals
    ]);
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';

    if ($action === 'save_raw') {
        $raw = $_POST['quests_json'] ?? '{}';
        $decoded = json_decode($raw, true);
        if (json_last_error() === JSON_ERROR_NONE) {
            $quests = array_merge(['faction_goals' => [], 'personal_goals' => [], 'pushed_faction_goals' => [], 'pushed_personal_goals' => []], $decoded);
            $faction_goals = $quests['faction_goals'] ?? [];
            $personal_goals = $quests['personal_goals'] ?? [];
            $pushed_faction_goals = is_array($quests['pushed_faction_goals'] ?? null) ? $quests['pushed_faction_goals'] : [];
            $pushed_personal_goals = is_array($quests['pushed_personal_goals'] ?? null) ? $quests['pushed_personal_goals'] : [];
            saveQuests($faction_goals, $personal_goals, $pushed_faction_goals, $pushed_personal_goals);
            $saveOk = true;
        } else {
            $saveError = 'Невірний JSON: ' . json_last_error_msg();
        }
    } elseif ($action === 'add_faction') {
        $f = $_POST['faction'] ?? '';
        $id = trim($_POST['goal_id'] ?? '');
        $title = trim($_POST['title'] ?? '');
        $text = trim($_POST['text'] ?? '');
        $incSymptom = !empty($_POST['increases_symptom_probability']);
        if ($f && $id && $title && in_array($f, ['OLYMPOS', 'ORIGIN', 'THEMIS', 'MOIRAI'])) {
            if (!isset($faction_goals[$f])) $faction_goals[$f] = [];
            $faction_goals[$f][] = ['id' => $id, 'title' => $title, 'text' => $text, 'increases_symptom_probability' => $incSymptom];
            saveQuests($faction_goals, $personal_goals, $pushed_faction_goals, $pushed_personal_goals);
            $saveOk = true;
        }
    } elseif ($action === 'add_personal') {
        $code = trim($_POST['access_code'] ?? '');
        $id = trim($_POST['goal_id'] ?? '');
        $title = trim($_POST['title'] ?? '');
        $text = trim($_POST['text'] ?? '');
        $incSymptom = !empty($_POST['increases_symptom_probability']);
        if ($code && $id && $title) {
            if (!isset($personal_goals[$code])) $personal_goals[$code] = [];
            $personal_goals[$code][] = ['id' => $id, 'title' => $title, 'text' => $text, 'increases_symptom_probability' => $incSymptom];
            saveQuests($faction_goals, $personal_goals, $pushed_faction_goals, $pushed_personal_goals);
            $saveOk = true;
        }
    } elseif ($action === 'delete_faction') {
        $f = $_POST['faction'] ?? '';
        $id = $_POST['goal_id'] ?? '';
        if ($f && $id && isset($faction_goals[$f])) {
            $faction_goals[$f] = array_values(array_filter($faction_goals[$f], fn($g) => ($g['id'] ?? '') !== $id));
            saveQuests($faction_goals, $personal_goals, $pushed_faction_goals, $pushed_personal_goals);
            $saveOk = true;
        }
    } elseif ($action === 'delete_personal') {
        $code = $_POST['access_code'] ?? '';
        $id = $_POST['goal_id'] ?? '';
        if ($code && $id && isset($personal_goals[$code])) {
            $personal_goals[$code] = array_values(array_filter($personal_goals[$code], fn($g) => ($g['id'] ?? '') !== $id));
            if (empty($personal_goals[$code])) unset($personal_goals[$code]);
            saveQuests($faction_goals, $personal_goals, $pushed_faction_goals, $pushed_personal_goals);
            $saveOk = true;
        }
    } elseif ($action === 'edit_faction') {
        $f = $_POST['faction'] ?? '';
        $id = $_POST['goal_id'] ?? '';
        $title = trim($_POST['title'] ?? '');
        $text = trim($_POST['text'] ?? '');
        $incSymptom = !empty($_POST['increases_symptom_probability']);
        if ($f && $id && $title && isset($faction_goals[$f])) {
            foreach ($faction_goals[$f] as &$g) {
                if (($g['id'] ?? '') === $id) { $g['title'] = $title; $g['text'] = $text; $g['increases_symptom_probability'] = $incSymptom; break; }
            }
            saveQuests($faction_goals, $personal_goals, $pushed_faction_goals, $pushed_personal_goals);
            $saveOk = true;
        }
    } elseif ($action === 'edit_personal') {
        $code = $_POST['access_code'] ?? '';
        $id = $_POST['goal_id'] ?? '';
        $title = trim($_POST['title'] ?? '');
        $text = trim($_POST['text'] ?? '');
        $incSymptom = !empty($_POST['increases_symptom_probability']);
        if ($code && $id && $title && isset($personal_goals[$code])) {
            foreach ($personal_goals[$code] as &$g) {
                if (($g['id'] ?? '') === $id) { $g['title'] = $title; $g['text'] = $text; $g['increases_symptom_probability'] = $incSymptom; break; }
            }
            saveQuests($faction_goals, $personal_goals, $pushed_faction_goals, $pushed_personal_goals);
            $saveOk = true;
        }
    } elseif ($action === 'send_faction') {
        $f = $_POST['faction'] ?? '';
        $id = $_POST['goal_id'] ?? '';
        if ($f && $id && isset($faction_goals[$f])) {
            if (!isset($pushed_faction_goals[$f])) $pushed_faction_goals[$f] = [];
            if (!in_array($id, $pushed_faction_goals[$f], true)) {
                $pushed_faction_goals[$f][] = $id;
            }
            saveQuests($faction_goals, $personal_goals, $pushed_faction_goals, $pushed_personal_goals);
            $saveOk = true;
        }
    } elseif ($action === 'send_personal') {
        $code = trim($_POST['access_code'] ?? '');
        $id = $_POST['goal_id'] ?? '';
        if ($code && $id && isset($personal_goals[$code])) {
            if (!isset($pushed_personal_goals[$code])) $pushed_personal_goals[$code] = [];
            if (!in_array($id, $pushed_personal_goals[$code], true)) {
                $pushed_personal_goals[$code][] = $id;
            }
            saveQuests($faction_goals, $personal_goals, $pushed_faction_goals, $pushed_personal_goals);
            $saveOk = true;
        }
    }

    if ($saveOk) {
        echo "<script>window.location.href='admin.php?view=quests';</script>";
        exit;
    }
}

$factions = ['OLYMPOS', 'ORIGIN', 'THEMIS', 'MOIRAI'];
?>

<div style="border-bottom:1px solid #333; padding-bottom:10px; margin-bottom:20px; color:#666;">
    ЦІЛІ ТА МІСІЇ — дані з <code>quests.json</code>. Додавання, редагування, видалення. У кабінетах гравців цілі беруться звідси.
</div>
<?php if ($saveError): ?>
    <p style="color:#f55;"><?= htmlspecialchars($saveError) ?></p>
<?php endif; ?>

<h3 style="color:#ffd700;">Додати фракційну ціль</h3>
<form method="POST" class="editor" style="margin-bottom:25px;">
    <input type="hidden" name="action" value="add_faction">
    <div class="row">
        <select name="faction" required style="flex:1;">
            <?php foreach ($factions as $f): ?>
                <option value="<?= htmlspecialchars($f) ?>"><?= htmlspecialchars($f) ?></option>
            <?php endforeach; ?>
        </select>
        <input type="text" name="goal_id" placeholder="ID цілі (напр. F_OLYMPOS_3)" required style="flex:1;">
    </div>
    <input type="text" name="title" placeholder="Назва" required>
    <textarea name="text" placeholder="Опис" style="height:80px;"></textarea>
    <label style="display:block; margin:10px 0; color:#888;"><input type="checkbox" name="increases_symptom_probability" value="1"> Збільшує ймовірність симптомів (чорні капіляри / гаряче дихання)</label>
    <button type="submit" class="btn-act" style="border-color:#0f0; color:#0f0;">ДОДАТИ</button>
</form>

<h3 style="color:#ffd700;">Додати персональну ціль</h3>
<form method="POST" class="editor" style="margin-bottom:25px;">
    <input type="hidden" name="action" value="add_personal">
    <div class="row">
        <input type="text" name="access_code" placeholder="Access code гравця" required style="flex:1;">
        <input type="text" name="goal_id" placeholder="ID цілі (напр. P_ARCH_2)" required style="flex:1;">
    </div>
    <input type="text" name="title" placeholder="Назва" required>
    <textarea name="text" placeholder="Опис" style="height:80px;"></textarea>
    <label style="display:block; margin:10px 0; color:#888;"><input type="checkbox" name="increases_symptom_probability" value="1"> Збільшує ймовірність симптомів (чорні капіляри / гаряче дихання)</label>
    <button type="submit" class="btn-act" style="border-color:#0f0; color:#0f0;">ДОДАТИ</button>
</form>

<h3 style="color:#ffd700;">Фракційні цілі (faction_goals)</h3>
<table class="data-table">
    <thead>
        <tr><th>Фракція</th><th>ID</th><th>Назва</th><th></th></tr>
    </thead>
    <tbody>
        <?php foreach ($factions as $f):
            foreach ($faction_goals[$f] ?? [] as $g):
                $id = $g['id'] ?? ''; $title = $g['title'] ?? ''; $text = $g['text'] ?? '';
        ?>
        <tr>
            <td style="color:#fff;"><?= htmlspecialchars($f) ?></td>
            <td style="color:#00f0ff;"><?= htmlspecialchars($id) ?></td>
            <td><?= htmlspecialchars($title) ?><?php if (!empty($pushed_faction_goals[$f]) && in_array($id, $pushed_faction_goals[$f], true)): ?> <span style="color:#0a0; font-size:0.75rem;">(відправлено)</span><?php endif; ?></td>
            <td>
                <form method="POST" style="display:inline; margin-right:6px;">
                    <input type="hidden" name="action" value="send_faction">
                    <input type="hidden" name="faction" value="<?= h($f) ?>">
                    <input type="hidden" name="goal_id" value="<?= h($id) ?>">
                    <button type="submit" class="btn-act" style="padding:4px 8px; font-size:0.75rem; border-color:#0f0; color:#0f0;">Відправити</button>
                </form>
                <a href="?view=quests&edit=1&scope=faction&key=<?= urlencode($f) ?>&id=<?= urlencode($id) ?>" style="color:#ffd700; margin-right:8px;">РЕДАГУВАТИ</a>
                <form method="POST" style="display:inline;" onsubmit="return confirm('Видалити?');">
                    <input type="hidden" name="action" value="delete_faction">
                    <input type="hidden" name="faction" value="<?= htmlspecialchars($f) ?>">
                    <input type="hidden" name="goal_id" value="<?= htmlspecialchars($id) ?>">
                    <button type="submit" style="background:none; border:none; color:#f55; cursor:pointer;">ВИДАЛИТИ</button>
                </form>
            </td>
        </tr>
        <?php endforeach; endforeach; ?>
    </tbody>
</table>

<h3 style="color:#ffd700; margin-top:30px;">Персональні цілі (personal_goals)</h3>
<table class="data-table">
    <thead>
        <tr><th>Access code</th><th>ID</th><th>Назва</th><th></th></tr>
    </thead>
    <tbody>
        <?php
        if (empty($personal_goals)) {
            echo '<tr><td colspan="4" style="color:#666;">Немає персональних цілей</td></tr>';
        } else {
            foreach ($personal_goals as $code => $list) {
                foreach ($list as $g) {
                    $id = $g['id'] ?? ''; $title = $g['title'] ?? '';
                    $sent = !empty($pushed_personal_goals[$code]) && in_array($id, $pushed_personal_goals[$code], true);
                    echo '<tr><td style="color:#00f0ff;">' . htmlspecialchars($code) . '</td><td>' . htmlspecialchars($id) . '</td><td>' . htmlspecialchars($title) . ($sent ? ' <span style="color:#0a0; font-size:0.75rem;">(відправлено)</span>' : '') . '</td><td>';
                    echo '<form method="POST" style="display:inline; margin-right:6px;"><input type="hidden" name="action" value="send_personal"><input type="hidden" name="access_code" value="' . htmlspecialchars($code) . '"><input type="hidden" name="goal_id" value="' . htmlspecialchars($id) . '"><button type="submit" class="btn-act" style="padding:4px 8px; font-size:0.75rem; border-color:#0f0; color:#0f0;">Відправити</button></form>';
                    echo '<a href="?view=quests&edit=1&scope=personal&key=' . urlencode($code) . '&id=' . urlencode($id) . '" style="color:#ffd700; margin-right:8px;">РЕДАГУВАТИ</a>';
                    echo '<form method="POST" style="display:inline;" onsubmit="return confirm(\'Видалити?\');">';
                    echo '<input type="hidden" name="action" value="delete_personal"><input type="hidden" name="access_code" value="' . htmlspecialchars($code) . '"><input type="hidden" name="goal_id" value="' . htmlspecialchars($id) . '">';
                    echo '<button type="submit" style="background:none; border:none; color:#f55; cursor:pointer;">ВИДАЛИТИ</button></form></td></tr>';
                }
            }
        }
        ?>
    </tbody>
</table>

<?php
$editScope = $_GET['scope'] ?? '';
$editKey = $_GET['key'] ?? '';
$editId = $_GET['id'] ?? '';
$editGoal = null;
if ($editScope === 'faction' && $editKey && $editId && isset($faction_goals[$editKey])) {
    foreach ($faction_goals[$editKey] as $g) {
        if (($g['id'] ?? '') === $editId) { $editGoal = $g; $editGoal['_key'] = $editKey; $editGoal['_scope'] = 'faction'; break; }
    }
} elseif ($editScope === 'personal' && $editKey && $editId && isset($personal_goals[$editKey])) {
    foreach ($personal_goals[$editKey] as $g) {
        if (($g['id'] ?? '') === $editId) { $editGoal = $g; $editGoal['_key'] = $editKey; $editGoal['_scope'] = 'personal'; break; }
    }
}
if ($editGoal):
?>
<div class="editor" style="margin-top:30px; border-color:#ffd700;">
    <h3 style="color:#ffd700;">Редагувати ціль</h3>
    <form method="POST">
        <input type="hidden" name="action" value="<?= $editGoal['_scope'] === 'faction' ? 'edit_faction' : 'edit_personal' ?>">
        <input type="hidden" name="goal_id" value="<?= htmlspecialchars($editGoal['id'] ?? '') ?>">
        <?php if ($editGoal['_scope'] === 'faction'): ?>
            <input type="hidden" name="faction" value="<?= htmlspecialchars($editGoal['_key']) ?>">
        <?php else: ?>
            <input type="hidden" name="access_code" value="<?= htmlspecialchars($editGoal['_key']) ?>">
        <?php endif; ?>
        <input type="text" name="title" value="<?= htmlspecialchars($editGoal['title'] ?? '') ?>" required placeholder="Назва">
        <textarea name="text" placeholder="Опис" style="height:100px;"><?= htmlspecialchars($editGoal['text'] ?? '') ?></textarea>
        <label style="display:block; margin:10px 0; color:#888;"><input type="checkbox" name="increases_symptom_probability" value="1" <?= !empty($editGoal['increases_symptom_probability']) ? 'checked' : '' ?>> Збільшує ймовірність симптомів (чорні капіляри / гаряче дихання)</label>
        <button type="submit" class="btn-act" style="border-color:#ffd700; color:#ffd700;">ЗБЕРЕГТИ</button>
        <a href="?view=quests" style="margin-left:15px; color:#666;">Скасувати</a>
    </form>
</div>
<?php endif; ?>

<div class="editor" style="margin-top:30px;">
    <h3 style="margin-top:0; color:#ffd700;">Редагувати quests.json (сирий JSON)</h3>
    <form method="POST">
        <input type="hidden" name="action" value="save_raw">
        <textarea name="quests_json" style="height:280px; font-family:monospace; font-size:0.8rem;"><?= htmlspecialchars(json_encode(['faction_goals' => $faction_goals, 'personal_goals' => $personal_goals, 'pushed_faction_goals' => $pushed_faction_goals, 'pushed_personal_goals' => $pushed_personal_goals], JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE)) ?></textarea>
        <button type="submit" class="btn-act" style="border-color:#ffd700; color:#ffd700;">ЗБЕРЕГТИ JSON</button>
    </form>
</div>
