<?php
// admin_modules/applications.php — при APPROVE заявки відповідний слот у users.json отримує booking_status = 'taken' (роль зникає з реєстрації до сбросу)

// Логіка зміни статусу або видалення
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $apps = getJson('applications.json');
    
    if ($_POST['action'] === 'delete') {
        $apps = array_values(array_filter($apps, fn($a) => $a['id'] !== $_POST['target_id']));
    } elseif ($_POST['action'] === 'approve') {
        $approvedRolePref = null;
        $approvedFaction = null;
        foreach ($apps as &$a) {
            if ($a['id'] === $_POST['target_id']) {
                $a['status'] = 'APPROVED';
                $approvedRolePref = isset($a['role_pref']) ? trim($a['role_pref']) : null;
                $approvedFaction = isset($a['faction_pref']) ? trim($a['faction_pref']) : null;
                break;
            }
        }
        unset($a);
        if ($approvedRolePref) {
            $usersFile = dirname(__DIR__) . '/users.json';
            if (file_exists($usersFile)) {
                $users = getJson($usersFile);
                $roleFound = false;
                foreach ($users as $i => $u) {
                    // Пропускаємо адмінів та гейммайстрів
                    if (($u['role'] ?? '') === 'GAMEMASTER' || ($u['chapter'] ?? '') === 'admin') continue;
                    
                    // Формуємо слот-лейбл у форматі "РОЛЬ (ІМ'Я)"
                    $slotLabel = trim(($u['role'] ?? '') . ' (' . ($u['name'] ?? '') . ')');
                    
                    // Якщо знайшли збіг - позначаємо роль як зайняту
                    if ($slotLabel === $approvedRolePref) {
                        $users[$i]['booking_status'] = 'taken';
                        $roleFound = true;
                        saveJson($usersFile, $users);
                        error_log("[HELIX] Application approved: Role '{$approvedRolePref}' marked as taken");
                        break;
                    }
                }
                if (!$roleFound) {
                    error_log("[HELIX] Warning: Could not find role '{$approvedRolePref}' in users.json");
                }
            }
        }
    }
    
    saveJson('applications.json', $apps);
    echo "<script>window.location.href='admin.php?view=applications';</script>";
    exit;
}

$apps = getJson('applications.json');
?>

<h2 style="color:#0f0;">RECRUITMENT APPLICATIONS</h2>

<?php if(empty($apps)): ?>
    <div style="color:#666;">No new messages.</div>
<?php else: ?>
    <div style="display:grid; gap:20px;">
    <?php foreach($apps as $a): ?>
        <div style="background:#111; border:1px solid <?= $a['status']=='APPROVED'?'#0f0':'#444' ?>; padding:20px;">
            <div style="display:flex; justify-content:space-between;">
                <h3 style="margin:0; color:#fff;"><?= $a['name'] ?> <span style="font-size:0.8rem; color:#00f0ff;"><?= $a['tg'] ?></span></h3>
                <span style="font-size:0.8rem; color:#666;"><?= $a['time'] ?></span>
            </div>
            
            <div style="margin:10px 0; font-size:0.9rem; color:#aaa;">
                <strong>FACTION:</strong> <?= htmlspecialchars($a['faction_pref'] ?? '—') ?><br>
                <strong>ROLE:</strong> <?= htmlspecialchars($a['role_pref'] ?? '—') ?><br>
                <strong>ANONYMOUS:</strong> <?= !empty($a['anon']) ? 'YES' : 'NO' ?><br>
                <strong>PSY-PROFILE:</strong> <?= implode(' / ', is_array($a['psy'] ?? null) ? $a['psy'] : []) ?><br>
                <strong>LORE SCORE:</strong> Q1:<?= (is_array($a['lore'] ?? null) ? ($a['lore']['q1'] ?? '—') : '—') ?> | Q2:<?= (is_array($a['lore'] ?? null) ? ($a['lore']['q2'] ?? '—') : '—') ?>
            </div>

            <div style="display:flex; gap:10px;">
                <a href="https://t.me/<?= str_replace('@','',$a['tg']) ?>" target="_blank" class="btn-act" style="text-decoration:none; text-align:center;">OPEN TELEGRAM</a>
                
                <?php if($a['status'] !== 'APPROVED'): ?>
                <form method="POST">
                    <input type="hidden" name="action" value="approve">
                    <input type="hidden" name="target_id" value="<?= $a['id'] ?>">
                    <button class="btn-act" style="background:#050; border-color:#0f0;">APPROVE</button>
                </form>
                <?php endif; ?>

                <form method="POST" onsubmit="return confirm('DEL?');">
                    <input type="hidden" name="action" value="delete">
                    <input type="hidden" name="target_id" value="<?= $a['id'] ?>">
                    <button class="btn-act" style="background:#300; border-color:#f00;">DELETE</button>
                </form>
            </div>
        </div>
    <?php endforeach; ?>
    </div>
<?php endif; ?>
