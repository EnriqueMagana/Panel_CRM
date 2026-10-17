import './bootstrap';
import { _parts as blobatarParts } from 'blobatar/internal';
import { blobatarUri } from 'blobatar/uri';
import { happy, idle, love, shy, sleepy, smug, surprised, thinking, unsure, wink } from 'blobatar/expression';
import * as FilePond from 'filepond';
import FilePondPluginImagePreview from 'filepond-plugin-image-preview';
import FilePondPluginFileValidateType from 'filepond-plugin-file-validate-type';
import FilePondPluginImageCrop from 'filepond-plugin-image-crop';

FilePond.registerPlugin(
	FilePondPluginImagePreview,
	FilePondPluginFileValidateType,
	FilePondPluginImageCrop,
);

const root = document.documentElement;
const blobatarSelector = 'img[data-blobatar-seed]';
const blobatarSvgNamespace = 'http://www.w3.org/2000/svg';
const blobatarPartsCache = new Map();
const blobatarUriCache = new Map();
const blobatarExpressions = { happy, idle, love, shy, sleepy, smug, surprised, thinking, unsure, wink };
const systemTheme = window.matchMedia('(prefers-color-scheme: dark)');
let confirmAction = null;
let draggedPinnedPresetMessageId = null;
const snapshots = new Map();
let snapshotSequence = 0;
let sessionWarningTimer = null;
let sessionLogoutTimer = null;
let sessionHooksRegistered = false;
let sessionValidationPromise = null;

const sessionTimeoutDialog = () => document.querySelector('[data-session-timeout-dialog]');

const redirectToLogin = () => {
	const loginUrl = document.body?.dataset.loginUrl || '/login';
	window.location.replace(loginUrl);
};

const validateRestoredSession = async () => {
	if (!document.body?.matches('[data-snippetdesk-app]')) return;
	if (sessionValidationPromise) return sessionValidationPromise;

	document.documentElement.classList.add('session-revalidating');
	const statusUrl = document.body.dataset.sessionStatusUrl || '/session/status';

	sessionValidationPromise = fetch(statusUrl, {
		method: 'GET',
		credentials: 'same-origin',
		cache: 'no-store',
		headers: {
			Accept: 'application/json',
			'X-Requested-With': 'XMLHttpRequest',
		},
	})
		.then((response) => {
			if (!response.ok || response.redirected) {
				redirectToLogin();
				return;
			}

			document.documentElement.classList.remove('session-revalidating');
		})
		.catch(redirectToLogin)
		.finally(() => {
			sessionValidationPromise = null;
		});

	return sessionValidationPromise;
};

const submitSessionLogout = () => {
	document.querySelector('[data-session-timeout-logout]')?.requestSubmit();
};

const closeSessionTimeoutWarning = () => {
	const dialog = sessionTimeoutDialog();
	if (dialog instanceof HTMLDialogElement && dialog.open) dialog.close();
};

const showSessionTimeoutWarning = (warningSeconds) => {
	const dialog = sessionTimeoutDialog();
	if (!(dialog instanceof HTMLDialogElement)) return;
	if (!dialog.open) dialog.showModal();

	const minutes = Math.floor(warningSeconds / 60);
	const seconds = String(warningSeconds % 60).padStart(2, '0');
	const output = dialog.querySelector('[data-session-countdown]');
	if (output) output.textContent = `${minutes}:${seconds}`;
};

const resetSessionTimeout = () => {
	const idleSeconds = Number(document.body?.dataset.sessionIdleTimeout || 0);
	if (!Number.isFinite(idleSeconds) || idleSeconds <= 0) return;
	const configuredWarning = Number(document.body?.dataset.sessionWarning || 120);
	const warningSeconds = Math.min(Math.max(15, configuredWarning), Math.max(15, idleSeconds - 1));

	window.clearTimeout(sessionWarningTimer);
	window.clearTimeout(sessionLogoutTimer);
	closeSessionTimeoutWarning();
	sessionWarningTimer = window.setTimeout(
		() => showSessionTimeoutWarning(warningSeconds),
		Math.max(0, idleSeconds - warningSeconds) * 1_000,
	);
	sessionLogoutTimer = window.setTimeout(submitSessionLogout, idleSeconds * 1_000);
};

const continueSession = async (button) => {
	button.disabled = true;
	try {
		const token = document.querySelector('meta[name="csrf-token"]')?.content || '';
		const response = await fetch('/session/keep-alive', {
			method: 'POST',
			credentials: 'same-origin',
			headers: { 'X-CSRF-TOKEN': token, Accept: 'application/json' },
		});
		if (!response.ok) {
			submitSessionLogout();
			return;
		}
		resetSessionTimeout();
	} finally {
		button.disabled = false;
	}
};

const registerSessionTimeoutHooks = () => {
	if (sessionHooksRegistered || !window.Livewire?.hook) return;
	sessionHooksRegistered = true;
	window.Livewire.hook('request', ({ succeed }) => {
		succeed(() => resetSessionTimeout());
	});
};

document.addEventListener('livewire:init', registerSessionTimeoutHooks);

window.addEventListener('pageshow', (event) => {
	const navigation = window.performance.getEntriesByType('navigation')[0];
	if (event.persisted || navigation?.type === 'back_forward') validateRestoredSession();
});

window.addEventListener('popstate', () => {
	window.queueMicrotask(validateRestoredSession);
});

document.addEventListener('click', (event) => {
	const continueButton = event.target instanceof Element ? event.target.closest('[data-session-continue]') : null;
	if (continueButton instanceof HTMLButtonElement) {
		continueSession(continueButton);
		return;
	}

	if (event.target instanceof Element && event.target.closest('[data-session-logout-now]')) {
		submitSessionLogout();
	}
});

const renderBlobatar = (image) => {
	const seed = image.dataset.blobatarSeed;
	const size = Number(image.dataset.blobatarSize) || 48;
	const animation = image.dataset.blobatarAnimate;
	const backgroundName = image.dataset.blobatarBackground || 'circle';
	const expressionName = image.dataset.blobatarExpression || 'idle';
	const backgroundColor = image.dataset.blobatarBackgroundColor;
	const hue = image.hasAttribute('data-blobatar-hue') ? Number(image.dataset.blobatarHue) : undefined;
	const tone = image.hasAttribute('data-blobatar-tone') ? Number(image.dataset.blobatarTone) : undefined;
	const options = {
		size,
		background: backgroundName === 'transparent' ? false : backgroundName,
		...(Number.isFinite(hue) ? { hue } : {}),
		...(Number.isFinite(tone) ? { tone } : {}),
		...(backgroundColor && backgroundName !== 'transparent' ? { palette: { bg: backgroundColor } } : {}),
		...(blobatarExpressions[expressionName] ? { expression: blobatarExpressions[expressionName] } : {}),
		...(animation ? { animate: animation } : {}),
	};
	const customizationKey = `${backgroundName}:${backgroundColor || 'auto'}:${hue ?? 'auto'}:${tone ?? 'auto'}:${expressionName}`;

	if (!seed) return;
	if (!animation) {
		const cacheKey = `${seed}:${size}:static:${customizationKey}`;
		if (!blobatarUriCache.has(cacheKey)) {
			blobatarUriCache.set(cacheKey, blobatarUri(seed, options));
		}
		const uri = blobatarUriCache.get(cacheKey);
		if (image.getAttribute('src') !== uri) image.src = uri;
		image.classList.add('is-loaded');
		return;
	}

	const cacheKey = `${seed}:${size}:${animation}:${customizationKey}`;
	if (!blobatarPartsCache.has(cacheKey)) {
		blobatarPartsCache.set(cacheKey, blobatarParts(seed, options));
	}
	const parts = blobatarPartsCache.get(cacheKey);
	const svg = document.createElementNS(blobatarSvgNamespace, 'svg');
	const generatedAttributes = new Set([
		'alt',
		'data-blobatar-seed',
		'data-blobatar-size',
		'data-blobatar-animate',
	]);

	image.getAttributeNames().forEach((name) => {
		if (!generatedAttributes.has(name)) svg.setAttribute(name, image.getAttribute(name));
	});

	svg.setAttribute('xmlns', blobatarSvgNamespace);
	svg.setAttribute('viewBox', '0 0 100 100');
	svg.setAttribute('class', (image.className + ' ' + (parts.cls || '') + ' is-loaded').trim());
	svg.dataset.blobatarSeed = seed;
	svg.dataset.blobatarSize = String(size);
	svg.dataset.blobatarAnimate = animation;
	svg.dataset.blobatarMode = image.dataset.blobatarMode || animation;
	svg.dataset.blobatarMotion = animation;
	svg.dataset.avatarCacheKey = cacheKey;

	if (image.alt) {
		svg.setAttribute('role', 'img');
		svg.setAttribute('aria-label', image.alt);
	} else {
		svg.setAttribute('aria-hidden', 'true');
	}

	Object.entries(parts.vars || {}).forEach(([property, value]) => svg.style.setProperty(property, value));

	if (parts.bg) {
		const backdrop = document.createElementNS(blobatarSvgNamespace, 'path');
		backdrop.setAttribute('d', parts.bg.d);
		backdrop.setAttribute('fill', parts.bg.fill);
		svg.append(backdrop);
	}

	svg.insertAdjacentHTML('beforeend', parts.inner);
	image.replaceWith(svg);
};

const imageFromAnimatedBlobatar = (svg) => {
	const image = document.createElement('img');
	const classes = [...svg.classList].filter((name) => !['mo-root', 'mo-always', 'mo-expr'].includes(name));

	image.className = classes.join(' ');
	['width', 'height', 'data-user-avatar-id', 'data-blobatar-seed', 'data-blobatar-size', 'data-blobatar-mode', 'data-blobatar-background', 'data-blobatar-background-color', 'data-blobatar-hue', 'data-blobatar-tone', 'data-blobatar-expression'].forEach((name) => {
		const value = svg.getAttribute(name);
		if (value) image.setAttribute(name, value);
	});
	image.alt = svg.getAttribute('aria-label') || '';
	image.dataset.blobatarSize = svg.getAttribute('width') || '40';

	return image;
};

const renderBlobatars = (node) => {
	if (node instanceof Element && node.matches(blobatarSelector)) renderBlobatar(node);
	node.querySelectorAll?.(blobatarSelector).forEach(renderBlobatar);
};

const revealAvatarPhoto = (image) => {
	if (!(image instanceof HTMLImageElement) || !image.matches('[data-chat-avatar-photo]')) return;
	if (image.complete && image.naturalWidth > 0) image.classList.add('is-loaded');
};

const initAvatarPhotos = (node) => {
	if (node instanceof HTMLImageElement) revealAvatarPhoto(node);
	node.querySelectorAll?.('[data-chat-avatar-photo]').forEach(revealAvatarPhoto);
};

const initGlobalFilePond = (node) => {
	if (!(node instanceof HTMLInputElement) || !node.matches('[data-filepond]')) return;
	if (node.dataset.filepondInitialized === 'true') return;

	FilePond.create(node, {
		allowMultiple: false,
		allowRevert: false,
		allowImagePreview: true,
		allowImageCrop: node.dataset.cropper === 'true',
		allowPaste: false,
		credits: false,
		imagePreviewHeight: 180,
		imageCropAspectRatio: node.dataset.cropRatio || '1:1',
		imageCropShape: 'rect',
		imageCropPermanent: true,
		acceptedFileTypes: ['image/png', 'image/jpeg', 'image/webp'],
		labelIdle: 'Arrastra y suelta una imagen o <span class="filepond--label-action">navega</span>',
		stylePanelLayout: 'compact',
		maxFileSize: '2MB',
	});

	node.dataset.filepondInitialized = 'true';
};

const initGlobalFilePondTree = (node) => {
	if (node instanceof HTMLInputElement) {
		initGlobalFilePond(node);
		return;
	}
	node.querySelectorAll?.('[data-filepond]').forEach(initGlobalFilePond);
};

const openLivewireModal = (dialog) => {
	if (!(dialog instanceof HTMLDialogElement)) return;
	if (dialog.dataset.modalBound !== 'true') {
		dialog.dataset.modalBound = 'true';
		dialog.addEventListener('cancel', (event) => {
			event.preventDefault();
			if (dialog.dataset.modalDismissible !== 'false') {
				dialog.querySelector('[data-modal-close-action]')?.click();
			}
		});
	}
	if (!dialog.open) dialog.showModal();
};

const openLivewireModals = (node) => {
	if (node instanceof HTMLDialogElement && node.matches('[data-livewire-modal]')) openLivewireModal(node);
	node.querySelectorAll?.('dialog[data-livewire-modal]').forEach(openLivewireModal);
};

const addFilesToTicketInput = (input, files) => {
	if (!(input instanceof HTMLInputElement) || files.length === 0) return;
	const transfer = new DataTransfer();
	const seen = new Set();

	[...(input.files || []), ...files].forEach((file) => {
		const key = `${file.name}:${file.size}:${file.lastModified}`;
		if (seen.has(key) || transfer.files.length >= 5) return;
		seen.add(key);
		transfer.items.add(file);
	});

	input.files = transfer.files;
	input.dispatchEvent(new Event('change', { bubbles: true }));
};

document.addEventListener('paste', (event) => {
	const editor = event.target instanceof Element ? event.target.closest('[data-ticket-rich-textarea]') : null;
	if (!editor) return;
	const files = [...(event.clipboardData?.items || [])]
		.filter((item) => item.kind === 'file')
		.map((item) => item.getAsFile())
		.filter(Boolean);
	const input = document.getElementById(editor.dataset.attachmentInput || '');
	addFilesToTicketInput(input, files);
});

document.addEventListener('dragover', (event) => {
	if (event.target instanceof Element && event.target.closest('[data-ticket-dropzone]')) {
		event.preventDefault();
	}
});

document.addEventListener('drop', (event) => {
	const dropzone = event.target instanceof Element ? event.target.closest('[data-ticket-dropzone]') : null;
	if (!dropzone) return;
	event.preventDefault();
	const input = document.getElementById(dropzone.dataset.attachmentInput || '');
	addFilesToTicketInput(input, [...(event.dataTransfer?.files || [])]);
});

const currentPreferences = () => ({
	theme: localStorage.getItem('snippetdesk-theme') || 'system',
	layout: root.dataset.layout || 'default',
	sidebar_variant: root.dataset.sidebarVariant || 'sidebar',
	direction: root.getAttribute('dir') || 'ltr',
});

const persistUserPreferences = () => {
	window.Livewire?.dispatch('preferences-updated', { preferences: currentPreferences() });
};

const commitTheme = (theme) => {
    const dark = theme === 'dark' || (theme === 'system' && systemTheme.matches);
    root.classList.toggle('dark', dark);
    root.style.colorScheme = dark ? 'dark' : 'light';
    document.querySelector('meta[name="theme-color"]')?.setAttribute('content', dark ? '#020817' : '#ffffff');
    localStorage.setItem('snippetdesk-theme', theme);

    document.querySelectorAll('[data-theme-value]').forEach((option) => {
        const selected = option.dataset.themeValue === theme;
        option.setAttribute('aria-pressed', String(selected));
        if (option instanceof HTMLInputElement) option.checked = selected;
        option.querySelectorAll('[data-theme-check]').forEach((check) => {
            check.classList.toggle('hidden', check.dataset.themeCheck !== theme);
        });
    });
};

const applyTheme = (theme, persist = true, origin = null) => {
    const reduceMotion = window.matchMedia('(prefers-reduced-motion: reduce)').matches;
    const canAnimate = persist && origin && document.startViewTransition && !reduceMotion;

    if (canAnimate) {
        const trigger = origin.currentTarget instanceof Element
            ? origin.currentTarget
            : origin.target instanceof Element ? origin.target.closest('[data-theme-value]') : null;
        const bounds = trigger?.getBoundingClientRect();
        const x = Number.isFinite(origin.clientX) && origin.clientX > 0
            ? origin.clientX
            : (bounds?.left ?? window.innerWidth / 2) + (bounds?.width ?? 0) / 2;
        const y = Number.isFinite(origin.clientY) && origin.clientY > 0
            ? origin.clientY
            : (bounds?.top ?? window.innerHeight / 2) + (bounds?.height ?? 0) / 2;
        const radius = Math.hypot(
            Math.max(x, window.innerWidth - x),
            Math.max(y, window.innerHeight - y),
        );

        root.style.setProperty('--theme-wave-x', `${x}px`);
        root.style.setProperty('--theme-wave-y', `${y}px`);
        root.style.setProperty('--theme-wave-radius', `${radius}px`);
        document.startViewTransition(() => commitTheme(theme));
    } else {
        commitTheme(theme);
    }

    if (persist) persistUserPreferences();
};

const updateSidebarButton = () => {
	const button = document.querySelector('[data-sidebar-toggle]');
	if (!button) return;
	const expanded = root.dataset.layout === 'default';
	const label = expanded ? 'Contraer barra lateral' : 'Mostrar barra lateral';
	button.setAttribute('aria-expanded', String(expanded));
	button.setAttribute('aria-label', label);
	button.setAttribute('title', label);
};

const applyLayout = (layout, persist = true) => {
	root.dataset.layout = layout;
	root.classList.toggle('sidebar-collapsed', layout === 'compact');
	localStorage.setItem('snippetdesk-layout', layout);
	localStorage.setItem('snippetdesk-sidebar', layout === 'compact' ? 'collapsed' : 'expanded');
	updateSidebarButton();
	if (persist) persistUserPreferences();
};

const applySidebarVariant = (variant, persist = true) => {
	root.dataset.sidebarVariant = variant;
	localStorage.setItem('snippetdesk-sidebar-variant', variant);
	if (persist) persistUserPreferences();
};

const applyDirection = (direction, persist = true) => {
	root.setAttribute('dir', direction);
	localStorage.setItem('snippetdesk-direction', direction);
	if (persist) persistUserPreferences();
};

const syncPreferenceRadios = () => {
	const values = {
		'[data-sidebar-value]': root.dataset.sidebarVariant || 'sidebar',
		'[data-layout-value]': root.dataset.layout || 'default',
		'[data-direction-value]': root.getAttribute('dir') || 'ltr',
	};

	Object.entries(values).forEach(([selector, current]) => {
		document.querySelectorAll(selector).forEach((option) => {
			option.checked = option.value === current;
		});
	});
};

const showConfirmModal = (message, callback) => {
	const modal = document.getElementById('global-confirm-modal');
	const copy = modal?.querySelector('[data-confirm-message]');
	if (!modal || !copy) return;

	copy.textContent = message;
	modal.classList.remove('hidden');
	modal.classList.add('flex');
	modal.setAttribute('aria-hidden', 'false');
	confirmAction = callback;
};

const hideConfirmModal = () => {
	const modal = document.getElementById('global-confirm-modal');
	if (!modal) return;
	modal.classList.add('hidden');
	modal.classList.remove('flex');
	modal.setAttribute('aria-hidden', 'true');
	confirmAction = null;
};

const positionFloatingMenu = (details, panel) => {
	if (!details.open) return;
	const trigger = details.querySelector('summary');
	if (!trigger) return;

	const triggerRect = trigger.getBoundingClientRect();
	const panelRect = panel.getBoundingClientRect();
	const left = Math.min(window.innerWidth - panelRect.width - 12, triggerRect.right - panelRect.width);
	const top = Math.min(window.innerHeight - panelRect.height - 12, triggerRect.bottom + 8);

	panel.style.position = 'fixed';
	panel.style.left = Math.max(12, left) + 'px';
	panel.style.top = Math.max(12, top) + 'px';
	panel.style.zIndex = '60';
};

const initFloatingMenus = () => {
	document.querySelectorAll('[data-floating-menu]').forEach((details) => {
		if (details.dataset.floatingBound === 'true') return;
		const panel = details.querySelector('[data-floating-panel]');
		if (!panel) return;

		details.dataset.floatingBound = 'true';
		const syncPosition = () => positionFloatingMenu(details, panel);
		details.addEventListener('toggle', syncPosition);
		details.querySelector('summary')?.addEventListener('click', () => requestAnimationFrame(syncPosition));
		window.addEventListener('resize', syncPosition);
		window.addEventListener('scroll', syncPosition, true);
	});
};

const moduleTypeForUrl = (value) => {
	const pathname = new URL(value, window.location.origin).pathname;
	if (pathname.startsWith('/chats')) return 'chat';
	if (pathname.startsWith('/users')) return 'table';
	if (pathname.startsWith('/profile')) return 'profile';
	if (pathname.startsWith('/roles') || pathname.startsWith('/navigation')) return 'list';
	return 'dashboard';
};

const setNavigationSkeleton = (loading, targetUrl = window.location.href) => {
	const content = document.querySelector('[data-module-content]');
	const loader = document.querySelector('[data-module-loader]');
	if (!content || !loader) return;

	content.setAttribute('aria-busy', String(loading));
	content.classList.toggle('opacity-30', loading);
	content.classList.toggle('pointer-events-none', loading);
	loader.dataset.moduleSkeleton = moduleTypeForUrl(targetUrl);
	loader.classList.toggle('hidden', !loading);
	loader.hidden = !loading;
};

const scrollChatToBottom = () => {
	requestAnimationFrame(() => {
		const list = document.querySelector('[data-chat-message-list]');
		list?.scrollTo({ top: list.scrollHeight, behavior: 'auto' });
		const composer = document.querySelector('[data-chat-composer-input]');
		if (composer) {
			composer.style.height = 'auto';
			composer.style.height = Math.min(composer.scrollHeight, 112) + 'px';
		}
	});
};

const initPage = () => {
	renderBlobatars(document);
	initAvatarPhotos(document);
	openLivewireModals(document);
	initGlobalFilePondTree(document);
	initFloatingMenus();
	applyTheme(localStorage.getItem('snippetdesk-theme') || 'system', false);
	applyLayout(localStorage.getItem('snippetdesk-layout') || root.dataset.layout || 'default', false);
	applySidebarVariant(localStorage.getItem('snippetdesk-sidebar-variant') || root.dataset.sidebarVariant || 'sidebar', false);
	applyDirection(localStorage.getItem('snippetdesk-direction') || root.getAttribute('dir') || 'ltr', false);
	syncPreferenceRadios();
	updateSidebarButton();
	scrollChatToBottom();
	hydrateSnapshotSurfaces();
	registerSessionTimeoutHooks();
	resetSessionTimeout();
};

document.addEventListener('click', (event) => {
	const target = event.target instanceof Element ? event.target : null;
	if (!target) return;
	const sidebarButton = target.closest('[data-sidebar-toggle]');
	if (sidebarButton) {
		applyLayout(root.dataset.layout === 'default' ? 'compact' : 'default');
		return;
	}

    const themeOption = target.closest('[data-theme-value]');
    if (themeOption) {
        applyTheme(themeOption.dataset.themeValue, true, event);
        return;
    }

	const passwordToggle = target.closest('[data-password-toggle]');
	if (passwordToggle) {
		const password = document.getElementById(passwordToggle.getAttribute('aria-controls'));
		if (!(password instanceof HTMLInputElement)) return;
		const shouldShow = password.type === 'password';
		password.type = shouldShow ? 'text' : 'password';
		passwordToggle.setAttribute('aria-label', shouldShow ? 'Ocultar contraseña' : 'Mostrar contraseña');
		passwordToggle.setAttribute('aria-pressed', String(shouldShow));
		passwordToggle.querySelector('[data-password-icon="show"]')?.classList.toggle('hidden', shouldShow);
		passwordToggle.querySelector('[data-password-icon="hide"]')?.classList.toggle('hidden', !shouldShow);
		return;
	}

	if (target.closest('[data-close-mobile-sidebar]')) {
		target.closest('.mobile-sidebar-root')?.removeAttribute('open');
		return;
	}

	if (target.closest('[data-open-theme-settings]')) {
		target.closest('details')?.removeAttribute('open');
		const dialog = document.querySelector('[data-theme-settings]');
		if (dialog instanceof HTMLDialogElement && !dialog.open) dialog.showModal();
		return;
	}

	if (target.closest('[data-close-theme-settings]')) {
		document.querySelector('[data-theme-settings]')?.close();
		return;
	}

	if (target.closest('[data-search-trigger]')) {
		document.querySelector('.mobile-sidebar-root')?.removeAttribute('open');
		const dialog = document.querySelector('[data-search-dialog]');
		if (dialog instanceof HTMLDialogElement && !dialog.open) dialog.showModal();
		dialog?.querySelector('[data-search-input]')?.focus();
		return;
	}

	if (target.closest('[data-close-search]')) {
		document.querySelector('[data-search-dialog]')?.close();
		return;
	}

	if (target.closest('[data-reset-theme]')) {
		applyTheme('system');
		return;
	}
	if (target.closest('[data-reset-sidebar]')) {
		applySidebarVariant('sidebar');
		syncPreferenceRadios();
		return;
	}
	if (target.closest('[data-reset-layout]')) {
		applyLayout('default');
		syncPreferenceRadios();
		return;
	}
	if (target.closest('[data-reset-direction]')) {
		applyDirection('ltr');
		syncPreferenceRadios();
		return;
	}
	if (target.closest('[data-reset-all-settings]')) {
		applyTheme('system', false);
		applySidebarVariant('sidebar', false);
		applyLayout('default', false);
		applyDirection('ltr', false);
		syncPreferenceRadios();
		persistUserPreferences();
		return;
	}

	const confirmTrigger = target.closest('[data-confirm-action]');
	if (confirmTrigger) {
		if (confirmTrigger.dataset.confirmPending === 'true') {
			confirmTrigger.dataset.confirmPending = 'false';
			return;
		}
		event.preventDefault();
		showConfirmModal(confirmTrigger.dataset.confirmMessage || '¿Estás seguro de continuar?', () => {
			confirmTrigger.dataset.confirmPending = 'true';
			confirmTrigger.click();
			confirmTrigger.dataset.confirmPending = 'false';
			hideConfirmModal();
		});
		return;
	}

	if (target.closest('[data-confirm-accept]')) {
		if (typeof confirmAction === 'function') confirmAction();
		return;
	}
	if (target.closest('[data-confirm-cancel]')) {
		hideConfirmModal();
		return;
	}

	const modal = target.closest('dialog');
	if (modal && event.target === modal) {
		if (modal.dataset.modalDismissible === 'false') return;
		if (modal.matches('[data-theme-settings], [data-search-dialog]')) modal.close();
		else modal.querySelector('[data-modal-close-action]')?.click();
	}
});

document.addEventListener('change', (event) => {
	const target = event.target;
	if (!(target instanceof HTMLInputElement) || !target.checked) return;

	if (target.matches('[data-sidebar-value]')) applySidebarVariant(target.value);
	if (target.matches('[data-layout-value]')) applyLayout(target.value);
	if (target.matches('[data-direction-value]')) applyDirection(target.value);
});

document.addEventListener('input', (event) => {
	const target = event.target;
	if (!(target instanceof HTMLInputElement || target instanceof HTMLTextAreaElement)) return;

	if (target.matches('[data-search-input]')) {
		const dialog = target.closest('[data-search-dialog]');
		const query = target.value.trim().toLocaleLowerCase();
		let visibleResults = 0;

		dialog?.querySelectorAll('[data-search-item]').forEach((item) => {
			const searchText = (item.textContent + ' ' + (item.dataset.searchValue || '')).toLocaleLowerCase();
			const matches = !query || searchText.includes(query);
			item.classList.toggle('hidden', !matches);
			visibleResults += Number(matches);
		});
		dialog?.querySelector('[data-search-empty]')?.classList.toggle('hidden', visibleResults > 0);
	}

	if (target.matches('[data-chat-composer-input]')) {
		target.style.height = 'auto';
		target.style.height = Math.min(target.scrollHeight, 112) + 'px';
	}
});

document.addEventListener('keydown', (event) => {
	if (event.key === 'Escape') {
		document.querySelector('.mobile-sidebar-root')?.removeAttribute('open');
		hideConfirmModal();
	}

	if (event.target instanceof HTMLTextAreaElement
		&& event.target.matches('[data-chat-composer-input]')
		&& event.key === 'Enter'
		&& !event.shiftKey
		&& !event.isComposing) {
		event.preventDefault();
		event.target.closest('form')?.requestSubmit();
		return;
	}

	if ((event.ctrlKey || event.metaKey) && event.key.toLowerCase() === 'k') {
		event.preventDefault();
		const dialog = document.querySelector('[data-search-dialog]');
		if (dialog instanceof HTMLDialogElement && !dialog.open) dialog.showModal();
		dialog?.querySelector('[data-search-input]')?.focus();
		return;
	}

	if (!(event.ctrlKey || event.metaKey) || !event.shiftKey) return;
	if (event.target instanceof HTMLElement && event.target.closest('input, textarea, select, [contenteditable="true"]')) return;

	const key = event.key.toLowerCase();
	if (key === 'p') {
		event.preventDefault();
		window.Livewire?.navigate(document.querySelector('[data-profile-link]')?.href || '/profile');
	} else if (key === 's') {
		event.preventDefault();
		const dialog = document.querySelector('[data-theme-settings]');
		if (dialog instanceof HTMLDialogElement && !dialog.open) dialog.showModal();
	} else if (key === 'q') {
		event.preventDefault();
		document.querySelector('[data-logout-form]')?.requestSubmit();
	}
});

window.addEventListener('profile-avatar-updated', (event) => {
	const { userId, profilePhotoUrl, avatarSeed, avatarPreferences = {} } = event.detail;

	document.querySelectorAll('[data-user-avatar-id]').forEach((avatar) => {
		if (avatar.dataset.userAvatarId !== String(userId)) return;

		const image = avatar instanceof SVGSVGElement ? imageFromAnimatedBlobatar(avatar) : avatar;
		if (image !== avatar) avatar.replaceWith(image);

		if (profilePhotoUrl) {
			image.removeAttribute('data-blobatar-seed');
			image.removeAttribute('data-blobatar-size');
			image.src = profilePhotoUrl;
		} else if (avatarSeed) {
			const size = Number(image.dataset.blobatarSize || image.getAttribute('width')) || 40;
			image.dataset.blobatarSeed = avatarSeed;
			image.dataset.blobatarSize = String(size);
			image.dataset.blobatarBackground = avatarPreferences.background || 'circle';
			image.dataset.blobatarExpression = avatarPreferences.expression || 'idle';
			if (avatarPreferences.background_color) image.dataset.blobatarBackgroundColor = avatarPreferences.background_color;
			if (avatarPreferences.color_mode === 'custom') {
				image.dataset.blobatarHue = String(avatarPreferences.hue ?? 35);
				image.dataset.blobatarTone = String(avatarPreferences.tone ?? 0.55);
			} else {
				image.removeAttribute('data-blobatar-hue');
				image.removeAttribute('data-blobatar-tone');
			}
			if (image.dataset.blobatarMode) {
				image.dataset.blobatarAnimate = image.dataset.blobatarMode;
			}
			renderBlobatar(image);
		}
	});
});

window.addEventListener('chat-scroll-bottom', scrollChatToBottom);
window.addEventListener('chat-focus-composer', () => {
	requestAnimationFrame(() => document.querySelector('[data-chat-composer-input]')?.focus());
});

document.addEventListener('error', (event) => {
	const image = event.target;
	if (image instanceof HTMLImageElement && image.matches('[data-chat-avatar-photo]')) {
		image.hidden = true;
	}
}, true);

document.addEventListener('load', (event) => {
	const image = event.target;
	if (image instanceof HTMLImageElement && image.matches('[data-chat-avatar-photo]')) {
		image.hidden = false;
		image.classList.add('is-loaded');
	}
}, true);

const snapshotDialog = () => document.querySelector('[data-snapshot-dialog]');
const snapshotSurfaces = () => [...document.querySelectorAll('[data-snapshot-workspace]')];

let productivityFabAnimationTimer = null;
let productivityFabReactionTimer = null;

const productivityFabExpressions = [
	{ expression: 'happy', reaction: 'hop' },
	{ expression: 'wink', reaction: 'tilt' },
	{ expression: 'surprised', reaction: 'pop' },
	{ expression: 'thinking', reaction: 'sway' },
	{ expression: 'love', reaction: 'heartbeat' },
	{ expression: 'shy', reaction: 'tuck' },
	{ expression: 'smug', reaction: 'nod' },
	{ expression: 'unsure', reaction: 'wobble' },
	{ expression: 'sleepy', reaction: 'float' },
];

const cycleProductivityFabExpression = (toggle) => {
	if (!(toggle instanceof HTMLElement)) return;

	const renderedAvatar = toggle.querySelector('[data-blobatar-seed]');
	if (!renderedAvatar) return;
	const currentExpression = renderedAvatar.dataset.blobatarExpression || toggle.dataset.fabExpression || 'idle';
	const currentIndex = productivityFabExpressions.findIndex(({ expression }) => expression === currentExpression);
	const nextIndex = (currentIndex + 1) % productivityFabExpressions.length;
	const next = productivityFabExpressions[nextIndex];

	const image = renderedAvatar instanceof SVGSVGElement
		? imageFromAnimatedBlobatar(renderedAvatar)
		: renderedAvatar;
	image.dataset.blobatarExpression = next.expression;
	image.dataset.blobatarAnimate = image.dataset.blobatarMode || 'always';
	if (image !== renderedAvatar) renderedAvatar.replaceWith(image);
	renderBlobatar(image);

	toggle.dataset.fabExpression = next.expression;
	toggle.dataset.fabReaction = next.reaction;
	window.clearTimeout(productivityFabReactionTimer);
	productivityFabReactionTimer = window.setTimeout(() => {
		if (toggle.dataset.fabReaction === next.reaction) delete toggle.dataset.fabReaction;
	}, 520);
};

const animateProductivityFabPress = (toggle) => {
	if (!(toggle instanceof HTMLElement)) return;
	if (window.matchMedia('(prefers-reduced-motion: reduce)').matches) return;

	window.clearTimeout(productivityFabAnimationTimer);
	toggle.classList.remove('is-celebrating');
	void toggle.offsetWidth;
	toggle.classList.add('is-celebrating');
	productivityFabAnimationTimer = window.setTimeout(() => toggle.classList.remove('is-celebrating'), 720);
};

const closeProductivityFab = () => {
	const root = document.querySelector('[data-productivity-fab]');
	const menu = root?.querySelector('[data-fab-menu]');
	const toggle = root?.querySelector('[data-fab-toggle]');
	if (!menu || !toggle) return;

	menu.dataset.state = 'closing';
	menu.classList.add('opacity-0', 'translate-y-3', 'scale-95');
	menu.classList.remove('opacity-100');
	menu.classList.remove('translate-y-0', 'scale-100');
	root.dataset.state = 'closed';
	toggle.setAttribute('aria-expanded', 'false');
	toggle.setAttribute('aria-label', 'Abrir acciones rápidas');
	window.setTimeout(() => {
		if (toggle.getAttribute('aria-expanded') === 'false') {
			menu.classList.add('hidden');
			menu.classList.remove('flex');
			menu.dataset.state = 'closed';
		}
	}, 220);
};

const toggleProductivityFab = () => {
	const root = document.querySelector('[data-productivity-fab]');
	const menu = root?.querySelector('[data-fab-menu]');
	const toggle = root?.querySelector('[data-fab-toggle]');
	if (!menu || !toggle) return;

	const shouldOpen = toggle.getAttribute('aria-expanded') !== 'true';
	if (!shouldOpen) {
		closeProductivityFab();
		return;
	}

	menu.classList.remove('hidden');
	menu.classList.add('flex');
	menu.dataset.state = 'opening';
	requestAnimationFrame(() => {
		menu.classList.remove('opacity-0');
		menu.classList.add('opacity-100');
		menu.classList.remove('translate-y-3', 'scale-95');
		menu.classList.add('translate-y-0', 'scale-100');
		menu.dataset.state = 'open';
	});
	root.dataset.state = 'open';
	toggle.setAttribute('aria-expanded', 'true');
	toggle.setAttribute('aria-label', 'Cerrar acciones rápidas');
};

const extensionForImage = (file) => {
	const fromName = file.name?.split('.').pop()?.toLowerCase();
	if (fromName && fromName !== file.name.toLowerCase()) return fromName;
	return ({ 'image/jpeg': 'jpg', 'image/png': 'png', 'image/webp': 'webp', 'image/gif': 'gif' })[file.type] || 'png';
};

const safeSnapshotName = (name, file) => {
	const extension = extensionForImage(file);
	const cleaned = String(name || '')
		.replace(/[\\/:*?"<>|]/gu, '-')
		.replace(/\s+/gu, ' ')
		.trim()
		.slice(0, 100);
	const fallback = `snapshot-${String(snapshotSequence).padStart(2, '0')}`;
	const value = cleaned || fallback;
	return value.toLowerCase().endsWith(`.${extension}`) ? value : `${value}.${extension}`;
};

const downloadSnapshotBlob = (blob, name) => {
	const url = URL.createObjectURL(blob);
	const link = document.createElement('a');
	link.href = url;
	link.download = name;
	document.body.appendChild(link);
	link.click();
	link.remove();
	window.setTimeout(() => URL.revokeObjectURL(url), 1_000);
};

const updateSnapshotSummary = () => {
	const count = snapshots.size;
	snapshotSurfaces().forEach((surface) => {
		const countLabel = surface.querySelector('[data-snapshot-count]');
		if (countLabel) countLabel.textContent = `${count} ${count === 1 ? 'imagen' : 'imágenes'}`;
		surface.querySelector('[data-snapshot-empty]')?.classList.toggle('hidden', count > 0);
		surface.querySelectorAll('[data-snapshot-clear], [data-snapshot-zip]').forEach((button) => {
			button.disabled = count === 0;
		});
	});
};

const renderSnapshotInSurface = (surface, snapshot) => {
	const list = surface.querySelector('[data-snapshot-list]');
	if (!list) return;
	if (list.querySelector(`[data-snapshot-id="${CSS.escape(snapshot.id)}"]`)) return;

	const card = document.createElement('article');
	card.dataset.snapshotId = snapshot.id;
	card.className = 'overflow-hidden rounded-xl border border-border bg-card shadow-sm';

	const preview = document.createElement('div');
	preview.className = 'group relative aspect-video cursor-grab overflow-hidden bg-muted active:cursor-grabbing';
	preview.draggable = true;
	preview.dataset.snapshotDrag = snapshot.id;
	preview.title = 'Arrastra esta imagen hacia otra herramienta';

	const image = document.createElement('img');
	image.src = snapshot.url;
	image.alt = '';
	image.draggable = false;
	image.className = 'size-full object-contain';
	preview.appendChild(image);

	const dragHint = document.createElement('span');
	dragHint.className = 'pointer-events-none absolute bottom-2 left-2 rounded-md bg-background/90 px-2 py-1 text-xs font-medium text-foreground shadow-sm';
	dragHint.textContent = 'Arrastrar imagen';
	preview.appendChild(dragHint);

	const body = document.createElement('div');
	body.className = 'grid gap-3 p-3';
	const label = document.createElement('label');
	label.className = 'grid gap-1.5 text-xs font-medium';
	label.textContent = 'Nombre del archivo';
	const input = document.createElement('input');
	input.type = 'text';
	input.value = snapshot.name;
	input.dataset.snapshotName = snapshot.id;
	input.className = 'h-11 rounded-md border border-input bg-background px-3 text-base outline-none focus-visible:ring-2 focus-visible:ring-ring sm:text-sm';
	label.appendChild(input);
	body.appendChild(label);

	const actions = document.createElement('div');
	actions.className = 'grid grid-cols-2 gap-2';
	const download = document.createElement('button');
	download.type = 'button';
	download.dataset.snapshotDownload = snapshot.id;
	download.className = 'min-h-11 rounded-md border border-input px-3 text-sm font-medium hover:bg-accent';
	download.textContent = 'Descargar';
	const remove = document.createElement('button');
	remove.type = 'button';
	remove.dataset.snapshotRemove = snapshot.id;
	remove.className = 'min-h-11 rounded-md border border-input px-3 text-sm font-medium text-destructive hover:bg-accent';
	remove.textContent = 'Eliminar';
	actions.append(download, remove);
	body.appendChild(actions);
	card.append(preview, body);
	list.appendChild(card);
};

const renderSnapshot = (snapshot) => {
	snapshotSurfaces().forEach((surface) => renderSnapshotInSurface(surface, snapshot));
};

const hydrateSnapshotSurfaces = () => {
	snapshotSurfaces().forEach((surface) => surface.querySelector('[data-snapshot-list]')?.replaceChildren());
	snapshots.forEach(renderSnapshot);
	updateSnapshotSummary();
};

const addSnapshots = (files) => {
	[...files].filter((file) => file.type.startsWith('image/')).forEach((file) => {
		snapshotSequence += 1;
		const id = crypto.randomUUID?.() || `snapshot-${Date.now()}-${snapshotSequence}`;
		const snapshot = {
			id,
			file,
			name: safeSnapshotName(file.name?.startsWith('image.') ? '' : file.name, file),
			url: URL.createObjectURL(file),
		};
		snapshots.set(id, snapshot);
		renderSnapshot(snapshot);
	});
	updateSnapshotSummary();
};

const removeSnapshot = (id) => {
	const snapshot = snapshots.get(id);
	if (!snapshot) return;
	URL.revokeObjectURL(snapshot.url);
	snapshots.delete(id);
	snapshotSurfaces().forEach((surface) => {
		surface.querySelector(`[data-snapshot-id="${CSS.escape(id)}"]`)?.remove();
	});
	updateSnapshotSummary();
};

const clearSnapshots = () => {
	snapshots.forEach((snapshot) => URL.revokeObjectURL(snapshot.url));
	snapshots.clear();
	snapshotSurfaces().forEach((surface) => surface.querySelector('[data-snapshot-list]')?.replaceChildren());
	updateSnapshotSummary();
};

const downloadSnapshotsZip = async (button) => {
	if (snapshots.size === 0) return;
	const originalLabel = button.textContent;
	button.disabled = true;
	button.textContent = 'Preparando ZIP...';

	try {
		const { default: JSZip } = await import('jszip');
		const zip = new JSZip();
		const usedNames = new Set();
		snapshots.forEach((snapshot) => {
			let name = safeSnapshotName(snapshot.name, snapshot.file);
			let suffix = 2;
			while (usedNames.has(name.toLocaleLowerCase())) {
				const extension = `.${extensionForImage(snapshot.file)}`;
				name = `${name.slice(0, -extension.length)}-${suffix}${extension}`;
				suffix += 1;
			}
			usedNames.add(name.toLocaleLowerCase());
			zip.file(name, snapshot.file);
		});
		const blob = await zip.generateAsync({ type: 'blob', compression: 'DEFLATE', compressionOptions: { level: 6 } });
		downloadSnapshotBlob(blob, `snapshots-${new Date().toISOString().slice(0, 10)}.zip`);
	} finally {
		button.textContent = originalLabel;
		button.disabled = snapshots.size === 0;
	}
};

const avatarCustomizerDialog = () => document.querySelector('[data-avatar-customizer-dialog]');

const applyAvatarCustomizationPreview = () => {
	const panel = document.querySelector('[data-avatar-customizer]');
	if (!panel) return;

	const value = (name) => panel.querySelector(`[data-avatar-field="${name}"]:checked`)?.value
		|| panel.querySelector(`[data-avatar-field="${name}"]`)?.value;
	const colorMode = value('color_mode') || 'auto';
	const hue = value('hue') || '35';
	const tone = value('tone') || '0.55';
	const background = value('background') || 'circle';
	const backgroundColor = value('background_color') || '#f6c76b';
	const expression = value('expression') || 'idle';

	document.querySelectorAll('[data-avatar-customizer-preview]').forEach((avatar) => {
		avatar.setAttribute('data-blobatar-background', background);
		avatar.setAttribute('data-blobatar-background-color', backgroundColor);
		avatar.setAttribute('data-blobatar-expression', expression);
		if (colorMode === 'custom') {
			avatar.setAttribute('data-blobatar-hue', hue);
			avatar.setAttribute('data-blobatar-tone', tone);
		} else {
			avatar.removeAttribute('data-blobatar-hue');
			avatar.removeAttribute('data-blobatar-tone');
		}
	});

	const hueOutput = panel.querySelector('[data-avatar-hue-output]');
	const toneOutput = panel.querySelector('[data-avatar-tone-output]');
	if (hueOutput) hueOutput.textContent = `${hue}°`;
	if (toneOutput) toneOutput.textContent = `${Math.round(Number(tone) * 100)}%`;
};

document.addEventListener('click', (event) => {
	const target = event.target instanceof Element ? event.target : null;
	if (!target) return;

	if (target.closest('[data-avatar-customizer-open]')) {
		const dialog = avatarCustomizerDialog();
		if (dialog instanceof HTMLDialogElement && !dialog.open) {
			dialog.showModal();
			applyAvatarCustomizationPreview();
			requestAnimationFrame(() => dialog.querySelector('[data-avatar-field="color_mode"]')?.focus());
		}
		return;
	}

	if (target.closest('[data-avatar-customizer-close]')) {
		avatarCustomizerDialog()?.close();
		return;
	}

	const fabToggle = target.closest('[data-fab-toggle]');

	if (fabToggle) {
		cycleProductivityFabExpression(fabToggle);
		animateProductivityFabPress(fabToggle);
		toggleProductivityFab();
		return;
	}

	if (target.closest('[data-fab-create-message]')) {
		closeProductivityFab();
		window.Livewire?.dispatch('open-preset-message-form');
		return;
	}

	if (target.closest('[data-fab-ticket-search]')) {
		closeProductivityFab();
		window.Livewire?.dispatch('open-ticket-quick-search');
		return;
	}

	if (target.closest('[data-fab-snapshot]')) {
		closeProductivityFab();
		const dialog = snapshotDialog();
		if (dialog instanceof HTMLDialogElement && !dialog.open) dialog.showModal();
		hydrateSnapshotSurfaces();
		requestAnimationFrame(() => dialog?.querySelector('[data-snapshot-dropzone]')?.focus());
		return;
	}

	if (target.closest('[data-fab-extensions]')) {
		closeProductivityFab();
		return;
	}

	if (target.closest('[data-fab-chat]')) {
		closeProductivityFab();
		return;
	}

	if (target.closest('[data-close-snapshot]')) {
		snapshotDialog()?.close();
		return;
	}

	const remove = target.closest('[data-snapshot-remove]');
	if (remove) {
		removeSnapshot(remove.dataset.snapshotRemove);
		return;
	}

	const download = target.closest('[data-snapshot-download]');
	if (download) {
		const snapshot = snapshots.get(download.dataset.snapshotDownload);
		if (snapshot) downloadSnapshotBlob(snapshot.file, safeSnapshotName(snapshot.name, snapshot.file));
		return;
	}

	if (target.closest('[data-snapshot-clear]')) {
		clearSnapshots();
		return;
	}

	const zipButton = target.closest('[data-snapshot-zip]');
	if (zipButton) {
		downloadSnapshotsZip(zipButton);
		return;
	}

	const dialog = target.closest('[data-snapshot-dialog]');
	if (dialog && event.target === dialog) dialog.close();

	if (!target.closest('[data-productivity-fab]')) closeProductivityFab();
});

document.addEventListener('change', (event) => {
	if (!event.target.matches('[data-snapshot-input]')) return;
	addSnapshots(event.target.files || []);
	event.target.value = '';
});

document.addEventListener('input', (event) => {
	if (!event.target.matches('[data-snapshot-name]')) return;
	const snapshot = snapshots.get(event.target.dataset.snapshotName);
	if (!snapshot) return;
	snapshot.name = event.target.value;
	document.querySelectorAll(`[data-snapshot-name="${CSS.escape(snapshot.id)}"]`).forEach((input) => {
		if (input !== event.target) input.value = snapshot.name;
	});
});

document.addEventListener('paste', (event) => {
	const dialog = snapshotDialog();
	const dialogOpen = dialog instanceof HTMLDialogElement && dialog.open;
	if (!dialogOpen && !document.querySelector('[data-snapshot-page]')) return;
	const files = [...(event.clipboardData?.items || [])]
		.filter((item) => item.kind === 'file' && item.type.startsWith('image/'))
		.map((item) => item.getAsFile())
		.filter(Boolean);
	if (files.length === 0) return;
	event.preventDefault();
	addSnapshots(files);
});

document.addEventListener('dragover', (event) => {
	const pinnedEntry = event.target.closest('[data-pinned-preset-id]');
	if (pinnedEntry && draggedPinnedPresetMessageId) {
		event.preventDefault();
		pinnedEntry.classList.add('is-drag-over');
		return;
	}

	const dropzone = event.target.closest('[data-snapshot-dropzone]');
	if (!dropzone) return;
	event.preventDefault();
	dropzone.dataset.dragging = 'true';
});

document.addEventListener('dragleave', (event) => {
	const pinnedEntry = event.target.closest('[data-pinned-preset-id]');
	if (pinnedEntry && !pinnedEntry.contains(event.relatedTarget)) pinnedEntry.classList.remove('is-drag-over');

	const dropzone = event.target.closest('[data-snapshot-dropzone]');
	if (dropzone && !dropzone.contains(event.relatedTarget)) dropzone.dataset.dragging = 'false';
});

document.addEventListener('drop', (event) => {
	const pinnedEntry = event.target.closest('[data-pinned-preset-id]');
	if (pinnedEntry && draggedPinnedPresetMessageId) {
		event.preventDefault();
		const list = pinnedEntry.closest('[data-pinned-preset-list]');
		const entries = [...(list?.querySelectorAll('[data-pinned-preset-id]') || [])];
		const targetId = pinnedEntry.dataset.pinnedPresetId;
		const orderedIds = entries.map((entry) => entry.dataset.pinnedPresetId).filter((id) => id !== draggedPinnedPresetMessageId);
		const bounds = pinnedEntry.getBoundingClientRect();
		const afterTarget = event.clientY > bounds.top + bounds.height / 2;
		const targetIndex = Math.max(0, orderedIds.indexOf(targetId) + Number(afterTarget));
		orderedIds.splice(targetIndex, 0, draggedPinnedPresetMessageId);
		const workspace = list?.closest('[wire\\:id]');
		const wire = workspace ? window.Livewire?.find(workspace.getAttribute('wire:id')) : null;
		wire?.$call('reorderPinned', orderedIds.map(Number));
		entries.forEach((entry) => entry.classList.remove('is-drag-over'));
		draggedPinnedPresetMessageId = null;
		return;
	}

	const dropzone = event.target.closest('[data-snapshot-dropzone]');
	if (!dropzone) return;
	event.preventDefault();
	dropzone.dataset.dragging = 'false';
	addSnapshots(event.dataTransfer?.files || []);
});

document.addEventListener('dragstart', (event) => {
	const pinnedEntry = event.target.closest('[data-pinned-preset-id]');
	if (pinnedEntry && event.dataTransfer) {
		draggedPinnedPresetMessageId = pinnedEntry.dataset.pinnedPresetId;
		event.dataTransfer.effectAllowed = 'move';
		event.dataTransfer.setData('text/plain', draggedPinnedPresetMessageId);
		return;
	}

	const dragTarget = event.target.closest('[data-snapshot-drag]');
	if (!dragTarget || !event.dataTransfer) return;
	const snapshot = snapshots.get(dragTarget.dataset.snapshotDrag);
	if (!snapshot) return;

	const name = safeSnapshotName(snapshot.name, snapshot.file);
	const file = new File([snapshot.file], name, { type: snapshot.file.type, lastModified: snapshot.file.lastModified });
	event.dataTransfer.effectAllowed = 'copy';
	try {
		event.dataTransfer.items.add(file);
	} catch {
		// DownloadURL remains available for browsers that do not expose writable drag items.
	}
	event.dataTransfer.setData('DownloadURL', `${file.type}:${name}:${snapshot.url}`);
	event.dataTransfer.setData('text/uri-list', snapshot.url);
});

document.addEventListener('dragend', () => {
	draggedPinnedPresetMessageId = null;
	document.querySelectorAll('[data-pinned-preset-id].is-drag-over').forEach((entry) => entry.classList.remove('is-drag-over'));
});

document.addEventListener('keydown', (event) => {
	if (event.key === 'Escape') closeProductivityFab();
});

const writeClipboardText = async (text) => {
	if (navigator.clipboard?.writeText) {
		await navigator.clipboard.writeText(text);
		return;
	}

	const textarea = document.createElement('textarea');
	textarea.value = text;
	textarea.setAttribute('readonly', '');
	textarea.style.position = 'fixed';
	textarea.style.opacity = '0';
	document.body.appendChild(textarea);
	textarea.select();
	document.execCommand('copy');
	textarea.remove();
};

const replacePresetVariable = (message, variable, value) => {
	const escapedVariable = variable.replace(/[.*+?^${}()|[\]\\]/g, '\\$&');
	return message.replace(new RegExp(`(?:\\{\\{\\s*${escapedVariable}\\s*\\}\\}|\\[\\s*${escapedVariable}\\s*\\])`, 'gu'), value);
};

const automaticPresetValues = (card) => {
	const now = new Date();
	const locale = 'es-MX';
	const hour = now.getHours();
	const greeting = hour < 12 ? 'Buenos días' : hour < 19 ? 'Buenas tardes' : 'Buenas noches';

	return {
		fecha: new Intl.DateTimeFormat(locale, { day: '2-digit', month: '2-digit', year: 'numeric' }).format(now),
		fecha_larga: new Intl.DateTimeFormat(locale, { weekday: 'long', day: 'numeric', month: 'long', year: 'numeric' }).format(now),
		dia: new Intl.DateTimeFormat(locale, { weekday: 'long' }).format(now),
		hora: new Intl.DateTimeFormat(locale, { hour: '2-digit', minute: '2-digit' }).format(now),
		saludo: greeting,
		mi_nombre: card.dataset.currentUser || '',
	};
};

const daysRemainingToNinety = (value) => {
	const start = new Date(`${value}T00:00:00`);
	if (Number.isNaN(start.getTime())) return '';

	const target = new Date(start);
	target.setDate(target.getDate() + 90);
	const today = new Date();
	today.setHours(0, 0, 0, 0);
	const days = Math.max(0, Math.ceil((target.getTime() - today.getTime()) / 86_400_000));

	return `${days} ${days === 1 ? 'día' : 'días'}`;
};

const presetVariableValue = (input) => {
	if (input.dataset.presetVariableCalculation === 'days-to-90') {
		return daysRemainingToNinety(input.value);
	}

	return input.value.trim();
};

const buildPresetMessage = (card) => {
	const inputs = [...card.querySelectorAll('[data-preset-variable]')];
	const missingInput = inputs.find((input) => input.value.trim() === '');
	const error = card.querySelector('[data-preset-error]');
	if (missingInput) {
		error?.classList.remove('hidden');
		missingInput.focus();
		return null;
	}

	error?.classList.add('hidden');
	let message = card.dataset.presetTemplate || '';
	Object.entries(automaticPresetValues(card)).forEach(([variable, value]) => {
		message = replacePresetVariable(message, variable, value);
	});
	inputs.forEach((input) => {
		message = replacePresetVariable(message, input.dataset.presetVariable, presetVariableValue(input));
	});

	return message;
};

const insertPresetVariable = (button) => {
	const textarea = document.getElementById(button.dataset.variableTarget || 'preset-body');
	if (!(textarea instanceof HTMLTextAreaElement)) return;

	const variable = button.dataset.insertPresetVariable;
	const start = textarea.selectionStart ?? textarea.value.length;
	const end = textarea.selectionEnd ?? start;
	const before = textarea.value.slice(0, start);
	const after = textarea.value.slice(end);
	const leadingSpace = before !== '' && !/\s$/u.test(before) ? ' ' : '';
	const trailingSpace = after !== '' && !/^[\s.,!?;:]/u.test(after) ? ' ' : '';
	const value = `${leadingSpace}{{${variable}}}${trailingSpace}`;

	textarea.setRangeText(value, start, end, 'end');
	textarea.dispatchEvent(new Event('input', { bubbles: true }));
	textarea.focus();
};

const selectDeferredAppearanceOption = (form, model, value) => {
	const input = form?.querySelector(`input[wire\\:model="${model}"][value="${value}"]`);
	if (!(input instanceof HTMLInputElement)) return;
	input.checked = true;
	input.dispatchEvent(new Event('input', { bubbles: true }));
	input.dispatchEvent(new Event('change', { bubbles: true }));
};

const markAppearanceAsCustom = (target) => {
	const form = target.closest('[data-preset-appearance-form]');
	if (!form) return;
	selectDeferredAppearanceOption(form, 'draftTheme', 'custom');
	if (target.matches('[data-preset-accent-picker]')) selectDeferredAppearanceOption(form, 'draftAccent', 'custom');
	if (target.matches('[data-preset-background-upload]') && target.files?.length) {
		selectDeferredAppearanceOption(form, 'draftBackground', 'image');
	}
};

const copyPresetMessage = async (button) => {
	const card = button.closest('[data-preset-message-card]');
	if (!card) return;
	const message = buildPresetMessage(card);
	if (message === null) return;

	const label = button.querySelector('[data-copy-label]');
	const originalLabel = label?.textContent;
	button.disabled = true;
	try {
		await writeClipboardText(message);
		if (label) label.textContent = '¡Copiado!';
		const status = card.querySelector('[data-copy-status]');
		if (status) status.textContent = 'Mensaje copiado al portapapeles.';
		window.Livewire?.dispatch('preset-message-used', {
			messageId: Number(card.dataset.presetMessageId),
			channel: 'clipboard',
		});
	} catch {
		if (label) label.textContent = 'No se pudo copiar';
	} finally {
		window.setTimeout(() => {
			if (label) label.textContent = originalLabel;
			button.disabled = false;
		}, 1800);
	}
};

const pastePresetMessageInBrowser = (button) => {
	const card = button.closest('[data-preset-message-card]');
	if (!card) return;
	const message = buildPresetMessage(card);
	if (message === null) return;

	const requestId = crypto.randomUUID?.() || `${Date.now()}-${Math.random()}`;
	button.disabled = true;
	button.dataset.extensionRequestId = requestId;
	window.postMessage({
		source: 'snippetdesk-app',
		type: 'paste-message',
		requestId,
		messageId: Number(card.dataset.presetMessageId),
		text: message,
	}, window.location.origin);

	window.setTimeout(() => {
		if (button.dataset.extensionRequestId !== requestId) return;
		button.disabled = false;
		const status = card.querySelector('[data-copy-status]');
		if (status) status.textContent = 'La extensión no respondió. Verifica su configuración.';
	}, 2500);
};

window.addEventListener('message', (event) => {
	if (event.source !== window || event.origin !== window.location.origin) return;
	const payload = event.data;
	if (!payload || payload.source !== 'snippetdesk-extension') return;

	if (payload.type === 'ready') {
		window.Livewire?.dispatch('browser-extension-detected', { browser: payload.browser || 'Chrome / Edge' });
		const status = document.querySelector('[data-extension-detection-status]');
		if (status) status.textContent = 'Extensión detectada correctamente.';
		return;
	}

	if (payload.type !== 'paste-result') return;
	const button = [...document.querySelectorAll('[data-paste-preset-message]')]
		.find((candidate) => candidate.dataset.extensionRequestId === payload.requestId);
	if (!button) return;
	button.disabled = false;
	delete button.dataset.extensionRequestId;
	const card = button.closest('[data-preset-message-card]');
	const status = card?.querySelector('[data-copy-status]');
	if (payload.success) {
		if (status) status.textContent = 'Mensaje pegado en el campo activo del navegador.';
		window.Livewire?.dispatch('preset-message-used', { messageId: Number(payload.messageId), channel: 'browser' });
	} else if (status) {
		status.textContent = payload.error || 'No se encontró un campo compatible para pegar.';
	}
});

document.addEventListener('click', (event) => {
	const variableButton = event.target.closest('[data-insert-preset-variable]');
	if (variableButton) {
		insertPresetVariable(variableButton);
		return;
	}

	const backgroundSwatch = event.target.closest('[data-preset-background-color-value]');
	if (backgroundSwatch) {
		const form = backgroundSwatch.closest('[data-preset-appearance-form]');
		const picker = form?.querySelector('[data-preset-background-picker]');
		if (picker instanceof HTMLInputElement) {
			picker.value = backgroundSwatch.dataset.presetBackgroundColorValue;
			picker.dispatchEvent(new Event('input', { bubbles: true }));
			picker.dispatchEvent(new Event('change', { bubbles: true }));
			markAppearanceAsCustom(picker);
		}
		return;
	}

	const button = event.target.closest('[data-copy-preset-message]');
	if (button) {
		copyPresetMessage(button);
		return;
	}

	const pasteButton = event.target.closest('[data-paste-preset-message]');
	if (pasteButton) {
		pastePresetMessageInBrowser(pasteButton);
		return;
	}

	if (event.target.closest('[data-detect-browser-extension]')) {
		const status = document.querySelector('[data-extension-detection-status]');
		if (status) status.textContent = 'Buscando la extensión…';
		window.postMessage({ source: 'snippetdesk-app', type: 'extension-ping' }, window.location.origin);
		window.setTimeout(() => {
			if (status?.textContent === 'Buscando la extensión…') {
				status.textContent = 'No se detectó. Confirma que esté instalada y recarga esta página.';
			}
		}, 1800);
	}
});

document.addEventListener('input', (event) => {
	if (event.target.matches('[data-preset-variable]')) {
		event.target.closest('[data-preset-message-card]')?.querySelector('[data-preset-error]')?.classList.add('hidden');
	}

	if (event.target.matches('[data-avatar-field]')) applyAvatarCustomizationPreview();

	if (event.target.matches('[data-avatar-seed-choice]')) {
		document.querySelectorAll('[data-avatar-customizer-primary]').forEach((avatar) => {
			avatar.setAttribute('data-blobatar-seed', event.target.value);
		});
	}
});

document.addEventListener('change', (event) => {
	if (!event.target.matches('[data-preset-custom-control]')) return;
	markAppearanceAsCustom(event.target);
});

systemTheme.addEventListener('change', () => {
	if ((localStorage.getItem('snippetdesk-theme') || 'system') === 'system') applyTheme('system', false);
});

document.addEventListener('livewire:navigate', (event) => {
	setNavigationSkeleton(true, event.detail.url);
});

document.addEventListener('livewire:navigated', () => {
	setNavigationSkeleton(false);
	observeAppDom();
	initPage();
});

const appDomObserver = new MutationObserver((mutations) => {
	mutations.forEach((mutation) => {
		if (mutation.type === 'attributes') {
			renderBlobatar(mutation.target);
			return;
		}

		mutation.addedNodes.forEach((node) => {
			if (node instanceof Element) {
				renderBlobatars(node);
				initAvatarPhotos(node);
				openLivewireModals(node);
				initGlobalFilePondTree(node);
			}
		});
	});
});

const observeAppDom = () => {
	appDomObserver.disconnect();
	appDomObserver.observe(document.body, {
		attributes: true,
		attributeFilter: ['data-blobatar-seed', 'data-blobatar-size', 'data-blobatar-animate', 'data-blobatar-background', 'data-blobatar-background-color', 'data-blobatar-hue', 'data-blobatar-tone', 'data-blobatar-expression', 'src'],
		childList: true,
		subtree: true,
	});
};

observeAppDom();
initPage();
