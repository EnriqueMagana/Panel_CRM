chrome.sidePanel.setPanelBehavior({ openPanelOnActionClick: true }).catch(() => {});

const insertTextIntoActiveField = (text) => {
  const element = document.activeElement;
  if (!element) return false;

  if (element instanceof HTMLInputElement || element instanceof HTMLTextAreaElement) {
    const start = element.selectionStart ?? element.value.length;
    const end = element.selectionEnd ?? start;
    const prototype = element instanceof HTMLTextAreaElement
      ? HTMLTextAreaElement.prototype
      : HTMLInputElement.prototype;
    const setter = Object.getOwnPropertyDescriptor(prototype, 'value')?.set;
    const nextValue = element.value.slice(0, start) + text + element.value.slice(end);
    setter?.call(element, nextValue);
    element.setSelectionRange(start + text.length, start + text.length);
    element.dispatchEvent(new InputEvent('input', { bubbles: true, inputType: 'insertText', data: text }));
    element.dispatchEvent(new Event('change', { bubbles: true }));
    return true;
  }

  if (element.isContentEditable) {
    element.focus();
    const selection = window.getSelection();
    if (!selection) return false;
    if (selection.rangeCount === 0) {
      const range = document.createRange();
      range.selectNodeContents(element);
      range.collapse(false);
      selection.addRange(range);
    }
    const range = selection.getRangeAt(0);
    range.deleteContents();
    const node = document.createTextNode(text);
    range.insertNode(node);
    range.setStartAfter(node);
    range.collapse(true);
    selection.removeAllRanges();
    selection.addRange(range);
    element.dispatchEvent(new InputEvent('input', { bubbles: true, inputType: 'insertText', data: text }));
    return true;
  }

  return false;
};

const pasteIntoActiveTab = async (text) => {
  const [tab] = await chrome.tabs.query({ active: true, currentWindow: true });
  if (!tab?.id) return { success: false, error: 'No se encontró una pestaña activa.' };

  const results = await chrome.scripting.executeScript({
    target: { tabId: tab.id, allFrames: true },
    func: insertTextIntoActiveField,
    args: [text],
  });

  return results.some((result) => result.result === true)
    ? { success: true }
    : { success: false, error: 'Selecciona primero un campo de texto en el sistema de tickets.' };
};

chrome.runtime.onMessage.addListener((message, _sender, sendResponse) => {
  if (message?.type !== 'snippetdesk-paste' || typeof message.text !== 'string') return false;

  pasteIntoActiveTab(message.text)
    .then(sendResponse)
    .catch((error) => sendResponse({ success: false, error: error?.message || 'No se pudo pegar el mensaje.' }));

  return true;
});
