// timer_sync.js
// Цей скрипт підключається до сторінок гравця (Hub, Terminal)

const TIMER_CONFIG = {
    target: 'ch2', // Яку главу слухаємо (можна змінити на 'ch1')
    refreshRate: 10000, // Як часто звіряти час з сервером (мс)
    domId: 'mission-timer' // ID елемента, куди писати час
};

let serverEndTime = 0;
let timerInterval = null;

// 1. Отримання даних з сервера
async function syncTime() {
    try {
        // Додаємо ?t=Date.now(), щоб браузер не кешував JSON
        const response = await fetch('../gamestate.json?t=' + Date.now());
        if (!response.ok) return;
        
        const data = await response.json();
        const chapterData = data[TIMER_CONFIG.target];

        if (chapterData && chapterData.status === 'running') {
            serverEndTime = chapterData.end_time;
            startLocalTimer();
        } else {
            serverEndTime = 0;
            updateDisplay("OFFLINE");
        }
    } catch (e) {
        console.error("Timer Sync Error:", e);
    }
}

// 2. Локальний відлік (щоб цифри бігли плавно)
function startLocalTimer() {
    if (timerInterval) return; // Вже запущено

    timerInterval = setInterval(() => {
        if (serverEndTime === 0) {
            updateDisplay("OFFLINE");
            return;
        }

        const now = Math.floor(Date.now() / 1000); // Поточний час (Unix)
        let diff = serverEndTime - now;

        if (diff <= 0) {
            updateDisplay("00:00:00");
            document.getElementById(TIMER_CONFIG.domId).classList.add('critical');
            return;
        }

        // Форматування
        let h = Math.floor(diff / 3600);
        let m = Math.floor((diff % 3600) / 60);
        let s = diff % 60;

        h = h < 10 ? '0' + h : h;
        m = m < 10 ? '0' + m : m;
        s = s < 10 ? '0' + s : s;

        updateDisplay(`${h}:${m}:${s}`);
    }, 1000);
}

function updateDisplay(text) {
    const el = document.getElementById(TIMER_CONFIG.domId);
    if (el) el.innerText = text;
}

// Запуск
document.addEventListener('DOMContentLoaded', () => {
    syncTime();
    // Періодична синхронізація з сервером (щоб не було розбіжностей)
    setInterval(syncTime, TIMER_CONFIG.refreshRate);
});
