<!DOCTYPE html>
<html lang="ja">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>AI記事レビュー - PR TIMES</title>
    
    <!-- Tailwind CSS -->
    <script src="https://cdn.tailwindcss.com"></script>
    
    <style>
        /* デザインシステム：統一されたカラーパレット */
        :root {
            --primary-blue: #3b82f6;
            --primary-purple: #8b5cf6;
            --success-green: #10b981;
            --warning-yellow: #f59e0b;
            --error-red: #ef4444;
            --neutral-gray: #6b7280;
            --bg-light: #f8fafc;
            --bg-card: #ffffff;
        }

        /* アニメーション */
        .fade-in {
            animation: fadeIn 0.5s ease-in-out;
        }
        
        .slide-up {
            animation: slideUp 0.6s ease-out;
        }
        
        .pulse-gentle {
            animation: pulseGentle 2s infinite;
        }
        
        @keyframes fadeIn {
            from { opacity: 0; transform: translateY(10px); }
            to { opacity: 1; transform: translateY(0); }
        }
        
        @keyframes slideUp {
            from { opacity: 0; transform: translateY(30px); }
            to { opacity: 1; transform: translateY(0); }
        }
        
        @keyframes pulseGentle {
            0%, 100% { transform: scale(1); opacity: 1; }
            50% { transform: scale(1.05); opacity: 0.8; }
        }

        /* 進捗バーアニメーション */
        .progress-animate {
            transition: width 0.8s cubic-bezier(0.4, 0, 0.2, 1);
        }

        /* カスタムスクロールバー */
        .custom-scroll::-webkit-scrollbar {
            width: 6px;
        }
        
        .custom-scroll::-webkit-scrollbar-track {
            background: #f1f5f9;
            border-radius: 3px;
        }
        
        .custom-scroll::-webkit-scrollbar-thumb {
            background: #cbd5e1;
            border-radius: 3px;
        }
        
        .custom-scroll::-webkit-scrollbar-thumb:hover {
            background: #94a3b8;
        }

        /* ボタンホバーエフェクト */
        .btn-primary {
            background: linear-gradient(135deg, var(--primary-blue) 0%, var(--primary-purple) 100%);
            transition: all 0.3s ease;
        }
        
        .btn-primary:hover {
            transform: translateY(-2px);
            box-shadow: 0 8px 25px rgba(59, 130, 246, 0.3);
        }

        /* 結果カード */
        .result-card {
            background: var(--bg-card);
            border-radius: 12px;
            box-shadow: 0 4px 6px -1px rgba(0, 0, 0, 0.1), 0 2px 4px -1px rgba(0, 0, 0, 0.06);
            transition: all 0.3s ease;
        }
        
        .result-card:hover {
            box-shadow: 0 10px 15px -3px rgba(0, 0, 0, 0.1), 0 4px 6px -2px rgba(0, 0, 0, 0.05);
        }

        /* チャット UI */
        .chat-message {
            animation: messageSlideIn 0.4s ease-out;
        }
        
        @keyframes messageSlideIn {
            from { opacity: 0; transform: translateX(-20px); }
            to { opacity: 1; transform: translateX(0); }
        }

        /* 改善提案クリックエフェクト */
        .suggestion-item {
            cursor: pointer;
            transition: all 0.2s ease;
        }
        
        .suggestion-item:hover {
            background-color: #f0f9ff;
            border-left: 4px solid var(--primary-blue);
        }

        /* ローディング状態の隠し表示 */
        .hidden { display: none; }
        .visible { display: block; }
    </style>
</head>
<body class="bg-gray-50 min-h-screen">
    <!-- ヘッダー：統一感のあるデザイン -->
    <header class="bg-white shadow-sm border-b fade-in">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
            <div class="flex justify-between items-center py-4">
                <div class="flex items-center space-x-4">
                    <div class="w-10 h-10 bg-gradient-to-br from-blue-500 to-purple-600 rounded-lg flex items-center justify-center text-white font-bold text-lg">
                        ✨
                    </div>
                    <div>
                        <h1 class="text-2xl font-bold text-gray-900">AI記事レビュー</h1>
                        <p class="text-sm text-gray-500">強み分析 ✕ なぜなぜ分析 ✕ 6W2Hチェックで記事を改善</p>
                    </div>
                    <span class="px-3 py-1 bg-gradient-to-r from-blue-100 to-purple-100 text-blue-800 text-sm font-medium rounded-full">PR TIMES</span>
                </div>
                <div class="flex space-x-4">
                    <button onclick="showInfo()" class="text-gray-500 hover:text-gray-700 transition-colors">
                        <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                        </svg>
                    </button>
                </div>
            </div>
        </div>
    </header>

    <main class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-8">
        <!-- 入力セクション：直感的で分かりやすい -->
        <div class="result-card p-6 mb-8 slide-up">
            <h2 class="text-xl font-semibold text-gray-800 mb-4 flex items-center">
                <span class="w-8 h-8 bg-blue-100 rounded-full flex items-center justify-center text-blue-600 mr-3">📝</span>
                記事を入力
            </h2>
            
            <form id="reviewForm" onsubmit="startIntegratedReview(event)">
                <div class="mb-6">
                    <label for="content" class="block text-sm font-medium text-gray-700 mb-2">
                        記事全文
                        <span class="text-gray-500 text-xs">（Markdown形式）</span>
                    </label>
                    <textarea 
                        id="content" 
                        name="content"
                        rows="12" 
                        placeholder="# 新商品リリースについて

私たちの会社では、来月に画期的な新商品をリリース予定です。
この商品は従来の課題を解決する独自の機能を持っています。

## 商品の特徴
- 機能A：○○を実現
- 機能B：従来比200%向上
- デザイン：シンプルで使いやすい

開発期間は2年間で、チーム一丸となって取り組んできました..."
                        class="w-full px-4 py-3 border border-gray-300 rounded-lg shadow-sm focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-transparent resize-y custom-scroll"
                        required></textarea>
                    <div class="flex justify-between items-center text-xs text-gray-500 mt-1">
                        <span id="charCount">0文字</span>
                        <span class="text-green-600">💡 詳しく書くほど精度が向上します</span>
                    </div>
                </div>

                <div class="grid grid-cols-1 md:grid-cols-2 gap-4 mb-6">
                    <div>
                        <label for="persona" class="block text-sm font-medium text-gray-700 mb-2">
                            ターゲット読者（任意）
                        </label>
                        <input 
                            type="text" 
                            id="persona" 
                            name="persona"
                            placeholder="例: 26・27卒就活生、ハッカソン好き、内定を探している"
                            class="w-full px-4 py-3 border border-gray-300 rounded-lg shadow-sm focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-transparent"
                            maxlength="500">
                    </div>

                    <div>
                        <label for="releaseType" class="block text-sm font-medium text-gray-700 mb-2">
                            リリースタイプ（任意）
                        </label>
                        <select 
                            id="releaseType" 
                            name="release_type"
                            class="w-full px-4 py-3 border border-gray-300 rounded-lg shadow-sm focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-transparent">
                            <option value="">-- 選択してください --</option>
                        </select>
                    </div>
                </div>
                
                <div class="text-center">
                    <button 
                        type="submit"
                        id="startReviewButton" 
                        class="btn-primary px-8 py-4 text-white rounded-lg font-semibold text-lg focus:outline-none focus:ring-4 focus:ring-blue-300 disabled:opacity-50 disabled:cursor-not-allowed"
                    >
                        ✨ AI レビューを開始
                    </button>
                    <p class="text-sm text-gray-500 mt-2">3つのAI分析を同時実行します</p>
                </div>
            </form>
        </div>

        <!-- 進捗表示セクション：ストレスフリーな待ち時間 -->
        <div id="progressSection" class="hidden">
            <div class="result-card p-6 mb-8">
                <div class="text-center">
                    <div class="w-20 h-20 mx-auto mb-4 bg-gradient-to-br from-blue-400 to-purple-500 rounded-full flex items-center justify-center pulse-gentle">
                        <span class="text-2xl text-white">🤖</span>
                    </div>
                    <h3 class="text-xl font-semibold text-gray-800 mb-2">AIが記事を分析中...</h3>
                    <p class="text-gray-600 mb-6" id="progressMessage">分析準備中です</p>
                    
                    <!-- 進捗バー -->
                    <div class="w-full bg-gray-200 rounded-full h-3 mb-4">
                        <div id="progressBar" class="bg-gradient-to-r from-blue-500 to-purple-600 h-3 rounded-full progress-animate" style="width: 0%"></div>
                    </div>
                    
                    <div class="grid grid-cols-3 gap-4 mt-6">
                        <div class="text-center p-4 bg-blue-50 rounded-lg">
                            <div class="text-lg font-bold text-blue-600" id="strengthProgress">準備中</div>
                            <div class="text-sm text-blue-700">強み分析</div>
                        </div>
                        <div class="text-center p-4 bg-purple-50 rounded-lg">
                            <div class="text-lg font-bold text-purple-600" id="whyProgress">準備中</div>
                            <div class="text-sm text-purple-700">なぜなぜ分析</div>
                        </div>
                        <div class="text-center p-4 bg-green-50 rounded-lg">
                            <div class="text-lg font-bold text-green-600" id="sixTwoProgress">準備中</div>
                            <div class="text-sm text-green-700">6W2Hチェック</div>
                        </div>
                    </div>
                    
                    <!-- 豆知識表示 -->
                    <div class="mt-6 p-4 bg-yellow-50 border-l-4 border-yellow-300 rounded-r-lg">
                        <div class="text-sm text-yellow-800">
                            <span class="font-medium">💡 豆知識：</span>
                            <span id="tipText">記事の質は最初の3行で決まると言われています</span>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- 結果表示セクション：視覚的で分かりやすい -->
        <div id="resultSection" class="hidden space-y-8">
            <!-- サマリーカード -->
            <div class="result-card p-6 slide-up">
                <h2 class="text-xl font-semibold text-gray-800 mb-4 flex items-center">
                    <span class="w-8 h-8 bg-green-100 rounded-full flex items-center justify-center text-green-600 mr-3">📊</span>
                    分析結果サマリー
                </h2>
                <div class="grid grid-cols-2 md:grid-cols-4 gap-4" id="summaryStats"></div>
            </div>

            <!-- 改善提案セクション：ポジティブな表現 -->
            <div class="result-card p-6 slide-up">
                <h2 class="text-xl font-semibold text-gray-800 mb-4 flex items-center">
                    <span class="w-8 h-8 bg-yellow-100 rounded-full flex items-center justify-center text-yellow-600 mr-3">💡</span>
                    改善提案
                    <span class="ml-2 text-sm bg-yellow-100 text-yellow-700 px-2 py-1 rounded-full">クリックして適用</span>
                </h2>
                <div id="suggestionsContainer" class="space-y-3"></div>
            </div>

            <!-- 強み分析結果 -->
            <div class="result-card p-6 slide-up">
                <h2 class="text-xl font-semibold text-gray-800 mb-4 flex items-center">
                    <span class="w-8 h-8 bg-blue-100 rounded-full flex items-center justify-center text-blue-600 mr-3">🌟</span>
                    発見された強み
                </h2>
                <div id="strengthResults" class="space-y-3"></div>
            </div>

            <!-- なぜなぜ分析結果 -->
            <div class="result-card p-6 slide-up">
                <h2 class="text-xl font-semibold text-gray-800 mb-4 flex items-center">
                    <span class="w-8 h-8 bg-purple-100 rounded-full flex items-center justify-center text-purple-600 mr-3">🔍</span>
                    深掘り分析
                </h2>
                <div id="whyResults" class="space-y-4"></div>
            </div>

            <!-- 6W2H分析結果 -->
            <div class="result-card p-6 slide-up">
                <h2 class="text-xl font-semibold text-gray-800 mb-4 flex items-center">
                    <span class="w-8 h-8 bg-green-100 rounded-full flex items-center justify-center text-green-600 mr-3">📋</span>
                    6W2Hチェック
                </h2>
                <div id="sixTwoResults" class="space-y-4"></div>
            </div>

            <!-- 記事編集セクション -->
            <div class="result-card p-6 slide-up">
                <h2 class="text-xl font-semibold text-gray-800 mb-4 flex items-center">
                    <span class="w-8 h-8 bg-green-100 rounded-full flex items-center justify-center text-green-600 mr-3">✏️</span>
                    改善された記事
                </h2>
                <div class="bg-gray-50 rounded-lg p-4">
                    <textarea 
                        id="improvedContent" 
                        rows="15" 
                        class="w-full px-4 py-3 border border-gray-300 rounded-lg resize-y custom-scroll"
                        placeholder="改善提案をクリックすると、ここに反映されます"></textarea>
                    <div class="flex justify-between items-center mt-3">
                        <button onclick="copyToClipboard()" class="text-blue-600 hover:text-blue-800 text-sm">📋 コピー</button>
                        <button onclick="downloadText()" class="text-green-600 hover:text-green-800 text-sm">💾 ダウンロード</button>
                    </div>
                </div>
            </div>

            <!-- 新しいレビュー -->
            <div class="text-center">
                <button onclick="resetReview()" class="btn-primary px-6 py-3 text-white rounded-lg font-semibold">
                    🔄 新しい記事をレビュー
                </button>
            </div>
        </div>
    </main>

    <!-- 使い方モーダル -->
    <div id="infoModal" class="fixed inset-0 bg-black bg-opacity-50 hidden z-50 flex items-center justify-center p-4">
        <div class="bg-white rounded-2xl max-w-2xl w-full p-6 max-h-[90vh] overflow-y-auto">
            <div class="flex justify-between items-center mb-4">
                <h3 class="text-2xl font-bold text-gray-800">AI記事レビューの使い方</h3>
                <button onclick="hideInfo()" class="text-gray-400 hover:text-gray-600 text-2xl">×</button>
            </div>
            <div class="space-y-4 text-sm text-gray-600">
                <div class="bg-blue-50 p-4 rounded-lg">
                    <h4 class="font-semibold text-blue-800 mb-2">📝 このツールについて</h4>
                    <p>記事や企画書の内容を分析し、「強み」と「なぜ」の観点から改善提案を行います。</p>
                </div>
                
                <div class="bg-green-50 p-4 rounded-lg">
                    <h4 class="font-semibold text-green-800 mb-2">🌟 特徴</h4>
                    <ul class="list-disc list-inside space-y-1">
                        <li>直感的で分かりやすいUI</li>
                        <li>ストレスフリーな待ち時間演出</li>
                        <li>建設的で前向きなフィードバック</li>
                        <li>ワンクリックで改善提案を適用</li>
                    </ul>
                </div>
                
                <div class="bg-yellow-50 p-4 rounded-lg">
                    <h4 class="font-semibold text-yellow-800 mb-2">💡 使い方</h4>
                    <ol class="list-decimal list-inside space-y-1">
                        <li>記事や企画の内容を入力</li>
                        <li>ターゲット読者を指定（任意）</li>
                        <li>「AIレビューを開始」をクリック</li>
                        <li>分析結果を確認</li>
                        <li>改善提案をクリックして適用</li>
                    </ol>
                </div>
            </div>
        </div>
    </div>

    <script>
        // グローバル変数
        let currentContent = '';
        let analysisData = {};
        
        // 豆知識リスト
        const tips = [
            '記事の質は最初の3行で決まると言われています',
            '読者が3秒で理解できる見出しが効果的です',
            '数字を使った具体例は信頼性を高めます',
            'ストーリー性のある文章は記憶に残りやすいです',
            '画像や図表は理解度を70%向上させます',
            '短い段落は読みやすさを格段に向上させます'
        ];
        
        // 文字数カウント
        const contentTextarea = document.getElementById('content');
        const charCount = document.getElementById('charCount');
        
        contentTextarea.addEventListener('input', function() {
            const count = this.value.length;
            charCount.textContent = `${count.toLocaleString()} 文字`;
            charCount.style.color = count > 45000 ? 'var(--error-red)' : 'var(--neutral-gray)';
        });

        // ページ読み込み時の初期化
        document.addEventListener('DOMContentLoaded', function() {
            loadReleaseTypes();
            document.getElementById('improvedContent').value = '';
        });

        // リリースタイプの読み込み
        async function loadReleaseTypes() {
            try {
                const response = await fetch('/api/strength-analysis/release-types');
                const result = await response.json();
                const select = document.getElementById('releaseType');
                
                if (result.success) {
                    const releaseTypes = result.data.error ? 
                        (result.data.default_types || []) : 
                        (result.data || []);
                    
                    releaseTypes.forEach(type => {
                        const option = document.createElement('option');
                        option.value = type.name || type.id;
                        option.textContent = type.name;
                        select.appendChild(option);
                    });
                }
            } catch (error) {
                console.error('Release types loading failed:', error);
            }
        }

        // 統合レビュー開始
        async function startIntegratedReview(event) {
            event.preventDefault();
            
            const formData = new FormData(event.target);
            currentContent = formData.get('content').trim();
            const persona = formData.get('persona').trim();
            const releaseType = formData.get('release_type').trim();
            
            if (!currentContent) {
                alert('記事内容を入力してください。');
                return;
            }

            // UIの切り替え
            showProgressSection();
            
            // 3つの分析を並行実行
            try {
                const [strengthResult, whyResult, sixTwoResult] = await Promise.all([
                    executeStrengthAnalysis(currentContent, persona, releaseType),
                    executeWhyAnalysis(currentContent),
                    executeSixTwoReview(currentContent)
                ]);
                
                // 結果をマージして表示
                analysisData = { 
                    strength: strengthResult, 
                    why: whyResult, 
                    sixTwo: sixTwoResult 
                };
                displayIntegratedResults();
                
            } catch (error) {
                console.error('Analysis failed:', error);
                showError('分析中にエラーが発生しました。もう一度お試しください。');
            }
        }

        // 強み分析実行
        async function executeStrengthAnalysis(content, persona, releaseType) {
            updateProgress('強み分析実行中...', 20, '分析中', '準備中', '準備中');
            
            const response = await fetch('/api/strength-analysis/analyze', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content')
                },
                body: JSON.stringify({ content, persona, release_type: releaseType })
            });
            
            const result = await response.json();
            updateProgress('強み分析完了！', 40, '完了 ✓', '準備中', '準備中');
            
            if (!result.success) {
                throw new Error(result.message || '強み分析に失敗しました');
            }
            
            return result.data;
        }

        // なぜなぜ分析実行
        async function executeWhyAnalysis(content) {
            updateProgress('なぜなぜ分析実行中...', 60, '完了 ✓', '分析中', '準備中');
            
            const response = await fetch('/api/why-analysis/start', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content')
                },
                body: JSON.stringify({ content })
            });
            
            const result = await response.json();
            
            if (result.success) {
                // チャット履歴を作成（初期質問として）
                const chatHistory = [
                    {
                        type: 'bot_question',
                        content: result.data.bot_response || 'なぜなぜ分析を開始しました',
                        timestamp: new Date().toISOString()
                    }
                ];
                
                // 自動的に洞察生成まで実行
                const insightResponse = await fetch('/api/why-analysis/insight', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content')
                    },
                    body: JSON.stringify({
                        content: content,
                        chat_history: chatHistory,
                        session_id: result.session_id
                    })
                });
                
                const insightResult = await insightResponse.json();
                updateProgress('分析完了！', 100, '完了 ✓', '完了 ✓');
                
                if (insightResult.success) {
                    return insightResult.data;
                } else {
                    throw new Error(insightResult.message || 'なぜなぜ分析に失敗しました');
                }
            } else {
                throw new Error(result.message || 'なぜなぜ分析の開始に失敗しました');
            }
        }

        // 6W2H分析実行
        async function executeSixTwoReview(content) {
            updateProgress('6W2Hレビュー実行中...', 80, '完了 ✓', '完了 ✓', '分析中');
            
            const response = await fetch('/api/sixtwo-review', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content')
                },
                body: JSON.stringify({ content })
            });
            
            const result = await response.json();
            updateProgress('全分析完了！', 100, '完了 ✓', '完了 ✓', '完了 ✓');
            
            if (!result.success) {
                throw new Error(result.message || '6W2Hレビューに失敗しました');
            }
            
            return result.data;
        }

        // 進捗更新
        function updateProgress(message, percentage, strengthStatus, whyStatus, sixTwoStatus = '準備中') {
            document.getElementById('progressMessage').textContent = message;
            document.getElementById('progressBar').style.width = percentage + '%';
            document.getElementById('strengthProgress').textContent = strengthStatus;
            document.getElementById('whyProgress').textContent = whyStatus;
            document.getElementById('sixTwoProgress').textContent = sixTwoStatus;
            
            // 豆知識をランダム表示
            if (percentage < 90) {
                const randomTip = tips[Math.floor(Math.random() * tips.length)];
                document.getElementById('tipText').textContent = randomTip;
            }
        }

        // 進捗セクション表示
        function showProgressSection() {
            document.getElementById('progressSection').classList.remove('hidden');
            document.getElementById('resultSection').classList.add('hidden');
            
            // ボタン無効化
            const button = document.getElementById('startReviewButton');
            button.disabled = true;
            button.textContent = '分析実行中...';
        }

        // 統合結果表示
        function displayIntegratedResults() {
            const { strength, why, sixTwo } = analysisData;
            
            // サマリー統計
            displaySummaryStats(strength, why, sixTwo);
            
            // 改善提案（統合）
            displayIntegratedSuggestions(strength, why, sixTwo);
            
            // 強み結果
            displayStrengthResults(strength);
            
            // なぜなぜ結果
            displayWhyResults(why);
            
            // 6W2H結果
            displaySixTwoResults(sixTwo);
            
            // 記事エディタに元内容設定
            document.getElementById('improvedContent').value = currentContent;
            
            // UI切り替え
            document.getElementById('progressSection').classList.add('hidden');
            document.getElementById('resultSection').classList.remove('hidden');
            
            // ボタン復帰
            const button = document.getElementById('startReviewButton');
            button.disabled = false;
            button.textContent = '✨ AI レビューを開始';
            
            // 結果までスクロール
            document.getElementById('resultSection').scrollIntoView({ behavior: 'smooth' });
        }

        // サマリー統計表示
        function displaySummaryStats(strength, why, sixTwo) {
            const container = document.getElementById('summaryStats');
            
            const stats = [
                { 
                    value: strength.summary?.total_strengths || 0, 
                    label: '発見された強み', 
                    color: 'blue',
                    icon: '🌟'
                },
                { 
                    value: strength.summary?.high_impact_count || 0, 
                    label: '高インパクト要素', 
                    color: 'red',
                    icon: '🔥'
                },
                { 
                    value: why.hidden_values?.length || 0, 
                    label: '隠れた価値', 
                    color: 'purple',
                    icon: '💎'
                },
                { 
                    value: sixTwo ? 1 : 0, 
                    label: '6W2Hチェック', 
                    color: 'green',
                    icon: '📋'
                }
            ];
            
            container.innerHTML = stats.map(stat => `
                <div class="text-center p-4 bg-${stat.color}-50 rounded-lg">
                    <div class="text-2xl mb-1">${stat.icon}</div>
                    <div class="text-2xl font-bold text-${stat.color}-600">${stat.value}</div>
                    <div class="text-sm text-${stat.color}-700">${stat.label}</div>
                </div>
            `).join('');
        }

        // 統合改善提案表示
        function displayIntegratedSuggestions(strength, why, sixTwo) {
            const container = document.getElementById('suggestionsContainer');
            const suggestions = [];
            
            // 強み分析からの提案
            if (strength.missing_elements) {
                strength.missing_elements.forEach(element => {
                    suggestions.push({
                        type: 'strength',
                        title: `${element.element}要素の追加`,
                        content: element.suggestion,
                        improvement: `「${element.element}」の観点を記事に追加することで、より魅力的になります。`,
                        color: 'blue'
                    });
                });
            }
            
            // なぜなぜ分析からの提案
            if (why.pr_recommendations) {
                why.pr_recommendations.forEach(rec => {
                    suggestions.push({
                        type: 'why',
                        title: 'PR活用のポイント',
                        content: rec,
                        improvement: `この視点を記事に反映することで、より訴求力が高まります。`,
                        color: 'purple'
                    });
                });
            }
            
            // 記事活用例からの提案
            if (why.article_applications) {
                why.article_applications.forEach(app => {
                    if (app.after_example || app.suggestion) {
                        suggestions.push({
                            type: 'application',
                            title: `${app.section}の改善`,
                            content: app.after_example || app.suggestion,
                            improvement: app.reason || '記事の品質向上に寄与します。',
                            color: 'green'
                        });
                    }
                });
            }
            
            // 6W2H分析からの提案
            if (sixTwo && sixTwo.review) {
                suggestions.push({
                    type: 'sixtwo',
                    title: '6W2H構成チェック',
                    content: sixTwo.review.substring(0, 200) + (sixTwo.review.length > 200 ? '...' : ''),
                    improvement: '記事の情報網羅性をチェックし、読者が知りたい情報が含まれているか確認します。',
                    color: 'green'
                });
            }
            
            if (suggestions.length === 0) {
                container.innerHTML = '<p class="text-green-600">✨ 素晴らしい記事です！大きな改善点は見つかりませんでした。</p>';
                return;
            }
            
            container.innerHTML = suggestions.map((suggestion, index) => `
                <div class="suggestion-item border-l-4 border-${suggestion.color}-300 bg-${suggestion.color}-50 p-4 rounded-r-lg" 
                     onclick="applySuggestion('${escapeHtml(suggestion.content)}', '${escapeHtml(suggestion.improvement)}')">
                    <div class="flex justify-between items-start">
                        <div class="flex-1">
                            <h4 class="font-medium text-${suggestion.color}-800">${suggestion.title}</h4>
                            <p class="text-sm text-${suggestion.color}-700 mt-1">${suggestion.content}</p>
                            <p class="text-xs text-${suggestion.color}-600 mt-2">${suggestion.improvement}</p>
                        </div>
                        <div class="ml-4 text-${suggestion.color}-500 cursor-pointer">
                            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6v6m0 0v6m0-6h6m-6 0H6"></path>
                            </svg>
                        </div>
                    </div>
                </div>
            `).join('');
        }

        // 強み結果表示
        function displayStrengthResults(strength) {
            const container = document.getElementById('strengthResults');
            
            if (!strength.strengths || strength.strengths.length === 0) {
                container.innerHTML = '<p class="text-gray-500">強みが検出されませんでした。</p>';
                return;
            }
            
            container.innerHTML = strength.strengths.map(str => {
                const impactColor = str.impact_score === '高' ? 'red' : 
                                  str.impact_score === '中' ? 'yellow' : 'green';
                
                return `
                    <div class="border-l-4 border-${impactColor}-400 bg-${impactColor}-50 p-4 rounded-r-lg">
                        <div class="flex justify-between items-start mb-2">
                            <span class="px-2 py-1 bg-blue-100 text-blue-800 text-xs rounded-full">${str.category}</span>
                            <div class="flex space-x-2">
                                <span class="px-2 py-1 bg-gray-100 text-gray-700 text-xs rounded-full">インパクト: ${str.impact_score}</span>
                                <span class="px-2 py-1 bg-gray-100 text-gray-700 text-xs rounded-full">${str.type}</span>
                            </div>
                        </div>
                        <div class="font-medium text-gray-800 mb-1">${str.content}</div>
                        <div class="text-xs text-gray-500">位置: ${str.position}</div>
                    </div>
                `;
            }).join('');
        }

        // なぜなぜ結果表示
        function displayWhyResults(why) {
            const container = document.getElementById('whyResults');
            
            const sections = [
                { title: '💡 本質的な洞察', content: why.final_insight, color: 'purple' },
                { title: '📖 ストーリー要素', content: why.story_elements, color: 'green', isList: true },
                { title: '💎 隠れた価値', content: why.hidden_values, color: 'yellow', isList: true },
                { title: '❤️ 感情フック', content: why.emotional_hooks, color: 'pink', isList: true }
            ];
            
            container.innerHTML = sections.map(section => {
                if (!section.content) return '';
                
                const contentHtml = section.isList ? 
                    `<ul class="list-disc list-inside space-y-1">${section.content.map(item => `<li>${item}</li>`).join('')}</ul>` :
                    `<p>${section.content}</p>`;
                
                return `
                    <div class="bg-${section.color}-50 border-l-4 border-${section.color}-300 p-4 rounded-r-lg">
                        <h4 class="font-medium text-${section.color}-800 mb-2">${section.title}</h4>
                        <div class="text-sm text-${section.color}-700">${contentHtml}</div>
                    </div>
                `;
            }).filter(section => section).join('');
        }

        // 6W2H結果表示
        function displaySixTwoResults(sixTwo) {
            const container = document.getElementById('sixTwoResults');
            
            if (!sixTwo || !sixTwo.review) {
                container.innerHTML = '<p class="text-gray-500">6W2Hレビューが取得できませんでした。</p>';
                return;
            }
            
            // AIレビューの内容を構造化して表示
            const reviewText = sixTwo.review;
            
            container.innerHTML = `
                <div class="bg-green-50 border-l-4 border-green-300 p-4 rounded-r-lg">
                    <h4 class="font-medium text-green-800 mb-2">📋 AI による6W2Hレビュー</h4>
                    <div class="text-sm text-green-700 whitespace-pre-line">${escapeHtml(reviewText)}</div>
                </div>
                
                <div class="bg-blue-50 border-l-4 border-blue-300 p-4 rounded-r-lg mt-4">
                    <h4 class="font-medium text-blue-800 mb-2">💡 6W2Hとは</h4>
                    <div class="text-sm text-blue-700">
                        <ul class="grid grid-cols-2 gap-2 list-disc list-inside">
                            <li><strong>誰が</strong> (Who) - 主体・対象者</li>
                            <li><strong>何を</strong> (What) - 内容・商品</li>
                            <li><strong>いつ</strong> (When) - 時期・期間</li>
                            <li><strong>どこで</strong> (Where) - 場所・市場</li>
                            <li><strong>なぜ</strong> (Why) - 理由・目的</li>
                            <li><strong>どのように</strong> (How) - 方法・手段</li>
                            <li><strong>いくらで</strong> (How much) - 価格・費用</li>
                            <li><strong>どのくらい</strong> (How many) - 数量・規模</li>
                        </ul>
                    </div>
                </div>
            `;
        }

        // 改善提案適用
        function applySuggestion(suggestion, improvement) {
            const editor = document.getElementById('improvedContent');
            const currentText = editor.value;
            
            // 簡単な適用ロジック（実際にはより高度な処理が必要）
            const appliedText = currentText + '\n\n' + 
                '## 💡 改善提案を反映\n' + suggestion + '\n' +
                '> ' + improvement;
            
            editor.value = appliedText;
            editor.scrollTop = editor.scrollHeight;
            
            // フィードバック
            showToast('改善提案を適用しました！', 'success');
        }

        // ユーティリティ関数
        function escapeHtml(text) {
            const div = document.createElement('div');
            div.textContent = text;
            return div.innerHTML;
        }

        function showError(message) {
            showToast(message, 'error');
            // ボタン復帰
            const button = document.getElementById('startReviewButton');
            button.disabled = false;
            button.textContent = '✨ AI レビューを開始';
            document.getElementById('progressSection').classList.add('hidden');
        }

        function showToast(message, type = 'info') {
            const toast = document.createElement('div');
            toast.className = `fixed top-4 right-4 p-4 rounded-lg text-white z-50 ${
                type === 'success' ? 'bg-green-500' : 
                type === 'error' ? 'bg-red-500' : 'bg-blue-500'
            }`;
            toast.textContent = message;
            document.body.appendChild(toast);
            
            setTimeout(() => {
                toast.remove();
            }, 3000);
        }

        function copyToClipboard() {
            const content = document.getElementById('improvedContent').value;
            navigator.clipboard.writeText(content).then(() => {
                showToast('クリップボードにコピーしました！', 'success');
            });
        }

        function downloadText() {
            const content = document.getElementById('improvedContent').value;
            const blob = new Blob([content], { type: 'text/plain' });
            const url = URL.createObjectURL(blob);
            const a = document.createElement('a');
            a.href = url;
            a.download = 'improved_article.md';
            a.click();
            URL.revokeObjectURL(url);
            showToast('ファイルをダウンロードしました！', 'success');
        }

        function resetReview() {
            document.getElementById('reviewForm').reset();
            document.getElementById('improvedContent').value = '';
            document.getElementById('resultSection').classList.add('hidden');
            charCount.textContent = '0文字';
            window.scrollTo({ top: 0, behavior: 'smooth' });
        }

        // モーダル操作
        function showInfo() {
            document.getElementById('infoModal').classList.remove('hidden');
        }

        function hideInfo() {
            document.getElementById('infoModal').classList.add('hidden');
        }

        // モーダル外クリックで閉じる
        document.getElementById('infoModal').addEventListener('click', function(e) {
            if (e.target === this) {
                hideInfo();
            }
        });
    </script>
</body>
</html>