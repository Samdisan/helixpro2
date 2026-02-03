<?php
$loreSection = $_GET['lore_section'] ?? 'root';

// SAVE LOGIC
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $data = getJson(LORE_FILE);
    if ($_POST['action'] === 'add') {
        array_unshift($data, [
            'id' => uniqid(), 
            'section' => $_POST['section_target'], 
            'title' => $_POST['title'], 
            'text' => $_POST['text'], 
            'display_id' => $_POST['display_id'], 
            'category' => $_POST['category'], 
            'level' => $_POST['level']
        ]);
    } elseif ($_POST['action'] === 'update') {
        foreach ($data as &$item) {
            if ($item['id'] === $_POST['target_id']) {
                $item['display_id'] = $_POST['display_id'];
                $item['category'] = $_POST['category'];
                $item['level'] = $_POST['level'];
                $item['title'] = $_POST['title'];
                $item['text'] = $_POST['text'];
                break;
            }
        }
    } elseif ($_POST['action'] === 'delete') {
        $data = array_values(array_filter($data, fn($i) => $i['id'] !== $_POST['target_id']));
    }
    saveJson(LORE_FILE, $data);
    echo "<script>window.location.href='admin.php?view=lore&lore_section=$loreSection';</script>";
    exit;
}

$editData = null;
if (isset($_GET['edit_id'])) {
    $source = getJson(LORE_FILE);
    foreach ($source as $item) { if ($item['id'] === $_GET['edit_id']) { $editData = $item; break; } }
}

$allLore = getJson(LORE_FILE);
$loreList = array_filter($allLore, fn($i) => ($i['section'] ?? 'root') === $loreSection);
?>

<div class="filter-bar" style="margin: -20px -20px 20px -20px;">
    <span style="color:#666;">SECTION:</span>
    <a href="?view=lore&lore_section=root" class="filter-btn <?= $loreSection=='root'?'active':'' ?>">ROOT</a>
    <a href="?view=lore&lore_section=ch1" class="filter-btn <?= $loreSection=='ch1'?'active':'' ?>">CH 1</a>
    <a href="?view=lore&lore_section=ch2" class="filter-btn <?= $loreSection=='ch2'?'active':'' ?>">CH 2</a>
</div>

<div class="editor">
    <h3 style="margin-top:0; color:#00f0ff;"><?= $editData ? "EDIT: ".$editData['title'] : "NEW LORE ENTRY" ?></h3>
    <form method="POST">
        <input type="hidden" name="action" value="<?= $editData?'update':'add' ?>">
        <input type="hidden" name="section_target" value="<?= $loreSection ?>">
        <?php if($editData): ?><input type="hidden" name="target_id" value="<?= $editData['id'] ?>"><?php endif; ?>
        
        <div class="row">
            <input type="text" name="display_id" placeholder="ID (DOC-01)" required value="<?= $editData['display_id']??'' ?>">
            <select name="category">
                <?php foreach(['INFO','CORP','BIO','SECRET'] as $c) echo "<option value='$c' ".($editData['category']??''==$c?'selected':'').">$c</option>"; ?>
            </select>
            <select name="level">
                <?php for($i=1;$i<=5;$i++) echo "<option value='$i' ".($editData['level']??''==$i?'selected':'').">LVL $i</option>"; ?>
            </select>
        </div>
        <input type="text" name="title" placeholder="TITLE" required value="<?= htmlspecialchars($editData['title']??'') ?>">
        <textarea name="text" placeholder="Content..." style="height:150px;" required><?= htmlspecialchars($editData['text']??'') ?></textarea>
        
        <button class="btn-act" style="width:100%;">SAVE LORE</button>
        <?php if($editData): ?><a href="?view=lore&lore_section=<?= $loreSection ?>" style="display:block; text-align:center; margin-top:10px; color:#666;">CANCEL</a><?php endif; ?>
    </form>
</div>

<div class="med-grid">
    <?php foreach($loreList as $item): ?>
    <div class="med-card">
        <div style="color:#00f0ff; font-weight:bold;"><?= htmlspecialchars($item['title']) ?></div>
        <div style="font-size:0.7rem; color:#666; margin-bottom:10px;">
            <?= $item['display_id'] ?> | LVL <?= $item['level'] ?>
        </div>
        <div style="font-size:0.8rem; color:#999; margin-bottom:10px;">
            <?= htmlspecialchars(substr(strip_tags($item['text']), 0, 80)) . '...' ?>
        </div>
        <div style="display:flex; justify-content:space-between;">
            <a href="?view=lore&lore_section=<?= $loreSection ?>&edit_id=<?= $item['id'] ?>" style="color:#ffd700; font-size:0.8rem;">EDIT</a>
            <form method="POST" style="display:inline;" onsubmit="return confirm('DEL?');">
                <input type="hidden" name="action" value="delete"><input type="hidden" name="target_id" value="<?= $item['id'] ?>">
                <button style="background:none; border:none; color:#f00; cursor:pointer; font-size:0.8rem;">DEL</button>
            </form>
        </div>
    </div>
    <?php endforeach; ?>
</div>
