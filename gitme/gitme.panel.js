/**
 * GitMe Control Panel - Overview Dashboard Interactivity
 * Handles toast notifications, clipboard copying, live site preview action,
 * and mobile navigation toggles.
 *
 * Conforms to TPX standards and pure vanilla ECMAScript guidelines.
 */

(function () {
  'use strict';

  document.addEventListener('DOMContentLoaded', function () {
    const toastEl = document.getElementById('gbToast');
    let toastTimeout = null;

    /**
     * Display a floating toast notification with automatic dismissal.
     * Debounces consecutive calls by clearing previous timeouts.
     *
     * @param {string} message - Text to display inside the toast container.
     */
    function showToast(message) {
      if (!toastEl) return;

      if (toastTimeout) {
        clearTimeout(toastTimeout);
      }

      toastEl.textContent = message;
      toastEl.classList.add('show');

      toastTimeout = setTimeout(function () {
        toastEl.classList.remove('show');
        toastTimeout = null;
      }, 2500);
    }

    // Expose showToast globally if needed by other components
    window.showToast = showToast;

    /**
     * 1. Generic [data-toast] Event Handling
     * Intercepts clicks on elements matching [data-toast].
     * Prevents default action if the element is an anchor with a hash target.
     */
    document.addEventListener('click', function (event) {
      const target = event.target.closest('[data-toast]');
      if (!target) return;

      const toastMessage = target.getAttribute('data-toast');
      if (!toastMessage) return;

      if (target.tagName.toLowerCase() === 'a') {
        const href = target.getAttribute('href');
        if (!href || href === '#' || href.startsWith('#')) {
          event.preventDefault();
        }
      }

      showToast(toastMessage);
    });

    /**
     * 2. Site URL Copy to Clipboard
     * Reads the configured URL and writes to navigator clipboard.
     * Displays a confirmation toast on success or error.
     */
    const copyUrlLink = document.getElementById('linkSiteUrl');
    if (copyUrlLink) {
      copyUrlLink.addEventListener('click', function (event) {
        event.preventDefault();

        let urlToCopy = copyUrlLink.getAttribute('data-copy-url') || copyUrlLink.getAttribute('href') || copyUrlLink.textContent.trim();
        try {
          urlToCopy = new URL(urlToCopy, window.location.href).href;
        } catch (e) {
          // Keep raw string if URL parsing fails
        }
        if (!urlToCopy) return;

        if (navigator.clipboard && window.isSecureContext) {
          navigator.clipboard.writeText(urlToCopy).then(
            function () {
              showToast('URL copied to clipboard!');
            },
            function () {
              fallbackCopy(urlToCopy);
            }
          );
        } else {
          fallbackCopy(urlToCopy);
        }
      });
    }

    /**
     * Fallback copy mechanism using a temporary textarea for non-secure
     * contexts or older browser environments.
     *
     * @param {string} text - Text string to copy into clipboard.
     */
    function fallbackCopy(text) {
      try {
        const tempInput = document.createElement('textarea');
        tempInput.value = text;
        tempInput.style.position = 'fixed';
        tempInput.style.top = '-9999px';
        tempInput.style.left = '-9999px';
        tempInput.style.opacity = '0';
        document.body.appendChild(tempInput);
        tempInput.focus();
        tempInput.select();
        const successful = document.execCommand('copy');
        document.body.removeChild(tempInput);
        if (successful) {
          showToast('URL copied to clipboard!');
        } else {
          showToast('Failed to copy URL');
        }
      } catch (err) {
        showToast('Failed to copy URL');
      }
    }

    /**
     * 3. Site Selector [Preview] Button Action
     * Opens the live documentation site in a new tab with user feedback.
     */
    const btnPreview = document.getElementById('btnPreviewReload');
    if (btnPreview) {
      btnPreview.addEventListener('click', function (event) {
        event.preventDefault();
        window.open('./gitme.litepaper.php', '_blank');
        showToast('Opening live documentation site...');
      });
    }

    /**
     * 4. Mobile & Desktop Sidebar Toggle
     * Toggles the .open class on the sidebar navigation for responsive layouts.
     */
    const toggleButtons = [
      document.getElementById('btnToggleSidebar'),
      document.getElementById('btnMobileMenu')
    ].filter(Boolean);
    const sidebarEl = document.getElementById('gbSidebar');

    if (sidebarEl && toggleButtons.length > 0) {
      toggleButtons.forEach(function (btn) {
        btn.addEventListener('click', function (event) {
          event.preventDefault();
          sidebarEl.classList.toggle('open');
        });
      });
    }
  });
})();
