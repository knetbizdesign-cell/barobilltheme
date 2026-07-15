(function () {
    'use strict';

    function buildTableHtml(rows, cols) {
        var html = '<table><tbody>';
        var r;
        var c;
        for (r = 0; r < rows; r++) {
            html += '<tr>';
            for (c = 0; c < cols; c++) {
                html += '<td></td>';
            }
            html += '</tr>';
        }
        html += '</tbody></table><p></p>';
        return html;
    }

    function closeTablePicker() {
        var picker = document.getElementById('borobill-table-picker');
        if (picker) {
            picker.remove();
        }
        document.removeEventListener('mousedown', onTablePickerOutside, true);
    }

    function onTablePickerOutside(event) {
        var picker = document.getElementById('borobill-table-picker');
        if (!picker || picker.contains(event.target)) {
            return;
        }
        var btn = document.querySelector('.mce-borobill_table');
        if (btn && btn.contains(event.target)) {
            return;
        }
        closeTablePicker();
    }

    function openTablePicker(editor, anchorEl) {
        closeTablePicker();

        var maxRows = 8;
        var maxCols = 8;

        var picker = document.createElement('div');
        picker.id = 'borobill-table-picker';
        picker.className = 'borobill-table-picker';
        picker.innerHTML =
            '<div class="borobill-table-picker__label">0 x 0</div>' +
            '<div class="borobill-table-picker__grid" role="grid" aria-label="표 크기 선택"></div>' +
            '<div class="borobill-table-picker__custom">' +
                '<label>행 <input type="number" class="borobill-table-picker__rows" min="1" max="20" value="3"></label>' +
                '<label>열 <input type="number" class="borobill-table-picker__cols" min="1" max="12" value="3"></label>' +
                '<button type="button" class="button button-small borobill-table-picker__apply">삽입</button>' +
            '</div>';

        document.body.appendChild(picker);

        var label = picker.querySelector('.borobill-table-picker__label');
        var grid = picker.querySelector('.borobill-table-picker__grid');
        var rowsInput = picker.querySelector('.borobill-table-picker__rows');
        var colsInput = picker.querySelector('.borobill-table-picker__cols');
        var applyBtn = picker.querySelector('.borobill-table-picker__apply');
        var r;
        var c;
        var cell;

        for (r = 1; r <= maxRows; r++) {
            for (c = 1; c <= maxCols; c++) {
                cell = document.createElement('button');
                cell.type = 'button';
                cell.className = 'borobill-table-picker__cell';
                cell.setAttribute('data-row', String(r));
                cell.setAttribute('data-col', String(c));
                cell.setAttribute('aria-label', r + '행 ' + c + '열');
                grid.appendChild(cell);
            }
        }

        function setSelection(rows, cols, fromInput) {
            label.textContent = rows + ' x ' + cols;
            grid.querySelectorAll('.borobill-table-picker__cell').forEach(function (node) {
                var row = parseInt(node.getAttribute('data-row'), 10);
                var col = parseInt(node.getAttribute('data-col'), 10);
                node.classList.toggle('is-selected', row <= rows && col <= cols);
            });
            if (!fromInput) {
                rowsInput.value = String(rows);
                colsInput.value = String(cols);
            }
        }

        function insertTable(rows, cols) {
            rows = Math.max(1, Math.min(20, parseInt(rows, 10) || 1));
            cols = Math.max(1, Math.min(12, parseInt(cols, 10) || 1));
            editor.insertContent(buildTableHtml(rows, cols));
            closeTablePicker();
            editor.focus();
        }

        grid.addEventListener('mouseover', function (event) {
            var target = event.target.closest('.borobill-table-picker__cell');
            if (!target) {
                return;
            }
            setSelection(
                parseInt(target.getAttribute('data-row'), 10),
                parseInt(target.getAttribute('data-col'), 10)
            );
        });

        grid.addEventListener('click', function (event) {
            var target = event.target.closest('.borobill-table-picker__cell');
            if (!target) {
                return;
            }
            event.preventDefault();
            insertTable(
                parseInt(target.getAttribute('data-row'), 10),
                parseInt(target.getAttribute('data-col'), 10)
            );
        });

        rowsInput.addEventListener('input', function () {
            setSelection(parseInt(rowsInput.value, 10) || 1, parseInt(colsInput.value, 10) || 1, true);
        });
        colsInput.addEventListener('input', function () {
            setSelection(parseInt(rowsInput.value, 10) || 1, parseInt(colsInput.value, 10) || 1, true);
        });

        applyBtn.addEventListener('click', function (event) {
            event.preventDefault();
            insertTable(rowsInput.value, colsInput.value);
        });

        setSelection(3, 3);

        if (anchorEl && anchorEl.getBoundingClientRect) {
            var rect = anchorEl.getBoundingClientRect();
            picker.style.position = 'fixed';
            picker.style.top = rect.bottom + 6 + 'px';
            picker.style.left = Math.max(8, rect.left) + 'px';
        }

        setTimeout(function () {
            document.addEventListener('mousedown', onTablePickerOutside, true);
        }, 0);
    }

    tinymce.PluginManager.add('borobill_table', function (editor) {
        editor.addButton('borobill_table', {
            text: '표',
            tooltip: '표 삽입',
            icon: false,
            onclick: function () {
                var anchorEl = this.getEl ? this.getEl() : null;
                openTablePicker(editor, anchorEl);
            },
        });
    });
})();
