const defaultUrl = 'http://127.0.0.1:8000/preset-messages';
const form = document.getElementById('settings');
const input = document.getElementById('app-url');
const toggle = document.getElementById('settings-toggle');
const openAppButton = document.getElementById('open-app');
const selectTargetButton = document.getElementById('select-target');
const verifyTargetButton = document.getElementById('verify-target');
const forgetTargetButton = document.getElementById('forget-target');
const targetDescription = document.getElementById('target-description');
const targetStatus = document.getElementById('target-status');
const targetIndicator = document.getElementById('target-indicator');

const sendMessage = (message) => new Promise((resolve) => {
  chrome.runtime.sendMessage(message, (response) => {
    const error = chrome.runtime.lastError?.message;
    resolve(error ? { success: false, error } : response);
  });
});

const renderTarget = (target) => {
  const configured = Boolean(target);
  targetIndicator.classList.toggle('is-connected', configured);
  verifyTargetButton.hidden = !configured;
  forgetTargetButton.hidden = !configured;
  targetDescription.textContent = configured
    ? `${target.ticketReference ? `Ticket ${target.ticketReference} · ` : ''}${target.label} · ${target.tabTitle}`
    : 'Selecciona el input o editor donde deben insertarse los mensajes.';
};

const loadTarget = async () => {
  const result = await sendMessage({ type: 'snippetdesk-get-target' });
  renderTarget(result?.target || null);
};

const loadUrl = async () => {
  const { snippetdeskUrl = defaultUrl } = await chrome.storage.local.get('snippetdeskUrl');
  input.value = snippetdeskUrl;
};

toggle.addEventListener('click', () => {
  form.hidden = !form.hidden;
  toggle.setAttribute('aria-expanded', String(!form.hidden));
  if (!form.hidden) input.focus();
});

form.addEventListener('submit', async (event) => {
  event.preventDefault();
  const url = new URL(input.value);
  if (!['http:', 'https:'].includes(url.protocol)) return;
  await chrome.storage.local.set({ snippetdeskUrl: url.href });
  form.hidden = true;
  toggle.setAttribute('aria-expanded', 'false');
});

openAppButton.addEventListener('click', async () => {
  const url = new URL(input.value || defaultUrl);
  await chrome.tabs.create({ url: url.href });
});

selectTargetButton.addEventListener('click', async () => {
  targetStatus.textContent = 'Activando el selector en la página web actual…';
  const result = await sendMessage({ type: 'snippetdesk-select-target' });
  targetStatus.textContent = result?.success
    ? 'Ahora haz clic en cualquier input, textarea o editor. Presiona Esc para cancelar.'
    : result?.error || 'No se pudo iniciar la selección.';
});

verifyTargetButton.addEventListener('click', async () => {
  targetStatus.textContent = 'Buscando el campo configurado…';
  const result = await sendMessage({ type: 'snippetdesk-verify-target' });
  targetStatus.textContent = result?.success
    ? 'Campo encontrado y resaltado en la página configurada.'
    : result?.error || 'No se encontró el campo.';
});

forgetTargetButton.addEventListener('click', async () => {
  await sendMessage({ type: 'snippetdesk-forget-target' });
  renderTarget(null);
  targetStatus.textContent = 'Campo eliminado. Puedes seleccionar uno nuevo.';
});

chrome.runtime.onMessage.addListener((message) => {
  if (message?.type !== 'snippetdesk-target-updated') return;
  renderTarget(message.target);
  targetStatus.textContent = message.reason === 'selected'
    ? 'Campo guardado. Ya puedes usar “Pegar en navegador”.'
    : `Destino actualizado${message.target?.ticketReference ? ` al ticket ${message.target.ticketReference}` : ''}.`;
});

loadUrl();
loadTarget();
