<?php
// admin_modules/instructions.php
?>

<div style="border-bottom:1px solid #333; padding-bottom:10px; margin-bottom:20px; color:#666;">
    <h1 style="color:#00f0ff; margin:0;">📖 СИСТЕМА ІНСТРУКЦІЙ HELIX</h1>
    <p style="margin-top:5px; color:#888;">Повний гайд по управлінню проєктом</p>
    <button onclick="updateCache()" style="margin-top:15px; padding:10px 20px; background:#00f0ff; color:#000; border:none; cursor:pointer; font-weight:bold; text-transform:uppercase; font-family:monospace;">🔄 ОНОВИТИ КЕШ ЗАРАЗ</button>
    <span id="cache-status" style="margin-left:15px; color:#0f0;"></span>
</div>

<script>
async function updateCache() {
    const btn = event.target;
    const status = document.getElementById('cache-status');
    btn.disabled = true;
    btn.innerText = 'ОНОВЛЕННЯ...';
    status.innerText = '';
    
    try {
        if ('serviceWorker' in navigator) {
            const registration = await navigator.serviceWorker.ready;
            const channel = new MessageChannel();
            
            channel.port1.onmessage = (e) => {
                if (e.data.success) {
                    status.innerText = `✓ Оновлено: ${e.data.result.success}/${e.data.result.total} файлів`;
                    status.style.color = '#0f0';
                } else {
                    status.innerText = '✗ Помилка: ' + e.data.error;
                    status.style.color = '#f55';
                }
                btn.disabled = false;
                btn.innerText = '🔄 ОНОВИТИ КЕШ ЗАРАЗ';
            };
            
            registration.active.postMessage({ type: 'UPDATE_CACHE' }, [channel.port2]);
        } else {
            throw new Error('Service Worker не підтримується');
        }
    } catch (e) {
        status.innerText = '✗ Помилка: ' + e.message;
        status.style.color = '#f55';
        btn.disabled = false;
        btn.innerText = '🔄 ОНОВИТИ КЕШ ЗАРАЗ';
    }
}
</script>

<div style="background:#111; padding:25px; border:1px solid #333; margin-bottom:20px;">
    <h2 style="color:#ffd700; border-bottom:1px solid #444; padding-bottom:10px;">🔄 ПОВНИЙ СБРОС РОЛЕЙ І ЗАВДАНЬ</h2>
    <p style="color:#ccc; margin-bottom:15px;">Скинути всі ролі в статус «вільні» (<code>booking_status = free</code>). Після сбросу зайняті ролі знову з’являться у списку реєстрації; цілі гравців (взяті місії, акт 1 Олімп) скидаються. Онбординг на клієнті (sessionStorage) не очищується — лише серверні дані.</p>
    <form method="POST" action="admin.php" onsubmit="return confirm('Скинути всі ролі в «вільні» та всі завдання (взяті місії, акт 1 Олімп)?');">
        <input type="hidden" name="action" value="reset_roles">
        <button type="submit" class="btn-act" style="background:#333; color:#f55; border-color:#f55;">ПОВНИЙ СБРОС: РОЛІ + ЗАВДАННЯ</button>
    </form>
</div>

<div style="background:#111; padding:25px; border:1px solid #333; margin-bottom:20px;">
    <h2 style="color:#ffd700; border-bottom:1px solid #444; padding-bottom:10px;">🔐 ДОСТУП ТА БЕЗПЕКА</h2>
    <ul style="line-height:2; color:#ccc;">
        <li><strong style="color:#fff;">Адмін-пароль:</strong> <code style="background:#000; padding:2px 6px;">HELIX2025</code> (зберігається в <code>admin_modules/config.php</code>)</li>
        <li><strong style="color:#fff;">Логін гравців:</strong> Через <code>index.html</code> або <code>chapter2/hub.html</code> з кодом доступу з <code>users.json</code></li>
        <li><strong style="color:#fff;">Гравці глави 2:</strong> Можуть зайти тільки якщо в <code>users.json</code> у них <code>"chapter": "ch2"</code> або поле відсутнє</li>
        <li><strong style="color:#fff;">Файли даних:</strong> Всі JSON-файли (<code>users.json</code>, <code>gamestate.json</code>, <code>helix_data.json</code>) мають бути доступні для читання</li>
        <li><strong style="color:#fff;">Автоматичне оновлення кешу:</strong> Кожного дня о 3:00 ночі Service Worker автоматично оновлює кеш</li>
    </ul>
</div>

<div style="background:#111; padding:25px; border:1px solid #333; margin-bottom:20px;">
    <h2 style="color:#ffd700; border-bottom:1px solid #444; padding-bottom:10px;">📄 ДЕТАЛЬНІ ІНСТРУКЦІЇ ПО СТОРІНКАХ</h2>
    
    <h3 style="color:#00f0ff; margin-top:20px;">🏠 index.html (Головна сторінка)</h3>
    <ul style="line-height:2; color:#ccc;">
        <li><strong>Призначення:</strong> Вхідна точка системи, меню навігації</li>
        <li><strong>Логіка логіну:</strong> Приймає будь-який <code>access_code</code> з <code>users.json</code>, веде в <code>chapter2/profile.html</code></li>
        <li><strong>Адмін-шорткат:</strong> Код <code>HELIX2025</code> відправляє форму в <code>admin.php</code></li>
        <li><strong>PWA:</strong> Реєструє Service Worker (<code>sw.js</code>) для офлайн-роботи</li>
    </ul>
    
    <h3 style="color:#00f0ff; margin-top:20px;">🏛️ chapter2/hub.html (Хаб глави 2)</h3>
    <ul style="line-height:2; color:#ccc;">
        <li><strong>Призначення:</strong> Центральний хаб для гравців глави 2</li>
        <li><strong>Логіка логіну:</strong> Перевіряє що <code>user.chapter === 'ch2'</code> або поле відсутнє, ігнорує адмінів</li>
        <li><strong>Аудіо:</strong> Показує екран вибору аудіо при першому відвідуванні (зберігає вибір на 4 дні)</li>
        <li><strong>Навігація:</strong> Ведє до профілю, реєстрації, персоналу, лору</li>
    </ul>
    
    <h3 style="color:#00f0ff; margin-top:20px;">👤 chapter2/profile.html (Кабінет гравця)</h3>
    <ul style="line-height:2; color:#ccc;">
        <li><strong>Призначення:</strong> Особистий кабінет з профілем, таймером, цілями, грецьким стилем (ΠΡΟΦΙΛ)</li>
        <li><strong>Рівень доступу (LVL):</strong> Відображається в статусі; задається в адмінці (Players, Onboarding). Впливає на список «об'єктів для дослідження» — доступні лише з рівнем ≤ вашому.</li>
        <li><strong>Таймер:</strong> Синхронізується з <code>gamestate.json</code> через <code>timer_sync.js</code> (оновлюється кожні 5 сек)</li>
        <li><strong>Цілі та місії:</strong> Завантажуються з <code>quests.json</code> (фракційні за <code>faction</code>, особисті за <code>access_code</code>). Зберігання на сервері: <code>get_missions.php</code> / <code>save_missions.php</code> (макс. 2).</li>
        <li><strong>Акт 1 OLYMPOS:</strong> (1) «Отримати повний контроль над Комплексом» — кнопка <strong>Повний доступ</strong> → повідомлення «ADMINISTRATOR_OFFLINE. Гієрархія порушена», рівень падає до 1. (2) «Обрати лідера» — випадаючий список усіх OLYMPOS, кнопка <strong>Голосувати</strong>; при 2/3 голосів за одного кандидата він стає лідером, у всіх OLYMPOS +1 рівень доступу (<code>get_leader_votes.php</code>, <code>submit_leader_vote.php</code>, <code>act1_leader_votes.json</code>).</li>
        <li><strong>Акт 1 THEMIS:</strong> «Підтвердіть повноваження лідера Іларії» — випадаючий список усіх гравців OLYMPOS; гравець THEMIS обирає, хто є лідером. Усього 3 спроби: невірно — рівень доступу падає до 1; правильно — +1 рівень (<code>get_leader_votes.php?code=...</code>, <code>submit_themis_leader_confirm.php</code>, <code>themis_leader_confirm.json</code>). До обраного лідера Олімпом квест лише показує назву та опис.</li>
        <li><strong>Замовити аналізи (дослідження):</strong> Кнопка відкриває модалку вибору <strong>об'єкта для дослідження</strong> (список з кешу users — швидко). Запит відправляється в <code>submit_analysis_request.php</code> і потрапляє у вкладку «Запити гравців» у Med-Bay. Кулдаун 1 год.</li>
        <li><strong>Аватар:</strong> Завантажується з <code>uploads/{access_code}.jpg</code> або <code>.png</code></li>
        <li><strong>QR-код:</strong> Локальна генерація через qrcodejs (CDN); працює офлайн після першого завантаження</li>
    </ul>
    
    <h3 style="color:#00f0ff; margin-top:20px;">👥 chapter2/personnel.html (Маніфест персонажів)</h3>
    <ul style="line-height:2; color:#ccc;">
        <li><strong>Призначення:</strong> Відображення всіх персонажів за фракціями (OLYMPOS, ORIGIN, THEMIS)</li>
        <li><strong>Фільтрація:</strong> Показує тільки гравців з <code>chapter: "ch2"</code> або без поля, виключає адмінів</li>
        <li><strong>Бейджі:</strong> "OPEN SLOT" для вільних ролей (<code>booking_status: "free"</code>), "YOU" для поточного гравця</li>
        <li><strong>Модальне вікно:</strong> При кліку на картку показує повну інформацію про персонажа</li>
        <li><strong>Статус "DEAD":</strong> Персонажі з <code>stats.status === "DEAD"</code> відображаються з перекресленим ім'ям</li>
    </ul>
    
    <h3 style="color:#00f0ff; margin-top:20px;">🧪 chapter2/test.html (Тест K.I.R.A.)</h3>
    <ul style="line-height:2; color:#ccc;">
        <li><strong>Призначення:</strong> Психологічний тест для визначення типу мислення гравця</li>
        <li><strong>Кількість питань:</strong> 10 питань з 3 варіантами відповіді кожне</li>
        <li><strong>Система балів:</strong> L (Логіка), H (Гуманізм), M (Макіавеллізм), C (Креативність)</li>
        <li><strong>Результати:</strong> STRATEGIST, PROTECTOR, OPERATOR, ADAPTOR, ANOMALY (залежить від найвищого балу)</li>
        <li><strong>Зберігання:</strong> Результат не зберігається автоматично (можна додати запис в <code>users.json</code>)</li>
    </ul>
    
    <h3 style="color:#00f0ff; margin-top:20px;">📚 lore.html (Глобальний архів)</h3>
    <ul style="line-height:2; color:#ccc;">
        <li><strong>Призначення:</strong> Відображення лор-документів з <code>helix_data.json</code></li>
        <li><strong>Фільтри:</strong> ALL, CORP, BIO, HIST, ORIGIN (за полем <code>category</code>)</li>
        <li><strong>Секція:</strong> Показує тільки документи з <code>section: "root"</code></li>
        <li><strong>Рівні доступу:</strong> YELLOW, RED, BLACK (відображаються як бейджі)</li>
    </ul>
    
    <h3 style="color:#00f0ff; margin-top:20px;">⚙️ admin.php (Адмін-панель)</h3>
    <ul style="line-height:2; color:#ccc;">
        <li><strong>Призначення:</strong> Центральна панель управління для ГМ</li>
        <li><strong>Модулі:</strong> Dashboard, Applications, Players (таблиця з колонкою «Рівень допуску», форма з полем «Рівень допуску (LEVEL)» 1–5), Lore, Med-Bay (Моніторинг + Запити гравців), Chapter Control, <strong>ЦІЛІ / QUESTS</strong>, <strong>ONBOARDING</strong> (біо, здібності, мед-карта, рівень допуску, пароль), Audio, Instructions (MANUAL)</li>
        <li><strong>Сесія:</strong> Використовує PHP <code>$_SESSION</code> для авторизації</li>
        <li><strong>Логін:</strong> Перевіряє <code>ADMIN_PASS</code> або роль GAMEMASTER з <code>users.json</code></li>
    </ul>
</div>

<div style="background:#111; padding:25px; border:1px solid #333; margin-bottom:20px;">
    <h2 style="color:#ffd700; border-bottom:1px solid #444; padding-bottom:10px;">👥 УПРАВЛІННЯ ГРАВЦЯМИ</h2>
    <h3 style="color:#00f0ff; margin-top:20px;">Структура users.json:</h3>
    <pre style="background:#000; padding:15px; overflow-x:auto; color:#0f0; font-size:0.85rem;">{
    "id": "u_unique_id",
    "name": "Ім'я",
    "access_code": "UNIQUE-CODE",
    "role": "ROLE_NAME",
    "faction": "OLYMPOS|ORIGIN|THEMIS",
    "booking_status": "free|taken",
    "level": "1-5",
    "chapter": "ch2",
    "history": "Довгий текст історії персонажа",
    "abilities": "Опис здібностей персонажа (відображається в Onboarding)",
    "stats": {
        "hp": "100",
        "psy": "0-100",
        "rad": "0",
        "status": "OK|DEAD|healthy|infected|unknown"
    }
}</pre>
    
    <h3 style="color:#00f0ff; margin-top:20px;">Ключові поля:</h3>
    <ul style="line-height:2; color:#ccc;">
        <li><code>access_code</code> — унікальний код для логіну (має бути унікальним, без пробілів)</li>
        <li><code>faction</code> — визначає фракцію та цілі в кабінеті гравця (OLYMPOS, ORIGIN, THEMIS)</li>
        <li><code>level</code> — рівень допуску (1–5); задається в Players та Onboarding; впливає на доступ до «об'єктів для дослідження» та ігрові механіки акту 1</li>
        <li><code>booking_status</code> — "free" = вільний слот, "taken" = зайнятий (впливає на відображення в personnel.html)</li>
        <li><code>chapter</code> — "ch2" для гравців другої глави (обов'язково для логіну в hub.html)</li>
        <li><code>abilities</code> — текст здібностей персонажа; редагується в Players, показується в Onboarding</li>
        <li><code>stats.status</code> — "DEAD" робить персонажа перекресленим в personnel.html; для Med-Bay: Здоровий/Заражений/Невідомо</li>
        <li><code>stats.psy</code> — психічне здоров'я (0–100%); відображається в Med-Bay та Onboarding</li>
        <li><code>act1_full_access_used</code> — true після виконання акту 1 «Повний доступ» (OLYMPOS)</li>
    </ul>
    
    <h3 style="color:#00f0ff; margin-top:20px;">Додавання нового гравця:</h3>
    <ol style="line-height:2; color:#ccc;">
        <li>Відкрити <code>users.json</code> в адмінці або текстовому редакторі</li>
        <li>Додати новий об'єкт з унікальним <code>id</code> та <code>access_code</code></li>
        <li>Встановити <code>chapter: "ch2"</code> для доступу до глави 2</li>
        <li>Встановити <code>faction</code> для автоматичного призначення цілей</li>
        <li>Перевірити синтаксис JSON (без коми після останнього елемента)</li>
    </ol>
</div>

<div style="background:#111; padding:25px; border:1px solid #333; margin-bottom:20px;">
    <h2 style="color:#ffd700; border-bottom:1px solid #444; padding-bottom:10px;">⏱️ ТАЙМЕРИ ГЛАВ</h2>
    <h3 style="color:#00f0ff; margin-top:20px;">Файл: gamestate.json</h3>
    <pre style="background:#000; padding:15px; overflow-x:auto; color:#0f0; font-size:0.85rem;">{
    "ch2": {
        "status": "running|paused|ended",
        "end_time": 1735689600,
        "start_time": 1735603200
    }
}</pre>
    <p style="color:#ccc; margin-top:15px;"><strong>end_time</strong> — Unix timestamp (секунди з 1970). Використовується для відліку таймера в кабінеті гравця.</p>
    <p style="color:#ccc;"><strong>status:</strong> "running" = таймер активний, "paused" = на паузі, "ended" = завершено</p>
    <p style="color:#ccc;"><strong>start_time:</strong> Час початку глави (для історії)</p>
    <p style="color:#ccc; margin-top:10px;"><strong>Як встановити таймер:</strong> Використовуйте <a href="?view=chapter_control&target=ch2" style="color:#00f0ff;">Chapter Control</a> в адмінці або редагуйте <code>gamestate.json</code> вручну</p>
</div>

<div style="background:#111; padding:25px; border:1px solid #333; margin-bottom:20px;">
    <h2 style="color:#ffd700; border-bottom:1px solid #444; padding-bottom:10px;">📚 ЛОР ТА АРХІВИ</h2>
    <h3 style="color:#00f0ff; margin-top:20px;">Файл: helix_data.json</h3>
    <pre style="background:#000; padding:15px; overflow-x:auto; color:#0f0; font-size:0.85rem;">[
    {
        "display_id": "DOC-001",
        "title": "Назва документа",
        "text": "Текст лору...",
        "category": "CORP|BIO|HIST|ORIGIN",
        "level": "YELLOW|RED|BLACK",
        "section": "root|ch2"
    }
]</pre>
    <p style="color:#ccc; margin-top:15px;"><strong>section:</strong> "root" = відображається в <code>lore.html</code>, "ch2" = в <code>chapter2/lore.html</code></p>
    <p style="color:#ccc;"><strong>category:</strong> Використовується для фільтрації (CORP, BIO, HIST, ORIGIN)</p>
    <p style="color:#ccc;"><strong>level:</strong> Рівень доступу (YELLOW = публічний, RED = секретний, BLACK = найвищий секрет)</p>
    <p style="color:#ccc; margin-top:10px;"><strong>Редагування:</strong> Використовуйте <a href="?view=lore" style="color:#00f0ff;">Lore DB</a> в адмінці</p>
</div>

<div style="background:#111; padding:25px; border:1px solid #333; margin-bottom:20px;">
    <h2 style="color:#ffd700; border-bottom:1px solid #444; padding-bottom:10px;">🎯 ЦІЛІ ТА МІСІЇ</h2>
    <p style="color:#ccc; line-height:1.8;">Цілі зберігаються в <code>quests.json</code> (фракційні та особисті). В кабінеті гравця завантажуються через <code>loadGoals()</code>.</p>
    <ul style="line-height:2; color:#ccc; margin-top:15px;">
        <li><strong>Файл:</strong> <code>quests.json</code> — структури <code>faction_goals</code> (по фракції), <code>personal_goals</code> (по access_code)</li>
        <li><strong>Управління в адмінці:</strong> Модуль <a href="?view=quests" style="color:#00f0ff;">ЦІЛІ / QUESTS</a> — додавання, редагування, видалення фракційних і особистих цілей</li>
        <li><strong>Обмеження:</strong> Гравець може взяти максимум 2 цілі одночасно. Зберігання на сервері: <code>missions_state.json</code> (через <code>get_missions.php</code> / <code>save_missions.php</code>); клієнт також кешує в <code>sessionStorage</code> для офлайн.</li>
        <li><strong>Raw-редактор:</strong> В модулі Quests є можливість прямого редагування JSON як запасний варіант</li>
    </ul>
</div>

<div style="background:#111; padding:25px; border:1px solid #333; margin-bottom:20px;">
    <h2 style="color:#ffd700; border-bottom:1px solid #444; padding-bottom:10px;">📋 ONBOARDING (НАЧИТКА ПО ГРАВЦЮ)</h2>
    <p style="color:#ccc; line-height:1.8;">Модуль в адмінці для поетапної начиткі ГМ по обраному гравцю.</p>
    <ul style="line-height:2; color:#ccc; margin-top:15px;">
        <li><strong>Де:</strong> Адмін-панель → <a href="?view=onboarding" style="color:#00f0ff;">ONBOARDING</a></li>
        <li><strong>Кроки:</strong> Адмін обирає гравця зі списку (гравці глави, без GM/admin) → система показує по пунктах:</li>
        <li><strong>Біо / Історія:</strong> Поле <code>history</code> з <code>users.json</code></li>
        <li><strong>Здібності:</strong> Поле <code>abilities</code> (редагується в Players → «ЗДІБНОСТІ»)</li>
        <li><strong>Мед-карта:</strong> Статус (<code>stats.status</code> — Здоровий/Заражений/Невідомо) та психічне здоров'я (<code>stats.psy</code>, %)</li>
    </ul>
</div>

<div style="background:#111; padding:25px; border:1px solid #333; margin-bottom:20px;">
    <h2 style="color:#ffd700; border-bottom:1px solid #444; padding-bottom:10px;">🏥 MED-BAY (АДМІН)</h2>
    <p style="color:#ccc; line-height:1.8;">Модуль моніторингу стану гравців та запитів на дослідження.</p>
    <ul style="line-height:2; color:#ccc; margin-top:15px;">
        <li><strong>Вкладка «МОНІТОРИНГ»:</strong> Статус (Здоровий/Заражений/Невідомо) та психічне здоров'я (PSY 0–100) по кожному гравцю. Редагування — форма по картці.</li>
        <li><strong>Вкладка «ЗАПИТИ ГРАВЦІВ»:</strong> Список запитів на проведення дослідження (аналізів): хто замовив, об'єкт дослідження, дата, статус (В очікуванні/Виконано/Скасовано). Дані з <code>analysis_requests.json</code> (заповнюється через <code>submit_analysis_request.php</code> з кабінету гравця).</li>
    </ul>
</div>

<div style="background:#111; padding:25px; border:1px solid #333; margin-bottom:20px;">
    <h2 style="color:#ffd700; border-bottom:1px solid #444; padding-bottom:10px;">📁 СТРУКТУРА ПРОЄКТУ</h2>
    <pre style="background:#000; padding:15px; overflow-x:auto; color:#0f0; font-size:0.85rem;">helix2/
├── index.html              # Головна сторінка (логін, навігація)
├── architect/             # YGGDRASIL — окрема папка, не пов’язана з index/главами
│   ├── index.html         # Вхід до YGGDRASIL
│   └── register.html      # Реєстрація (окрема від chapter2/register)
├── admin.php              # Адмін-панель (PHP, сесії)
├── users.json             # База гравців (JSON) — захист: див. LOGIC_REVIEW, розділ «Захист файлу»
├── missions_state.json    # Взяті місії по access_code (доступ тільки через get/save_missions.php)
├── get_missions.php       # API: отримання взятих місій гравця
├── save_missions.php      # API: збереження взятих місій (макс. 2)
├── gamestate.json         # Стан таймерів глав (JSON)
├── helix_data.json        # Лор-документи (JSON)
├── quests.json            # Цілі (фракційні та особисті, акт 1 OLYMPOS)
├── get_users.php          # API: список гравців (захист users.json)
├── submit_analysis_request.php  # Запити на дослідження → analysis_requests.json
├── analysis_requests.json # Запити гравців на аналізи (Med-Bay)
├── act1_full_access.php   # Акт 1 OLYMPOS: «Повний доступ» → level = 1
├── get_leader_votes.php   # Акт 1 OLYMPOS: голоси; для THEMIS — themis_attempts_used/left, themis_confirmed
├── submit_leader_vote.php # Акт 1 OLYMPOS: подати голос; при 2/3 — лідер, +1 рівень усім OLYMPOS
├── submit_themis_leader_confirm.php # THEMIS: підтвердження лідера (3 спроби)
├── act1_leader_votes.json # Голоси та обраний лідер (акт 1)
├── themis_leader_confirm.json # THEMIS: спроби та статус підтвердження лідера
├── sw.js                  # Service Worker (PWA, кешування)
├── system.js              # Аудіо та UI звуки
├── timer_sync.js          # Синхронізація таймера з сервером
├── style.css              # Глобальні стилі
├── offline.html           # Сторінка-заглушка для офлайн режиму
├── manifest.json          # PWA манифест
├── upload.php             # Обробка завантаження аватарів
├── chapter2/
│   ├── hub.html           # Хаб глави 2 (головне меню)
│   ├── profile.html       # Кабінет гравця (профіль, цілі, таймер)
│   ├── personnel.html     # Маніфест персонажів (сітка ролей)
│   ├── test.html          # Тест K.I.R.A. (10 питань)
│   ├── terminal.html      # Термінал доступу
│   ├── register.html      # Форма реєстрації
│   └── lore.html          # Лор глави 2
├── game1/                 # Міні-ігри (за потреби)
└── admin_modules/
    ├── config.php         # Конфігурація (паролі, константи)
    ├── players.php        # Управління гравцями (CRUD)
    ├── applications.php   # Заявки на реєстрацію
    ├── lore.php           # Редактор лору
    ├── chapter_control.php # Контроль таймерів глав
    ├── medbay.php         # Моніторинг стану гравців (статус, PSY)
    ├── quests.php         # Цілі: додавання, редагування, видалення
    ├── onboarding.php    # Онбординг: начитка по гравцю (біо, здібності, мед-карта)
    ├── audio.php         # Аудіо: поточний стан, файли в assets/audio, як розширити
    └── instructions.php  # Ця сторінка (MANUAL)</pre>
</div>

<div style="background:#111; padding:25px; border:1px solid #333; margin-bottom:20px;">
    <h2 style="color:#ffd700; border-bottom:1px solid #444; padding-bottom:10px;">🔧 ТЕХНІЧНІ ДЕТАЛІ</h2>
    <h3 style="color:#00f0ff; margin-top:20px;">PWA / Офлайн-режим:</h3>
    <ul style="line-height:2; color:#ccc;">
        <li>Service Worker (<code>sw.js</code>) кешує всі HTML, JS, CSS та JSON-файли</li>
        <li>Після першого відкриття сайт працює офлайн</li>
        <li><strong>Автоматичне оновлення:</strong> Кожного дня о 3:00 ночі кеш автоматично оновлюється</li>
        <li><strong>Ручне оновлення:</strong> Використовуйте кнопку "ОНОВИТИ КЕШ ЗАРАЗ" вище</li>
        <li>Для примусового оновлення зміни версію в <code>sw.js</code>: <code>const CACHE_NAME = 'helix-system-v6'</code></li>
    </ul>
    
    <h3 style="color:#00f0ff; margin-top:20px;">Завантаження аватарів:</h3>
    <ul style="line-height:2; color:#ccc;">
        <li>Файли зберігаються в <code>uploads/</code> з ім'ям <code>{access_code}.jpg</code> або <code>{access_code}.png</code></li>
        <li>Обробка через <code>upload.php</code> (перевірка типу файлу, розміру)</li>
        <li>Права доступу: папка <code>uploads/</code> має бути доступна для запису (chmod 755)</li>
    </ul>
    
    <h3 style="color:#00f0ff; margin-top:20px;">Синхронізація таймера:</h3>
    <ul style="line-height:2; color:#ccc;">
        <li><code>timer_sync.js</code> запитує <code>gamestate.json</code> кожні 10 секунд</li>
        <li>Локальний відлік працює між синхронізаціями для плавності</li>
        <li>При відсутності інтернету показує "OFFLINE"</li>
    </ul>
</div>

<div style="background:#111; padding:25px; border:1px solid #333; margin-bottom:20px;">
    <h2 style="color:#ffd700; border-bottom:1px solid #444; padding-bottom:10px;">🐛 ВИРІШЕННЯ ПРОБЛЕМ</h2>
    <h3 style="color:#00f0ff; margin-top:20px;">Гравці не можуть зайти:</h3>
    <ul style="line-height:2; color:#ccc;">
        <li>Перевірте що в <code>users.json</code> у гравця є <code>"chapter": "ch2"</code> або поле відсутнє</li>
        <li>Перевірте що <code>access_code</code> унікальний і без пробілів</li>
        <li>Перевірте що <code>role</code> не дорівнює "GAMEMASTER"</li>
        <li>Відкрийте консоль браузера (F12) і перевірте помилки</li>
        <li>Переконайтеся що сайт запущений через HTTP-сервер (не <code>file://</code>)</li>
    </ul>
    
    <h3 style="color:#00f0ff; margin-top:20px;">Пуста сітка ролей в personnel.html:</h3>
    <ul style="line-height:2; color:#ccc;">
        <li>Перевірте що в <code>users.json</code> у гравців є поле <code>faction</code> (OLYMPOS, ORIGIN, THEMIS)</li>
        <li>Перевірте що <code>chapter</code> дорівнює "ch2" або відсутнє</li>
        <li>Відкрийте консоль браузера (F12) і перевірте помилки завантаження</li>
        <li>Перевірте що <code>users.json</code> доступний по URL <code>../users.json</code></li>
    </ul>
    
    <h3 style="color:#00f0ff; margin-top:20px;">Кеш не оновлюється:</h3>
    <ul style="line-height:2; color:#ccc;">
        <li>Використайте кнопку "ОНОВИТИ КЕШ ЗАРАЗ" вище</li>
        <li>Або змініть версію в <code>sw.js</code>: <code>const CACHE_NAME = 'helix-system-v6'</code></li>
        <li>Очистіть кеш браузера вручну (Ctrl+Shift+Delete)</li>
    </ul>
    
    <h3 style="color:#00f0ff; margin-top:20px;">Запити гравців не з'являються в Med-Bay:</h3>
    <ul style="line-height:2; color:#ccc;">
        <li>Перевірте, що з профілю відправляється запит на <code>submit_analysis_request.php</code> (вкладка Мережа в F12)</li>
        <li>Перевірте права на запис у корені проєкту для створення/оновлення <code>analysis_requests.json</code></li>
        <li>У Med-Bay відкрийте вкладку «ЗАПИТИ ГРАВЦІВ» — таблиця читає дані з <code>analysis_requests.json</code></li>
    </ul>
    
    <h3 style="color:#00f0ff; margin-top:20px;">Голосування за лідера (OLYMPOS) не працює:</h3>
    <ul style="line-height:2; color:#ccc;">
        <li>Перевірте, що <code>get_leader_votes.php</code> та <code>submit_leader_vote.php</code> доступні; <code>act1_leader_votes.json</code> створюється при першому голосі</li>
        <li>Поріг 2/3: при одному гравцю OLYMPOS один голос одразу дає лідера</li>
    </ul>
    
    <h3 style="color:#00f0ff; margin-top:20px;">THEMIS: «Підтвердіть повноваження лідера»:</h3>
    <ul style="line-height:2; color:#ccc;">
        <li><strong>Лідер Олімпа ще не обраний:</strong> у THEMIS у квесті показується лише назва та опис (без кнопки та підказки) — це очікувана поведінка</li>
        <li><strong>Спроби вичерпано:</strong> після 3 невірних відповідей гравець THEMIS бачить статус «Спроби вичерпано»; змінити можна лише через повний сброс або ручне редагування <code>themis_leader_confirm.json</code></li>
        <li>Перевірте, що <code>submit_themis_leader_confirm.php</code> доступний і <code>themis_leader_confirm.json</code> створюється при першій спробі</li>
    </ul>
</div>

<div style="background:#111; padding:25px; border:1px solid #333; margin-bottom:20px;">
    <h2 style="color:#ffd700; border-bottom:1px solid #444; padding-bottom:10px;">📜 ІСТОРІЯ ЗМІН (CHANGELOG) — ДЛЯ ГМ</h2>
    <p style="color:#888; font-size:0.85rem; margin-bottom:15px;">Лог оновлень системи (тільки для адміністраторів). Доповнюйте вручну після релізів.</p>
    <ul style="line-height:2; color:#ccc; list-style:none; padding-left:0;">
        <li><strong style="color:#00f0ff;">2026-01-30</strong> — Акт 1 THEMIS: квест «Підтвердіть повноваження лідера Іларії» — 3 спроби (невірно → рівень 1, правильно → +1 рівень). Якщо лідер Олімпа ще не обраний — у профілі THEMIS показується лише назва та опис квесту, без кнопки та підказки. Повний сброс тепер скидає завдання: взяті місії, голосування OLYMPOS, THEMIS підтвердження.</li>
        <li><strong style="color:#00f0ff;">2026-01-30</strong> — Акт 1 OLYMPOS: задача «Отримати повний контроль» (Повний доступ → рівень до 1); задача «Обрати лідера» (голосування, при 2/3 — лідер, +1 рівень усім OLYMPOS). Запити на дослідження → Med-Bay. Рівень допуску в Players та Onboarding.</li>
        <li><strong style="color:#00f0ff;">2026-01-30</strong> — Сектор YGGDRASIL винесено в окрему папку architect/ (index.html — вхід, register.html — реєстрація); не пов’язаний з index та главами; доступ лише за прямим URL (architect/).</li>
        <li><strong style="color:#00f0ff;">2026-01-30</strong> — Місії: CRUD у адмінці (ЦІЛІ / QUESTS). Онбординг: редагування всіх пунктів (біо, здібності, мед-карта). Профіль: блок Здібності, K.I.R.A. у статус-барі. Збереження результатів K.I.R.A. на сервері.</li>
        <li><strong style="color:#00f0ff;">2026-01-30</strong> — Адмін: повний сброс ролей у вільні; логаут веде на index.html; менші іконки на дашборді. Personnel: дрібні елементи дизайну (серійний код, гліч-лінія).</li>
        <li><strong style="color:#00f0ff;">—</strong> — Таймер протоколу: звірка з gamestate.json кожні 10 с. PWA / Android: інструкція в LOGIC_REVIEW.</li>
    </ul>
</div>

<div style="background:#111; padding:25px; border:1px solid #333; margin-bottom:20px;">
    <h2 style="color:#ffd700; border-bottom:1px solid #444; padding-bottom:10px;">⚠️ ВАЖЛИВО</h2>
    <ul style="line-height:2; color:#f55;">
        <li>Завжди робіть бекап JSON-файлів перед редагуванням!</li>
        <li>Перевіряйте синтаксис JSON після змін (можна через <a href="https://jsonlint.com" target="_blank" style="color:#00f0ff;">jsonlint.com</a>)</li>
        <li>Не видаляйте поля <code>id</code> та <code>access_code</code> — вони унікальні ідентифікатори</li>
        <li>Для запуску локально використовуйте HTTP-сервер (не <code>file://</code>)</li>
        <li>Після змін в <code>users.json</code> перезавантажте сторінку для оновлення даних</li>
        <li>Service Worker автоматично оновлює кеш о 3:00 ночі кожного дня</li>
    </ul>
</div>
