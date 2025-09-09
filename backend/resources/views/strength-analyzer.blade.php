<!DOCTYPE html>
<html lang="ja">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>記事強み分析ツール - PR TIMES</title>
    
    <!-- Tailwind CSS -->
    <script src="https://cdn.tailwindcss.com"></script>
    
    <style>
        .loading {
            display: none;
        }
        .loading.active {
            display: block;
        }
        .result-section {
            display: none;
        }
        .result-section.active {
            display: block;
        }
        .strength-item {
            transition: all 0.3s ease;
        }
        .strength-item:hover {
            transform: translateY(-2px);
            box-shadow: 0 4px 12px rgba(0,0,0,0.1);
        }
        .impact-high { border-left: 4px solid #ef4444; }
        .impact-medium { border-left: 4px solid #f59e0b; }
        .impact-low { border-left: 4px solid #10b981; }
    </style>
</head>
<body class="bg-gray-50 min-h-screen">
    <!-- ヘッダー -->
    <header class="bg-white shadow-sm border-b">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
            <div class="flex justify-between items-center py-4">
                <div class="flex items-center">
                    <h1 class="text-2xl font-bold text-gray-900">記事強み分析ツール</h1>
                    <span class="ml-3 px-3 py-1 bg-blue-100 text-blue-800 text-sm font-medium rounded-full">PR TIMES</span>
                </div>
                <div class="flex space-x-4">
                    <a href="#" class="text-gray-500 hover:text-gray-700" onclick="showInfo()">使い方</a>
                    <a href="/api/strength-analysis/health" target="_blank" class="text-gray-500 hover:text-gray-700">API状態</a>
                </div>
            </div>
        </div>
    </header>

    <main class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-8">
        <!-- 入力セクション -->
        <div class="bg-white rounded-lg shadow-lg p-6 mb-8">
            <h2 class="text-xl font-semibold text-gray-800 mb-4">Markdown記事を入力してください</h2>
            
            <form id="analysisForm" onsubmit="analyzeStrengths(event)">
                <div class="mb-6">
                    <label for="content" class="block text-sm font-medium text-gray-700 mb-2">
                        記事全文（Markdown形式・タイトル含む）
                        <span class="text-gray-500 text-xs">* 最大50,000文字</span>
                    </label>
                    <textarea 
                        id="content" 
                        name="content"
                        rows="15" 
                        placeholder="# チーム開発×データ分析に挑む3Daysハッカソン受付開始 

![PR TIMES HACKATHON]( https://prtimes.jp/api/file.php?t=origin&f=d112-1552-a6dd09c5580a91f669c7-0.jpg )

**プレスリリース配信サービス「PR TIMES」等を運営する株式会社PR TIMES（東京都港区、代表取締役：山口拓己、東証プライム：3922）は、2026・27年卒業予定のエンジニア志望学生を対象に、「PR TIMES HACKATHON 2025 Summer」を開催します。**

## 同世代エンジニアとつながり、チーム開発の経験を積める3日間

PR TIMESハッカソンは、2016年より開催している内定直結型のハッカソンイベントです。2025年9月8日〜10日の3日間でWebサービスの開発を行い、特に優秀な方には **年収500万円以上の中途採用基準での内定** をお出しします。

## 累計200万件超のデータ分析を通してWebサービスを開発

今回のテーマは **「プレスリリースを改善するためのレビュー機能を持ったWebサービスの開発」** です。PR TIMESの累計200万件超のプレスリリースデータをAPIとして提供します。"
                        class="w-full px-3 py-2 border border-gray-300 rounded-md shadow-sm focus:outline-none focus:ring-blue-500 focus:border-blue-500 resize-y"
                        required
                        maxlength="50000"></textarea>
                    <div class="text-right text-xs text-gray-500 mt-1">
                        <span id="charCount">0</span> / 50,000 文字
                    </div>
                </div>

                <div class="mb-6">
                    <label for="persona" class="block text-sm font-medium text-gray-700 mb-2">
                        伝えたい人物像（任意）
                    </label>
                    <input 
                        type="text" 
                        id="persona" 
                        name="persona"
                        placeholder="例: 26・27卒就活生、ハッカソン好き、内定を探している"
                        class="w-full px-3 py-2 border border-gray-300 rounded-md shadow-sm focus:outline-none focus:ring-blue-500 focus:border-blue-500"
                        maxlength="500">
                </div>
                
                <div class="flex justify-end space-x-4">
                    <button 
                        type="submit"
                        id="analyzeButton" 
                        class="px-6 py-2 bg-blue-600 text-white rounded-md hover:bg-blue-700 focus:outline-none focus:ring-2 focus:ring-blue-500 disabled:opacity-50 disabled:cursor-not-allowed"
                    >
                        強みを分析する
                    </button>
                </div>
            </form>
        </div>

        <!-- ローディング -->
        <div id="loading" class="loading">
            <div class="bg-white rounded-lg shadow-lg p-8 text-center">
                <div class="animate-spin rounded-full h-12 w-12 border-b-2 border-blue-600 mx-auto mb-4"></div>
                <p class="text-gray-600">記事を分析しています...</p>
                <p class="text-sm text-gray-500 mt-2">メディアフック9要素による詳細分析を実行中</p>
            </div>
        </div>

        <!-- 結果セクション -->
        <div id="resultSection" class="result-section">
            <!-- 分析サマリー -->
            <div class="bg-white rounded-lg shadow-lg p-6 mb-8">
                <h2 class="text-xl font-semibold text-gray-800 mb-4">分析結果サマリー</h2>
                <div class="grid grid-cols-1 md:grid-cols-4 gap-4">
                    <div class="bg-blue-50 p-4 rounded-lg">
                        <div class="text-2xl font-bold text-blue-600" id="totalStrengths">0</div>
                        <div class="text-sm text-gray-600">抽出された強み</div>
                    </div>
                    <div class="bg-red-50 p-4 rounded-lg">
                        <div class="text-2xl font-bold text-red-600" id="highImpactCount">0</div>
                        <div class="text-sm text-gray-600">高インパクト強み</div>
                    </div>
                    <div class="bg-green-50 p-4 rounded-lg">
                        <div class="text-2xl font-bold text-green-600" id="coveredElements">0</div>
                        <div class="text-sm text-gray-600">カバー要素数</div>
                    </div>
                    <div class="bg-yellow-50 p-4 rounded-lg">
                        <div class="text-2xl font-bold text-yellow-600" id="missingElements">0</div>
                        <div class="text-sm text-gray-600">不足要素数</div>
                    </div>
                </div>
            </div>

            <!-- 特に優れた強み -->
            <div class="bg-white rounded-lg shadow-lg p-6 mb-8">
                <h3 class="text-lg font-semibold text-gray-800 mb-4">🌟 特に優れた強み（推奨アピールポイント）</h3>
                <div id="highlights"></div>
            </div>

            <!-- 抽出された強み一覧 -->
            <div class="bg-white rounded-lg shadow-lg p-6 mb-8">
                <h3 class="text-lg font-semibold text-gray-800 mb-4">📊 抽出された強み一覧</h3>
                <div id="strengthsList"></div>
            </div>

            <!-- 改善提案 -->
            <div class="bg-white rounded-lg shadow-lg p-6 mb-8">
                <h3 class="text-lg font-semibold text-gray-800 mb-4">💡 より良くするための提案</h3>
                <div id="suggestions"></div>
            </div>

            <!-- ターゲットユーザーの感想 -->
            <div class="bg-white rounded-lg shadow-lg p-6 mb-8" id="personaFeedbackSection" style="display: none;">
                <h3 class="text-lg font-semibold text-gray-800 mb-4">👤 ターゲットユーザーの感想</h3>
                <div class="bg-blue-50 border-l-4 border-blue-400 p-4">
                    <div class="text-sm text-gray-600 mb-2" id="personaDescription"></div>
                    <div class="font-medium text-gray-800" id="personaFeedback"></div>
                </div>
            </div>

            <!-- メディアフック要素カバレッジ -->
            <div class="bg-white rounded-lg shadow-lg p-6">
                <h3 class="text-lg font-semibold text-gray-800 mb-4">🎯 メディアフック9要素カバレッジ</h3>
                <div class="text-xs text-gray-500 mb-4">
                    参考: <a href="https://prtimes.jp/magazine/media-hook/" target="_blank" class="text-blue-600 hover:underline">PR TIMES メディアフック理論</a>
                </div>
                <div id="mediahookCoverage" class="grid grid-cols-1 md:grid-cols-3 gap-4"></div>
            </div>
        </div>
    </main>

    <!-- 使い方モーダル -->
    <div id="infoModal" class="fixed inset-0 bg-gray-600 bg-opacity-50 hidden z-50">
        <div class="flex items-center justify-center min-h-screen p-4">
            <div class="bg-white rounded-lg max-w-2xl w-full p-6">
                <div class="flex justify-between items-center mb-4">
                    <h3 class="text-lg font-semibold">記事強み分析ツールの使い方</h3>
                    <button onclick="hideInfo()" class="text-gray-400 hover:text-gray-600">
                        <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path>
                        </svg>
                    </button>
                </div>
                <div class="space-y-4 text-sm text-gray-600">
                    <p><strong>このツールについて：</strong><br>
                    Markdown形式の記事から企業・組織の強みを自動抽出し、PR TIMES独自の「メディアフック9要素」で分類・分析します。</p>
                    
                    <p><strong>使い方：</strong><br>
                    1. ファイル名（任意）を入力<br>
                    2. Markdown形式でタイトルを含む記事全文を入力<br>
                    3. 「強みを分析する」ボタンをクリック</p>
                    
                    <p><strong>分析結果：</strong><br>
                    • 強みの抽出と分類<br>
                    • インパクトスコア評価（高/中/低）<br>
                    • 不足している要素の指摘<br>
                    • 具体的な改善提案</p>
                    
                    <p><strong>メディアフック9要素：</strong><br>
                    時代性、画像/映像、矛盾/対立、地域性、話題性、社会性、新規性、特級性、意外性</p>
                </div>
            </div>
        </div>
    </div>

    <script>
        // 文字数カウント
        const contentTextarea = document.getElementById('content');
        const charCount = document.getElementById('charCount');
        
        contentTextarea.addEventListener('input', function() {
            charCount.textContent = this.value.length.toLocaleString();
        });

        // フォームクリア
        function clearForm() {
            document.getElementById('analysisForm').reset();
            charCount.textContent = '0';
            document.getElementById('resultSection').classList.remove('active');
        }

        // 情報モーダル
        function showInfo() {
            document.getElementById('infoModal').classList.remove('hidden');
        }
        
        function hideInfo() {
            document.getElementById('infoModal').classList.add('hidden');
        }

        // 分析実行
        async function analyzeStrengths(event) {
            event.preventDefault();
            
            const formData = new FormData(event.target);
            const content = (formData.get('content') || '').trim();
            const persona = (formData.get('persona') || '').trim();
            
            // デバッグ用ログ（本番では削除）
            console.log('Form data:', { content: content.length, persona: persona.length });
            
            if (!content) {
                alert('記事内容を入力してください。');
                return;
            }

            // UI状態変更
            const analyzeButton = document.getElementById('analyzeButton');
            analyzeButton.disabled = true;
            analyzeButton.textContent = '分析中...';
            
            document.getElementById('loading').classList.add('active');
            document.getElementById('resultSection').classList.remove('active');

            try {
                const response = await fetch('/api/strength-analysis/analyze', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'Accept': 'application/json',
                        'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content')
                    },
                    body: JSON.stringify({
                        content: content,
                        persona: persona
                    })
                });

                console.log('Response status:', response.status);
                console.log('Response headers:', response.headers);

                if (!response.ok) {
                    const errorText = await response.text();
                    console.error('API Error Response:', errorText);
                    throw new Error(`API request failed with status ${response.status}: ${errorText}`);
                }

                const result = await response.json();
                console.log('API Response:', result);

                if (result.success) {
                    displayResults(result.data);
                } else {
                    throw new Error(result.message || '分析に失敗しました');
                }

            } catch (error) {
                console.error('Full error object:', error);
                console.error('Error message:', error.message);
                console.error('Error stack:', error.stack);
                
                let errorMessage = 'Unknown error occurred';
                if (error.message) {
                    errorMessage = error.message;
                } else if (error.toString) {
                    errorMessage = error.toString();
                }
                
                alert('分析でエラーが発生しました: ' + errorMessage);
            } finally {
                // UI状態復帰
                analyzeButton.disabled = false;
                analyzeButton.textContent = '強みを分析する';
                document.getElementById('loading').classList.remove('active');
            }
        }

        // 結果表示
        function displayResults(data) {
            // サマリー表示
            document.getElementById('totalStrengths').textContent = data.summary?.total_strengths || 0;
            document.getElementById('highImpactCount').textContent = data.summary?.high_impact_count || 0;
            document.getElementById('coveredElements').textContent = data.summary?.covered_elements?.length || 0;
            document.getElementById('missingElements').textContent = data.missing_elements?.length || 0;

            // ハイライト表示
            displayHighlights(data.highlights || []);
            
            // 強み一覧表示
            displayStrengths(data.strengths || []);
            
            // 改善提案表示
            displaySuggestions(data.missing_elements || []);
            
            // ターゲットユーザーの感想表示
            displayPersonaFeedback(data.persona_feedback);
            
            // メディアフック要素カバレッジ表示
            displayMediahookCoverage(data.summary?.covered_elements || []);

            // 結果セクション表示
            document.getElementById('resultSection').classList.add('active');
            
            // 結果までスクロール
            document.getElementById('resultSection').scrollIntoView({ behavior: 'smooth' });
        }

        function displayHighlights(highlights) {
            const container = document.getElementById('highlights');
            if (highlights.length === 0) {
                container.innerHTML = '<p class="text-gray-500">特に優れた強みが検出されませんでした。</p>';
                return;
            }

            container.innerHTML = highlights.map(highlight => `
                <div class="bg-yellow-50 border-l-4 border-yellow-400 p-4 mb-3">
                    <div class="font-medium text-gray-800">${escapeHtml(highlight.content)}</div>
                    <div class="text-sm text-gray-600 mt-1">${escapeHtml(highlight.reason)}</div>
                </div>
            `).join('');
        }

        function displayStrengths(strengths) {
            const container = document.getElementById('strengthsList');
            if (strengths.length === 0) {
                container.innerHTML = '<p class="text-gray-500">強みが検出されませんでした。</p>';
                return;
            }

            container.innerHTML = strengths.map(strength => {
                const impactClass = {
                    '高': 'impact-high bg-red-50',
                    '中': 'impact-medium bg-yellow-50', 
                    '低': 'impact-low bg-green-50'
                }[strength.impact_score] || 'bg-gray-50';

                return `
                    <div class="strength-item ${impactClass} p-4 rounded-lg mb-3 cursor-pointer">
                        <div class="flex justify-between items-start mb-2">
                            <span class="inline-block px-2 py-1 bg-blue-100 text-blue-800 text-xs rounded-full">${escapeHtml(strength.category)}</span>
                            <div class="flex space-x-2">
                                <span class="inline-block px-2 py-1 bg-gray-100 text-gray-700 text-xs rounded-full">インパクト: ${strength.impact_score}</span>
                                <span class="inline-block px-2 py-1 bg-gray-100 text-gray-700 text-xs rounded-full">${strength.type}</span>
                            </div>
                        </div>
                        <div class="font-medium text-gray-800 mb-1">${escapeHtml(strength.content)}</div>
                        <div class="text-xs text-gray-500">位置: ${escapeHtml(strength.position)}</div>
                    </div>
                `;
            }).join('');
        }

        function displaySuggestions(missingElements) {
            const container = document.getElementById('suggestions');
            if (missingElements.length === 0) {
                container.innerHTML = '<p class="text-green-600">すべての要素が適切にカバーされています。</p>';
                return;
            }

            container.innerHTML = missingElements.map(element => `
                <div class="bg-orange-50 border-l-4 border-orange-400 p-4 mb-3">
                    <div class="font-medium text-gray-800">不足要素: ${escapeHtml(element.element)}</div>
                    <div class="text-sm text-gray-600 mt-1">${escapeHtml(element.suggestion)}</div>
                </div>
            `).join('');
        }

        function displayMediahookCoverage(coveredElements) {
            const container = document.getElementById('mediahookCoverage');
            const allElements = {
                'time_seasonality': '時代性/季節性',
                'images_video': '画像/映像',
                'contradiction_conflict': '矛盾/対立',
                'regional_focus': '地域性',
                'topicality': '話題性',
                'social_public_interest': '社会性/公共性',
                'novelty_uniqueness': '新規性/独自性',
                'superlative_rarity': '特級性/希少性',
                'unexpectedness': '意外性'
            };

            container.innerHTML = Object.entries(allElements).map(([key, name]) => {
                const isCovered = coveredElements.includes(name);
                return `
                    <div class="p-3 rounded-lg ${isCovered ? 'bg-green-100 text-green-800' : 'bg-gray-100 text-gray-500'}">
                        <div class="flex items-center">
                            <span class="mr-2">${isCovered ? '✓' : '○'}</span>
                            <span class="text-sm font-medium">${name}</span>
                        </div>
                    </div>
                `;
            }).join('');
        }

        function displayPersonaFeedback(feedback) {
            const section = document.getElementById('personaFeedbackSection');
            const feedbackElement = document.getElementById('personaFeedback');
            const descriptionElement = document.getElementById('personaDescription');
            
            if (feedback && feedback.trim()) {
                // フォームから取得したpersonaを表示
                const personaInput = document.getElementById('persona');
                const personaValue = personaInput ? personaInput.value.trim() : '';
                
                if (personaValue) {
                    descriptionElement.textContent = `「${personaValue}」の視点から：`;
                } else {
                    descriptionElement.textContent = 'ユーザー視点から：';
                }
                
                feedbackElement.textContent = feedback;
                section.style.display = 'block';
            } else {
                section.style.display = 'none';
            }
        }

        function escapeHtml(text) {
            const div = document.createElement('div');
            div.textContent = text;
            return div.innerHTML;
        }

        // モーダルの外側をクリックで閉じる
        document.getElementById('infoModal').addEventListener('click', function(event) {
            if (event.target === this) {
                hideInfo();
            }
        });
    </script>
</body>
</html>