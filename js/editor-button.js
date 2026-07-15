(function () {
    'use strict';

    function toggleSummaryBlock(editor) {
        var blocks = editor.dom.select('div.post-summary');
        if (blocks && blocks.length) {
            blocks.forEach(function (node) {
                editor.dom.remove(node, true);
            });
            return;
        }
        editor.insertContent('<div class="post-summary"><p></p></div><p></p>');
    }

    tinymce.PluginManager.add('borobill_editor', function (editor) {
        editor.addCommand('borobill_toggle_summary', function () {
            toggleSummaryBlock(editor);
        });

        editor.addButton('borobill_lineheight', {
            text: '줄간격',
            tooltip: '줄간격',
            type: 'listbox',
            values: [
                { text: '1.4', value: '1.4' },
                { text: '1.6', value: '1.6' },
                { text: '1.8', value: '1.8' },
                { text: '2.0', value: '2.0' },
            ],
            onselect: function (e) {
                editor.formatter.apply('borobill_lineheight', { value: e.control.settings.value });
            },
        });

        editor.formatter.register('borobill_lineheight', {
            inline: 'span',
            styles: { lineHeight: '%value' },
            remove_similar: true,
        });
    });
})();
