<?php
// admin_modules/chapter_control.php

$target = $_GET['target'] ?? 'ch2'; 
$actIndex = isset($_GET['act_id']) ? (int)$_GET['act_id'] : null; // Вибраний акт
$chapterName = ($target === 'ch1') ? 'ARCTIC PROTOCOL' : 'PANDORA PROTOCOL';
$themeColor = ($target === 'ch1') ? '#00f0ff' : '#0f0';

// ЗАВАНТАЖЕННЯ ДАНИХ
$state = getJson(GAMESTATE_FILE);
$quests = getJson(QUESTS_FILE);
$users = getJson(USERS_FILE);

// Ініціалізація структури, якщо порожня
if (!isset($state[$target])) {
    $state[$target] = ['status' => 'stopped', 'end_time' => 0, 'duration' => 60, 'acts' => []];
}
if (!isset($state[$target]['acts'])) $state[$target]['acts'] = [];

// --- ФУНКЦІЯ: ПЕРЕРАХУНОК ЧАСУ АКТУ ---
function recalculateActDuration($target, $actId, $quests) {
    // 1. Знаходимо всі квести цього акту
    $actQuests = array_filter($quests, fn($q) => ($q['chapter'] ?? '') === $target && ($q['act_id'] ?? 0) == $actId);
    
    // 2. Рахуємо час персональних квестів для кожного гравця
    $playerTimes = [];
    $factionTimes = [];

    foreach ($actQuests as $q) {
        $dur = (int)$q['duration'];
        if ($q['type'] === 'personal') {
            $pid = $q['target']; // ID гравця
            if (!isset($playerTimes[$pid])) $playerTimes[$pid] = 0;
            $playerTimes[$pid] += $dur;
        } else {
            // Фракційні квести йдуть паралельно, беремо найдовший
            $fid = $q['target']; // Назва фракції
            if (!isset($factionTimes[$fid])) $factionTimes[$fid] = 0;
            if ($dur > $factionTimes[$fid]) $factionTimes[$fid] = $dur;
        }
    }

    // 3. Знаходимо максимум серед гравців (хто найбільш зайнятий)
    $maxPlayerTime = empty($playerTimes) ? 0 : max($playerTimes);
    
    // 4. Час акту = Максимум (Час найзайнятішого гравця, Найдовший фракційний квест)
    // Або, згідно ТЗ: "сума часу персональних квестів з максимальним часом гравця"
    // Якщо треба врахувати, що фракційний квест теж займає час:
    $maxFactionTime = empty($factionTimes) ? 0 : max($factionTimes);
    
    // Фінальний час: або найдовший ланцюжок персоналок, або найдовший загальний квест.
    // Якщо хочеш суворо ТІЛЬКИ по персоналках, прибери $maxFactionTime з max()
    $totalMinutes = max($maxPlayerTime, $maxFactionTime, 15); // Мінімум 15 хв на акт

    return $totalMinutes;
}

// --- ОБРОБКА POST ЗАПИТІВ ---
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    
    // 1. ДОДАВАННЯ АКТУ
    if ($_POST['action'] === 'add_act') {
        $state[$target]['acts'][] = ['name' => 'New Act', 'duration' => 15];
        saveJson(GAMESTATE_FILE, $state);
    }
    
    // 2. ЗБЕРЕЖЕННЯ НАЗВ АКТІВ
    elseif ($_POST['action'] === 'save_acts_names') {
        $names = $_POST['act_names'];
        foreach ($names as $idx => $name) {
            if(isset($state[$target]['acts'][$idx])) {
                $state[$target]['acts'][$idx]['name'] = htmlspecialchars($name);
            }
        }
        saveJson(GAMESTATE_FILE, $state);
    }

    // 3. ВИДАЛЕННЯ АКТУ (і квестів)
    elseif ($_POST['action'] === 'delete_act') {
        $delId = (int)$_POST['act_id'];
        array_splice($state[$target]['acts'], $delId, 1);
        // Видаляємо квести цього акту
        $quests = array_filter($quests, fn($q) => !(($q['chapter'] ?? '') === $target && ($q['act_id'] ?? 0) == $delId));
        saveJson(GAMESTATE_FILE, $state);
        saveJson(QUESTS_FILE, array_values($quests));
        echo "<script>window.location.href='admin.php?view=chapter_control&target=$target';</script>"; exit;
    }

    // 4. ДОДАВАННЯ КВЕСТУ
    elseif ($_POST['action'] === 'add_quest') {
        $newQuest = [
            'id' => uniqid('q_'),
            'chapter' => $target,
            'act_id' => (int)$_POST['act_id'],
            'type' => $_POST['q_type'], // faction / personal
            'target' => $_POST['q_target'], // OLYMPOS or u_123...
            'title' => $_POST['q_title'],
            'desc' => $_POST['q_desc'],
            'duration' => (int)$_POST['q_duration'],
            'status' => 'active'
        ];
        $quests[] = $newQuest;
        saveJson(QUESTS_FILE, $quests);
        
        // Авто-перерахунок часу акту
        $newTime = recalculateActDuration($target, $newQuest['act_id'], $quests);
        $state[$target]['acts'][$newQuest['act_id']]['duration'] = $newTime;
        saveJson(GAMESTATE_FILE, $state);
    }

    // 5. ВИДАЛЕННЯ КВЕСТУ
    elseif ($_POST['action'] === 'delete_quest') {
        $qActId = 0;
        foreach($quests as $k => $q) {
            if($q['id'] === $_POST['quest_id']) { 
                $qActId = $q['act_id']; 
                unset($quests[$k]); 
                break; 
            }
        }
        $quests = array_values($quests);
        saveJson(QUESTS_FILE, $quests);
        
        // Авто-перерахунок
        $newTime = recalculateActDuration($target, $qActId, $quests);
        $state[$target]['acts'][$qActId]['duration'] = $newTime;
        saveJson(GAMESTATE_FILE, $state);
    }

    // 6. ЗАПУСК ГРИ
    elseif ($_POST['action'] === 'start_game') {
        $totalMinutes = 0;
        foreach ($state[$target]['acts'] as $act) $totalMinutes += (int)$act['duration'];
        $state[$target]['status'] = 'running';
        $state[$target]['start_time'] = time();
        $state[$target]['end_time'] = time() + ($totalMinutes * 60);
        $state[$target]['duration'] = $totalMinutes;
        saveJson(GAMESTATE_FILE, $state);
    }

    // 7. СТОП ГРИ
    elseif ($_POST['action'] === 'stop_game') {
        $state[$target]['status'] = 'stopped';
        $state[$target]['end_time'] = 0;
        saveJson(GAMESTATE_FILE, $state);
    }

    // Редірект, щоб зберегти вибраний акт
    $actParam = ($actIndex !== null) ? "&act_id=$actIndex" : "";
    echo "<script>window.location.href='admin.php?view=chapter_control&target=$target$actParam';</script>";
    exit;
}

// ПІДГОТОВКА ДО ВІДОБРАЖЕННЯ
$currentState = $state[$target];
$status = $currentState['status'];
$endTime = $currentState['end_time'];
$timeLeft = $endTime - time();
if ($timeLeft < 0) $timeLeft = 0;

$acts = $currentState['acts'];
$totalScenarioTime = 0;
foreach($acts as $a) $totalScenarioTime += $a['duration'];

// Фільтрація квестів для вибраного акту
$currentActQuests = [];
if ($actIndex !== null) {
    $currentActQuests = array_filter($quests, fn($q) => ($q['chapter'] ?? '') === $target && ($q['act_id'] ?? 0) == $actIndex);
}

// Гравці для списку (тільки з поточної глави, не адміни)
$chapterPlayers = array_filter($users, fn($u) => ($u['chapter'] ?? '') === $target && ($u['role'] ?? '') !== 'GAMEMASTER');
?>

<style>
    .control-container { border: 2px solid <?= $themeColor ?>; padding: 20px; text-align: center; margin-bottom: 20px; background: rgba(0,0,0,0.5); }
    .timer-display { font-size: 4rem; font-weight: bold; color: #fff; text-shadow: 0 0 20px <?= $themeColor ?>; margin: 10px 0; font-family: 'Courier New', monospace; }
    
    /* ACTS LIST */
    .acts-list { display: flex; gap: 10px; overflow-x: auto; padding-bottom: 10px; border-bottom: 1px solid #333; margin-bottom: 20px; }
    .act-tab { 
        background: #111; border: 1px solid #444; padding: 10px 20px; cursor: pointer; min-width: 120px; text-align: center; position: relative;
        text-decoration: none; color: #888; display: block;
    }
    .act-tab.active { border-color: <?= $themeColor ?>; color: #fff; background: #222; }
    .act-time { font-size: 0.8rem; color: <?= $themeColor ?>; font-weight: bold; }
    
    /* QUEST EDITOR */
    .quest-editor { display: grid; grid-template-columns: 1fr 1fr; gap: 20px; text-align: left; }
    .q-col { background: rgba(255,255,255,0.03); padding: 15px; border: 1px solid #333; }
    .q-col h4 { margin-top: 0; color: #fff; border-bottom: 1px solid #444; padding-bottom: 10px; }
    
    .quest-card { background: #000; border: 1px solid #444; padding: 10px; margin-bottom: 10px; display: flex; justify-content: space-between; align-items: center; }
    .q-info { font-size: 0.8rem; }
    .q-target { color: <?= $themeColor ?>; font-weight: bold; }
    
    .btn-del { background: #300; color: #f55; border: 1px solid #500; cursor: pointer; font-size: 0.7rem; padding: 2px 5px; }
</style>

<div style="display:flex; justify-content:space-between; align-items:center;">
    <a href="?view=dashboard" style="color:#666; text-decoration:none;">&larr; BACK</a>
    <span style="color:<?= $themeColor ?>">TOTAL SCENARIO: <?= $totalScenarioTime ?> MIN</span>
</div>

<div class="control-container">
    <h2 style="color:<?= $themeColor ?>; margin:0;"><?= $chapterName ?></h2>
    <div class="timer-display" id="timer">00:00:00</div>
    
    <?php if ($status === 'stopped' || $timeLeft <= 0): ?>
        <form method="POST">
            <input type="hidden" name="action" value="start_game">
            <button class="btn-act" style="width:100%; border-color:<?= $themeColor ?>; color:<?= $themeColor ?>; padding: 15px; font-size: 1.2rem;">INITIALIZE SEQUENCE</button>
        </form>
    <?php else: ?>
        <p>ENDS AT: <span style="color:#fff"><?= date('H:i:s', $endTime) ?></span></p>
        <form method="POST" onsubmit="return confirm('ABORT?');">
            <input type="hidden" name="action" value="stop_game">
            <button class="btn-act" style="width:100%; border-color:#f00; color:#f00;">EMERGENCY ABORT</button>
        </form>
    <?php endif; ?>
</div>

<div class="acts-list">
    <?php foreach ($acts as $idx => $act): ?>
        <a href="?view=chapter_control&target=<?= $target ?>&act_id=<?= $idx ?>" class="act-tab <?= ($actIndex === $idx) ? 'active' : '' ?>">
            <?= htmlspecialchars($act['name']) ?><br>
            <span class="act-time"><?= $act['duration'] ?> min</span>
        </a>
    <?php endforeach; ?>
    
    <form method="POST" style="display:inline;">
        <input type="hidden" name="action" value="add_act">
        <button class="act-tab" style="font-size:1.5rem; line-height:1;">+</button>
    </form>
</div>

<?php if ($actIndex !== null && isset($acts[$actIndex])): 
    $currentAct = $acts[$actIndex];
?>
    <div style="margin-bottom: 20px; display:flex; gap:10px;">
        <form method="POST" style="flex:1; display:flex; gap:10px;">
            <input type="hidden" name="action" value="save_acts_names">
            <?php foreach($acts as $i => $a): ?>
                <input type="hidden" name="act_names[<?= $i ?>]" value="<?= $a['name'] ?>" id="name_hidden_<?= $i ?>">
            <?php endforeach; ?>
            
            <input type="text" value="<?= $currentAct['name'] ?>" onchange="document.getElementById('name_hidden_<?= $actIndex ?>').value = this.value" style="margin:0;">
            <button class="btn-act">RENAME</button>
        </form>
        
        <form method="POST" onsubmit="return confirm('DELETE ACT? ALL QUESTS WILL BE LOST.')">
            <input type="hidden" name="action" value="delete_act">
            <input type="hidden" name="act_id" value="<?= $actIndex ?>">
            <button class="btn-act" style="border-color:#f00; color:#f00;">DELETE ACT</button>
        </form>
    </div>

    <div class="quest-editor">
        
        <div class="q-col">
            <h4 style="color:#ffd700;">FACTION OBJECTIVES (GROUP)</h4>
            
            <?php foreach($currentActQuests as $q): if($q['type'] !== 'faction') continue; ?>
                <div class="quest-card">
                    <div class="q-info">
                        <span class="q-target">[<?= $q['target'] ?>]</span> 
                        <strong><?= $q['title'] ?></strong> (<?= $q['duration'] ?>m)<br>
                        <span style="color:#666;"><?= substr($q['desc'], 0, 30) ?>...</span>
                    </div>
                    <form method="POST" onsubmit="return confirm('DEL?')">
                        <input type="hidden" name="action" value="delete_quest">
                        <input type="hidden" name="quest_id" value="<?= $q['id'] ?>">
                        <button class="btn-del">X</button>
                    </form>
                </div>
            <?php endforeach; ?>

            <div style="margin-top:20px; border-top:1px dashed #444; padding-top:10px;">
                <form method="POST">
                    <input type="hidden" name="action" value="add_quest">
                    <input type="hidden" name="act_id" value="<?= $actIndex ?>">
                    <input type="hidden" name="q_type" value="faction">
                    
                    <select name="q_target" style="padding:5px; margin-bottom:5px;">
                        <option value="OLYMPOS">OLYMPOS</option>
                        <option value="ORIGIN">ORIGIN</option>
                        <option value="THEMIS">THEMIS</option>
                        <option value="MOIRAI">МОЙРИ</option>
                        <option value="ALL">ALL SECTORS</option>
                    </select>
                    <input type="text" name="q_title" placeholder="Objective Title" required style="padding:5px; margin-bottom:5px;">
                    <div style="display:flex; gap:5px;">
                        <input type="number" name="q_duration" placeholder="Min" required style="width:60px; padding:5px;">
                        <input type="text" name="q_desc" placeholder="Briefing..." style="padding:5px;">
                    </div>
                    <button class="btn-act" style="width:100%; font-size:0.7rem;">+ ADD GROUP QUEST</button>
                </form>
            </div>
        </div>

        <div class="q-col">
            <h4 style="color:#00f0ff;">PERSONAL ASSIGNMENTS</h4>
            
            <?php foreach($currentActQuests as $q): if($q['type'] !== 'personal') continue; 
                // Знаходимо ім'я гравця для краси
                $pName = "Unknown";
                foreach($users as $u) { if($u['id'] === $q['target']) { $pName = $u['name']; break; } }
            ?>
                <div class="quest-card">
                    <div class="q-info">
                        <span class="q-target" style="color:#00f0ff;">[<?= $pName ?>]</span> 
                        <strong><?= $q['title'] ?></strong> (<?= $q['duration'] ?>m)<br>
                        <span style="color:#666;"><?= substr($q['desc'], 0, 30) ?>...</span>
                    </div>
                    <form method="POST" onsubmit="return confirm('DEL?')">
                        <input type="hidden" name="action" value="delete_quest">
                        <input type="hidden" name="quest_id" value="<?= $q['id'] ?>">
                        <button class="btn-del">X</button>
                    </form>
                </div>
            <?php endforeach; ?>

            <div style="margin-top:20px; border-top:1px dashed #444; padding-top:10px;">
                <form method="POST">
                    <input type="hidden" name="action" value="add_quest">
                    <input type="hidden" name="act_id" value="<?= $actIndex ?>">
                    <input type="hidden" name="q_type" value="personal">
                    
                    <select name="q_target" style="padding:5px; margin-bottom:5px;">
                        <?php foreach($chapterPlayers as $cp): ?>
                            <option value="<?= $cp['id'] ?>"><?= $cp['name'] ?> (<?= $cp['faction'] ?>)</option>
                        <?php endforeach; ?>
                    </select>
                    <input type="text" name="q_title" placeholder="Secret Task" required style="padding:5px; margin-bottom:5px;">
                    <div style="display:flex; gap:5px;">
                        <input type="number" name="q_duration" placeholder="Min" required style="width:60px; padding:5px;">
                        <input type="text" name="q_desc" placeholder="Details..." style="padding:5px;">
                    </div>
                    <button class="btn-act" style="width:100%; font-size:0.7rem;">+ ASSIGN PERSONAL QUEST</button>
                </form>
            </div>
        </div>
    </div>

<?php elseif($actIndex !== null): ?>
    <div style="text-align:center; padding:50px; color:#666;">ACT NOT FOUND</div>
<?php else: ?>
    <div style="text-align:center; padding:50px; color:#666;">SELECT AN ACT TO MANAGE QUESTS</div>
<?php endif; ?>

<script>
    let endTime = <?= $endTime ?>; 
    const display = document.getElementById('timer');
    function updateTimer() {
        const now = Math.floor(Date.now() / 1000);
        let diff = endTime - now;
        if (diff <= 0) { display.innerText = "00:00:00"; display.style.opacity = "0.5"; return; }
        let h = Math.floor(diff / 3600); let m = Math.floor((diff % 3600) / 60); let s = diff % 60;
        h = h < 10 ? '0' + h : h; m = m < 10 ? '0' + m : m; s = s < 10 ? '0' + s : s;
        display.innerText = `${h}:${m}:${s}`;
    }
    if (endTime > 0) { updateTimer(); setInterval(updateTimer, 1000); }
</script>
