const CACHE_NAME = 'helix-system-v5'; // Змінюй цифру, щоб оновити кеш у гравців
const ASSETS_TO_CACHE = [
  './',
  './index.html',
  './style.css',
  './system.js',
  './timer_sync.js',
  './manifest.json',
  './sw.js',
  './lore.html',
  './declaration.html',
  './helix_data.json',
  './lore_data.json',
  './get_users.php',
  './gamestate.json',
  './quests.json',
  './chapter2/hub.html',
  './chapter2/profile.html',
  './chapter2/terminal.html',
  './chapter2/personnel.html',
  './chapter2/lore.html',
  './chapter2/register.html',
  './chapter2/test.html',
  './game1/game.html',
  './game1/script.js',
  './game1/style.css',
  './assets/audio/ambience.mp3',
  './assets/audio/hover.mp3'
];

let lastCacheUpdate = 0;

// Автоматичне оновлення кешу о 3:00 ночі кожного дня
function shouldUpdateCache() {
  try {
    const now = new Date();
    const currentHour = now.getHours();
    
    // Перевіряємо чи зараз 3:00-3:59 і чи не оновлювали сьогодні
    if (currentHour === 3) {
      const today = now.toDateString();
      const lastUpdateDate = lastCacheUpdate ? new Date(lastCacheUpdate).toDateString() : '';
      
      // Якщо останнє оновлення було не сьогодні - треба оновити
      if (lastUpdateDate !== today) {
        return true;
      }
    }
    
    return false;
  } catch (e) {
    return false;
  }
}

// Перевірка автоматичного оновлення при кожному fetch (якщо це запит після 3:00)
// Service Worker не має доступу до setInterval, тому перевіряємо при кожному запиті

// Функція оновлення кешу
async function updateCache() {
  try {
    const cache = await caches.open(CACHE_NAME);
    console.log('[SW] Starting cache update...');
    
    const results = await Promise.allSettled(
      ASSETS_TO_CACHE.map(async url => {
        try {
          const response = await fetch(url);
          if (response.ok) {
            await cache.put(url, response);
            return { url, status: 'ok' };
          }
          return { url, status: 'failed', reason: 'not ok' };
        } catch (err) {
          console.error('[SW] Could not update:', url, err);
          return { url, status: 'failed', reason: err.message };
        }
      })
    );
    
    const success = results.filter(r => r.status === 'fulfilled' && r.value.status === 'ok').length;
    console.log(`[SW] Cache updated: ${success}/${ASSETS_TO_CACHE.length} files`);
    
    lastCacheUpdate = Date.now();
    
    // Повідомляємо клієнтів про оновлення
    self.clients.matchAll().then(clients => {
      clients.forEach(client => {
        client.postMessage({
          type: 'CACHE_UPDATED',
          timestamp: lastCacheUpdate,
          success: success,
          total: ASSETS_TO_CACHE.length
        });
      });
    });
    
    return { success, total: ASSETS_TO_CACHE.length };
  } catch (e) {
    console.error('[SW] Cache update failed:', e);
    throw e;
  }
}

// Перевірка при активації service worker
self.addEventListener('activate', (evt) => {
  evt.waitUntil(
    Promise.all([
      caches.keys().then((keys) => {
        return Promise.all(keys.map((key) => {
          if (key !== CACHE_NAME) return caches.delete(key);
        }));
      }),
      (async () => {
        // Перевірка автоматичного оновлення при активації
        if (shouldUpdateCache()) {
          console.log('[SW] Auto-updating cache (3 AM schedule)');
          await updateCache();
        }
      })()
    ])
  );
  self.clients.claim();
});

// Обробка повідомлень від клієнта (для ручного оновлення)
self.addEventListener('message', (event) => {
  if (event.data && event.data.type === 'UPDATE_CACHE') {
    updateCache().then(result => {
      if (event.ports && event.ports[0]) {
        event.ports[0].postMessage({ success: true, result });
      }
    }).catch(err => {
      if (event.ports && event.ports[0]) {
        event.ports[0].postMessage({ success: false, error: err.message });
      }
    });
  }
});

// 1. Встановлення (Кешування)
self.addEventListener('install', (evt) => {
  self.skipWaiting(); // Примусово активувати новий воркер
  evt.waitUntil(
    caches.open(CACHE_NAME).then((cache) => {
      console.log('[SW] Caching Assets');
      // Використовуємо map, щоб помилка в одному файлі не зупинила інші
      return Promise.all(
        ASSETS_TO_CACHE.map(url => {
          return cache.add(url).catch(err => console.error('[SW] Could not cache:', url));
        })
      );
    })
  );
});

// 2. Активація (Очистка старого кешу) - тепер вище з автоматичним оновленням

// 3. Перехоплення запитів (Робота офлайн)
self.addEventListener('fetch', (evt) => {
  // Ігноруємо POST запити (логін, адмінка), бо вони потребують інтернету
  if (evt.request.method !== 'GET') return;

  // Перевірка автоматичного оновлення о 3:00 (при кожному запиті)
  if (shouldUpdateCache()) {
    updateCache().catch(err => console.error('[SW] Auto-update failed:', err));
  }

  const req = evt.request;

  // Для навігації (HTML-сторінки) намагаємось тягнути з мережі, але маємо fallback в кеш і offline.html
  if (req.mode === 'navigate') {
    evt.respondWith(
      fetch(req)
        .then((networkRes) => {
          const copy = networkRes.clone();
          caches.open(CACHE_NAME).then((cache) => cache.put(req, copy));
          return networkRes;
        })
        .catch(() =>
          caches.match(req)
            .then((cacheRes) => cacheRes || caches.match('./offline.html'))
            .then((res) => res instanceof Response ? res : new Response('Offline', { status: 503, statusText: 'Offline' }))
        )
    );
    return;
  }

  // Для JSON/API файлів з даними (get_users.php, gamestate.json, quests.json) - завжди network-first
  const url = new URL(req.url);
  const isDataFile = url.pathname.includes('get_users.php') || 
                     url.pathname.includes('gamestate.json') || 
                     url.pathname.includes('quests.json') ||
                     url.pathname.includes('helix_data.json');
  
  if (isDataFile) {
    evt.respondWith(
      fetch(req.clone(), {
        cache: 'no-store',
        mode: 'cors',
        credentials: 'same-origin',
        headers: {
          'Cache-Control': 'no-cache, no-store, must-revalidate',
          'Pragma': 'no-cache',
          'Expires': '0'
        }
      })
        .then((networkRes) => {
          // Перевіряємо що відповідь успішна
          if (!networkRes || !networkRes.ok) {
            throw new Error('Network response not ok: ' + (networkRes ? networkRes.status : 'no response'));
          }
          // Не кешуємо дані файли, щоб завжди були свіжі
          return networkRes;
        })
        .catch((error) => {
          if (typeof console !== 'undefined' && console.error) console.error('[SW] Failed to fetch data file:', req.url, error);
          return caches.match(req).then((cacheRes) => {
            if (cacheRes instanceof Response) return cacheRes;
            return new Response(
              JSON.stringify({ error: 'Failed to fetch data file', url: req.url }),
              { status: 503, statusText: 'Service Unavailable', headers: { 'Content-Type': 'application/json' } }
            );
          }).catch(() => new Response(
            JSON.stringify({ error: 'Offline' }),
            { status: 503, statusText: 'Service Unavailable', headers: { 'Content-Type': 'application/json' } }
          ));
        })
    );
    return;
  }

  // Для всіх інших GET — cache-first з підвантаженням у фоні (завжди повертаємо Response)
  evt.respondWith(
    caches.match(req).then((cacheRes) => {
      const fetchPromise = fetch(req)
        .then((networkRes) => {
          if (
            networkRes &&
            networkRes.status === 200 &&
            (networkRes.type === 'basic' || networkRes.type === 'cors')
          ) {
            const copy = networkRes.clone();
            caches.open(CACHE_NAME).then((cache) => cache.put(req, copy));
          }
          return networkRes;
        })
        .catch(() => null);

      if (cacheRes) return cacheRes;

      // fetchPromise може розв'язатися в null — тоді потрібен fallback Response
      const isHtml = (req.headers.get('accept') || '').includes('text/html');
      const offlineResponse = new Response('OFFLINE', { status: 503, statusText: 'Offline' });
      return fetchPromise.then((networkRes) => {
        if (networkRes instanceof Response) return networkRes;
        if (isHtml) {
          return caches.match('./offline.html').then((c) => c instanceof Response ? c : offlineResponse);
        }
        return offlineResponse;
      }).catch(() => offlineResponse);
    }).catch(() => new Response('Offline', { status: 503, statusText: 'Offline' }))
  );
});
