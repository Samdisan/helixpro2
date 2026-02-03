// HELIX A11y — перемикач доступності в куті екрану
// Увімкнення: контраст кнопок/посилань, aria-labels, видимий focus (клавіатура).
// Зберігається в localStorage (helix_a11y_enabled).

(function () {
  const KEY = 'helix_a11y_enabled';
  const CLASS = 'a11y-on';

  function isEnabled() {
    try {
      return localStorage.getItem(KEY) === 'true';
    } catch (e) {
      return false;
    }
  }

  function setEnabled(on) {
    try {
      localStorage.setItem(KEY, on ? 'true' : 'false');
    } catch (e) {}
  }

  function apply() {
    const on = isEnabled();
    if (document.body) {
      if (on) document.body.classList.add(CLASS);
      else document.body.classList.remove(CLASS);
    }
  }

  function createToggle() {
    const btn = document.createElement('button');
    btn.type = 'button';
    btn.setAttribute('aria-label', 'Доступність: увімкнути підвищений контраст та підтримку клавіатури');
    btn.className = 'helix-a11y-toggle';
    btn.textContent = 'A11y';
    btn.title = 'Доступність (контраст, клавіатура)';
    btn.addEventListener('click', function () {
      const next = !isEnabled();
      setEnabled(next);
      apply();
      btn.setAttribute('aria-pressed', next ? 'true' : 'false');
    });
    btn.setAttribute('aria-pressed', isEnabled() ? 'true' : 'false');
    return btn;
  }

  function injectStyles() {
    if (document.getElementById('helix-a11y-styles')) return;
    const style = document.createElement('style');
    style.id = 'helix-a11y-styles';
    style.textContent =
      '.helix-a11y-toggle { padding: 6px 10px; font-size: 0.65rem; letter-spacing: 0.05em; background: rgba(0,0,0,0.6); border: 1px solid #555; color: #aaa; cursor: pointer; border-radius: 2px; } .helix-a11y-toggle:hover { color: #fff; border-color: #888; } body.a11y-on .helix-a11y-toggle { border-color: var(--accent-cyan, #0ff); color: var(--accent-cyan, #0ff); } body.a11y-on .helix-a11y-toggle[aria-pressed="true"] { background: rgba(0,240,255,0.15); }' +
      'body.a11y-on, body.a11y-on * { font-family: sans-serif !important; }' +
      'body.a11y-on a:focus-visible, body.a11y-on button:focus-visible, body.a11y-on .btn-corp:focus-visible, body.a11y-on .btn-main:focus-visible, body.a11y-on input:focus-visible { outline: 2px solid var(--accent-cyan, #0ff); outline-offset: 2px; }' +
      'body.a11y-on .btn-corp, body.a11y-on .btn-main { border-width: 2px; } body.a11y-on a[href] { text-decoration: underline; }';
    (document.head || document.documentElement).appendChild(style);
  }

  function init() {
    injectStyles();
    apply();
    const toggle = createToggle();
    const wrap = document.createElement('div');
    wrap.className = 'helix-a11y-wrap';
    wrap.style.cssText = 'position:fixed;bottom:max(12px,env(safe-area-inset-bottom));right:max(8px,env(safe-area-inset-right));z-index:9998;';
    wrap.appendChild(toggle);
    document.body.appendChild(wrap);
  }

  if (document.readyState === 'loading') {
    document.addEventListener('DOMContentLoaded', init);
  } else {
    init();
  }

  window.helixA11y = { isEnabled, setEnabled, apply };
})();
