// HELIX Session Store — політика «пам'яті» між сесіями
// Опційне збереження access_code в sessionStorage з TTL (час життя).
// Ключі: helix_access_code, helix_access_code_ttl (timestamp закінчення).

(function () {
  const KEY_CODE = 'helix_access_code';
  const KEY_TTL = 'helix_access_code_ttl';
  const DEFAULT_TTL_MS = 24 * 60 * 60 * 1000; // 24 години

  function getStoredCode() {
    try {
      const ttl = sessionStorage.getItem(KEY_TTL);
      if (ttl) {
        const expiry = parseInt(ttl, 10);
        if (expiry && Date.now() > expiry) {
          sessionStorage.removeItem(KEY_CODE);
          sessionStorage.removeItem(KEY_TTL);
          return null;
        }
      }
      return sessionStorage.getItem(KEY_CODE);
    } catch (e) {
      return null;
    }
  }

  function setStoredCode(code, ttlMs) {
    try {
      if (!code) {
        sessionStorage.removeItem(KEY_CODE);
        sessionStorage.removeItem(KEY_TTL);
        return;
      }
      sessionStorage.setItem(KEY_CODE, code);
      const ttl = typeof ttlMs === 'number' && ttlMs > 0 ? ttlMs : DEFAULT_TTL_MS;
      sessionStorage.setItem(KEY_TTL, String(Date.now() + ttl));
    } catch (e) {}
  }

  /** Код доступу: спочатку ?code= з URL, потім store з TTL, потім sessionStorage. */
  function getAccessCode() {
    try {
      const p = new URLSearchParams(window.location.search);
      const fromUrl = p.get('code');
      if (fromUrl) return fromUrl;
      const fromStore = getStoredCode();
      if (fromStore) return fromStore;
      return sessionStorage.getItem(KEY_CODE) || '';
    } catch (e) {
      return '';
    }
  }

  window.helixSessionStore = {
    getStoredCode,
    setStoredCode,
    getAccessCode,
    DEFAULT_TTL_MS
  };
})();
