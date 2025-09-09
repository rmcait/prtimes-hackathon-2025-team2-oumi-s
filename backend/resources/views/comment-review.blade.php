<!DOCTYPE html>
<html lang="ja">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>AI記事レビュー - インラインコメント</title>
    
    <!-- Tailwind CSS -->
    <script src="https://cdn.tailwindcss.com"></script>
    
    <style>
        /* GoogleDocsライクなコメント表示 */
        .comment-highlight {
            background-color: #fef3c7;
            border-bottom: 2px solid #f59e0b;
            cursor: pointer;
            position: relative;
        }
        
        .comment-highlight:hover {
            background-color: #fde68a;
        }
        
        .comment-highlight.active {
            background-color: #fed7aa;
            border-bottom-color: #ea580c;
        }
        
        .comment-panel {
            position: sticky;
            top: 20px;
            max-height: calc(100vh - 40px);
            overflow-y: auto;
        }
        
        .comment-item {
            border: 1px solid #e5e7eb;
            border-radius: 8px;
            background: white;
            margin-bottom: 16px;
            box-shadow: 0 1px 3px rgba(0, 0, 0, 0.1);
            transition: all 0.2s ease;
        }
        
        .comment-item:hover {
            box-shadow: 0 4px 12px rgba(0, 0, 0, 0.15);
        }
        
        .comment-item.highlighted {
            border-color: #3b82f6;
            box-shadow: 0 4px 12px rgba(59, 130, 246, 0.3);
        }
        
        .article-content {
            line-height: 1.8;
            font-size: 16px;
        }
        
        .comment-thread {
            border-left: 3px solid #e5e7eb;
            padding-left: 12px;
            margin-left: 8px;
        }
        
        .loading-overlay {
            position: fixed;
            top: 0;
            left: 0;
            right: 0;
            bottom: 0;
            background: rgba(0, 0, 0, 0.5);
            display: flex;
            justify-content: center;
            z-index: 50;
        }
        
        .custom-scroll::-webkit-scrollbar {
            width: 6px;
        }
        
        .custom-scroll::-webkit-scrollbar-track {
            background: #f1f5f9;
        }
        
        .custom-scroll::-webkit-scrollbar-thumb {
            background: #cbd5e1;
            border-radius: 3px;
        }
    </style>
</head>
<body class="bg-gray-50">
    <!-- ヘッダー -->
    <header class="bg-white shadow-sm border-b sticky top-0 z-40">
        <div class="mx-[5%] px-4 sm:px-6 lg:px-8">
            <div class="flex justify-between items-center py-4">
                <div class="flex items-center space-x-4">
                    <div class="w-10 h-10 bg-gradient-to-br from-blue-500 to-purple-600 rounded-lg flex items-center justify-center text-white font-bold text-lg">
                        📝
                    </div>
                    <div>
                        <h1 class="text-2xl font-bold text-gray-900">AI記事レビュー</h1>
                        <p class="text-sm text-gray-500">インラインコメント形式でリアルタイム改善提案</p>
                    </div>
                </div>
                <div class="flex space-x-4">
                    <button onclick="showNewArticleModal()" class="px-4 py-2 bg-blue-600 text-white rounded-lg hover:bg-blue-700 text-sm">
                        新しい記事
                    </button>
                    <button onclick="exportComments()" class="px-4 py-2 bg-green-600 text-white rounded-lg hover:bg-green-700 text-sm">
                        📄 エクスポート
                    </button>
                </div>
            </div>
        </div>
    </header>

    <div class="mx-[5%] px-4 sm:px-6 lg:px-8 py-6">
        <!-- メインコンテンツエリア -->
        <div id="reviewContainer" class="grid grid-cols-1 lg:grid-cols-3 gap-8 hidden">
            <!-- 左側：記事コンテンツ -->
            <div class="lg:col-span-2">
                <div class="bg-white rounded-lg shadow-lg">
                    <div class="p-6 border-b border-gray-200">
                        <div class="flex items-center justify-between mb-4">
                            <h2 class="text-xl font-semibold text-gray-800">記事編集</h2>
                            <div class="flex space-x-2">
                                <button onclick="toggleEditMode()" id="editModeBtn" class="px-3 py-1 bg-blue-100 text-blue-700 text-sm rounded-full hover:bg-blue-200 transition-colors">
                                    編集モード
                                </button>
                                <span class="px-3 py-1 bg-green-100 text-green-700 text-sm rounded-full" id="analysisStatus">
                                    分析完了
                                </span>
                                <span class="px-3 py-1 bg-blue-100 text-blue-700 text-sm rounded-full" id="commentCount">
                                    コメント: 0
                                </span>
                            </div>
                        </div>
                    </div>
                    
                    <!-- プレビューモード -->
                    <div class="p-8 article-content prose max-w-none" id="articleContent">
                        <!-- 記事内容がここに表示される -->
                    </div>
                    
                    <!-- 編集モード -->
                    <div class="p-6 hidden" id="editMode">
                        <div class="mb-4">
                            <div class="flex justify-between items-center mb-2">
                                <label class="text-sm font-medium text-gray-700">記事内容（Markdown）</label>
                                <div class="text-xs text-gray-500">
                                    <span id="editCharCount">0 文字</span>
                                </div>
                            </div>
                            <textarea 
                                id="editTextarea" 
                                rows="20"
                                class="w-full px-4 py-3 border border-gray-300 rounded-lg shadow-sm focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-transparent resize-y font-mono text-sm"
                                placeholder="Markdown形式で記事を編集してください..."></textarea>
                        </div>
                        
                        <div class="flex justify-between">
                            <button onclick="previewChanges()" class="px-4 py-2 bg-green-600 text-white rounded-lg hover:bg-green-700 transition-colors">
                                👀 プレビュー
                            </button>
                            <div class="space-x-2">
                                <button onclick="saveChanges()" class="px-4 py-2 bg-blue-600 text-white rounded-lg hover:bg-blue-700 transition-colors">
                                    💾 保存
                                </button>
                                <button onclick="reAnalyze()" class="px-4 py-2 bg-purple-600 text-white rounded-lg hover:bg-purple-700 transition-colors">
                                    🔄 再分析
                                </button>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- 右側：コメント・改善提案パネル -->
            <div class="lg:col-span-1">
                <div class="comment-panel">
                    <div class="bg-white rounded-lg shadow-lg p-6">
                        <h3 class="text-lg font-semibold text-gray-800 mb-4 flex items-center">
                            <span class="w-6 h-6 bg-yellow-100 rounded-full flex items-center justify-center text-yellow-600 mr-2">💡</span>
                            改善提案
                        </h3>
                        
                        <div id="commentsContainer" class="space-y-4 custom-scroll">
                            <!-- コメントがここに表示される -->
                        </div>
                        
                        <div id="noCommentsMessage" class="text-center py-8 text-gray-500">
                            記事を分析中です...
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- 初期入力画面 -->
        <div id="inputContainer" class="max-w-4xl mx-auto">
            <div class="bg-white rounded-lg shadow-lg p-8">
                <div class="text-center mb-8">
                    <h2 class="text-3xl font-bold text-gray-900 mb-4">記事レビューを始めましょう</h2>
                    <p class="text-gray-600">記事を入力すると、AI が自動で改善提案をインラインコメント形式で表示します</p>
                </div>

                <form id="articleForm" onsubmit="analyzeArticle(event)">
                    <div class="mb-6">
                        <label for="articleInput" class="block text-sm font-medium text-gray-700 mb-2">
                            記事内容（Markdown対応）
                        </label>
                        <textarea 
                            id="articleInput" 
                            name="content"
                            rows="15" 
                            class="w-full px-4 py-3 border border-gray-300 rounded-lg shadow-sm focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-transparent resize-y"
                            placeholder="# チーム開発×データ分析に挑む3Daysハッカソン受付開始

![PR TIMES HACKATHON](https://example.com/image.jpg)

プレスリリース配信サービス「PR TIMES」等を運営する株式会社PR TIMES（東京都港区、代表取締役：山口拓己、東証プライム：3922）は、2026・27年卒業予定のエンジニア志望学生を対象に、「PR TIMES HACKATHON 2025 Summer」を開催します。

## 同世代エンジニアとつながり、チーム開発の経験を積める3日間

PR TIMESハッカソンは、2016年より開催している内定直結型のハッカソンイベントです。2025年9月8日〜10日の3日間でWebサービスの開発を行い、特に優秀な方には年収500万円以上の中途採用基準での内定をお出しします。"
                            required></textarea>
                        <div class="text-right text-xs text-gray-500 mt-1">
                            <span id="charCount">0 文字</span>
                        </div>
                    </div>

                    <div class="grid grid-cols-1 md:grid-cols-2 gap-4 mb-6">
                        <div>
                            <label for="targetPersona" class="block text-sm font-medium text-gray-700 mb-2">
                                ターゲット読者（任意）
                            </label>
                            <input 
                                type="text" 
                                id="targetPersona" 
                                name="persona"
                                placeholder="例: 26・27卒就活生、IT業界志望"
                                class="w-full px-4 py-3 border border-gray-300 rounded-lg shadow-sm focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-transparent">
                        </div>

                        <div>
                            <label for="releaseType" class="block text-sm font-medium text-gray-700 mb-2">
                                リリースタイプ（任意）
                            </label>
                            <select 
                                id="releaseType" 
                                name="release_type"
                                class="w-full px-4 py-3 border border-gray-300 rounded-lg shadow-sm focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-transparent">
                                <option value="">選択してください</option>
                                <option value="イベント">イベント</option>
                                <option value="新商品">新商品</option>
                                <option value="サービス">サービス</option>
                                <option value="企業">企業</option>
                            </select>
                        </div>
                    </div>

                    <div class="text-center">
                        <button 
                            type="submit"
                            id="analyzeButton" 
                            class="px-8 py-4 bg-gradient-to-r from-blue-600 to-purple-600 text-white rounded-lg font-semibold text-lg hover:from-blue-700 hover:to-purple-700 transition-all duration-300 transform hover:scale-105 disabled:opacity-50 disabled:cursor-not-allowed disabled:transform-none"
                        >
                            🚀 AI レビューを開始
                        </button>
                        <p class="text-sm text-gray-500 mt-2">強み分析・なぜなぜ分析・6W2H分析を実行します</p>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <!-- ローディングオーバーレイ -->
    <div id="loadingOverlay" class="loading-overlay hidden">
        <div class="bg-white rounded-lg p-6 max-w-sm mx-auto text-center">
            <div class="animate-spin rounded-full h-8 w-8 border-b-2 border-blue-600 mx-auto mb-3"></div>
            <h3 class="text-base font-semibold text-gray-800 mb-1">記事を分析中...</h3>
            <p class="text-gray-600 text-xs" id="loadingMessage">AI が記事を詳細分析しています</p>
            <div class="mt-3 bg-gray-200 rounded-full h-1.5">
                <div class="bg-gradient-to-r from-blue-600 to-purple-600 h-1.5 rounded-full transition-all duration-500" id="loadingProgress" style="width: 0%"></div>
            </div>
        </div>
    </div>

    <!-- 新記事入力モーダル -->
    <div id="newArticleModal" class="fixed inset-0 bg-black bg-opacity-50 hidden z-50 flex items-center justify-center p-4">
        <div class="bg-white rounded-lg max-w-2xl w-full p-6 max-h-[90vh] overflow-y-auto">
            <div class="flex justify-between items-center mb-4">
                <h3 class="text-xl font-bold text-gray-800">新しい記事を入力</h3>
                <button onclick="hideNewArticleModal()" class="text-gray-400 hover:text-gray-600 text-2xl">×</button>
            </div>
            <form id="newArticleForm" onsubmit="analyzeNewArticle(event)">
                <div class="mb-4">
                    <textarea 
                        id="newArticleInput" 
                        rows="12" 
                        class="w-full px-4 py-3 border border-gray-300 rounded-lg shadow-sm focus:outline-none focus:ring-2 focus:ring-blue-500 resize-y"
                        placeholder="新しい記事内容を入力してください..."
                        required></textarea>
                </div>
                <div class="flex justify-end space-x-3">
                    <button type="button" onclick="hideNewArticleModal()" class="px-4 py-2 text-gray-600 border border-gray-300 rounded-lg hover:bg-gray-50">
                        キャンセル
                    </button>
                    <button type="submit" class="px-6 py-2 bg-blue-600 text-white rounded-lg hover:bg-blue-700">
                        分析開始
                    </button>
                </div>
            </form>
        </div>
    </div>

    <script>
        let currentComments = [];
        let analysisResults = {};
        let isEditMode = false;
        let originalContent = '';

        // 文字数カウント
        const articleInput = document.getElementById('articleInput');
        const charCount = document.getElementById('charCount');
        
        articleInput.addEventListener('input', function() {
            charCount.textContent = this.value.length.toLocaleString() + ' 文字';
        });

        // 記事分析の実行
        async function analyzeArticle(event) {
            event.preventDefault();
            
            const formData = new FormData(event.target);
            const content = formData.get('content').trim();
            const persona = formData.get('persona').trim();
            const releaseType = formData.get('release_type').trim();
            
            if (!content) {
                alert('記事内容を入力してください。');
                return;
            }

            // ローディング表示
            showLoading();
            
            try {
                // 3つのAI分析を並行実行
                updateLoadingProgress(20, 'AI分析を開始しています...');
                
                const [strengthResult, whyResult, sixTwoResult] = await Promise.all([
                    executeStrengthAnalysis(content, persona, releaseType),
                    executeWhyAnalysis(content),
                    executeSixTwoReview(content)
                ]);
                
                updateLoadingProgress(90, '分析結果を処理中...');
                
                analysisResults = {
                    strength: strengthResult,
                    why: whyResult,
                    sixTwo: sixTwoResult,
                    content: content
                };
                
                // 結果を表示
                displayArticleWithComments(content);
                generateComments();
                
                updateLoadingProgress(100, '完了！');
                
                setTimeout(() => {
                    hideLoading();
                    showReviewContainer();
                }, 500);
                
            } catch (error) {
                console.error('Analysis failed:', error);
                hideLoading();
                alert('分析中にエラーが発生しました。もう一度お試しください。');
            }
        }

        // 強み分析実行
        async function executeStrengthAnalysis(content, persona, releaseType) {
            updateLoadingProgress(30, '強み分析実行中...');
            
            const response = await fetch('/api/strength-analysis/analyze', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content')
                },
                body: JSON.stringify({ content, persona, release_type: releaseType })
            });

            const result = await response.json();
            if (!result.success) {
                throw new Error(result.message || '強み分析に失敗しました');
            }
            
            return result.data;
        }

        // なぜなぜ分析実行
        async function executeWhyAnalysis(content) {
            updateLoadingProgress(50, 'なぜなぜ分析実行中...');
            
            const startResponse = await fetch('/api/why-analysis/start', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content')
                },
                body: JSON.stringify({ content })
            });

            const startResult = await startResponse.json();
            if (!startResult.success) {
                throw new Error(startResult.message || 'なぜなぜ分析に失敗しました');
            }

            // 自動的に洞察生成
            const insightResponse = await fetch('/api/why-analysis/insight', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content')
                },
                body: JSON.stringify({
                    content: content,
                    chat_history: [{
                        type: 'bot_question',
                        content: startResult.data.bot_response || 'なぜなぜ分析を開始しました',
                        timestamp: new Date().toISOString()
                    }],
                    session_id: startResult.session_id
                })
            });

            const insightResult = await insightResponse.json();
            if (!insightResult.success) {
                throw new Error(insightResult.message || '洞察生成に失敗しました');
            }

            return insightResult.data;
        }

        // 6W2H分析実行
        async function executeSixTwoReview(content) {
            updateLoadingProgress(70, '6W2H分析実行中...');
            
            const response = await fetch('/api/sixtwo-review', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content')
                },
                body: JSON.stringify({ content })
            });

            const result = await response.json();
            if (!result.success) {
                throw new Error(result.message || '6W2H分析に失敗しました');
            }

            return result.data;
        }

        // 記事内容をハイライト付きで表示
        function displayArticleWithComments(content) {
            originalContent = content;
            const articleContent = document.getElementById('articleContent');
            const editTextarea = document.getElementById('editTextarea');
            
            // 編集用テキストエリアにも内容を設定
            editTextarea.value = content;
            updateEditCharCount();
            
            // Markdown を HTML に変換（簡易版）
            let htmlContent = content
                .replace(/^# (.*$)/gim, '<h1 class="text-3xl font-bold text-gray-900 mb-6">$1</h1>')
                .replace(/^## (.*$)/gim, '<h2 class="text-2xl font-semibold text-gray-800 mb-4 mt-8">$1</h2>')
                .replace(/^### (.*$)/gim, '<h3 class="text-xl font-medium text-gray-800 mb-3 mt-6">$1</h3>')
                .replace(/\*\*(.*?)\*\*/g, '<strong class="font-semibold">$1</strong>')
                .replace(/\*(.*?)\*/g, '<em class="italic">$1</em>')
                .replace(/!\[([^\]]*)\]\(([^)]+)\)/g, '<img src="$2" alt="$1" class="w-full h-auto rounded-lg my-6 shadow-md">')
                .replace(/\[([^\]]+)\]\(([^)]+)\)/g, '<a href="$2" class="text-blue-600 hover:text-blue-800 underline">$1</a>')
                .replace(/^- (.*$)/gim, '<li class="ml-4">$1</li>')
                .replace(/\n\n/g, '</p><p class="mb-4">')
                .replace(/\n/g, '<br>');

            // 段落で囲む
            htmlContent = '<p class="mb-4">' + htmlContent + '</p>';
            
            // リストを ul で囲む
            htmlContent = htmlContent.replace(/(<li class="ml-4">.*?<\/li>)/gs, '<ul class="list-disc list-inside mb-4 space-y-1">$1</ul>');
            
            articleContent.innerHTML = htmlContent;
        }

        // コメントの生成と表示
        function generateComments() {
            currentComments = [];
            const { strength, why, sixTwo } = analysisResults;

            // 強み分析からのコメント
            if (strength.missing_elements) {
                strength.missing_elements.forEach((element, index) => {
                    currentComments.push({
                        id: `strength_${index}`,
                        type: 'strength',
                        title: `${element.element}要素の追加`,
                        content: element.suggestion,
                        severity: 'medium',
                        category: '強み分析',
                        position: index * 100 + 50 // 仮の位置
                    });
                });
            }

            // なぜなぜ分析からのコメント  
            if (why.pr_recommendations) {
                why.pr_recommendations.forEach((rec, index) => {
                    currentComments.push({
                        id: `why_${index}`,
                        type: 'why',
                        title: 'PR活用のポイント',
                        content: rec,
                        severity: 'high',
                        category: 'なぜなぜ分析',
                        position: index * 120 + 80
                    });
                });
            }

            // 記事活用例からのコメント
            if (why.article_applications) {
                why.article_applications.forEach((app, index) => {
                    if (app.after_example || app.suggestion) {
                        currentComments.push({
                            id: `application_${index}`,
                            type: 'application',
                            title: `${app.section}の改善`,
                            content: app.after_example || app.suggestion,
                            detail: app.reason,
                            tips: app.tips,
                            severity: 'high',
                            category: '記事改善',
                            position: index * 140 + 120
                        });
                    }
                });
            }

            // 6W2H分析からのコメント
            if (sixTwo.review) {
                currentComments.push({
                    id: 'sixtwo_main',
                    type: 'sixtwo',
                    title: '6W2H構成チェック',
                    content: sixTwo.review,
                    severity: 'low',
                    category: '6W2H分析',
                    position: 200
                });
            }

            displayComments();
            addHighlights();
            updateCommentCount();
        }

        // コメントの表示
        function displayComments() {
            const container = document.getElementById('commentsContainer');
            const noCommentsMessage = document.getElementById('noCommentsMessage');

            if (currentComments.length === 0) {
                noCommentsMessage.style.display = 'block';
                noCommentsMessage.textContent = '改善提案が見つかりませんでした。素晴らしい記事です！';
                return;
            }

            noCommentsMessage.style.display = 'none';

            container.innerHTML = currentComments.map(comment => {
                const severityColor = {
                    'high': 'border-red-300 bg-red-50',
                    'medium': 'border-yellow-300 bg-yellow-50', 
                    'low': 'border-blue-300 bg-blue-50'
                }[comment.severity] || 'border-gray-300 bg-gray-50';

                const severityIcon = {
                    'high': '🔥',
                    'medium': '⚠️',
                    'low': '💡'
                }[comment.severity] || '💬';

                return `
                    <div class="comment-item ${severityColor}" data-comment-id="${comment.id}" onclick="highlightComment('${comment.id}')">
                        <div class="p-4">
                            <div class="flex items-start justify-between mb-2">
                                <div class="flex items-center space-x-2">
                                    <span class="text-lg">${severityIcon}</span>
                                    <span class="text-xs px-2 py-1 bg-white rounded-full text-gray-600">${comment.category}</span>
                                </div>
                                <div class="flex space-x-2">
                                    <button onclick="applyComment('${comment.id}')" class="text-blue-600 hover:text-blue-800 text-sm">
                                        適用
                                    </button>
                                    <button onclick="applyCommentToEditor('${comment.id}')" class="text-green-600 hover:text-green-800 text-sm">
                                        編集へ
                                    </button>
                                </div>
                            </div>
                            <h4 class="font-medium text-gray-800 mb-2">${comment.title}</h4>
                            <p class="text-sm text-gray-700 mb-2">${comment.content.substring(0, 150)}${comment.content.length > 150 ? '...' : ''}</p>
                            
                            ${comment.detail ? `
                                <div class="mt-3 p-3 bg-white bg-opacity-70 rounded border-l-2 border-gray-300">
                                    <p class="text-xs text-gray-600">${comment.detail}</p>
                                </div>
                            ` : ''}
                            
                            ${comment.tips ? `
                                <div class="mt-2 text-xs text-gray-500">
                                    💡 <strong>コツ:</strong> ${comment.tips}
                                </div>
                            ` : ''}
                        </div>
                    </div>
                `;
            }).join('');
        }

        // 記事内にハイライトを追加
        function addHighlights() {
            // 実装簡略化：各段落に潜在的な改善点として薄いハイライトを追加
            const articleContent = document.getElementById('articleContent');
            const paragraphs = articleContent.querySelectorAll('p, h1, h2, h3');
            
            paragraphs.forEach((paragraph, index) => {
                if (index < currentComments.length) {
                    const comment = currentComments[index];
                    paragraph.classList.add('comment-highlight');
                    paragraph.setAttribute('data-comment-id', comment.id);
                    paragraph.addEventListener('click', () => highlightComment(comment.id));
                }
            });
        }

        // コメントのハイライト
        function highlightComment(commentId) {
            // 全てのハイライトをリセット
            document.querySelectorAll('.comment-highlight').forEach(el => {
                el.classList.remove('active');
            });
            document.querySelectorAll('.comment-item').forEach(el => {
                el.classList.remove('highlighted');
            });

            // 選択されたコメントをハイライト
            const commentElement = document.querySelector(`[data-comment-id="${commentId}"]`);
            const commentItem = document.querySelector(`.comment-item[data-comment-id="${commentId}"]`);
            
            if (commentElement) {
                commentElement.classList.add('active');
                commentElement.scrollIntoView({ behavior: 'smooth', block: 'center' });
            }
            
            if (commentItem) {
                commentItem.classList.add('highlighted');
                commentItem.scrollIntoView({ behavior: 'smooth', block: 'center' });
            }
        }

        // コメントの適用
        function applyComment(commentId) {
            const comment = currentComments.find(c => c.id === commentId);
            if (!comment) return;

            // 簡単な適用処理：アラートで内容を表示
            alert(`改善提案を適用しました:\n\n${comment.title}\n\n${comment.content}`);
            
            // 実際の実装では、記事内容を直接編集する機能を追加できます
        }

        // コメントを編集エリアに適用
        function applyCommentToEditor(commentId) {
            const comment = currentComments.find(c => c.id === commentId);
            if (!comment) return;

            // 編集モードに切り替え
            if (!isEditMode) {
                toggleEditMode();
            }

            // テキストエリアに改善提案を追加
            const textarea = document.getElementById('editTextarea');
            const currentText = textarea.value;
            
            // 改善提案をコメント形式で追加
            const improvement = `\n\n<!-- ${comment.title} -->\n<!-- ${comment.content} -->\n<!-- カテゴリ: ${comment.category} -->\n`;
            
            textarea.value = currentText + improvement;
            textarea.scrollTop = textarea.scrollHeight;
            updateEditCharCount();
            
            // フォーカス
            textarea.focus();
            
            // 成功メッセージ
            showToast('改善提案を編集エリアに追加しました', 'success');
        }

        // 編集モードの切り替え
        function toggleEditMode() {
            const editModeBtn = document.getElementById('editModeBtn');
            const articleContent = document.getElementById('articleContent');
            const editMode = document.getElementById('editMode');
            
            isEditMode = !isEditMode;
            
            if (isEditMode) {
                editModeBtn.textContent = 'プレビュー';
                editModeBtn.className = 'px-3 py-1 bg-green-100 text-green-700 text-sm rounded-full hover:bg-green-200 transition-colors';
                articleContent.classList.add('hidden');
                editMode.classList.remove('hidden');
            } else {
                editModeBtn.textContent = '編集モード';
                editModeBtn.className = 'px-3 py-1 bg-blue-100 text-blue-700 text-sm rounded-full hover:bg-blue-200 transition-colors';
                articleContent.classList.remove('hidden');
                editMode.classList.add('hidden');
            }
        }

        // プレビュー更新
        function previewChanges() {
            const textarea = document.getElementById('editTextarea');
            const newContent = textarea.value;
            
            if (newContent !== originalContent) {
                displayArticleWithComments(newContent);
                showToast('プレビューを更新しました', 'success');
            }
        }

        // 変更を保存
        function saveChanges() {
            const textarea = document.getElementById('editTextarea');
            originalContent = textarea.value;
            analysisResults.content = originalContent;
            
            previewChanges();
            showToast('変更を保存しました', 'success');
        }

        // 再分析
        async function reAnalyze() {
            const textarea = document.getElementById('editTextarea');
            const newContent = textarea.value.trim();
            
            if (!newContent) {
                alert('分析する内容がありません。');
                return;
            }

            // 保存してから再分析
            saveChanges();
            
            showLoading();
            updateLoadingProgress(10, '再分析を開始しています...');
            
            try {
                const [strengthResult, whyResult, sixTwoResult] = await Promise.all([
                    executeStrengthAnalysis(newContent, '', ''),
                    executeWhyAnalysis(newContent),
                    executeSixTwoReview(newContent)
                ]);
                
                updateLoadingProgress(90, '新しい分析結果を処理中...');
                
                analysisResults = {
                    strength: strengthResult,
                    why: whyResult,
                    sixTwo: sixTwoResult,
                    content: newContent
                };
                
                generateComments();
                updateLoadingProgress(100, '完了！');
                
                setTimeout(() => {
                    hideLoading();
                    showToast('再分析が完了しました', 'success');
                }, 500);
                
            } catch (error) {
                console.error('Re-analysis failed:', error);
                hideLoading();
                showToast('再分析中にエラーが発生しました', 'error');
            }
        }

        // 編集エリアの文字数カウント
        function updateEditCharCount() {
            const textarea = document.getElementById('editTextarea');
            const charCountEl = document.getElementById('editCharCount');
            if (textarea && charCountEl) {
                charCountEl.textContent = textarea.value.length.toLocaleString() + ' 文字';
            }
        }

        // トースト通知
        function showToast(message, type = 'info') {
            const toast = document.createElement('div');
            toast.className = `fixed top-4 right-4 p-4 rounded-lg text-white z-50 animate-in slide-in-from-top ${
                type === 'success' ? 'bg-green-500' : 
                type === 'error' ? 'bg-red-500' : 'bg-blue-500'
            }`;
            toast.textContent = message;
            document.body.appendChild(toast);
            
            setTimeout(() => {
                toast.classList.add('animate-out', 'slide-out-to-top');
                setTimeout(() => toast.remove(), 300);
            }, 3000);
        }

        // UI制御関数
        function showLoading() {
            document.getElementById('loadingOverlay').classList.remove('hidden');
        }

        function hideLoading() {
            document.getElementById('loadingOverlay').classList.add('hidden');
        }

        function updateLoadingProgress(percent, message) {
            document.getElementById('loadingProgress').style.width = percent + '%';
            document.getElementById('loadingMessage').textContent = message;
        }

        function showReviewContainer() {
            document.getElementById('inputContainer').classList.add('hidden');
            document.getElementById('reviewContainer').classList.remove('hidden');
        }

        function showNewArticleModal() {
            document.getElementById('newArticleModal').classList.remove('hidden');
        }

        function hideNewArticleModal() {
            document.getElementById('newArticleModal').classList.add('hidden');
            document.getElementById('newArticleForm').reset();
        }

        function updateCommentCount() {
            document.getElementById('commentCount').textContent = `コメント: ${currentComments.length}`;
        }

        function analyzeNewArticle(event) {
            event.preventDefault();
            const content = document.getElementById('newArticleInput').value.trim();
            if (!content) return;
            
            // フォームにデータを設定して既存の分析関数を呼び出し
            document.getElementById('articleInput').value = content;
            document.getElementById('targetPersona').value = '';
            document.getElementById('releaseType').value = '';
            
            hideNewArticleModal();
            analyzeArticle(event);
        }

        function exportComments() {
            if (currentComments.length === 0) {
                alert('エクスポートするコメントがありません。');
                return;
            }

            const exportData = {
                article: analysisResults.content,
                comments: currentComments,
                timestamp: new Date().toISOString(),
                total_comments: currentComments.length
            };

            const blob = new Blob([JSON.stringify(exportData, null, 2)], { type: 'application/json' });
            const url = URL.createObjectURL(blob);
            const a = document.createElement('a');
            a.href = url;
            a.download = `article_review_${new Date().toISOString().slice(0, 10)}.json`;
            a.click();
            URL.revokeObjectURL(url);
        }

        // モーダル外クリックで閉じる
        document.getElementById('newArticleModal').addEventListener('click', function(e) {
            if (e.target === this) {
                hideNewArticleModal();
            }
        });
    </script>
</body>
</html>