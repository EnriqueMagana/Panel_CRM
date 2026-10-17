(() => {
  if (globalThis.__snippetdeskContentScriptLoaded) return;
  globalThis.__snippetdeskContentScriptLoaded = true;

  const APP_SOURCE = 'snippetdesk-app';
  const EXTENSION_SOURCE = 'snippetdesk-extension';
  const PICKER_STYLE_ID = 'snippetdesk-field-picker-style';
  const PICKER_HINT_ID = 'snippetdesk-field-picker-hint';
  let pickerActive = false;
  let highlightedElement = null;

  const isEditable = (element) => element instanceof HTMLTextAreaElement
    || (element instanceof HTMLInputElement && !['button', 'checkbox', 'color', 'file', 'hidden', 'image', 'radio', 'range', 'reset', 'submit'].includes(element.type))
    || element?.isContentEditable
    || element?.getAttribute?.('role') === 'textbox';

  const escapeValue = (value) => CSS.escape(String(value));

  const uniqueSelector = (element) => {
    for (const attribute of ['data-testid', 'data-test', 'name', 'aria-label', 'placeholder']) {
      const value = element.getAttribute(attribute);
      if (!value) continue;
      const selector = `${element.localName}[${attribute}="${escapeValue(value)}"]`;
      if (document.querySelectorAll(selector).length === 1) return selector;
    }

    const stableId = element.id && !/^:.*:$/.test(element.id) && !/^mui-/i.test(element.id);
    if (stableId) {
      const selector = `#${escapeValue(element.id)}`;
      if (document.querySelectorAll(selector).length === 1) return selector;
    }

    const path = [];
    let current = element;
    while (current instanceof Element && current !== document.body && path.length < 7) {
      let part = current.localName;
      const siblings = current.parentElement
        ? [...current.parentElement.children].filter((sibling) => sibling.localName === current.localName)
        : [];
      if (siblings.length > 1) part += `:nth-of-type(${siblings.indexOf(current) + 1})`;
      path.unshift(part);
      const selector = path.join(' > ');
      if (document.querySelectorAll(selector).length === 1) return selector;
      current = current.parentElement;
    }
    return path.join(' > ');
  };

  const fieldLabel = (element) => {
    const ariaLabel = element.getAttribute('aria-label');
    if (ariaLabel) return ariaLabel.trim();
    if (element.id) {
      const label = document.querySelector(`label[for="${escapeValue(element.id)}"]`);
      if (label?.textContent?.trim()) return label.textContent.trim();
    }
    const wrappingLabel = element.closest('label');
    if (wrappingLabel?.textContent?.trim()) return wrappingLabel.textContent.trim();
    return element.getAttribute('placeholder') || element.getAttribute('name') || 'Campo de texto';
  };

  const clearHighlight = () => {
    highlightedElement?.removeAttribute('data-snippetdesk-field-candidate');
    highlightedElement = null;
  };

  const stopPicker = () => {
    if (!pickerActive) return;
    pickerActive = false;
    clearHighlight();
    document.removeEventListener('pointerover', onPickerHover, true);
    document.removeEventListener('click', onPickerClick, true);
    document.removeEventListener('keydown', onPickerKeydown, true);
    document.getElementById(PICKER_STYLE_ID)?.remove();
    document.getElementById(PICKER_HINT_ID)?.remove();
  };

  function onPickerHover(event) {
    const candidate = event.target instanceof Element ? event.target.closest('input, textarea, [contenteditable="true"], [role="textbox"]') : null;
    clearHighlight();
    if (!isEditable(candidate)) return;
    highlightedElement = candidate;
    candidate.setAttribute('data-snippetdesk-field-candidate', 'true');
  }

  function onPickerClick(event) {
    const candidate = event.target instanceof Element ? event.target.closest('input, textarea, [contenteditable="true"], [role="textbox"]') : null;
    if (!isEditable(candidate)) return;
    event.preventDefault();
    event.stopImmediatePropagation();
    const target = { selector: uniqueSelector(candidate), label: fieldLabel(candidate), tagName: candidate.localName };
    stopPicker();
    chrome.runtime.sendMessage({ type: 'snippetdesk-target-selected', target }).catch(() => {});
  }

  function onPickerKeydown(event) {
    if (event.key !== 'Escape') return;
    event.preventDefault();
    stopPicker();
  }

  const startPicker = () => {
    stopPicker();
    pickerActive = true;
    const style = document.createElement('style');
    style.id = PICKER_STYLE_ID;
    style.textContent = '[data-snippetdesk-field-candidate="true"]{outline:3px solid #2563eb!important;outline-offset:3px!important;cursor:crosshair!important}';
    (document.head || document.documentElement).append(style);

    if (window === window.top) {
      const hint = document.createElement('div');
      hint.id = PICKER_HINT_ID;
      hint.setAttribute('role', 'status');
      hint.textContent = 'Snippetdesk: haz clic en cualquier campo donde quieras pegar · Esc para cancelar';
      Object.assign(hint.style, {
        position: 'fixed', top: '16px', left: '50%', transform: 'translateX(-50%)', zIndex: '2147483647',
        maxWidth: 'calc(100vw - 32px)', padding: '10px 14px', borderRadius: '10px', background: '#0f172a',
        color: '#fff', font: '600 13px/1.4 system-ui, sans-serif', boxShadow: '0 12px 30px rgba(15,23,42,.3)'
      });
      document.documentElement.append(hint);
    }

    document.addEventListener('pointerover', onPickerHover, true);
    document.addEventListener('click', onPickerClick, true);
    document.addEventListener('keydown', onPickerKeydown, true);
  };

  globalThis.__snippetdeskStartFieldPicker = startPicker;

  const announceReady = () => {
    if (!document.body?.hasAttribute('data-snippetdesk-app')) return;
    window.postMessage({ source: EXTENSION_SOURCE, type: 'ready', browser: 'Chrome / Edge' }, window.location.origin);
  };

  window.addEventListener('message', (event) => {
    if (event.source !== window || event.origin !== window.location.origin) return;
    const payload = event.data;
    if (!payload || payload.source !== APP_SOURCE) return;
    if (payload.type === 'extension-ping') return announceReady();
    if (payload.type !== 'paste-message' || typeof payload.text !== 'string') return;

    chrome.runtime.sendMessage({ type: 'snippetdesk-paste', text: payload.text }, (result) => {
      const error = chrome.runtime.lastError?.message;
      window.postMessage({
        source: EXTENSION_SOURCE,
        type: 'paste-result',
        requestId: payload.requestId,
        messageId: payload.messageId,
        success: Boolean(result?.success) && !error,
        error: error || result?.error || null,
      }, window.location.origin);
    });
  });

  chrome.runtime.onMessage.addListener((message, _sender, sendResponse) => {
    if (message?.type === 'snippetdesk-content-ping') {
      sendResponse({ success: true });
      return false;
    }
    if (message?.type === 'snippetdesk-start-field-picker') {
      startPicker();
      sendResponse({ success: true });
      return false;
    }
    return false;
  });

  if (document.readyState === 'loading') document.addEventListener('DOMContentLoaded', announceReady, { once: true });
  else announceReady();
})();
