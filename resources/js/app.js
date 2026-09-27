import './bootstrap';
import { _parts as blobatarParts } from 'blobatar/internal';
import { blobatarUri } from 'blobatar/uri';
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
const systemTheme = window.matchMedia('(prefers-color-scheme: dark)');
let confirmAction = null;
let chatRefreshTimer = null;
let chatRefreshInFlight = false;

const renderBlobatar = (image) => {
	const seed = image.dataset.blobatarSeed;
	const size = Number(image.dataset.blobatarSize) || 48;
	const animation = image.dataset.blobatarAnimate;

	if (!seed) return;
	if (!animation) {
		const cacheKey = `${seed}:${size}:static`;
		if (!blobatarUriCache.has(cacheKey)) {
			blobatarUriCache.set(cacheKey, blobatarUri(seed, { size, background: 'circle' }));
		}
		image.src = blobatarUriCache.get(cacheKey);
		image.classList.add('is-loaded');
		return;
	}

	const cacheKey = `${seed}:${size}:${animation}`;
	if (!blobatarPartsCache.has(cacheKey)) {
		blobatarPartsCache.set(cacheKey, blobatarParts(seed, { size, background: 'circle', animate: animation }));
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
	['width', 'height', 'data-user-avatar-id', 'data-blobatar-mode'].forEach((name) => {
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
			dialog.querySelector('[data-modal-close-action]')?.click();
		});
	}
	if (!dialog.open) dialog.showModal();
};

const openLivewireModals = (node) => {
	if (node instanceof HTMLDialogElement && node.matches('[data-livewire-modal]')) openLivewireModal(node);
	node.querySelectorAll?.('dialog[data-livewire-modal]').forEach(openLivewireModal);
};

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

const scheduleChatRefresh = () => {
	if (chatRefreshTimer) window.clearTimeout(chatRefreshTimer);

	const workspace = document.querySelector('[data-chat-workspace][wire\\:id]');
	if (!workspace) {
		chatRefreshTimer = null;
		return;
	}

	chatRefreshTimer = window.setTimeout(async () => {
		if (document.hidden || chatRefreshInFlight) {
			scheduleChatRefresh();
			return;
		}

		const wire = window.Livewire?.find(workspace.getAttribute('wire:id'));
		if (typeof wire?.$call !== 'function') {
			scheduleChatRefresh();
			return;
		}

		chatRefreshInFlight = true;
		try {
			await wire.$call('refreshConversation');
		} finally {
			chatRefreshInFlight = false;
			scheduleChatRefresh();
		}
	}, 8_000);
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
	scheduleChatRefresh();
};

document.addEventListener('click', (event) => {
	const target = event.target instanceof Element ? event.target : null;
	if (!target) return;
	if (target.closest('[data-chat-workspace]')) scheduleChatRefresh();

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
		if (modal.matches('[data-theme-settings], [data-search-dialog]')) modal.close();
		else modal.querySelector('[data-modal-close-action]')?.click();
	}
});

document.addEventListener('input', (event) => {
	if (event.target instanceof Element && event.target.closest('[data-chat-workspace]')) {
		scheduleChatRefresh();
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
	const { userId, profilePhotoUrl, avatarSeed } = event.detail;

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
			if (image.dataset.blobatarMode) {
				image.dataset.blobatarAnimate = image.dataset.blobatarMode;
				renderBlobatar(image);
			} else {
				image.src = blobatarUri(avatarSeed, { size, background: 'circle' });
			}
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

systemTheme.addEventListener('change', () => {
	if ((localStorage.getItem('snippetdesk-theme') || 'system') === 'system') applyTheme('system', false);
});

document.addEventListener('livewire:navigate', (event) => {
	setNavigationSkeleton(true, event.detail.url);
});

document.addEventListener('livewire:navigated', () => {
	setNavigationSkeleton(false);
	initPage();
});

new MutationObserver((mutations) => {
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
}).observe(document.body, {
	attributes: true,
	attributeFilter: ['data-blobatar-seed', 'data-blobatar-size', 'data-blobatar-animate'],
	childList: true,
	subtree: true,
});

initPage();
