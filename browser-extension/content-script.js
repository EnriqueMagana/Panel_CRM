const APP_SOURCE = 'snippetdesk-app';
const EXTENSION_SOURCE = 'snippetdesk-extension';

const announceReady = () => {
  if (!document.body?.hasAttribute('data-snippetdesk-app')) return;
  window.postMessage({ source: EXTENSION_SOURCE, type: 'ready', browser: 'Chrome / Edge' }, window.location.origin);
};

window.addEventListener('message', (event) => {
  if (event.source !== window || event.origin !== window.location.origin) return;
  const payload = event.data;
  if (!payload || payload.source !== APP_SOURCE) return;

  if (payload.type === 'extension-ping') {
    announceReady();
    return;
  }

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

if (document.readyState === 'loading') {
  document.addEventListener('DOMContentLoaded', announceReady, { once: true });
} else {
  announceReady();
}
