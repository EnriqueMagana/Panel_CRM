chrome.sidePanel?.setPanelBehavior({ openPanelOnActionClick: true }).catch(() => {});

const TARGET_STORAGE_KEY = 'snippetdeskPasteTarget';
const getActiveTab = async () => {
  const [lastFocusedTab] = await chrome.tabs.query({ active: true, lastFocusedWindow: true });
  if (lastFocusedTab?.id) return lastFocusedTab;
  const activeTabs = await chrome.tabs.query({ active: true });
  return activeTabs.find((tab) => tab?.id && isSupportedPage(tab.url)) || activeTabs[0];
};

const isSupportedPage = (url = '') => /^(https?|file):\/\//i.test(url);

const sendTabMessage = (tabId, message) => new Promise((resolve, reject) => {
  chrome.tabs.sendMessage(tabId, message, (response) => {
    const error = chrome.runtime.lastError;
    if (error) reject(new Error(error.message));
    else resolve(response);
  });
});

const ensureContentScript = async (tabId) => {
  try {
    await sendTabMessage(tabId, { type: 'snippetdesk-content-ping' });
  } catch (_error) {
    await chrome.scripting.executeScript({ target: { tabId, allFrames: true }, files: ['content-script.js'] });
  }
};

const beginTargetSelection = async () => {
  const tab = await getActiveTab();
  if (!tab?.id || !isSupportedPage(tab.url)) {
    return { success: false, error: 'No encontramos una página web activa. Activa la pestaña que contiene el campo y vuelve a intentarlo.' };
  }

  try {
    await ensureContentScript(tab.id);
    await chrome.scripting.executeScript({
      target: { tabId: tab.id, allFrames: true },
      func: () => globalThis.__snippetdeskStartFieldPicker?.(),
    });
    return { success: true, pending: true, tabTitle: tab.title || 'Página web' };
  } catch (_error) {
    return { success: false, error: 'El navegador no permite acceder a esta página. Prueba en una página web normal; las páginas internas del navegador están protegidas.' };
  }
};

const readTarget = async () => {
  const stored = await chrome.storage.local.get(TARGET_STORAGE_KEY);
  return stored[TARGET_STORAGE_KEY] || null;
};

const saveTarget = async (target, sender) => {
  if (!sender.tab?.id || !isSupportedPage(sender.tab.url) || !target?.selector) {
    return { success: false, error: 'No fue posible guardar este campo.' };
  }

  const record = {
    tabId: sender.tab.id,
    tabTitle: sender.tab.title || 'Sistema de tickets',
    topUrl: sender.tab.url,
    topOrigin: new URL(sender.tab.url).origin,
    frameUrl: sender.url || sender.tab.url,
    frameOrigin: new URL(sender.url || sender.tab.url).origin,
    selector: target.selector,
    label: target.label || 'Campo de texto',
    tagName: target.tagName || 'input',
    updatedAt: new Date().toISOString(),
  };

  await chrome.storage.local.set({ [TARGET_STORAGE_KEY]: record });
  chrome.runtime.sendMessage({ type: 'snippetdesk-target-updated', target: record }).catch(() => {});
  return { success: true, target: record };
};

const insertTextIntoTarget = (selector, frameOrigin, text, allowActiveFallback) => {
  if (frameOrigin && location.origin !== frameOrigin) return { success: false };

  const isEditable = (element) => element instanceof HTMLTextAreaElement
    || (element instanceof HTMLInputElement && !['button', 'checkbox', 'color', 'file', 'hidden', 'image', 'radio', 'range', 'reset', 'submit'].includes(element.type))
    || element?.isContentEditable
    || element?.getAttribute?.('role') === 'textbox';

  let element = null;
  if (selector) {
    try { element = document.querySelector(selector); } catch (_error) { element = null; }
  }
  if (!isEditable(element) && allowActiveFallback && isEditable(document.activeElement)) element = document.activeElement;
  if (!isEditable(element) || element.disabled || element.readOnly) return { success: false };

  element.focus({ preventScroll: false });
  if (element instanceof HTMLInputElement || element instanceof HTMLTextAreaElement) {
    const start = element.selectionStart ?? element.value.length;
    const end = element.selectionEnd ?? start;
    const prototype = element instanceof HTMLTextAreaElement ? HTMLTextAreaElement.prototype : HTMLInputElement.prototype;
    const setter = Object.getOwnPropertyDescriptor(prototype, 'value')?.set;
    const nextValue = element.value.slice(0, start) + text + element.value.slice(end);
    if (setter) setter.call(element, nextValue);
    else element.value = nextValue;
    element.setSelectionRange(start + text.length, start + text.length);
  } else {
    const selection = window.getSelection();
    const range = document.createRange();
    range.selectNodeContents(element);
    range.collapse(false);
    selection?.removeAllRanges();
    selection?.addRange(range);
    if (!document.execCommand('insertText', false, text)) {
      range.insertNode(document.createTextNode(text));
      range.collapse(false);
    }
  }

  element.dispatchEvent(new InputEvent('input', { bubbles: true, composed: true, inputType: 'insertText', data: text }));
  element.dispatchEvent(new Event('change', { bubbles: true, composed: true }));
  return { success: true };
};

const resolveTargetTab = async (target) => {
  if (target?.tabId) {
    try {
      const tab = await chrome.tabs.get(target.tabId);
      if (tab?.id && new URL(tab.url).origin === target.topOrigin) return tab;
    } catch (_error) {
      // The configured tab was closed; the active tab fallback is handled below.
    }
  }
  return getActiveTab();
};

const pasteMessage = async (text) => {
  const target = await readTarget();
  const tab = await resolveTargetTab(target);
  if (!tab?.id || !isSupportedPage(tab.url)) {
    return { success: false, error: 'Abre la página que contiene el campo y selecciónalo desde el panel de la extensión.' };
  }

  const sameConfiguredSite = target && new URL(tab.url).origin === target.topOrigin;
  const results = await chrome.scripting.executeScript({
    target: { tabId: tab.id, allFrames: true },
    func: insertTextIntoTarget,
    args: [sameConfiguredSite ? target.selector : null, sameConfiguredSite ? target.frameOrigin : null, text, !target],
  });

  return results.some(({ result }) => result?.success)
    ? { success: true, targetLabel: target?.label || 'campo activo' }
    : {
        success: false,
        error: target
          ? `No encontramos “${target.label}”. Vuelve a seleccionar el campo en la página correspondiente.`
          : 'Selecciona primero un campo de texto o configura un campo destino desde el panel de la extensión.',
      };
};

const highlightTarget = (selector, frameOrigin) => {
  if (frameOrigin && location.origin !== frameOrigin) return { success: false };
  let element;
  try { element = document.querySelector(selector); } catch (_error) { return { success: false }; }
  if (!element) return { success: false };
  element.scrollIntoView({ behavior: 'smooth', block: 'center' });
  const previousOutline = element.style.outline;
  const previousOffset = element.style.outlineOffset;
  element.style.outline = '3px solid #2563eb';
  element.style.outlineOffset = '3px';
  window.setTimeout(() => {
    element.style.outline = previousOutline;
    element.style.outlineOffset = previousOffset;
  }, 1800);
  return { success: true };
};

const verifyTarget = async () => {
  const target = await readTarget();
  if (!target) return { success: false, error: 'Todavía no has seleccionado un campo.' };
  const tab = await resolveTargetTab(target);
  if (!tab?.id || new URL(tab.url).origin !== target.topOrigin) {
    return { success: false, error: 'La pestaña configurada ya no está disponible. Selecciona nuevamente el campo.' };
  }
  const results = await chrome.scripting.executeScript({
    target: { tabId: tab.id, allFrames: true },
    func: highlightTarget,
    args: [target.selector, target.frameOrigin],
  });
  return results.some(({ result }) => result?.success)
    ? { success: true }
    : { success: false, error: 'El campo cambió o ya no existe. Selecciónalo nuevamente.' };
};

chrome.runtime.onMessage.addListener((message, sender, sendResponse) => {
  let operation;
  if (message?.type === 'snippetdesk-paste' && typeof message.text === 'string') operation = pasteMessage(message.text);
  else if (message?.type === 'snippetdesk-select-target') operation = beginTargetSelection();
  else if (message?.type === 'snippetdesk-get-target') operation = readTarget().then((target) => ({ success: true, target }));
  else if (message?.type === 'snippetdesk-target-selected') operation = saveTarget(message.target, sender);
  else if (message?.type === 'snippetdesk-verify-target') operation = verifyTarget();
  else if (message?.type === 'snippetdesk-forget-target') operation = chrome.storage.local.remove(TARGET_STORAGE_KEY).then(() => ({ success: true }));
  else return false;

  operation
    .then(sendResponse)
    .catch((error) => sendResponse({ success: false, error: error?.message || 'La extensión no pudo completar la acción.' }));
  return true;
});
