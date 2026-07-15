(function ($) {
    'use strict';

    function triggerFocusMode() {
        if (window.wp && window.wp.editor && window.wp.editor.dfw && typeof window.wp.editor.dfw.toggle === 'function') {
            window.wp.editor.dfw.toggle();
            return;
        }

        var editor = typeof tinymce !== 'undefined' ? tinymce.get('content') : null;
        if (editor && editor.commands.wpToggleDFW) {
            editor.execCommand('wpToggleDFW');
            return;
        }

        if (editor) {
            editor.execCommand('mceFullScreen');
        }
    }

    function initEditorToolsExtra() {
        var tools = document.getElementById('wp-content-editor-tools');
        if (!tools) {
            return;
        }

        var tabs = tools.querySelector('.wp-editor-tabs');
        if (!tabs) {
            return;
        }

        var extra = document.getElementById('borobill-editor-tools-extra');
        if (!extra) {
            var group = document.createElement('div');
            group.className = 'borobill-editor-tools-right';

            extra = document.createElement('div');
            extra.id = 'borobill-editor-tools-extra';
            extra.className = 'borobill-editor-tools-extra';
            extra.innerHTML =
                '<button type="button" class="button borobill-editor-tool-btn" data-borobill-action="summary">요약 블록</button>' +
                '<button type="button" class="button borobill-editor-tool-btn" data-borobill-action="focus">집중 모드</button>';

            tabs.parentNode.insertBefore(group, tabs);
            group.appendChild(tabs);
            group.appendChild(extra);

            extra.addEventListener('click', function (event) {
                var btn = event.target.closest('[data-borobill-action]');
                if (!btn) {
                    return;
                }
                event.preventDefault();

                var action = btn.getAttribute('data-borobill-action');
                if (action === 'summary') {
                    var editor = typeof tinymce !== 'undefined' ? tinymce.get('content') : null;
                    if (editor) {
                        editor.execCommand('borobill_toggle_summary');
                    }
                } else if (action === 'focus') {
                    triggerFocusMode();
                }
            });

            $(document)
                .on('dfw-on.borobillEditorTools', function () {
                    extra.querySelector('[data-borobill-action="focus"]').classList.add('is-active');
                })
                .on('dfw-off.borobillEditorTools', function () {
                    extra.querySelector('[data-borobill-action="focus"]').classList.remove('is-active');
                });
        }

        if (window.getUserSetting && window.getUserSetting('post_dfw') === 'on') {
            extra.querySelector('[data-borobill-action="focus"]').classList.add('is-active');
        }
    }

    $(function () {
        initEditorToolsExtra();
        $(document).on('tinymce-editor-init.borobillTools', initEditorToolsExtra);
    });
})(jQuery);
