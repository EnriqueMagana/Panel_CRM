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

Usa **Verificar** para localizar y resaltar el destino. Sólo necesitas usar **Seleccionar campo** nuevamente si el sistema cambia su interfaz o el campo deja de existir.

Cuando varias pestañas comparten el mismo dominio y patrón de ruta —por ejemplo `/tickets/402711` y `/tickets/402689`— no necesitas configurar cada una. La extensión conserva el selector y dirige el pegado hacia la última pestaña compatible que visitaste. El panel muestra el folio detectado como destino actual.

Si el campo existe pero la aplicación lo mantiene `disabled`, Snippetdesk no elimina esa protección: te indicará que debes activar el chat o esperar a que la página permita responder.

La selección funciona con `input`, `textarea`, elementos `contenteditable` y editores con `role="textbox"`, incluso cuando están dentro de marcos accesibles para la extensión.
