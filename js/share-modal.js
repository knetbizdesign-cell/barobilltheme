(function () {
    'use strict';

    var modal = null;
    var panel = null;
    var urlEl = null;
    var copyBtn = null;
    var triggerEl = null;
    var modalHost = null;
    var modalPlaceholder = null;
    var currentUrl = '';
    var isOpen = false;
    var copyResetTimer = null;
    var mobileOverlayMq = null;

    document.addEventListener('DOMContentLoaded', init);

    function init() {
        modal = document.getElementById('share-modal');
        if (!modal) {
            return;
        }

        panel = modal.querySelector('.share-modal__panel');
        urlEl = document.getElementById('share-modal-url');
        copyBtn = document.getElementById('share-modal-copy');
        modalHost = modal.parentNode;
        mobileOverlayMq = window.matchMedia ? window.matchMedia('(max-width: 768px)') : null;

        document.addEventListener('click', onDocumentClick);
        document.addEventListener('keydown', onDocumentKeydown);

        if (copyBtn) {
            copyBtn.addEventListener('click', onCopyClick);
        }

        modal.querySelectorAll('[data-share-close]').forEach(function (el) {
            el.addEventListener('click', function (e) {
                e.preventDefault();
                e.stopPropagation();
                close();
            });
        });
    }

    function onDocumentClick(e) {
        var trigger = e.target.closest('[data-open-share]');

        if (trigger) {
            e.preventDefault();
            e.stopPropagation();
            if (isOpen && triggerEl === trigger) {
                close();
            } else {
                open(trigger);
            }
            return;
        }

        if (!isOpen) {
            return;
        }

        if (modal.contains(e.target)) {
            return;
        }

        close();
    }

    function onDocumentKeydown(e) {
        if (!isOpen || e.key !== 'Escape') {
            return;
        }
        e.preventDefault();
        close();
    }

    function isMobileOverlay() {
        return mobileOverlayMq ? mobileOverlayMq.matches : window.innerWidth <= 768;
    }

    function mountMobileOverlay() {
        if (!isMobileOverlay() || !modal || !modalHost) {
            return;
        }

        if (!modalPlaceholder) {
            modalPlaceholder = document.createComment('share-modal-anchor');
            modalHost.insertBefore(modalPlaceholder, modal);
        }

        document.body.appendChild(modal);
        modal.classList.add('is-mobile-overlay');
    }

    function restoreMobileOverlay() {
        if (!modal || !modal.classList.contains('is-mobile-overlay')) {
            return;
        }

        modal.classList.remove('is-mobile-overlay');

        if (modalPlaceholder && modalPlaceholder.parentNode) {
            modalPlaceholder.parentNode.insertBefore(modal, modalPlaceholder.nextSibling);
            return;
        }

        if (modalHost) {
            modalHost.appendChild(modal);
        }
    }

    function open(trigger) {
        if (!modal) {
            return;
        }

        triggerEl = trigger;
        currentUrl = trigger.getAttribute('data-post-url') || window.location.href;

        if (urlEl) {
            urlEl.textContent = currentUrl;
        }

        resetCopyButton();
        mountMobileOverlay();
        modal.hidden = false;
        modal.classList.add('is-open');
        trigger.setAttribute('aria-expanded', 'true');
        isOpen = true;
    }

    function close() {
        if (!modal || !isOpen) {
            return;
        }

        modal.classList.remove('is-open');
        modal.hidden = true;
        restoreMobileOverlay();
        isOpen = false;
        resetCopyButton();

        if (triggerEl) {
            triggerEl.setAttribute('aria-expanded', 'false');
            if (typeof triggerEl.focus === 'function') {
                triggerEl.focus();
            }
        }
        triggerEl = null;
    }

    function resetCopyButton() {
        if (!copyBtn) {
            return;
        }
        copyBtn.textContent = '복사';
        copyBtn.classList.remove('is-copied');
    }

    function onCopyClick(e) {
        e.stopPropagation();
        if (!currentUrl) {
            return;
        }

        copyText(currentUrl).then(function () {
            if (!copyBtn) {
                return;
            }
            copyBtn.textContent = '복사됨';
            copyBtn.classList.add('is-copied');
            if (copyResetTimer) {
                window.clearTimeout(copyResetTimer);
            }
            copyResetTimer = window.setTimeout(resetCopyButton, 1500);
        });
    }

    function copyText(text) {
        if (navigator.clipboard && navigator.clipboard.writeText) {
            return navigator.clipboard.writeText(text);
        }

        return new Promise(function (resolve, reject) {
            var ta = document.createElement('textarea');
            ta.value = text;
            ta.setAttribute('readonly', '');
            ta.style.position = 'fixed';
            ta.style.left = '-9999px';
            document.body.appendChild(ta);
            ta.select();
            try {
                document.execCommand('copy');
                document.body.removeChild(ta);
                resolve();
            } catch (err) {
                document.body.removeChild(ta);
                reject(err);
            }
        });
    }
})();
