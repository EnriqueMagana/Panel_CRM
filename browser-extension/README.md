# Extensión de Snippetdesk

1. Abre `chrome://extensions` o `edge://extensions`.
2. Activa el modo desarrollador.
3. Elige **Cargar descomprimida** y selecciona esta carpeta.
4. Pulsa el icono de Snippetdesk para abrir el panel lateral.
5. Si tu instalación usa otra dirección, abre **Configurar** dentro del panel y guárdala.
6. Abre cualquier página web y, desde el panel, pulsa **Seleccionar campo**.
7. Haz clic sobre el input, textarea o editor donde normalmente escribes la respuesta.

La extensión guardará el campo y la pestaña elegidos. Después puedes abrir Snippetdesk en otra pestaña y usar **Pegar en navegador**; el mensaje se insertará en el destino configurado aunque haya perdido el foco.

Snippetdesk se abre en una pestaña porque algunos navegadores bloquean sesiones autenticadas dentro de paneles incrustados. El selector puede utilizarse sobre cualquier página web normal, incluido un chat, CRM o sistema de tickets.

Usa **Verificar** para localizar y resaltar el destino. Si el sistema de tickets cambia su interfaz o abres otro ticket en una pestaña distinta, usa **Seleccionar campo** nuevamente.

La selección funciona con `input`, `textarea`, elementos `contenteditable` y editores con `role="textbox"`, incluso cuando están dentro de marcos accesibles para la extensión.
