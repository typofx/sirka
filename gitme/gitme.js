/**
 * Module Client Controller for gitme
 * Conforms to gitme client controller conventions.
 * Handles search modal, clipboard copying, and responsive mobile sidebar.
 */

document.addEventListener('DOMContentLoaded', () => {
    // Introspect current module prefix from body dataset
    const currentFolder = document.body.dataset.folder || 'gitme';

    // -------------------------------------------------------------------------
    // 1. Copy Link Button Handler
    // -------------------------------------------------------------------------
    const copyBtn = document.querySelector('.gb-copy-btn');
    if (copyBtn) {
        let copyResetTimeout = null;
        copyBtn.addEventListener('click', async () => {
            if (copyBtn.classList.contains('copied')) {
                return;
            }

            const currentUrl = window.location.href;
            try {
                if (navigator.clipboard && window.isSecureContext) {
                    await navigator.clipboard.writeText(currentUrl);
                } else {
                    const tempInput = document.createElement('input');
                    tempInput.value = currentUrl;
                    document.body.appendChild(tempInput);
                    tempInput.select();
                    document.execCommand('copy');
                    document.body.removeChild(tempInput);
                }

                const labelSpan = copyBtn.querySelector('span');
                const originalText = 'Copy';
                if (labelSpan) {
                    labelSpan.textContent = 'Copied!';
                }
                copyBtn.classList.add('copied');

                if (copyResetTimeout) {
                    clearTimeout(copyResetTimeout);
                }

                copyResetTimeout = setTimeout(() => {
                    if (labelSpan) {
                        labelSpan.textContent = originalText;
                    }
                    copyBtn.classList.remove('copied');
                    copyResetTimeout = null;
                }, 2000);
            } catch (err) {
                console.error(`[${currentFolder}] Failed to copy page link:`, err);
            }
        });
    }

    // -------------------------------------------------------------------------
    // 2. Search Modal Overlay Handler (Ctrl+K / Cmd+K / Click / ESC)
    // -------------------------------------------------------------------------
    const searchTriggers = document.querySelectorAll('.gb-search-box, .gb-search-btn');
    const modalOverlay = document.querySelector('.gb-modal-overlay');
    const modalInput = document.querySelector('.gb-modal-input');

    const openSearchModal = () => {
        if (!modalOverlay) return;
        modalOverlay.classList.add('open', 'active');
        if (modalInput) {
            modalInput.value = '';
            setTimeout(() => modalInput.focus(), 50);
        }
    };

    const closeSearchModal = () => {
        if (!modalOverlay) return;
        modalOverlay.classList.remove('open', 'active');
        if (modalInput) {
            modalInput.value = '';
        }
    };

    searchTriggers.forEach((trigger) => {
        trigger.addEventListener('click', (e) => {
            e.preventDefault();
            openSearchModal();
        });
    });

    if (modalOverlay) {
        modalOverlay.addEventListener('click', (e) => {
            if (e.target === modalOverlay) {
                closeSearchModal();
            }
        });

        // Allow clicking the ESC keycap badge to close modal on touch/mouse
        const escKeycap = modalOverlay.querySelector('.gb-modal-header .gb-kbd');
        if (escKeycap) {
            escKeycap.style.cursor = 'pointer';
            escKeycap.setAttribute('title', 'Close (ESC)');
            escKeycap.addEventListener('click', closeSearchModal);
        }
    }

    window.addEventListener('keydown', (e) => {
        if ((e.ctrlKey || e.metaKey) && e.key.toLowerCase() === 'k') {
            e.preventDefault();
            openSearchModal();
        } else if (e.key === 'Escape' && modalOverlay && (modalOverlay.classList.contains('open') || modalOverlay.classList.contains('active'))) {
            e.preventDefault();
            closeSearchModal();
        }
    });

    // -------------------------------------------------------------------------
    // 3. Responsive Mobile Sidebar Toggle
    // -------------------------------------------------------------------------
    const menuToggle = document.querySelector('.gb-menu-toggle');
    const sidebar = document.querySelector('.gb-sidebar');

    if (menuToggle && sidebar) {
        menuToggle.addEventListener('click', () => {
            sidebar.classList.toggle('open');
        });

        // Handle active navigation state and close on mobile
        sidebar.querySelectorAll('.gb-nav-item a').forEach((navLink) => {
            navLink.addEventListener('click', () => {
                sidebar.querySelectorAll('.gb-nav-item').forEach((item) => item.classList.remove('active'));
                const parentLi = navLink.closest('.gb-nav-item');
                if (parentLi) {
                    parentLi.classList.add('active');
                }
                if (window.innerWidth <= 1024 && sidebar.classList.contains('open')) {
                    sidebar.classList.remove('open');
                }
            });
        });

        // Close sidebar on body click outside sidebar when open
        document.addEventListener('click', (e) => {
            if (sidebar.classList.contains('open') &&
                !sidebar.contains(e.target) &&
                !menuToggle.contains(e.target)) {
                sidebar.classList.remove('open');
            }
        });
    }
});
