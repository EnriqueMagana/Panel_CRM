const defaultUrl = 'http://127.0.0.1:8000/preset-messages';
const frame = document.getElementById('snippetdesk');
const form = document.getElementById('settings');
const input = document.getElementById('app-url');
const toggle = document.getElementById('settings-toggle');

const loadUrl = async () => {
  const { snippetdeskUrl = defaultUrl } = await chrome.storage.local.get('snippetdeskUrl');
  input.value = snippetdeskUrl;
  frame.src = snippetdeskUrl;
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
  frame.src = url.href;
  form.hidden = true;
  toggle.setAttribute('aria-expanded', 'false');
});

loadUrl();
