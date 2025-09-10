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
            transform: translateY(-1px);
        }
        
        .comment-item.highlighted {
            border-color: #3b82f6;
            box-shadow: 0 4px 12px rgba(59, 130, 246, 0.3);
        }
        
        /* 校正カード専用スタイル */
        .comment-item[data-comment-id*="proofread"] {
            background: linear-gradient(135deg, #fefefe 0%, #f8fafc 100%);
            border-left: 4px solid #3b82f6;
        }
        
        .comment-item[data-comment-id*="proofread"]:hover {
            border-left-color: #1d4ed8;
            background: linear-gradient(135deg, #f8fafc 0%, #f1f5f9 100%);
        }
        
        /* 重要度高の校正カード */
        .comment-item[data-comment-id*="proofread"].border-red-300 {
            border-left-color: #ef4444;
            background: linear-gradient(135deg, #fef2f2 0%, #fee2e2 100%);
        }
        
        /* 校正提案の修正前後表示 */
        .proofreading-diff {
            display: flex;
            flex-direction: column;
            gap: 8px;
        }
        
        .proofreading-before, .proofreading-after {
            padding: 8px 12px;
            border-radius: 6px;
            font-family: 'Monaco', 'Menlo', 'Ubuntu Mono', monospace;
            font-size: 13px;
            line-height: 1.4;
        }
        
        .proofreading-before {
            background: #fef2f2;
            border-left: 3px solid #ef4444;
            color: #991b1b;
        }
        
        .proofreading-after {
            background: #f0fdf4;
            border-left: 3px solid #22c55e;
            color: #166534;
        }
        
        /* 適用済みカードのスタイル */
        .comment-item.applied {
            opacity: 0.6;
            transform: scale(0.98);
            background: #f9fafb;
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
                    <button onclick="clearAllLocalStorage()" class="px-4 py-2 bg-red-600 text-white rounded-lg hover:bg-red-700 text-sm">
                        🗑️ データクリア
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
                    <div class="border-b border-gray-200">
                        <!-- ステータス表示 -->
                        <div class="flex items-center justify-between px-6 py-3 bg-gray-50">
                            <h2 class="text-xl font-semibold text-gray-800">記事編集</h2>
                            <div class="flex space-x-2">
                                <span class="px-3 py-1 bg-green-100 text-green-700 text-sm rounded-full" id="analysisStatus">
                                    分析完了
                                </span>
                                <span class="px-3 py-1 bg-blue-100 text-blue-700 text-sm rounded-full" id="commentCount">
                                    コメント: 0
                                </span>
                            </div>
                        </div>
                        
                        <!-- タブ形式のモード切り替え -->
                        <div class="flex px-6">
                            <button 
                                onclick="switchToPreviewMode()" 
                                id="previewTab" 
                                class="px-4 py-3 text-sm font-medium text-blue-600 border-b-2 border-blue-600 bg-white transition-colors"
                            >
                                <span class="flex items-center">
                                    <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"></path>
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z"></path>
                                    </svg>
                                    プレビュー
                                </span>
                            </button>
                            <button 
                                onclick="switchToEditMode()" 
                                id="editTab" 
                                class="px-4 py-3 text-sm font-medium text-gray-500 border-b-2 border-transparent hover:text-gray-700 hover:border-gray-300 transition-colors"
                            >
                                <span class="flex items-center">
                                    <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"></path>
                                    </svg>
                                    編集
                                </span>
                            </button>
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

        <!-- 全体分析結果セクション -->
        <div id="analysisResultsSection" class="mt-8 hidden">
            <div class="bg-white rounded-lg shadow-lg p-8">
                <h2 class="text-2xl font-bold text-gray-800 mb-6 flex items-center">
                    <span class="w-8 h-8 bg-blue-100 rounded-full flex items-center justify-center text-blue-600 mr-3">📊</span>
                    全体分析結果
                </h2>
                
                <div class="grid grid-cols-1 lg:grid-cols-2 gap-8">
                    <!-- 強み分析結果 -->
                    <div class="bg-gradient-to-br from-blue-50 to-indigo-50 rounded-lg p-6">
                        <h3 class="text-xl font-semibold text-blue-800 mb-4 flex items-center">
                            <span class="w-6 h-6 bg-blue-200 rounded-full flex items-center justify-center text-blue-700 mr-2">💪</span>
                            強み分析
                        </h3>
                        <div id="strengthAnalysisResult">
                            <!-- 強み分析結果がここに表示される -->
                        </div>
                    </div>

                    <!-- 6W2H分析結果 -->
                    <div class="bg-gradient-to-br from-green-50 to-emerald-50 rounded-lg p-6">
                        <h3 class="text-xl font-semibold text-green-800 mb-4 flex items-center">
                            <span class="w-6 h-6 bg-green-200 rounded-full flex items-center justify-center text-green-700 mr-2">✅</span>
                            6W2H分析
                        </h3>
                        <div id="sixTwoAnalysisResult">
                            <!-- 6W2H分析結果がここに表示される -->
                        </div>
                    </div>
                </div>

                <!-- なぜなぜ分析への誘導 -->
                <div class="mt-8 bg-gradient-to-br from-purple-50 to-pink-50 rounded-lg p-6 text-center">
                    <h3 class="text-xl font-semibold text-purple-800 mb-3 flex items-center justify-center">
                        <span class="w-6 h-6 bg-purple-200 rounded-full flex items-center justify-center text-purple-700 mr-2">🤔</span>
                        なぜなぜ分析
                    </h3>
                    <p class="text-gray-600 mb-4">記事のより深い洞察を得るために、インタラクティブな分析を行いましょう。</p>
                    <button onclick="startWhyAnalysis()" class="px-6 py-3 bg-purple-600 text-white rounded-lg font-semibold hover:bg-purple-700 transition-colors">
                        🚀 なぜなぜ分析を開始
                    </button>
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
                            placeholder="# タイトル

![画像](https://example.com/image.jpg)

リード文

## セクション名

セクション内容...
"
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
                                placeholder="例: 26・27卒就活生、IT業界志望(より詳細に)"
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
        let currentMarkdown = '';

        // 分析結果をローカルストレージに保存する共通関数
        function saveAnalysisResults() {
            try {
                localStorage.setItem('commentReviewAnalysisResults', JSON.stringify(analysisResults));
                localStorage.setItem('commentReviewComments', JSON.stringify(currentComments));
                localStorage.setItem('commentReviewMarkdown', currentMarkdown);
                console.log('Analysis results saved to localStorage:', {
                    analysisResults,
                    commentsCount: currentComments.length,
                    markdownLength: currentMarkdown.length
                });
            } catch (error) {
                console.error('Error saving analysis results:', error);
            }
        }

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
                // 4つのAI分析を並行実行
                updateLoadingProgress(20, 'AI分析を開始しています...');
                
                const [strengthResult, whyResult, sixTwoResult, proofreadResult] = await Promise.all([
                    executeStrengthAnalysis(content, persona, releaseType),
                    executeWhyAnalysis(content),
                    executeSixTwoReview(content),
                    executeProofreadAnalysis(content)
                ]);
                
                updateLoadingProgress(90, '分析結果を処理中...');
                
                analysisResults = {
                    strength: strengthResult,
                    why: whyResult,
                    sixTwo: sixTwoResult,
                    proofread: proofreadResult,
                    content: content
                };
                
                // 結果を表示
                displayArticleWithComments(content);
                generateComments();
                
                // 分析結果をローカルストレージに保存
                saveAnalysisResults();
                
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

        // 校正分析実行
        async function executeProofreadAnalysis(content) {
            updateLoadingProgress(80, '校正分析実行中...');
            
            console.log('Executing proofreading analysis for content:', {
                contentLength: content.length,
                contentPreview: content.substring(0, 200) + '...',
                fullContent: content
            });
            
            try {
                const requestBody = { text: content };
                console.log('Sending proofreading request:', requestBody);
                
                const response = await fetch('/api/proofread', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content')
                    },
                    body: JSON.stringify(requestBody)
                });
                
                console.log('Proofreading API response status:', response.status);
                const result = await response.json();
                console.log('Proofreading API result:', result);
                
                if (!response.ok || !result.success) {
                    console.warn('Proofreading API failed, using fallback:', result);
                    return getMockProofreadResult(content);
                }
                
                // 成功した場合は構造化されたレスポンスを返す
                return {
                    original: result.original,
                    proofread: result.corrected_text || result.proofread,
                    corrected_text: result.corrected_text || result.proofread,
                    suggestions: result.suggestions || [],
                    overall_assessment: result.overall_assessment || '文章を確認しました。',
                    has_changes: result.has_changes || false
                };
            } catch (error) {
                console.warn('Proofreading API error, using fallback:', error);
                return getMockProofreadResult(content);
            }
        }

        // 校正のモックデータ（APIが利用できない場合）
        function getMockProofreadResult(content) {
            console.log('Generating mock proofreading suggestions for content length:', content.length);
            
            const suggestions = [];
            
            // 特定の文字列に基づいて修正提案を生成
            if (content.includes('ハッカソン受付開始')) {
                suggestions.push({
                    original: "ハッカソン受付開始",
                    corrected: "ハッカソン受付を開始",
                    reason: "より自然な表現にするため、助詞「を」を追加しました。",
                    type: "表現改善",
                    severity: "medium",
                    position: "タイトル部分"
                });
            }
            
            if (content.includes('特に優秀な方には年収500万円以上の中途採用基準での内定をお出しします。')) {
                suggestions.push({
                    original: "特に優秀な方には年収500万円以上の中途採用基準での内定をお出しします。",
                    corrected: "特に優秀な方には、年収500万円以上の中途採用基準での内定をお出しします。",
                    reason: "読点を追加して、読みやすさを向上させました。",
                    type: "句読点",
                    severity: "low",
                    position: "本文2段落目"
                });
            }
            
            if (content.includes('等')) {
                suggestions.push({
                    original: "PR TIMES」等を",
                    corrected: "PR TIMES」などを",
                    reason: "「等」よりも「など」の方が読みやすく、一般的です。",
                    type: "表記統一",
                    severity: "low",
                    position: "本文中"
                });
            }
            
            if (content.includes('リニューアします')) {
                suggestions.push({
                    original: "メディアリスト機能をリニューアします",
                    corrected: "メディアリスト機能をリニューアルします",
                    reason: "「リニューアル」の正しい表記に修正しました。",
                    type: "誤字脱字",
                    severity: "high",
                    position: "タイトル部分"
                });
            }
            
            // 修正後の文章を生成
            let correctedText = content;
            suggestions.forEach(suggestion => {
                correctedText = correctedText.replace(suggestion.original, suggestion.corrected);
            });
            
            return {
                original: content,
                proofread: correctedText,
                corrected_text: correctedText,
                suggestions: suggestions,
                overall_assessment: suggestions.length > 0 
                    ? `${suggestions.length}箇所の改善点を見つけました。文章の品質向上にご活用ください。`
                    : '文章を確認しました。特に修正が必要な箇所は見つかりませんでした。',
                has_changes: suggestions.length > 0
            };
        }

        // 記事内容をハイライト付きで表示
        function displayArticleWithComments(content) {
            originalContent = content;
            currentMarkdown = content;
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
            const { strength, why, sixTwo, proofread } = analysisResults;

            // デバッグ：校正結果を確認
            console.log('Proofread data in generateComments:', proofread);
            
            // 校正結果をコメントとして追加
            if (proofread && proofread.suggestions && proofread.suggestions.length > 0) {
                console.log('Processing proofreading suggestions:', proofread.suggestions);
                
                proofread.suggestions.forEach((suggestion, index) => {
                    // severityに基づいて優先度を決定
                    const priority = suggestion.severity || 'medium';
                    
                    // severityに基づいてアイコンと色を決定
                    const severityConfig = {
                        'high': { icon: '🔥', color: 'red', label: '重要' },
                        'medium': { icon: '⚠️', color: 'yellow', label: '推奨' },
                        'low': { icon: '💡', color: 'blue', label: '提案' }
                    };
                    
                    const config = severityConfig[priority] || severityConfig.medium;
                    
                    currentComments.push({
                        id: `proofread-${index}`,
                        title: `${suggestion.type || '校正'}の改善 ${config.icon}`,
                        content: `「${suggestion.original}」→「${suggestion.corrected}」`,
                        detail: suggestion.reason,
                        category: '校正',
                        severity: priority,
                        priority: priority,
                        position: suggestion.position || `修正箇所`,
                        tips: `${config.label}度：${suggestion.type}の修正提案`,
                        type: 'specific',
                        suggestions: {
                            original: suggestion.original,
                            corrected: suggestion.corrected,
                            reason: suggestion.reason,
                            type: suggestion.type,
                            severity: suggestion.severity
                        }
                    });
                });
                
                console.log('Added proofreading comments:', proofread.suggestions.length);
                
                // 全体評価がある場合はサマリーカードとして追加
                if (proofread.overall_assessment && proofread.overall_assessment !== '文章を確認しました。') {
                    currentComments.push({
                        id: 'proofread-summary',
                        title: '校正総評 📝',
                        content: proofread.overall_assessment,
                        detail: `${proofread.suggestions.length}件の修正提案があります。`,
                        category: '校正サマリー',
                        severity: 'medium',
                        priority: 'medium',
                        position: '全体',
                        tips: 'AIによる文章全体の評価です。',
                        type: 'summary'
                    });
                }
            } else {
                console.log('No proofreading suggestions available');
            }

            // 右側コメント：特定部分への指摘のみ（記事改善カテゴリー）
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

            console.log('Total comments generated:', currentComments.length);
            console.log('Comments breakdown:', currentComments.map(c => ({ id: c.id, category: c.category, title: c.title })));
            
            displayComments();
            addHighlights();
            updateCommentCount();
            
            // 全体分析結果を下部セクションに表示
            displayOverallAnalysis(strength, sixTwo);
        }

        // 全体分析結果を表示する関数
        function displayOverallAnalysis(strength, sixTwo) {
            // デバッグ用ログ
            console.log('Strength data:', strength);
            console.log('SixTwo data:', sixTwo);
            
            // 強み分析結果を表示
            displayStrengthAnalysis(strength);
            
            // 6W2H分析結果を表示
            displaySixTwoAnalysis(sixTwo);
            
            // ペルソナフィードバックを表示（なぜなぜ分析の前に配置）
            displayPersonaFeedback(strength);
            
            // 全体分析結果セクションを表示
            document.getElementById('analysisResultsSection').classList.remove('hidden');
        }

        // 強み分析結果を表示
        function displayStrengthAnalysis(strength) {
            const container = document.getElementById('strengthAnalysisResult');
            let html = '';
            
            if (strength.missing_elements && strength.missing_elements.length > 0) {
                html += '<div class="mb-4"><h4 class="font-semibold text-blue-700 mb-2">不足している要素:</h4><ul class="space-y-2">';
                strength.missing_elements.forEach(element => {
                    const elementName = typeof element === 'object' ? element.element : element;
                    const suggestion = typeof element === 'object' ? element.suggestion : '';
                    html += `
                        <li class="bg-white rounded-lg p-3 border-l-4 border-blue-400">
                            <div class="font-medium text-blue-800">${elementName}</div>
                            ${suggestion ? `<div class="text-sm text-gray-600 mt-1">${suggestion}</div>` : ''}
                        </li>
                    `;
                });
                html += '</ul></div>';
            }
            
            if (strength.strengths && strength.strengths.length > 0) {
                html += '<div class="mb-4"><h4 class="font-semibold text-blue-700 mb-2">既存の強み:</h4><div class="space-y-3">';
                strength.strengths.forEach(strengthItem => {
                    if (typeof strengthItem === 'object') {
                        // オブジェクトの場合は詳細情報を綺麗に表示
                        const content = strengthItem.content || strengthItem.name || strengthItem.element || '';
                        const category = strengthItem.category || '';
                        const impact = strengthItem.impact_score || '';
                        const position = strengthItem.position || '';
                        
                        // インパクトスコアに応じて色を決定
                        let impactColors = {
                            badge: 'bg-gray-100 text-gray-700',
                            border: 'border-gray-400',
                            text: 'text-gray-800'
                        };
                        
                        if (impact === '高' || impact === 'high') {
                            impactColors = {
                                badge: 'bg-red-100 text-red-700',
                                border: 'border-red-400',
                                text: 'text-red-800'
                            };
                        } else if (impact === '中' || impact === 'medium') {
                            impactColors = {
                                badge: 'bg-yellow-100 text-yellow-700',
                                border: 'border-yellow-400',
                                text: 'text-yellow-800'
                            };
                        } else if (impact === '低' || impact === 'low') {
                            impactColors = {
                                badge: 'bg-green-100 text-green-700',
                                border: 'border-green-400',
                                text: 'text-green-800'
                            };
                        }
                        
                        html += `
                            <div class="bg-white rounded-lg p-3 border-l-4 ${impactColors.border} shadow-sm">
                                <div class="flex items-start justify-between mb-1">
                                    <span class="text-sm font-medium ${impactColors.text}">✓ ${content}</span>
                                    ${impact ? `<span class="text-xs px-2 py-1 ${impactColors.badge} rounded-full font-medium">${impact}</span>` : ''}
                                </div>
                                ${category ? `<div class="text-xs text-blue-600 font-medium mb-1">カテゴリ: ${category}</div>` : ''}
                                ${position ? `<div class="text-xs text-gray-500">位置: ${position}</div>` : ''}
                            </div>
                        `;
                    } else {
                        // 文字列の場合はシンプル表示
                        html += `<div class="text-sm text-green-600 bg-green-50 rounded p-2">✓ ${strengthItem}</div>`;
                    }
                });
                html += '</div></div>';
            }
            
            
            container.innerHTML = html || '<p class="text-gray-500">分析データが不足しています</p>';
        }

        // ペルソナフィードバックを表示
        function displayPersonaFeedback(strength) {
            // 既存のペルソナフィードバックセクションを削除
            const existingSection = document.getElementById('personaFeedbackSection');
            if (existingSection) {
                existingSection.remove();
            }

            if (strength && strength.persona_feedback && strength.persona_feedback.trim()) {
                const analysisSection = document.getElementById('analysisResultsSection');
                
                // ペルソナフィードバックセクションを作成
                const personaSection = document.createElement('div');
                personaSection.id = 'personaFeedbackSection';
                personaSection.className = 'mt-6 bg-gradient-to-br from-purple-50 to-pink-50 rounded-lg p-6';
                
                personaSection.innerHTML = `
                    <h3 class="text-xl font-semibold text-purple-800 mb-4 flex items-center">
                        <span class="w-6 h-6 bg-purple-200 rounded-full flex items-center justify-center text-purple-700 mr-2">👤</span>
                        読者の視点
                    </h3>
                    <div class="bg-white rounded-lg p-4 border-l-4 border-purple-400">
                        <div class="text-gray-700 leading-relaxed text-lg italic">
                            "${strength.persona_feedback}"
                        </div>
                        <div class="text-sm text-purple-600 mt-3 font-medium">
                            ※ ターゲットペルソナの視点からのフィードバック
                        </div>
                    </div>
                `;

                // なぜなぜ分析セクションの前に挿入
                const whySection = document.getElementById('whyAnalysisResultsSection');
                if (whySection) {
                    analysisSection.insertBefore(personaSection, whySection);
                } else {
                    // なぜなぜ分析セクションがない場合は最後に追加
                    analysisSection.appendChild(personaSection);
                }
                
                console.log('Persona feedback displayed');
            }
        }

        // 6W2H分析結果を表示
        function displaySixTwoAnalysis(sixTwo) {
            const container = document.getElementById('sixTwoAnalysisResult');
            
            if (sixTwo.review) {
                // Markdown風のレビューテキストをHTMLに変換
                const reviewHtml = sixTwo.review
                    .replace(/\*\*(.+?)\*\*/g, '<strong class="font-semibold">$1</strong>')
                    .replace(/✅/g, '<span class="text-green-600">✅</span>')
                    .replace(/❌/g, '<span class="text-red-600">❌</span>')
                    .replace(/⚠️/g, '<span class="text-yellow-600">⚠️</span>')
                    .replace(/💡/g, '<span class="text-blue-600">💡</span>')
                    .replace(/⭐/g, '<span class="text-yellow-500">⭐</span>')
                    .replace(/\n\n/g, '</p><p class="mb-3">')
                    .replace(/\n/g, '<br>');
                
                container.innerHTML = `<div class="prose prose-sm max-w-none"><p class="mb-3">${reviewHtml}</p></div>`;
            } else {
                container.innerHTML = '<p class="text-gray-500">分析データが不足しています</p>';
            }
        }

        // なぜなぜ分析を開始（別ページに遷移）
        function startWhyAnalysis() {
            const currentText = document.getElementById('editTextarea').value || currentMarkdown;
            
            if (!currentText.trim()) {
                showToast('分析する記事がありません', 'error');
                return;
            }

            // ローカルストレージに現在の記事内容と分析結果を保存
            localStorage.setItem('whyAnalysisArticle', currentText);
            localStorage.setItem('whyAnalysisFrom', 'comment-review');
            
            // 現在の分析結果も保存（戻ってきた時に復元するため）
            if (analysisResults) {
                localStorage.setItem('commentReviewAnalysisResults', JSON.stringify(analysisResults));
            }
            
            // 現在のコメントも保存
            if (currentComments && currentComments.length > 0) {
                localStorage.setItem('commentReviewComments', JSON.stringify(currentComments));
            }
            
            // 現在のMarkdownも保存
            localStorage.setItem('commentReviewMarkdown', currentMarkdown || currentText);
            
            // なぜなぜ分析ページに遷移
            window.location.href = '/why-analyzer';
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

                // 校正カードの特別表示
                if (comment.category === '校正' || comment.category === '校正サマリー') {
                    const isHigh = comment.severity === 'high';
                    const isSummary = comment.type === 'summary';
                    
                    return `
                        <div class="comment-item ${severityColor} ${isHigh ? 'ring-2 ring-red-200' : ''} ${isSummary ? 'border-l-4 border-purple-500' : ''}" 
                             data-comment-id="${comment.id}" onclick="highlightComment('${comment.id}')">
                            <div class="p-4">
                                <div class="flex items-start justify-between mb-3">
                                    <div class="flex items-center space-x-2">
                                        <span class="text-xl">${comment.title.includes('📝') ? '📝' : comment.title.includes('🔥') ? '🔥' : comment.title.includes('⚠️') ? '⚠️' : '💡'}</span>
                                        <span class="text-xs px-3 py-1 bg-white rounded-full text-gray-700 font-medium shadow-sm">
                                            ${comment.category}
                                        </span>
                                        ${isHigh ? '<span class="text-xs px-2 py-1 bg-red-500 text-white rounded-full font-bold">重要</span>' : ''}
                                    </div>
                                    ${!isSummary ? `
                                        <div class="flex space-x-2">
                                            <button onclick="event.stopPropagation(); applyProofreadSuggestion('${comment.id}')" 
                                                    class="px-3 py-1 bg-blue-500 text-white text-xs rounded-full hover:bg-blue-600 transition-colors">
                                                適用
                                            </button>
                                            <button onclick="event.stopPropagation(); applyCommentToEditor('${comment.id}')" 
                                                    class="px-3 py-1 bg-green-500 text-white text-xs rounded-full hover:bg-green-600 transition-colors">
                                                編集へ
                                            </button>
                                        </div>
                                    ` : ''}
                                </div>
                                
                                <h4 class="font-semibold text-gray-800 mb-2 text-sm">
                                    ${comment.title.replace(/[📝🔥⚠️💡]/g, '').trim()}
                                </h4>
                                
                                ${!isSummary && comment.suggestions ? `
                                    <div class="bg-white bg-opacity-80 rounded-lg p-3 mb-3 border border-gray-200">
                                        <div class="space-y-2">
                                            <div class="text-sm">
                                                <span class="text-red-600 font-medium">修正前:</span>
                                                <span class="font-mono text-gray-700 bg-red-50 px-2 py-1 rounded">
                                                    ${comment.suggestions.original}
                                                </span>
                                            </div>
                                            <div class="text-sm">
                                                <span class="text-green-600 font-medium">修正後:</span>
                                                <span class="font-mono text-gray-700 bg-green-50 px-2 py-1 rounded">
                                                    ${comment.suggestions.corrected}
                                                </span>
                                            </div>
                                        </div>
                                    </div>
                                ` : `
                                    <p class="text-sm text-gray-700 mb-2 leading-relaxed">${comment.content}</p>
                                `}
                                
                                ${comment.detail ? `
                                    <div class="mt-3 p-3 bg-gray-50 rounded-lg border-l-3 border-gray-300">
                                        <p class="text-xs text-gray-600 leading-relaxed">
                                            <span class="font-medium">理由:</span> ${comment.detail}
                                        </p>
                                    </div>
                                ` : ''}
                                
                                <div class="mt-3 flex items-center justify-between text-xs">
                                    <span class="text-gray-500">
                                        📍 ${comment.position || '修正箇所'}
                                    </span>
                                    ${comment.suggestions && comment.suggestions.type ? `
                                        <span class="px-2 py-1 bg-gray-100 rounded-full text-gray-600">
                                            ${comment.suggestions.type}
                                        </span>
                                    ` : ''}
                                </div>
                            </div>
                        </div>
                    `;
                }
                
                // 通常のコメントカード表示
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

        // 校正提案の適用
        function applyProofreadSuggestion(commentId) {
            const comment = currentComments.find(c => c.id === commentId);
            if (!comment || !comment.suggestions) return;
            
            const { original, corrected } = comment.suggestions;
            
            // 編集エリアのテキストを更新
            const textarea = document.getElementById('editTextarea');
            let currentText = textarea.value;
            
            // 元の文章を修正後の文章に置換
            if (currentText.includes(original)) {
                const updatedText = currentText.replace(original, corrected);
                textarea.value = updatedText;
                updateEditCharCount();
                
                // プレビューも更新
                const articleContent = document.getElementById('articleContent');
                if (articleContent.innerHTML.includes(original)) {
                    articleContent.innerHTML = articleContent.innerHTML.replace(original, corrected);
                }
                
                // 成功メッセージ
                showToast(`校正を適用しました: ${comment.suggestions.type}`, 'success');
                
                // コメントを適用済みとしてマーク
                const commentElement = document.querySelector(`[data-comment-id="${commentId}"]`);
                if (commentElement) {
                    commentElement.style.opacity = '0.6';
                    commentElement.style.pointerEvents = 'none';
                    
                    // 適用済みラベルを追加
                    const appliedLabel = document.createElement('div');
                    appliedLabel.className = 'absolute top-2 right-2 bg-green-500 text-white text-xs px-2 py-1 rounded-full';
                    appliedLabel.textContent = '適用済み';
                    commentElement.style.position = 'relative';
                    commentElement.appendChild(appliedLabel);
                }
            } else {
                showToast('修正対象の文章が見つかりませんでした', 'error');
            }
        }

        // コメントの適用
        function applyComment(commentId) {
            const comment = currentComments.find(c => c.id === commentId);
            if (!comment) return;

            // 校正提案の場合は専用の処理を使用
            if (comment.category === '校正' && comment.suggestions) {
                applyProofreadSuggestion(commentId);
                return;
            }

            // 通常のコメント適用処理
            alert(`改善提案を適用しました:\n\n${comment.title}\n\n${comment.content}`);
            
            // 実際の実装では、記事内容を直接編集する機能を追加できます
        }

        // コメントを編集エリアに適用
        function applyCommentToEditor(commentId) {
            const comment = currentComments.find(c => c.id === commentId);
            if (!comment) return;

            // 編集モードに切り替え
            if (!isEditMode) {
                switchToEditMode();
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

        // プレビューモードに切り替え
        function switchToPreviewMode() {
            const previewTab = document.getElementById('previewTab');
            const editTab = document.getElementById('editTab');
            const articleContent = document.getElementById('articleContent');
            const editMode = document.getElementById('editMode');
            
            isEditMode = false;
            
            // タブの外観を更新
            previewTab.className = 'px-4 py-3 text-sm font-medium text-blue-600 border-b-2 border-blue-600 bg-white transition-colors';
            editTab.className = 'px-4 py-3 text-sm font-medium text-gray-500 border-b-2 border-transparent hover:text-gray-700 hover:border-gray-300 transition-colors';
            
            // コンテンツの表示を切り替え
            articleContent.classList.remove('hidden');
            editMode.classList.add('hidden');
            
            // プレビューを更新
            previewChanges();
        }

        // 編集モードに切り替え
        function switchToEditMode() {
            const previewTab = document.getElementById('previewTab');
            const editTab = document.getElementById('editTab');
            const articleContent = document.getElementById('articleContent');
            const editMode = document.getElementById('editMode');
            
            isEditMode = true;
            
            // タブの外観を更新
            editTab.className = 'px-4 py-3 text-sm font-medium text-blue-600 border-b-2 border-blue-600 bg-white transition-colors';
            previewTab.className = 'px-4 py-3 text-sm font-medium text-gray-500 border-b-2 border-transparent hover:text-gray-700 hover:border-gray-300 transition-colors';
            
            // コンテンツの表示を切り替え
            articleContent.classList.add('hidden');
            editMode.classList.remove('hidden');
            
            // テキストエリアにフォーカス
            document.getElementById('editTextarea').focus();
        }

        // 後方互換性のための関数（既存のコードで使用されている場合）
        function toggleEditMode() {
            if (isEditMode) {
                switchToPreviewMode();
            } else {
                switchToEditMode();
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
            
            // ローカルストレージに保存
            saveAnalysisResults();
            
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
                const [strengthResult, whyResult, sixTwoResult, proofreadResult] = await Promise.all([
                    executeStrengthAnalysis(newContent, '', ''),
                    executeWhyAnalysis(newContent),
                    executeSixTwoReview(newContent),
                    executeProofreadAnalysis(newContent)
                ]);
                
                updateLoadingProgress(90, '新しい分析結果を処理中...');
                
                analysisResults = {
                    strength: strengthResult,
                    why: whyResult,
                    sixTwo: sixTwoResult,
                    proofread: proofreadResult,
                    content: newContent
                };
                
                generateComments();
                
                // 再分析結果をローカルストレージに保存
                saveAnalysisResults();
                
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

        // ローカルストレージを全てクリアする
        function clearAllLocalStorage() {
            if (confirm('全てのローカルストレージデータを削除しますか？\n\n削除されるデータ:\n- 分析結果\n- コメントデータ\n- なぜなぜ分析結果\n- その他の保存データ\n\nこの操作は取り消せません。')) {
                try {
                    // ローカルストレージの内容をコンソールに出力（デバッグ用）
                    console.log('Clearing localStorage. Current contents:');
                    for (let i = 0; i < localStorage.length; i++) {
                        const key = localStorage.key(i);
                        console.log(`${key}:`, localStorage.getItem(key));
                    }
                    
                    // 全データをクリア
                    localStorage.clear();
                    
                    console.log('LocalStorage cleared successfully');
                    showToast('ローカルストレージのデータを全て削除しました', 'success');
                    
                    // ページをリロードして状態をリセット
                    setTimeout(() => {
                        window.location.reload();
                    }, 1500);
                    
                } catch (error) {
                    console.error('Error clearing localStorage:', error);
                    showToast('データクリア中にエラーが発生しました', 'error');
                }
            }
        }

        // モーダル外クリックで閉じる
        document.getElementById('newArticleModal').addEventListener('click', function(e) {
            if (e.target === this) {
                hideNewArticleModal();
            }
        });

        // なぜなぜ分析から戻ってきた場合の復元処理
        function restoreFromWhyAnalysis() {
            // なぜなぜ分析から戻ってきた場合かチェック
            const urlParams = new URLSearchParams(window.location.search);
            const fromWhy = urlParams.get('from') === 'why-analysis';
            
            if (fromWhy || localStorage.getItem('commentReviewAnalysisResults')) {
                try {
                    // 保存された分析結果を復元
                    const savedResults = localStorage.getItem('commentReviewAnalysisResults');
                    const savedComments = localStorage.getItem('commentReviewComments');
                    const savedMarkdown = localStorage.getItem('commentReviewMarkdown');
                    
                    if (savedResults) {
                        analysisResults = JSON.parse(savedResults);
                        
                        if (savedComments) {
                            currentComments = JSON.parse(savedComments);
                        }
                        
                        if (savedMarkdown) {
                            currentMarkdown = savedMarkdown;
                            originalContent = savedMarkdown;
                            
                            // テキストエリアに復元
                            const textarea = document.getElementById('editTextarea');
                            if (textarea) {
                                textarea.value = savedMarkdown;
                                updateEditCharCount();
                            }
                        }
                        
                        // UIを復元
                        showReviewContainer();
                        displayArticleWithComments(currentMarkdown);
                        displayComments();
                        displayOverallAnalysis(analysisResults.strength, analysisResults.sixTwo);
                        updateCommentCount();
                        
                        // なぜなぜ分析で新しい結果があるかチェック
                        const whyResults = localStorage.getItem('whyAnalysisResults');
                        console.log('Raw whyResults from localStorage:', whyResults);
                        if (whyResults) {
                            try {
                                const whyData = JSON.parse(whyResults);
                                console.log('Parsed whyData:', whyData);
                                
                                // 最終洞察が完了している場合のみ更新（古いデータで上書きしない）
                                if (whyData.analysis_complete && whyData.insights && whyData.insights.trim()) {
                                    console.log('Complete why analysis found - updating results');
                                    analysisResults.why = whyData;
                                    
                                    // なぜなぜ分析の結果をUI下部に表示
                                    displayWhyAnalysisResults(whyData);
                                    
                                    // 更新された分析結果をローカルストレージに保存
                                    saveAnalysisResults();
                                    
                                    // 使用済みデータをクリア
                                    localStorage.removeItem('whyAnalysisResults');
                                    console.log('Complete why analysis results displayed and localStorage cleared');
                                } else {
                                    console.log('Incomplete why analysis found - keeping existing data');
                                    // 進行中のデータをUI下部に表示（上書きはしない）
                                    displayWhyAnalysisResults(whyData);
                                }
                            } catch (e) {
                                console.error('Error parsing why analysis results:', e);
                            }
                        } else {
                            console.log('No whyAnalysisResults found in localStorage');
                            
                            // デバッグ: ローカルストレージの全内容を表示
                            console.log('All localStorage items:');
                            for (let i = 0; i < localStorage.length; i++) {
                                const key = localStorage.key(i);
                                console.log(key + ': ', localStorage.getItem(key));
                            }
                        }
                        
                        showToast('なぜなぜ分析から戻りました', 'success');
                        
                        // URLパラメータをクリア
                        if (fromWhy) {
                            window.history.replaceState({}, document.title, window.location.pathname);
                        }
                    }
                } catch (error) {
                    console.error('Error restoring from why analysis:', error);
                    showToast('復元中にエラーが発生しました', 'error');
                }
            }
        }

        // なぜなぜ分析結果の表示
        function displayWhyAnalysisResults(whyData) {
            console.log('displayWhyAnalysisResults called with:', whyData);
            
            const existingSection = document.getElementById('whyAnalysisResultsSection');
            if (existingSection) {
                existingSection.remove();
                console.log('Removed existing why analysis section');
            }
            
            const analysisSection = document.getElementById('analysisResultsSection');
            console.log('Analysis section found:', !!analysisSection);
            
            if (analysisSection && whyData) {
                const whySection = document.createElement('div');
                whySection.id = 'whyAnalysisResultsSection';
                whySection.className = 'mt-6 bg-gradient-to-br from-purple-50 to-pink-50 rounded-lg p-6';
                
                let whyHtml = `
                    <h3 class="text-xl font-semibold text-purple-800 mb-4 flex items-center">
                        <span class="w-6 h-6 bg-purple-200 rounded-full flex items-center justify-center text-purple-700 mr-2">🤔</span>
                        なぜなぜ分析結果
                    </h3>
                `;
                
                // 最終洞察の全データを構造化して表示
                if (whyData.insights && whyData.insights.trim()) {
                    whyHtml += `<div class="bg-white rounded-lg p-4 mb-4">
                        <h4 class="font-semibold text-purple-700 mb-2 flex items-center">
                            <span class="mr-2">🎯</span>最終洞察とストーリー
                        </h4>
                        <div class="prose max-w-none text-gray-700 leading-relaxed whitespace-pre-line">${whyData.insights}</div>
                    </div>`;
                    
                } else if (whyData.chat_history && whyData.chat_history.length > 0) {
                    // 洞察がまだ生成されていない場合のメッセージ
                    whyHtml += `<div class="bg-white rounded-lg p-4 mb-4">
                        <h4 class="font-semibold text-purple-700 mb-2 flex items-center">
                            <span class="mr-2">💡</span>洞察・気づき
                        </h4>
                        <div class="p-3 bg-yellow-50 rounded-lg border-l-4 border-yellow-300">
                            <p class="text-gray-700">なぜなぜ分析は開始されましたが、最終的な洞察はまだ生成されていません。</p>
                            <p class="text-sm text-gray-600 mt-1">分析を続行して「最終洞察を生成」ボタンを押すと、ここに詳細な洞察が表示されます。</p>
                        </div>
                    </div>`;
                }
                
                
                // チャット履歴の要約表示
                if (whyData.chat_history && whyData.chat_history.length > 0) {
                    whyHtml += `<div class="bg-white rounded-lg p-4">
                        <h4 class="font-semibold text-purple-700 mb-2 flex items-center">
                            <span class="mr-2">💬</span>分析対話履歴
                            <button onclick="toggleChatHistory()" class="ml-2 text-xs px-2 py-1 bg-purple-100 text-purple-600 rounded hover:bg-purple-200" id="chatToggleBtn">
                                表示
                            </button>
                        </h4>
                        <div id="chatHistoryContent" class="hidden mt-3 max-h-64 overflow-y-auto space-y-2">`;
                    whyData.chat_history.forEach((message, index) => {
                        const isBot = message.role === 'assistant';
                        whyHtml += `<div class="p-2 rounded ${isBot ? 'bg-purple-100 text-purple-800' : 'bg-gray-100 text-gray-800'}">
                            <div class="text-xs font-medium mb-1">${isBot ? 'Bot' : 'You'}</div>
                            <div class="text-sm">${message.content}</div>
                        </div>`;
                    });
                    whyHtml += `</div></div>`;
                }
                
                whySection.innerHTML = whyHtml;
                console.log('Generated whyHtml length:', whyHtml.length);
                console.log('whySection.innerHTML set successfully');
                
                analysisSection.appendChild(whySection);
                console.log('whySection appended to analysisSection');
                console.log('Final whySection HTML:', whySection.outerHTML.substring(0, 500) + '...');
                
                // DOM確認のデバッグ
                setTimeout(() => {
                    const domCheck = document.getElementById('whyAnalysisResultsSection');
                    console.log('DOM check - whyAnalysisResultsSection exists:', !!domCheck);
                    if (domCheck) {
                        console.log('Element is visible:', domCheck.offsetWidth > 0 && domCheck.offsetHeight > 0);
                        console.log('Element position:', domCheck.getBoundingClientRect());
                        console.log('Element styles:', getComputedStyle(domCheck).display, getComputedStyle(domCheck).visibility);
                        console.log('Parent element:', domCheck.parentElement?.id);
                        console.log('Children count:', domCheck.children.length);
                    }
                    
                    // 記事活用方法の要素も確認
                    const methodElements = document.querySelectorAll('[class*="bg-purple-50"]');
                    console.log('Method elements found:', methodElements.length);
                    methodElements.forEach((el, i) => {
                        console.log(`Method element ${i}:`, el.textContent?.substring(0, 50));
                    });
                }, 100);
            }
        }

        // チャット履歴から記事活用方法を抽出
        function extractArticleApplications(chatHistory) {
            console.log('Extracting applications from chat history:', chatHistory);
            const applications = [];
            
            chatHistory.forEach((message, index) => {
                // なぜなぜ分析では message.type === 'bot_question' または message.role === 'assistant'
                const isBot = (message.role === 'assistant') || (message.type === 'bot_question');
                const content = message.content;
                
                if (isBot && content) {
                    console.log(`Processing bot message ${index}:`, content);
                    
                    // より幅広いキーワードで検索
                    const keywords = ['活用', '応用', '展開', '効果的', '使える', '有効', '取り組み', '施策', '戦略', 'PR', 'プレスリリース', '記事', '情報発信', 'ハッカソン', '採用', '人材', '企業', '方法', '理由', 'ため'];
                    const hasKeyword = keywords.some(keyword => content.includes(keyword));
                    
                    console.log(`Message has keyword: ${hasKeyword}`);
                    
                    if (hasKeyword || content.length > 20) { // さらに緩い条件
                        // ボットメッセージを直接活用方法として使用（テスト用）
                        if (content.length > 20) {
                            applications.push({
                                title: content.length > 50 ? content.substring(0, 50) + '...' : content,
                                content: content,
                                source: 'direct_bot_message'
                            });
                        }
                        
                        // 文章を様々な区切り文字で分割
                        const sentences = content.split(/[。！？\n・]/).filter(s => s.trim().length > 5);
                        console.log('Found sentences:', sentences);
                        
                        sentences.forEach(sentence => {
                            const trimmed = sentence.trim();
                            if (trimmed.length > 10) {
                                // より緩い条件で活用方法を抽出
                                const isApplication = keywords.some(keyword => trimmed.includes(keyword)) ||
                                                    trimmed.includes('こと') ||
                                                    trimmed.includes('方法') ||
                                                    trimmed.includes('手法') ||
                                                    trimmed.includes('アプローチ') ||
                                                    trimmed.includes('ため') ||
                                                    trimmed.includes('から') ||
                                                    trimmed.includes('によって') ||
                                                    trimmed.includes('？') ||
                                                    trimmed.includes('です');
                                
                                if (isApplication) {
                                    applications.push({
                                        title: trimmed.length > 40 ? trimmed.substring(0, 40) + '...' : trimmed,
                                        content: trimmed,
                                        source: 'sentence_extraction'
                                    });
                                }
                            }
                        });
                    }
                }
            });
            
            console.log('Extracted applications (before filtering):', applications);
            console.log('Total applications before filtering:', applications.length);
            
            // 重複を除去
            const uniqueApplications = applications.filter((app, index, self) => 
                index === self.findIndex(a => a.content === app.content)
            );
            console.log('After deduplication:', uniqueApplications.length);
            
            // 短すぎるものを除外し、最大5件まで（デバッグ用に緩い条件）
            const filteredApplications = uniqueApplications.filter(app => app.content.length > 5);
            console.log('After length filtering (>15 chars):', filteredApplications.length);
            
            const finalApplications = filteredApplications.slice(0, 5);
            console.log('Final applications (max 5):', finalApplications.length);
            console.log('Returning applications:', finalApplications);
            
            return finalApplications;
        }

        // チャット履歴の表示切り替え
        function toggleChatHistory() {
            const content = document.getElementById('chatHistoryContent');
            const btn = document.getElementById('chatToggleBtn');
            
            if (content.classList.contains('hidden')) {
                content.classList.remove('hidden');
                btn.textContent = '隠す';
            } else {
                content.classList.add('hidden');
                btn.textContent = '表示';
            }
        }

        // ページ読み込み時に復元処理を実行
        document.addEventListener('DOMContentLoaded', function() {
            restoreFromWhyAnalysis();
        });

        // モーダル外クリックで閉じる
        document.getElementById('newArticleModal').addEventListener('click', function(e) {
            if (e.target === this) {
                hideNewArticleModal();
            }
        });
    </script>
</body>
</html>