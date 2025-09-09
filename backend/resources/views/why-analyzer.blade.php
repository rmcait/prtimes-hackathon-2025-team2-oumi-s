<!DOCTYPE html>
<html lang="ja">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>なぜなぜ分析チャットbot - PR TIMES</title>
    
    <!-- Tailwind CSS -->
    <script src="https://cdn.tailwindcss.com"></script>
    
    <style>
        .chat-container {
            height: 400px;
            overflow-y: auto;
        }
        .chat-message {
            animation: fadeIn 0.3s ease-in;
        }
        .chat-message.bot {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
        }
        .chat-message.user {
            background: linear-gradient(135deg, #f093fb 0%, #f5576c 100%);
            color: white;
        }
        .typing-indicator {
            display: none;
        }
        .typing-indicator.active {
            display: block;
            animation: pulse 1.5s infinite;
        }
        @keyframes fadeIn {
            from { opacity: 0; transform: translateY(10px); }
            to { opacity: 1; transform: translateY(0); }
        }
        @keyframes pulse {
            0%, 100% { opacity: 1; }
            50% { opacity: 0.5; }
        }
        .analysis-stage {
            background: linear-gradient(135deg, #4facfe 0%, #00f2fe 100%);
        }
    </style>
</head>
<body class="bg-gray-50 min-h-screen">
    <!-- ヘッダー -->
    <header class="bg-white shadow-sm border-b">
        <div class="max-w-4xl mx-auto px-4 sm:px-6 lg:px-8">
            <div class="flex justify-between items-center py-4">
                <div class="flex items-center">
                    <h1 class="text-2xl font-bold text-gray-900">なぜなぜ分析チャットbot</h1>
                    <span class="ml-3 px-3 py-1 bg-purple-100 text-purple-800 text-sm font-medium rounded-full">PR TIMES</span>
                </div>
                <div class="flex space-x-4">
                    <a href="#" class="text-gray-500 hover:text-gray-700" onclick="showInfo()">使い方</a>
                    <a href="/api/why-analysis/health" target="_blank" class="text-gray-500 hover:text-gray-700">API状態</a>
                </div>
            </div>
        </div>
    </header>

    <main class="max-w-4xl mx-auto px-4 sm:px-6 lg:px-8 py-8">
        <!-- 入力セクション -->
        <div class="bg-white rounded-lg shadow-lg p-6 mb-8" id="inputSection">
            <h2 class="text-xl font-semibold text-gray-800 mb-4">記事・企画内容を入力してください</h2>
            
            <form id="analysisForm" onsubmit="startWhyAnalysis(event)">
                <div class="mb-6">
                    <label for="content" class="block text-sm font-medium text-gray-700 mb-2">
                        記事全文・企画書・アイデア等
                        <span class="text-gray-500 text-xs">* 最大50,000文字</span>
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
                        class="w-full px-3 py-2 border border-gray-300 rounded-md shadow-sm focus:outline-none focus:ring-purple-500 focus:border-purple-500 resize-y"
                        required
                        maxlength="50000"></textarea>
                    <div class="text-right text-xs text-gray-500 mt-1">
                        <span id="charCount">0</span> / 50,000 文字
                    </div>
                </div>
                
                <div class="flex justify-end">
                    <button 
                        type="submit"
                        id="startButton" 
                        class="px-6 py-2 bg-purple-600 text-white rounded-md hover:bg-purple-700 focus:outline-none focus:ring-2 focus:ring-purple-500 disabled:opacity-50 disabled:cursor-not-allowed"
                    >
                        なぜなぜ分析を開始
                    </button>
                </div>
            </form>
        </div>

        <!-- チャットセクション -->
        <div class="bg-white rounded-lg shadow-lg p-6 mb-8 hidden" id="chatSection">
            <div class="flex items-center justify-between mb-4">
                <h2 class="text-xl font-semibold text-gray-800">なぜなぜ分析セッション</h2>
                <div class="analysis-stage px-3 py-1 rounded-full text-white text-sm" id="analysisStage">
                    ステージ 1
                </div>
            </div>

            <!-- チャット表示エリア -->
            <div class="chat-container border rounded-lg p-4 bg-gray-50 mb-4" id="chatContainer">
                <div class="text-center text-gray-500 text-sm" id="chatPlaceholder">
                    チャットが開始されると、ここに会話が表示されます
                </div>
            </div>

            <!-- タイピング中インディケータ -->
            <div class="typing-indicator mb-4" id="typingIndicator">
                <div class="flex items-center space-x-2 text-gray-500">
                    <div class="w-2 h-2 bg-gray-400 rounded-full animate-bounce"></div>
                    <div class="w-2 h-2 bg-gray-400 rounded-full animate-bounce" style="animation-delay: 0.1s;"></div>
                    <div class="w-2 h-2 bg-gray-400 rounded-full animate-bounce" style="animation-delay: 0.2s;"></div>
                    <span class="ml-2">botが考えています...</span>
                </div>
            </div>

            <!-- 回答入力エリア -->
            <div class="flex space-x-3" id="responseArea" style="display: none;">
                <input 
                    type="text" 
                    id="userResponse" 
                    placeholder="回答を入力してください..."
                    class="flex-1 px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-purple-500 focus:border-purple-500"
                    maxlength="1000">
                <button 
                    onclick="sendResponse()"
                    id="sendButton"
                    class="px-4 py-2 bg-purple-600 text-white rounded-md hover:bg-purple-700 focus:outline-none focus:ring-2 focus:ring-purple-500 disabled:opacity-50"
                >
                    送信
                </button>
            </div>

            <!-- アクションボタン -->
            <div class="mt-4" id="actionButtons" style="display: none;">
                <div class="bg-blue-50 border-l-4 border-blue-400 p-4 mb-3">
                    <div class="text-sm text-blue-700">
                        <strong>3回以上の分析が完了しました！</strong><br>
                        さらに深掘りを続けるか、現在の分析から最終洞察を生成できます。
                    </div>
                </div>
                <div class="flex space-x-3">
                    <button 
                        onclick="generateFinalInsight()"
                        class="px-4 py-2 bg-green-600 text-white rounded-md hover:bg-green-700 focus:outline-none focus:ring-2 focus:ring-green-500"
                    >
                        🎯 最終洞察を生成
                    </button>
                    <button 
                        onclick="resetChat()"
                        class="px-4 py-2 bg-gray-600 text-white rounded-md hover:bg-gray-700 focus:outline-none focus:ring-2 focus:ring-gray-500"
                    >
                        🔄 新しい分析を開始
                    </button>
                </div>
            </div>
        </div>

        <!-- 最終洞察セクション -->
        <div class="bg-white rounded-lg shadow-lg p-6 hidden" id="insightSection">
            <h2 class="text-xl font-semibold text-gray-800 mb-4">🎯 最終洞察とストーリー</h2>
            <div id="insightContent"></div>
        </div>
    </main>

    <!-- 使い方モーダル -->
    <div id="infoModal" class="fixed inset-0 bg-gray-600 bg-opacity-50 hidden z-50">
        <div class="flex items-center justify-center min-h-screen p-4">
            <div class="bg-white rounded-lg max-w-2xl w-full p-6">
                <div class="flex justify-between items-center mb-4">
                    <h3 class="text-lg font-semibold">なぜなぜ分析チャットbotの使い方</h3>
                    <button onclick="hideInfo()" class="text-gray-400 hover:text-gray-600">
                        <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path>
                        </svg>
                    </button>
                </div>
                <div class="space-y-4 text-sm text-gray-600">
                    <p><strong>このツールについて：</strong><br>
                    記事や企画の独自性がありそうな要素について「なぜ？」を繰り返し聞くことで、商品・イベント・企画の本質的なストーリーや魅力を深掘りします。</p>
                    
                    <p><strong>使い方：</strong><br>
                    1. 記事や企画の内容を入力<br>
                    2. botが独自性のある要素を特定し、「なぜ？」を質問<br>
                    3. 質問に答えてさらに深掘り<br>
                    4. 5回程度「なぜ」を繰り返す<br>
                    5. 最終的な洞察とストーリーを確認</p>
                    
                    <p><strong>期待される効果：</strong><br>
                    • 気づかなかった価値の発見<br>
                    • 本質的なストーリーの明確化<br>
                    • PR活用のためのポイント整理<br>
                    • 感情に訴える要素の特定</p>
                    
                    <p><strong>参考：</strong><br>
                    <a href="https://www.keyence.co.jp/ss/general/manufacture-tips/5whys.jsp" target="_blank" class="text-blue-600 hover:underline">なぜなぜ分析について</a></p>
                </div>
            </div>
        </div>
    </div>

    <script>
        let currentSession = null;
        let chatHistory = [];
        let originalContent = '';

        // 文字数カウント
        const contentTextarea = document.getElementById('content');
        const charCount = document.getElementById('charCount');
        
        contentTextarea.addEventListener('input', function() {
            charCount.textContent = this.value.length.toLocaleString();
        });

        // 情報モーダル
        function showInfo() {
            document.getElementById('infoModal').classList.remove('hidden');
        }
        
        function hideInfo() {
            document.getElementById('infoModal').classList.add('hidden');
        }

        // なぜなぜ分析開始
        async function startWhyAnalysis(event) {
            event.preventDefault();
            
            const content = document.getElementById('content').value.trim();
            if (!content) {
                alert('内容を入力してください。');
                return;
            }

            originalContent = content;
            chatHistory = [];

            // UI状態変更
            document.getElementById('startButton').disabled = true;
            document.getElementById('startButton').textContent = '分析開始中...';
            document.getElementById('typingIndicator').classList.add('active');
            
            // チャットセクション表示
            document.getElementById('chatSection').classList.remove('hidden');
            document.getElementById('chatPlaceholder').style.display = 'none';

            try {
                const response = await fetch('/api/why-analysis/start', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'Accept': 'application/json',
                        'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content')
                    },
                    body: JSON.stringify({
                        content: content
                    })
                });

                if (!response.ok) {
                    const errorText = await response.text();
                    throw new Error(`API request failed with status ${response.status}: ${errorText}`);
                }

                const result = await response.json();

                if (result.success) {
                    currentSession = result.session_id;
                    addBotMessage(result.data.bot_response);
                    updateAnalysisStage(result.data.analysis_stage);
                    console.log('Initial analysis stage:', result.data.analysis_stage, 'Minimum reached:', result.data.minimum_reached);
                    showResponseArea();
                } else {
                    throw new Error(result.message || 'なぜなぜ分析の開始に失敗しました');
                }

            } catch (error) {
                console.error('Error:', error);
                alert('なぜなぜ分析でエラーが発生しました: ' + error.message);
            } finally {
                // UI状態復帰
                document.getElementById('startButton').disabled = false;
                document.getElementById('startButton').textContent = 'なぜなぜ分析を開始';
                document.getElementById('typingIndicator').classList.remove('active');
            }
        }

        // 回答送信
        async function sendResponse() {
            const userResponse = document.getElementById('userResponse').value.trim();
            if (!userResponse) {
                alert('回答を入力してください。');
                return;
            }

            // ユーザーメッセージを表示
            addUserMessage(userResponse);
            document.getElementById('userResponse').value = '';
            hideResponseArea();
            document.getElementById('typingIndicator').classList.add('active');

            // チャット履歴に追加
            chatHistory.push({
                type: 'user_response',
                content: userResponse,
                timestamp: new Date().toISOString()
            });

            try {
                const response = await fetch('/api/why-analysis/continue', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'Accept': 'application/json',
                        'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content')
                    },
                    body: JSON.stringify({
                        content: originalContent,
                        chat_history: chatHistory,
                        user_response: userResponse,
                        session_id: currentSession
                    })
                });

                if (!response.ok) {
                    const errorText = await response.text();
                    throw new Error(`API request failed with status ${response.status}: ${errorText}`);
                }

                const result = await response.json();

                if (result.success) {
                    addBotMessage(result.data.bot_response);
                    updateAnalysisStage(result.data.analysis_stage);
                    
                    // チャット履歴に追加
                    chatHistory.push({
                        type: 'bot_question',
                        content: result.data.bot_response,
                        timestamp: new Date().toISOString()
                    });

                    // 分析段階に応じてUIを調整
                    console.log('Analysis stage:', result.data.analysis_stage, 'Minimum reached:', result.data.minimum_reached);
                    
                    if (result.data.minimum_reached) {
                        // 最低回数に達したら、継続と洞察生成の両方を表示
                        showBothOptions();
                    } else {
                        // まだ最低回数に達していない場合は継続のみ
                        showResponseArea();
                    }
                } else {
                    throw new Error(result.message || 'なぜなぜ分析の継続に失敗しました');
                }

            } catch (error) {
                console.error('Error:', error);
                alert('なぜなぜ分析でエラーが発生しました: ' + error.message);
                showResponseArea();
            } finally {
                document.getElementById('typingIndicator').classList.remove('active');
            }
        }

        // 最終洞察生成
        async function generateFinalInsight() {
            document.getElementById('typingIndicator').classList.add('active');
            hideActionButtons();

            try {
                const response = await fetch('/api/why-analysis/insight', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'Accept': 'application/json',
                        'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content')
                    },
                    body: JSON.stringify({
                        content: originalContent,
                        chat_history: chatHistory,
                        session_id: currentSession
                    })
                });

                if (!response.ok) {
                    const errorText = await response.text();
                    throw new Error(`API request failed with status ${response.status}: ${errorText}`);
                }

                const result = await response.json();

                if (result.success) {
                    displayFinalInsight(result.data);
                    showActionButtons();
                } else {
                    throw new Error(result.message || '最終洞察の生成に失敗しました');
                }

            } catch (error) {
                console.error('Error:', error);
                alert('最終洞察の生成でエラーが発生しました: ' + error.message);
                showActionButtons();
            } finally {
                document.getElementById('typingIndicator').classList.remove('active');
            }
        }

        // UI操作関数
        function addBotMessage(message) {
            const chatContainer = document.getElementById('chatContainer');
            const messageDiv = document.createElement('div');
            messageDiv.className = 'chat-message bot p-3 rounded-lg mb-3 max-w-3xl';
            messageDiv.innerHTML = `
                <div class="flex items-start space-x-2">
                    <div class="w-8 h-8 bg-white bg-opacity-20 rounded-full flex items-center justify-center text-xs">🤖</div>
                    <div class="flex-1">${escapeHtml(message)}</div>
                </div>
            `;
            chatContainer.appendChild(messageDiv);
            chatContainer.scrollTop = chatContainer.scrollHeight;
        }

        function addUserMessage(message) {
            const chatContainer = document.getElementById('chatContainer');
            const messageDiv = document.createElement('div');
            messageDiv.className = 'chat-message user p-3 rounded-lg mb-3 max-w-3xl ml-auto';
            messageDiv.innerHTML = `
                <div class="flex items-start space-x-2 justify-end">
                    <div class="flex-1 text-right">${escapeHtml(message)}</div>
                    <div class="w-8 h-8 bg-white bg-opacity-20 rounded-full flex items-center justify-center text-xs">👤</div>
                </div>
            `;
            chatContainer.appendChild(messageDiv);
            chatContainer.scrollTop = chatContainer.scrollHeight;
        }

        function updateAnalysisStage(stage) {
            document.getElementById('analysisStage').textContent = `ステージ ${stage}`;
        }

        function showResponseArea() {
            document.getElementById('responseArea').style.display = 'flex';
            document.getElementById('actionButtons').style.display = 'none';
            document.getElementById('userResponse').placeholder = '回答を入力してください...';
            document.getElementById('userResponse').focus();
        }

        function hideResponseArea() {
            document.getElementById('responseArea').style.display = 'none';
        }

        function showActionButtons() {
            document.getElementById('actionButtons').style.display = 'flex';
        }

        function hideActionButtons() {
            document.getElementById('actionButtons').style.display = 'none';
        }

        function displayFinalInsight(data) {
            const container = document.getElementById('insightContent');
            container.innerHTML = `
                <div class="space-y-6">
                    <div class="bg-gradient-to-r from-purple-50 to-blue-50 p-4 rounded-lg">
                        <h3 class="font-semibold text-gray-800 mb-2">💡 本質的な洞察</h3>
                        <p class="text-gray-700">${escapeHtml(data.final_insight || 'なし')}</p>
                    </div>
                    
                    <div class="bg-gradient-to-r from-green-50 to-teal-50 p-4 rounded-lg">
                        <h3 class="font-semibold text-gray-800 mb-2">📖 ストーリー要素</h3>
                        <ul class="list-disc list-inside space-y-1 text-gray-700">
                            ${(data.story_elements || []).map(element => `<li>${escapeHtml(element)}</li>`).join('')}
                        </ul>
                    </div>
                    
                    <div class="bg-gradient-to-r from-yellow-50 to-orange-50 p-4 rounded-lg">
                        <h3 class="font-semibold text-gray-800 mb-2">💎 隠れた価値</h3>
                        <ul class="list-disc list-inside space-y-1 text-gray-700">
                            ${(data.hidden_values || []).map(value => `<li>${escapeHtml(value)}</li>`).join('')}
                        </ul>
                    </div>
                    
                    <div class="bg-gradient-to-r from-pink-50 to-red-50 p-4 rounded-lg">
                        <h3 class="font-semibold text-gray-800 mb-2">📢 PR推奨ポイント</h3>
                        <ul class="list-disc list-inside space-y-1 text-gray-700">
                            ${(data.pr_recommendations || []).map(rec => `<li>${escapeHtml(rec)}</li>`).join('')}
                        </ul>
                    </div>
                    
                    <div class="bg-gradient-to-r from-indigo-50 to-purple-50 p-4 rounded-lg">
                        <h3 class="font-semibold text-gray-800 mb-2">❤️ 感情フック</h3>
                        <ul class="list-disc list-inside space-y-1 text-gray-700">
                            ${(data.emotional_hooks || []).map(hook => `<li>${escapeHtml(hook)}</li>`).join('')}
                        </ul>
                    </div>
                </div>
            `;
            document.getElementById('insightSection').classList.remove('hidden');
            document.getElementById('insightSection').scrollIntoView({ behavior: 'smooth' });
        }

        function resetChat() {
            currentSession = null;
            chatHistory = [];
            originalContent = '';
            document.getElementById('chatContainer').innerHTML = '<div class="text-center text-gray-500 text-sm" id="chatPlaceholder">チャットが開始されると、ここに会話が表示されます</div>';
            document.getElementById('chatSection').classList.add('hidden');
            document.getElementById('insightSection').classList.add('hidden');
            document.getElementById('content').value = '';
            charCount.textContent = '0';
        }

        function showBothOptions() {
            // 継続と洞察生成の両方のオプションを表示
            document.getElementById('responseArea').style.display = 'flex';
            document.getElementById('actionButtons').style.display = 'block';
            
            // プレースホルダーを変更して継続可能であることを示す
            document.getElementById('userResponse').placeholder = "さらに深掘りしたい場合は回答を入力、または下のボタンから洞察を生成...";
        }

        function escapeHtml(text) {
            const div = document.createElement('div');
            div.textContent = text;
            return div.innerHTML;
        }

        // Enterキーで送信
        document.getElementById('userResponse').addEventListener('keypress', function(e) {
            if (e.key === 'Enter' && !e.shiftKey) {
                e.preventDefault();
                sendResponse();
            }
        });

        // モーダルの外側をクリックで閉じる
        document.getElementById('infoModal').addEventListener('click', function(event) {
            if (event.target === this) {
                hideInfo();
            }
        });
    </script>
</body>
</html>
