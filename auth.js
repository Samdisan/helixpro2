// HELIX AUTH MODULE
// Відповідає поточній логіці: sessionStorage + URL, без localStorage для auth.
// Секретні коди перевіряються тільки на бекенді (admin.php).

(function () {
  const path = window.location.pathname || '';
  const isSubFolder = /\/chapter[12]\//.test(path) || /\/architect\//.test(path);
  const basePath = isSubFolder ? '../' : './';
  const ERR_MSG = 'Не вдалося завантажити дані. Спробуйте пізніше.';

  async function loginPlayer(accessCode, options = {}) {
    const { onSuccess, onError, msgElement, buttonElement, validateUser } = options;

    if (!accessCode) return;

    if (buttonElement) buttonElement.innerText = 'CHECKING...';
    if (msgElement) {
      msgElement.style.color = 'var(--accent-red)';
      msgElement.innerText = '';
    }

    try {
      const response = await fetch(basePath + 'get_users.php', { cache: 'default' });
      if (!response.ok) throw new Error('DB_ERROR');

      const users = await response.json();
      const user = users.find((u) => u.access_code === accessCode);

      if (!user) throw new Error('INVALID_CODE');
      if (user.role === 'GAMEMASTER' || user.chapter === 'admin') throw new Error('INVALID_CODE');
      if (typeof validateUser === 'function' && !validateUser(user)) throw new Error('INVALID_CODE');

      if (window.helixSessionStore) window.helixSessionStore.setStoredCode(user.access_code);
      else sessionStorage.setItem('helix_access_code', user.access_code);

      if (msgElement) {
        msgElement.style.color = '#0f0';
        msgElement.innerText = 'ACCESS GRANTED: ' + user.name;
      }

      if (typeof onSuccess === 'function') onSuccess(user);
    } catch (err) {
      if (buttonElement) buttonElement.innerText = 'CONNECT';
      if (msgElement) {
        msgElement.style.color = 'var(--accent-red)';
        msgElement.innerText = err && err.message === 'INVALID_CODE' ? 'ACCESS DENIED' : ERR_MSG;
      }
      if (window.DEBUG) console.error('[HELIX AUTH] Login error:', err);
      if (typeof onError === 'function') onError(err);
    }
  }

  window.helixAuth = {
    loginPlayer,
  };
})();
