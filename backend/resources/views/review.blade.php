<!DOCTYPE html>
<html lang="ja">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>レビュー機能</title>
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        body {
            font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, Oxygen, Ubuntu, Cantarell, sans-serif;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            min-height: 100vh;
            padding: 20px;
        }

        .container {
            max-width: 1200px;
            margin: 0 auto;
            background: white;
            border-radius: 20px;
            box-shadow: 0 20px 40px rgba(0, 0, 0, 0.1);
            overflow: hidden;
            animation: fadeInUp 0.8s ease-out;
        }

        @keyframes fadeInUp {
            from {
                opacity: 0;
                transform: translateY(30px);
            }
            to {
                opacity: 1;
                transform: translateY(0);
            }
        }

        .header {
            background: linear-gradient(90deg, #667eea, #764ba2);
            color: white;
            padding: 30px;
            text-align: center;
        }

        .header h1 {
            font-size: 2.5rem;
            font-weight: 300;
            margin-bottom: 10px;
            animation: slideInDown 0.6s ease-out;
        }

        .header p {
            opacity: 0.9;
            font-size: 1.1rem;
            animation: slideInDown 0.6s ease-out 0.2s both;
        }

        @keyframes slideInDown {
            from {
                opacity: 0;
                transform: translateY(-20px);
            }
            to {
                opacity: 1;
                transform: translateY(0);
            }
        }

        .content {
            padding: 40px;
        }

        .section {
            margin-bottom: 40px;
            animation: fadeIn 0.6s ease-out;
            animation-fill-mode: both;
        }

        .section:nth-child(1) { animation-delay: 0.3s; }
        .section:nth-child(2) { animation-delay: 0.4s; }
        .section:nth-child(3) { animation-delay: 0.5s; }

        @keyframes fadeIn {
            from {
                opacity: 0;
                transform: translateY(20px);
            }
            to {
                opacity: 1;
                transform: translateY(0);
            }
        }

        .section-title {
            font-size: 1.4rem;
            color: #333;
            margin-bottom: 20px;
            font-weight: 600;
            position: relative;
            padding-left: 15px;
        }

        .section-title::before {
            content: '';
            position: absolute;
            left: 0;
            top: 50%;
            transform: translateY(-50%);
            width: 4px;
            height: 20px;
            background: linear-gradient(135deg, #667eea, #764ba2);
            border-radius: 2px;
        }

        .markdown-editor {
            position: relative;
            border: 2px solid #e1e5e9;
            border-radius: 12px;
            overflow: hidden;
            transition: all 0.3s ease;
            background: #fafbfc;
        }

        .markdown-editor:focus-within {
            border-color: #667eea;
            box-shadow: 0 0 0 3px rgba(102, 126, 234, 0.1);
            transform: translateY(-2px);
        }

        .editor-toolbar {
            background: linear-gradient(90deg, #f8f9fa, #e9ecef);
            padding: 12px 16px;
            border-bottom: 1px solid #e1e5e9;
            display: flex;
            gap: 10px;
            align-items: center;
        }

        .toolbar-btn {
            padding: 6px 10px;
            border: none;
            background: transparent;
            border-radius: 6px;
            cursor: pointer;
            font-size: 0.9rem;
            color: #666;
            transition: all 0.2s ease;
        }

        .toolbar-btn:hover {
            background: rgba(102, 126, 234, 0.1);
            color: #667eea;
            transform: translateY(-1px);
        }

        .markdown-textarea {
            width: 100%;
            min-height: 200px;
            padding: 20px;
            border: none;
            outline: none;
            font-family: 'Menlo', 'Monaco', 'Courier New', monospace;
            font-size: 14px;
            line-height: 1.6;
            background: transparent;
            resize: vertical;
        }

        .markdown-textarea::placeholder {
            color: #999;
            font-style: italic;
        }

        .media-section {
            margin-top: 30px;
        }

        .media-grid {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(150px, 1fr));
            gap: 12px;
            margin-top: 20px;
        }

        .media-item {
            background: white;
            border: 1px solid #e1e5e9;
            border-radius: 24px;
            padding: 16px 12px;
            text-align: center;
            cursor: pointer;
            transition: all 0.2s ease;
            position: relative;
            overflow: hidden;
            min-height: 50px;
            display: flex;
            align-items: center;
            justify-content: center;
        }


        .media-item:hover {
            border-color: #667eea;
            transform: scale(1.02);
            box-shadow: 0 4px 12px rgba(102, 126, 234, 0.15);
        }

        .media-item.selected {
            border-color: #667eea;
            background: linear-gradient(135deg, #667eea, #764ba2);
            color: white;
            transform: scale(1.05);
            box-shadow: 0 6px 16px rgba(102, 126, 234, 0.3);
        }

        .media-name {
            font-size: 0.9rem;
            color: #333;
            font-weight: 600;
            letter-spacing: 0.3px;
        }

        .media-item.selected .media-name {
            color: white;
        }

        .action-buttons {
            display: flex;
            gap: 20px;
            justify-content: center;
            margin-top: 40px;
            padding-top: 30px;
            border-top: 1px solid #e1e5e9;
        }

        .btn {
            padding: 15px 30px;
            border: none;
            border-radius: 50px;
            font-size: 1.1rem;
            font-weight: 600;
            cursor: pointer;
            transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1);
            position: relative;
            overflow: hidden;
            min-width: 140px;
            text-transform: uppercase;
            letter-spacing: 0.5px;
        }

        .btn::before {
            content: '';
            position: absolute;
            top: 50%;
            left: 50%;
            width: 0;
            height: 0;
            background: rgba(255, 255, 255, 0.3);
            border-radius: 50%;
            transition: all 0.4s ease;
            transform: translate(-50%, -50%);
        }

        .btn:hover::before {
            width: 300px;
            height: 300px;
        }

        .btn-primary {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            box-shadow: 0 8px 20px rgba(102, 126, 234, 0.3);
        }

        .btn-primary:hover {
            transform: translateY(-3px);
            box-shadow: 0 12px 30px rgba(102, 126, 234, 0.4);
        }

        .btn-secondary {
            background: linear-gradient(135deg, #ff9a9e 0%, #fad0c4 100%);
            color: white;
            box-shadow: 0 8px 20px rgba(255, 154, 158, 0.3);
        }

        .btn-secondary:hover {
            transform: translateY(-3px);
            box-shadow: 0 12px 30px rgba(255, 154, 158, 0.4);
        }

        .btn:active {
            transform: translateY(-1px);
        }

        /* Diff styles (minimal) */
        .Diff {
            font-family: Menlo, Monaco, Consolas, "Courier New", monospace;
            font-size: 13px;
            line-height: 1.5;
        }
        .Diff .changeTypeInserted { background: #e6ffed; }
        .Diff .changeTypeDeleted { background: #ffeef0; }
        .Diff del { background: #ffeef0; text-decoration: none; }
        .Diff ins { background: #e6ffed; text-decoration: none; }

        @media (max-width: 768px) {
            .content {
                padding: 20px;
            }
            
            .header {
                padding: 20px;
            }
            
            .header h1 {
                font-size: 2rem;
            }
            
            .media-grid {
                grid-template-columns: repeat(auto-fill, minmax(120px, 1fr));
                gap: 10px;
            }
            
            .action-buttons {
                flex-direction: column;
                align-items: center;
            }
        }
    </style>
</head>
<body>
    <div class="container">
        <div class="header">
            <h1>レビュー機能</h1>
        </div>

        <div class="content">
            <div class="section">
                <h2 class="section-title">記事内容</h2>
                <div class="markdown-editor">
                    <div class="editor-toolbar">
                        <button class="toolbar-btn" title="太字">Bold</button>
                        <button class="toolbar-btn" title="斜体">Italic</button>
                        <button class="toolbar-btn" title="見出し">Heading</button>
                        <button class="toolbar-btn" title="リンク">Link</button>
                        <button class="toolbar-btn" title="画像">Image</button>
                        <button class="toolbar-btn" title="コード">Code</button>
                    </div>
                    <textarea 
                        class="markdown-textarea" 
                        placeholder="こちらにMarkdown形式で記事を書いてください...

例：
# 見出し1
## 見出し2

**太字**のテキストや *斜体* のテキスト

- リストアイテム1
- リストアイテム2

[リンクテキスト](https://example.com)

`コード`のサンプル

```javascript
console.log('Hello World');
```"></textarea>
                </div>
            </div>

            <div class="section">
                <h2 class="section-title">配信先メディア</h2>
                <div class="media-grid">
                    <div class="media-item" data-media="tv">
                        <div class="media-name">テレビ</div>
                    </div>
                    <div class="media-item" data-media="magazine">
                        <div class="media-name">雑誌</div>
                    </div>
                    <div class="media-item" data-media="newspaper">
                        <div class="media-name">新聞</div>
                    </div>
                    <div class="media-item" data-media="web">
                        <div class="media-name">Web</div>
                    </div>
                    <div class="media-item" data-media="freepaper">
                        <div class="media-name">フリーペーパー</div>
                    </div>
                    <div class="media-item" data-media="radio">
                        <div class="media-name">ラジオ</div>
                    </div>
                    <div class="media-item" data-media="news-agency">
                        <div class="media-name">通信社</div>
                    </div>
                    <div class="media-item" data-media="others">
                        <div class="media-name">未分類</div>
                    </div>
                </div>
            </div>

            <div class="section" id="diffSection" style="display:none;">
                <h2 class="section-title">校正結果（差分）</h2>
                <div style="margin-bottom:12px; display:flex; gap:12px; flex-wrap:wrap; align-items:center;">
                    <div style="display:flex; gap:8px;">
                        <button class="btn btn-secondary" id="showAllBtn">全体</button>
                        <button class="btn btn-primary" id="showChangesOnlyBtn">変更のみ</button>
                    </div>
                    <div style="display:flex; gap:8px; margin-left:auto;">
                        <label style="display:flex; align-items:center; gap:6px;">
                            <input type="radio" name="renderer" value="Inline"> インライン
                        </label>
                        <label style="display:flex; align-items:center; gap:6px;">
                            <input type="radio" name="renderer" value="SideBySide" checked> 左右並び
                        </label>
                    </div>
                </div>
                <div id="diffContainer" class="Diff"></div>
            </div>

            <div class="section" id="sixTwoSection" style="display:none;">
                <h2 class="section-title">6W2H レビュー</h2>
                <div id="sixTwoContent" style="white-space:pre-wrap; background:#f8fafc; border:1px solid #e2e8f0; border-radius:12px; padding:16px;"></div>
            </div>

            <div class="action-buttons display-flex">
                <button class="btn btn-secondary" id="reviewBtn">
                    レビュー
                </button>
                <button class="btn btn-primary" id="publishBtn">
                    配信
                </button>
            </div>
        </div>
    </div>

    <script>
        // メディア選択機能
        document.querySelectorAll('.media-item').forEach(item => {
            item.addEventListener('click', function() {
                this.classList.toggle('selected');
                
                // 選択時のアニメーション
                this.style.transform = 'scale(0.95)';
                setTimeout(() => {
                    this.style.transform = '';
                }, 100);
            });
        });

        // ツールバーボタンの機能
        document.querySelectorAll('.toolbar-btn').forEach(btn => {
            btn.addEventListener('click', function() {
                const textarea = document.querySelector('.markdown-textarea');
                const text = this.textContent;
                
                // 簡単なMarkdown挿入機能
                let insertText = '';
                switch(text) {
                    case 'Bold':
                        insertText = '**太字**';
                        break;
                    case 'Italic':
                        insertText = '*斜体*';
                        break;
                    case 'Heading':
                        insertText = '# 見出し';
                        break;
                    case 'Link':
                        insertText = '[リンクテキスト](URL)';
                        break;
                    case 'Image':
                        insertText = '![画像の説明](画像URL)';
                        break;
                    case 'Code':
                        insertText = '`コード`';
                        break;
                }
                
                if (insertText) {
                    const start = textarea.selectionStart;
                    const end = textarea.selectionEnd;
                    const currentText = textarea.value;
                    textarea.value = currentText.substring(0, start) + insertText + currentText.substring(end);
                    textarea.focus();
                }
            });
        });

        // ボタンクリックイベント
        document.getElementById('reviewBtn').addEventListener('click', async function() {
            const selectedMedia = document.querySelectorAll('.media-item.selected');
            const content = document.querySelector('.markdown-textarea').value;
            
            if (content.trim() === '') {
                alert('記事内容を入力してください。');
                return;
            }
            
            if (selectedMedia.length === 0) {
                alert('配信先メディアを選択してください。');
                return;
            }
            
            // レビュー処理
            try {
                const token = document.querySelector('meta[name="csrf-token"]').getAttribute('content');
                const selectedRenderer = document.querySelector('input[name="renderer"]:checked')?.value || 'SideBySide';
                const res = await fetch('/proofread', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'X-CSRF-TOKEN': token,
                        'Accept': 'application/json'
                    },
                    body: JSON.stringify({ content, renderer: selectedRenderer })
                });

                if (!res.ok) {
                    const text = await res.text();
                    throw new Error(text || 'レビューAPIエラー');
                }

                const data = await res.json();
                const diffContainer = document.getElementById('diffContainer');
                diffContainer.innerHTML = data.diffHtml;
                document.getElementById('diffSection').style.display = '';

                // 6W2H レビュー
                if (data.sixTwoReview) {
                    const sixTwo = document.getElementById('sixTwoContent');
                    sixTwo.textContent = data.sixTwoReview;
                    document.getElementById('sixTwoSection').style.display = '';
                }
            } catch (e) {
                alert('レビューに失敗しました: ' + e.message);
            }
        });

        // 変更部分のみ表示トグル
        function toggleShowOnlyChanges(showOnly) {
            const container = document.getElementById('diffContainer');
            if (!container) return;
            // レイアウトは保持し、変更のないブロックは薄く表示
            const blocks = container.querySelectorAll('tr, td, li, p, div');
            blocks.forEach(el => {
                if (el === container) return;
                if (!showOnly) {
                    el.style.opacity = '';
                    return;
                }
                const hasChange = el.querySelector('ins, del');
                const isStructural = ['TBODY','THEAD','TABLE','TFOOT'].includes(el.tagName);
                el.style.opacity = (hasChange || isStructural) ? '' : '0.35';
            });
        }

        document.getElementById('showAllBtn').addEventListener('click', function() {
            toggleShowOnlyChanges(false);
        });

        document.getElementById('showChangesOnlyBtn').addEventListener('click', function() {
            toggleShowOnlyChanges(true);
        });

        document.getElementById('publishBtn').addEventListener('click', function() {
            const selectedMedia = document.querySelectorAll('.media-item.selected');
            const content = document.querySelector('.markdown-textarea').value;
            
            if (content.trim() === '') {
                alert('記事内容を入力してください。');
                return;
            }
            
            if (selectedMedia.length === 0) {
                alert('配信先メディアを選択してください。');
                return;
            }
            
            // 配信処理
            if (confirm('選択したメディアに配信しますか？')) {
                alert('配信を開始します...');
            }
        });

        // テキストエリアの自動リサイズ
        document.querySelector('.markdown-textarea').addEventListener('input', function() {
            this.style.height = 'auto';
            this.style.height = this.scrollHeight + 'px';
        });
    </script>
</body>
</html>