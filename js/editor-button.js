(function () {
    tinymce.PluginManager.add('borobill_summary', function (editor) {
        editor.addButton('borobill_summary', {
            text: '요약 블록',
            icon: false,
            onclick: function () {
                var blocks = editor.dom.select('div.post-summary');

                // 이미 있으면 추가 생성하지 않고 제거(토글 동작)
                if (blocks && blocks.length) {
                    blocks.forEach(function (node) {
                        editor.dom.remove(node, true);
                    });
                    return;
                }

                // 없을 때만 빈 요약 블록 생성 (안내 문구 없음)
                var html = '<div class="post-summary"><p></p></div><p></p>';
                editor.insertContent(html);
            }
        });
    });
})();
