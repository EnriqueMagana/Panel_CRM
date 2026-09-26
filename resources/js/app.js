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

const blobatarSelector = '[data-blobatar-seed]';
const blobatarSvgNamespace = 'http://www.w3.org/2000/svg';

const renderBlobatar = (image) => {
	const seed = image.dataset.blobatarSeed;
	const size = Number(image.dataset.blobatarSize) || 48;
	const animation = image.dataset.blobatarAnimate;

	if (!seed) return;
	if (!animation) {
		image.src = blobatarUri(seed, { size, background: 'circle' });
		return;
	}

	const parts = blobatarParts(seed, { size, background: 'circle', animate: animation });
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
	svg.setAttribute('class', `${image.className} ${parts.cls || ''}`.trim());
	svg.dataset.blobatarMotion = animation;

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

const initGlobalFilePond = (node) => {
	if (!(node instanceof HTMLInputElement) || !node.matches('[data-filepond]')) return;
	if (node.dataset.filepondInitialized === 'true') return;

	const ratio = node.dataset.cropRatio || '1:1';
	const acceptedTypes = ['image/png', 'image/jpeg', 'image/webp'];

	FilePond.create(node, {
		allowMultiple: false,
		allowRevert: false,
		allowImagePreview: true,
		allowImageCrop: node.dataset.cropper === 'true',
		allowPaste: false,
		credits: false,
		imagePreviewHeight: 180,
		imageCropAspectRatio: ratio,
		imageCropShape: 'rect',
		imageCropPermanent: true,
		acceptedFileTypes: acceptedTypes,
		labelIdle: 'Arrastra y suelta una imagen o <span class="filepond--label-action">navega</span>',
		stylePanelLayout: 'compact',
		styleLoadIndicatorPosition: 'center bottom',
		styleProgressIndicatorPosition: 'center bottom',
		styleButtonRemoveItemPosition: 'left bottom',
		styleButtonProcessItemPosition: 'right bottom',
		maxFileSize: '2MB',
	});

	node.dataset.filepondInitialized = 'true';
};

const initGlobalFilePondTree = (node) => {
	if (node instanceof HTMLInputElement) {
		initGlobalFilePond(node);
		return;
	}
	if (node instanceof Element) {
		node.querySelectorAll?.('[data-filepond]').forEach(initGlobalFilePond);
	}
};

const openLivewireModal = (dialog) => {
	if (!(dialog instanceof HTMLDialogElement) || dialog.open) return;

	const closeButton = dialog.querySelector('[data-modal-close-action]');
	dialog.addEventListener('cancel', (event) => {
		event.preventDefault();
		closeButton?.click();
	});
	dialog.addEventListener('click', (event) => {
		if (event.target === dialog) closeButton?.click();
	});
	dialog.showModal();
};

const openLivewireModals = (node) => {
	if (node instanceof HTMLDialogElement && node.matches('[data-livewire-modal]')) openLivewireModal(node);
	node.querySelectorAll?.('dialog[data-livewire-modal]').forEach(openLivewireModal);
};

renderBlobatars(document);
openLivewireModals(document);
initGlobalFilePondTree(document);

new MutationObserver((mutations) => {
	mutations.forEach((mutation) => {
		if (mutation.type === 'attributes') {
			renderBlobatar(mutation.target);
			return;
		}

		mutation.addedNodes.forEach((node) => {
			if (node instanceof Element) {
				renderBlobatars(node);
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

document.querySelectorAll('[data-password-toggle]').forEach((toggle) => {
	const password = document.getElementById(toggle.getAttribute('aria-controls'));
	const showIcon = toggle.querySelector('[data-password-icon="show"]');
	const hideIcon = toggle.querySelector('[data-password-icon="hide"]');

	toggle.addEventListener('click', () => {
		const shouldShow = password.type === 'password';

		password.type = shouldShow ? 'text' : 'password';
		toggle.setAttribute('aria-label', shouldShow ? 'Ocultar contraseña' : 'Mostrar contraseña');
		toggle.setAttribute('aria-pressed', String(shouldShow));
		showIcon.classList.toggle('hidden', shouldShow);
		hideIcon.classList.toggle('hidden', !shouldShow);
	});
});

const root = document.documentElement;
const sidebarToggle = document.querySelector('[data-sidebar-toggle]');
const csrfToken = document.querySelector('meta[name="csrf-token"]')?.content || '';

const initChatControls = () => {
	const shell = document.querySelector('[data-chat-shell]');
	const toggle = document.getElementById('chat-create-group-toggle');
	const form = document.getElementById('chat-group-form-wrapper');
	const searchInput = document.getElementById('chat-search-input');
	const backButton = document.querySelector('[data-chat-back]');
	const listItems = Array.from(document.querySelectorAll('[data-chat-item]'));

	if (toggle && form && !toggle.dataset.chatBound) {
		toggle.dataset.chatBound = 'true';
		toggle.addEventListener('click', () => {
			form.classList.toggle('hidden');
		});
	}

	if (backButton && shell && !backButton.dataset.chatBound) {
		backButton.dataset.chatBound = 'true';
		backButton.addEventListener('click', () => {
			shell.dataset.mobileView = 'list';
		});
	}

	if (searchInput && !searchInput.dataset.chatBound) {
		searchInput.dataset.chatBound = 'true';
		searchInput.addEventListener('input', (event) => {
			const term = (event.target.value || '').toLowerCase().trim();
			listItems.forEach((item) => {
				const name = (item.dataset.chatName || '').toLowerCase();
				const text = (item.dataset.chatText || '').toLowerCase();
				const visible = !term || name.includes(term) || text.includes(term);
				item.classList.toggle('hidden', !visible);
			});
		});
	}

	if (shell && !shell.dataset.chatBound) {
		shell.dataset.chatBound = 'true';
		listItems.forEach((item) => {
			item.addEventListener('click', () => {
				if (window.innerWidth < 1024) {
					shell.dataset.mobileView = 'chat';
				}
			});
		});
	}
};

initChatControls();

const persistUserPreferences = async (preferences) => {
	if (!csrfToken) return;

	try {
		await fetch('/settings/preferences', {
			method: 'POST',
			headers: {
				'Content-Type': 'application/json',
				'X-CSRF-TOKEN': csrfToken,
				'Accept': 'application/json',
			},
			body: JSON.stringify(preferences),
		});
	} catch (error) {
		console.warn('No se pudieron guardar las preferencias del usuario.', error);
	}
};

const confirmModal = document.getElementById('global-confirm-modal');
const confirmMessage = document.querySelector('[data-confirm-message]');
const confirmCancel = document.querySelector('[data-confirm-cancel]');
const confirmAccept = document.querySelector('[data-confirm-accept]');
let confirmAction = null;

const showConfirmModal = (message, callback) => {
	if (!confirmModal || !confirmMessage || !confirmAccept) return;
	confirmMessage.textContent = message;
	confirmModal.classList.remove('hidden');
	confirmModal.classList.add('flex');
	confirmModal.setAttribute('aria-hidden', 'false');
	confirmAction = callback;
};

const hideConfirmModal = () => {
	if (!confirmModal) return;
	confirmModal.classList.add('hidden');
	confirmModal.classList.remove('flex');
	confirmModal.setAttribute('aria-hidden', 'true');
	confirmAction = null;
};

document.addEventListener('click', (event) => {
	const trigger = event.target.closest('[data-confirm-action]');
	if (!trigger) return;

	if (trigger.dataset.confirmPending === 'true') {
		trigger.dataset.confirmPending = 'false';
		return;
	}

	event.preventDefault();
	const message = trigger.dataset.confirmMessage || '¿Estás seguro de continuar?';
	showConfirmModal(message, () => {
		trigger.dataset.confirmPending = 'true';
		trigger.click();
		trigger.dataset.confirmPending = 'false';
		hideConfirmModal();
	});
});

confirmAccept?.addEventListener('click', () => {
	if (typeof confirmAction === 'function') {
		confirmAction();
	}
});

confirmCancel?.addEventListener('click', hideConfirmModal);
confirmModal?.addEventListener('click', (event) => {
	if (event.target === confirmModal) hideConfirmModal();
});

document.addEventListener('keydown', (event) => {
	if (event.key === 'Escape' && confirmModal && !confirmModal.classList.contains('hidden')) {
		hideConfirmModal();
	}
});

const positionFloatingMenu = (details, panel) => {
	if (!details || !panel || !details.open) return;

	const trigger = details.querySelector('summary');
	if (!trigger) return;

	const triggerRect = trigger.getBoundingClientRect();
	const panelRect = panel.getBoundingClientRect();
	const left = Math.min(window.innerWidth - panelRect.width - 12, triggerRect.right - panelRect.width);
	const top = Math.min(window.innerHeight - panelRect.height - 12, triggerRect.bottom + 8);

	panel.style.position = 'fixed';
	panel.style.left = `${Math.max(12, left)}px`;
	panel.style.top = `${Math.max(12, top)}px`;
	panel.style.zIndex = '60';
};

document.querySelectorAll('[data-floating-menu]').forEach((details) => {
	const panel = details.querySelector('[data-floating-panel]');
	if (!panel) return;

	const syncPosition = () => positionFloatingMenu(details, panel);
	details.addEventListener('toggle', syncPosition);
	details.querySelector('summary')?.addEventListener('click', () => requestAnimationFrame(syncPosition));
	window.addEventListener('resize', syncPosition);
	window.addEventListener('scroll', syncPosition, true);
});

const updateSidebarButton = () => {
	if (!sidebarToggle) return;
	const expanded = root.dataset.layout === 'default';
	const label = expanded ? 'Contraer barra lateral' : 'Mostrar barra lateral';
	sidebarToggle.setAttribute('aria-expanded', String(expanded));
	sidebarToggle.setAttribute('aria-label', label);
	sidebarToggle.setAttribute('title', label);
};

const applyLayout = (layout) => {
	root.dataset.layout = layout;
	root.classList.toggle('sidebar-collapsed', layout === 'compact');
	localStorage.setItem('snippetdesk-layout', layout);
	localStorage.setItem('snippetdesk-sidebar', layout === 'compact' ? 'collapsed' : 'expanded');
	updateSidebarButton();
	persistUserPreferences({ layout, sidebar_variant: root.dataset.sidebarVariant || 'sidebar', direction: root.getAttribute('dir') || 'ltr', theme: localStorage.getItem('snippetdesk-theme') || 'system' });
};

const applySidebarVariant = (variant) => {
	root.dataset.sidebarVariant = variant;
	localStorage.setItem('snippetdesk-sidebar-variant', variant);
	persistUserPreferences({ layout: root.dataset.layout || 'default', sidebar_variant: variant, direction: root.getAttribute('dir') || 'ltr', theme: localStorage.getItem('snippetdesk-theme') || 'system' });
};

const applyDirection = (direction) => {
	root.setAttribute('dir', direction);
	localStorage.setItem('snippetdesk-direction', direction);
	persistUserPreferences({ layout: root.dataset.layout || 'default', sidebar_variant: root.dataset.sidebarVariant || 'sidebar', direction, theme: localStorage.getItem('snippetdesk-theme') || 'system' });
};

updateSidebarButton();
sidebarToggle?.addEventListener('click', () => {
	applyLayout(root.dataset.layout === 'default' ? 'compact' : 'default');
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
			return;
		}

		if (avatarSeed) {
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

document.querySelectorAll('[data-close-mobile-sidebar]').forEach((button) => {
	button.addEventListener('click', () => {
		button.closest('.mobile-sidebar-root')?.removeAttribute('open');
	});
});

const themeOptions = document.querySelectorAll('[data-theme-value]');
const themeColor = document.querySelector('meta[name="theme-color"]');
const systemTheme = window.matchMedia('(prefers-color-scheme: dark)');

const applyTheme = (theme) => {
	const dark = theme === 'dark' || (theme === 'system' && systemTheme.matches);
	root.classList.toggle('dark', dark);
	if (themeColor) themeColor.setAttribute('content', dark ? '#020817' : '#ffffff');

	themeOptions.forEach((option) => {
		const selected = option.dataset.themeValue === theme;
		option.setAttribute('aria-pressed', String(selected));
		if (option instanceof HTMLInputElement) option.checked = selected;
		option.querySelectorAll('[data-theme-check]').forEach((check) => {
			check.classList.toggle('hidden', check.dataset.themeCheck !== theme);
		});
	});
};

applyTheme(localStorage.getItem('snippetdesk-theme') || 'system');

themeOptions.forEach((option) => {
	option.addEventListener('click', () => {
		const theme = option.dataset.themeValue;
		localStorage.setItem('snippetdesk-theme', theme);
		applyTheme(theme);
		persistUserPreferences({
			theme,
			layout: root.dataset.layout || 'default',
			sidebar_variant: root.dataset.sidebarVariant || 'sidebar',
			direction: root.getAttribute('dir') || 'ltr',
		});
	});
});

const sidebarOptions = document.querySelectorAll('[data-sidebar-value]');
const layoutOptions = document.querySelectorAll('[data-layout-value]');
const directionOptions = document.querySelectorAll('[data-direction-value]');
const settingsDialog = document.querySelector('[data-theme-settings]');

const syncPreferenceRadios = () => {
	[
		[sidebarOptions, root.dataset.sidebarVariant || 'sidebar'],
		[layoutOptions, root.dataset.layout || 'default'],
		[directionOptions, root.getAttribute('dir') || 'ltr'],
	].forEach(([options, current]) => {
		options.forEach((option) => {
			option.checked = option.value === current;
		});
	});
};

syncPreferenceRadios();
sidebarOptions.forEach((option) => option.addEventListener('change', () => {
	if (option.checked) applySidebarVariant(option.value);
}));
layoutOptions.forEach((option) => option.addEventListener('change', () => {
	if (option.checked) applyLayout(option.value);
}));
directionOptions.forEach((option) => option.addEventListener('change', () => {
	if (option.checked) applyDirection(option.value);
}));

const resetTheme = () => {
	localStorage.removeItem('snippetdesk-theme');
	applyTheme('system');
	persistUserPreferences({
		theme: 'system',
		layout: root.dataset.layout || 'default',
		sidebar_variant: root.dataset.sidebarVariant || 'sidebar',
		direction: root.getAttribute('dir') || 'ltr',
	});
};

document.querySelector('[data-reset-theme]')?.addEventListener('click', resetTheme);
document.querySelector('[data-reset-sidebar]')?.addEventListener('click', () => applySidebarVariant('sidebar'));
document.querySelector('[data-reset-layout]')?.addEventListener('click', () => applyLayout('default'));
document.querySelector('[data-reset-direction]')?.addEventListener('click', () => applyDirection('ltr'));
document.querySelector('[data-reset-all-settings]')?.addEventListener('click', () => {
	resetTheme();
	applySidebarVariant('sidebar');
	applyLayout('default');
	applyDirection('ltr');
	syncPreferenceRadios();
});

document.querySelectorAll('[data-open-theme-settings]').forEach((button) => {
	button.addEventListener('click', () => {
		button.closest('details')?.removeAttribute('open');
		if (settingsDialog instanceof HTMLDialogElement && !settingsDialog.open) settingsDialog.showModal();
	});
});

if (settingsDialog instanceof HTMLDialogElement) {
	settingsDialog.querySelector('[data-close-theme-settings]')?.addEventListener('click', () => settingsDialog.close());
	settingsDialog.addEventListener('click', (event) => {
		if (event.target === settingsDialog) settingsDialog.close();
	});
}

document.addEventListener('keydown', (event) => {
	if (!(event.ctrlKey || event.metaKey) || !event.shiftKey) return;
	if (event.target instanceof HTMLElement && event.target.closest('input, textarea, select, [contenteditable="true"]')) return;

	const key = event.key.toLowerCase();
	if (key === 'p') {
		event.preventDefault();
		window.location.assign(document.querySelector('[data-profile-link]')?.href || '/profile');
	} else if (key === 's') {
		event.preventDefault();
		if (settingsDialog instanceof HTMLDialogElement && !settingsDialog.open) settingsDialog.showModal();
	} else if (key === 'q') {
		event.preventDefault();
		document.querySelector('[data-logout-form]')?.requestSubmit();
	}
});

let currentModuleContent = document.querySelector('[data-module-content]');
let currentModuleLoader = document.querySelector('[data-module-loader]');

const setModuleLoading = (loading) => {
	if (!currentModuleContent || !currentModuleLoader) return;
	currentModuleContent.setAttribute('aria-busy', loading ? 'true' : 'false');
	currentModuleContent.classList.toggle('opacity-50', loading);
	currentModuleContent.classList.toggle('pointer-events-none', loading);

	const moduleType = currentModuleContent?.dataset?.moduleType || 'dashboard';
	currentModuleLoader.dataset.moduleSkeleton = moduleType;
	currentModuleLoader.querySelectorAll('.module-skeleton-variant').forEach((variant) => {
		const visible = variant.classList.contains(`module-skeleton-${moduleType}`);
		variant.style.display = visible ? 'block' : 'none';
	});
	currentModuleLoader.classList.toggle('hidden', !loading);
	currentModuleLoader.hidden = !loading;
};

const setModuleActiveState = (path) => {
	const normalized = path.split('#')[0] || '/';
	document.querySelectorAll('[data-module-link], .sidebar-link, [data-profile-link], [data-search-item]').forEach((link) => {
		const href = link.getAttribute('href');
		if (!href) return;
		const linkPath = new URL(href, window.location.origin).pathname;
		const active = linkPath === normalized;
		link.classList.toggle('bg-sidebar-accent', active && link.classList.contains('sidebar-link'));
		link.classList.toggle('text-sidebar-accent-foreground', active && link.classList.contains('sidebar-link'));
		link.setAttribute('aria-current', active ? 'page' : 'false');
	});
};

const loadModuleContent = async (url, shouldPushState = true) => {
	const target = new URL(url, window.location.origin);
	if (target.origin !== window.location.origin) return false;
	if (target.pathname === window.location.pathname && target.search === window.location.search && !target.hash) {
		return false;
	}

	setModuleLoading(true);

	try {
		const response = await fetch(target.href, {
			headers: {
				'X-Requested-With': 'XMLHttpRequest',
			},
		});
		if (!response.ok) throw new Error(`HTTP ${response.status}`);

		const html = await response.text();
		const parser = new DOMParser();
		const doc = parser.parseFromString(html, 'text/html');
		const nextContent = doc.querySelector('[data-module-content]');
		if (!nextContent) throw new Error('The response does not include a module container.');

		const nextTitle = doc.querySelector('title')?.textContent || document.title;
		if (currentModuleContent && currentModuleContent.parentNode) {
			currentModuleContent.replaceWith(nextContent);
		}
		currentModuleContent = document.querySelector('[data-module-content]');
		currentModuleLoader = document.querySelector('[data-module-loader]');
		document.title = nextTitle;
		setModuleLoading(false);
		setModuleActiveState(target.pathname);
		if (shouldPushState) {
			history.pushState({ moduleUrl: target.href }, '', target.href);
		}
		if (target.hash) {
			requestAnimationFrame(() => {
				const targetElement = document.getElementById(target.hash.slice(1));
				targetElement?.scrollIntoView({ behavior: 'smooth', block: 'start' });
			});
		}
		return true;
	} catch (error) {
		console.warn('No se pudo cargar el módulo.', error);
		window.location.assign(target.href);
		return false;
	}
};

document.addEventListener('click', (event) => {
	const link = event.target.closest('a[href]');
	if (!link) return;

	const href = link.getAttribute('href');
	if (!href || href.startsWith('#') || href.startsWith('mailto:') || href.startsWith('tel:')) return;
	if (link.target === '_blank' || link.hasAttribute('download')) return;
	if (link.hasAttribute('data-no-module-load') || link.closest('[data-no-module-load]')) return;

	const url = new URL(href, window.location.origin);
	if (url.origin !== window.location.origin) return;
	if (url.pathname.startsWith('/chats')) return;
	if (link.closest('[data-search-dialog]') || link.closest('dialog')) {
		return;
	}

	event.preventDefault();
	loadModuleContent(url.href, true);
});

window.addEventListener('popstate', () => {
	loadModuleContent(window.location.href, false);
});

const searchDialog = document.querySelector('[data-search-dialog]');
const searchInput = document.querySelector('[data-search-input]');

if (searchDialog instanceof HTMLDialogElement) {
	const openSearch = () => {
		document.querySelector('.mobile-sidebar-root')?.removeAttribute('open');
		if (!searchDialog.open) searchDialog.showModal();
		searchInput?.focus();
	};

	document.querySelectorAll('[data-search-trigger]').forEach((button) => {
		button.addEventListener('click', openSearch);
	});

	searchDialog.querySelector('[data-close-search]')?.addEventListener('click', () => searchDialog.close());
	searchDialog.addEventListener('click', (event) => {
		if (event.target === searchDialog) searchDialog.close();
	});

	searchInput?.addEventListener('input', () => {
		const query = searchInput.value.trim().toLocaleLowerCase();
		let visibleResults = 0;

		searchDialog.querySelectorAll('[data-search-item]').forEach((item) => {
			const searchText = `${item.textContent} ${item.dataset.searchValue || ''}`.toLocaleLowerCase();
			const matches = !query || searchText.includes(query);
			item.classList.toggle('hidden', !matches);
			visibleResults += Number(matches);
		});

		searchDialog.querySelector('[data-search-empty]')?.classList.toggle('hidden', visibleResults > 0);
	});

	document.addEventListener('keydown', (event) => {
		if (event.key.toLowerCase() === 'k' && (event.ctrlKey || event.metaKey)) {
			event.preventDefault();
			openSearch();
		}
	});
}

document.addEventListener('keydown', (event) => {
	if (event.key === 'Escape') document.querySelector('.mobile-sidebar-root')?.removeAttribute('open');
});
