import { Head } from '@inertiajs/react';
import { useState, FormEvent } from 'react';

const mediaOptions = [
  { id: 'tv', name: 'テレビ' },
  { id: 'magazine', name: '雑誌' },
  { id: 'newspaper', name: '新聞' },
  { id: 'web', name: 'Web' },
  { id: 'freepaper', name: 'フリーペーパー' },
  { id: 'radio', name: 'ラジオ' },
  { id: 'news-agency', name: '通信社' },
  { id: 'others', name: '未分類' },
];

export default function Review() {
  const [content, setContent] = useState('');
  const [selectedMedia, setSelectedMedia] = useState<string[]>([]);

  const toggleMedia = (mediaId: string) => {
    setSelectedMedia(prev =>
      prev.includes(mediaId)
        ? prev.filter(id => id !== mediaId)
        : [...prev, mediaId]
    );
  };

  const insertMarkdown = (type: string) => {
    const textarea = document.querySelector('.markdown-textarea') as HTMLTextAreaElement;
    if (!textarea) return;

    let insertText = '';
    switch(type) {
      case 'bold':
        insertText = '**太字**';
        break;
      case 'italic':
        insertText = '*斜体*';
        break;
      case 'heading':
        insertText = '# 見出し';
        break;
      case 'link':
        insertText = '[リンクテキスト](URL)';
        break;
      case 'image':
        insertText = '![画像の説明](画像URL)';
        break;
      case 'code':
        insertText = '`コード`';
        break;
    }

    const start = textarea.selectionStart;
    const end = textarea.selectionEnd;
    const currentText = textarea.value;
    
    const newContent = currentText.substring(0, start) + insertText + currentText.substring(end);
    setContent(newContent);
    
    setTimeout(() => {
      textarea.focus();
      textarea.setSelectionRange(start + insertText.length, start + insertText.length);
    }, 0);
  };

  const handleReview = (e: FormEvent) => {
    e.preventDefault();
    
    if (content.trim() === '') {
      alert('記事内容を入力してください。');
      return;
    }
    
    if (selectedMedia.length === 0) {
      alert('配信先メディアを選択してください。');
      return;
    }
    
    alert('レビューを開始します...');
  };

  const handlePublish = (e: FormEvent) => {
    e.preventDefault();
    
    if (content.trim() === '') {
      alert('記事内容を入力してください。');
      return;
    }
    
    if (selectedMedia.length === 0) {
      alert('配信先メディアを選択してください。');
      return;
    }
    
    if (confirm('選択したメディアに配信しますか？')) {
      alert('配信を開始します...');
    }
  };

  return (
    <div className="min-h-screen bg-gray-100">
      <Head title="レビュー機能" />

      <div className="container max-w-7xl mx-auto p-6">
        <div className="bg-white rounded-lg shadow-md overflow-hidden">
          <div className="bg-white border-b border-gray-200 p-6">
            <h1 className="text-2xl font-semibold text-gray-900 text-center">レビュー機能</h1>
          </div>
          
          <div className="p-8">
              <form className="space-y-6">
                {/* 記事内容セクション */}
                <div>
                  <h3 className="text-lg font-medium text-gray-900 mb-4">記事内容</h3>
                  <div className="border-2 border-gray-200 rounded-lg overflow-hidden focus-within:border-blue-500 transition-colors">
                    {/* ツールバー */}
                    <div className="bg-gray-50 px-4 py-2 border-b border-gray-200 flex gap-2">
                      <button
                        type="button"
                        onClick={() => insertMarkdown('bold')}
                        className="px-3 py-1 text-sm text-gray-600 hover:text-blue-600 hover:bg-blue-50 rounded transition-colors"
                      >
                        Bold
                      </button>
                      <button
                        type="button"
                        onClick={() => insertMarkdown('italic')}
                        className="px-3 py-1 text-sm text-gray-600 hover:text-blue-600 hover:bg-blue-50 rounded transition-colors"
                      >
                        Italic
                      </button>
                      <button
                        type="button"
                        onClick={() => insertMarkdown('heading')}
                        className="px-3 py-1 text-sm text-gray-600 hover:text-blue-600 hover:bg-blue-50 rounded transition-colors"
                      >
                        Heading
                      </button>
                      <button
                        type="button"
                        onClick={() => insertMarkdown('link')}
                        className="px-3 py-1 text-sm text-gray-600 hover:text-blue-600 hover:bg-blue-50 rounded transition-colors"
                      >
                        Link
                      </button>
                      <button
                        type="button"
                        onClick={() => insertMarkdown('image')}
                        className="px-3 py-1 text-sm text-gray-600 hover:text-blue-600 hover:bg-blue-50 rounded transition-colors"
                      >
                        Image
                      </button>
                      <button
                        type="button"
                        onClick={() => insertMarkdown('code')}
                        className="px-3 py-1 text-sm text-gray-600 hover:text-blue-600 hover:bg-blue-50 rounded transition-colors"
                      >
                        Code
                      </button>
                    </div>
                    
                    {/* テキストエリア */}
                    <textarea
                      className="markdown-textarea w-full min-h-[200px] p-4 border-0 outline-none resize-vertical font-mono text-sm"
                      value={content}
                      onChange={(e) => setContent(e.target.value)}
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
```"
                    />
                  </div>
                </div>

                {/* 配信先メディアセクション */}
                <div>
                  <h3 className="text-lg font-medium text-gray-900 mb-4">配信先メディア</h3>
                  <div className="grid grid-cols-2 sm:grid-cols-3 md:grid-cols-4 gap-3">
                    {mediaOptions.map((media) => (
                      <button
                        key={media.id}
                        type="button"
                        onClick={() => toggleMedia(media.id)}
                        className={`
                          p-3 rounded-lg border-2 text-center transition-all duration-200 transform hover:scale-105
                          ${selectedMedia.includes(media.id)
                            ? 'border-blue-500 bg-blue-500 text-white shadow-lg scale-105'
                            : 'border-gray-200 bg-white text-gray-700 hover:border-blue-300 hover:shadow-md'
                          }
                        `}
                      >
                        <div className="text-sm font-medium">{media.name}</div>
                      </button>
                    ))}
                  </div>
                </div>

                {/* アクションボタン */}
                <div className="flex justify-center gap-4 pt-6 border-t border-gray-200">
                  <button
                    type="button"
                    onClick={handleReview}
                    className="px-8 py-3 bg-gray-500 hover:bg-gray-600 text-white font-semibold rounded-lg transform hover:-translate-y-1 transition-all duration-200 shadow-lg hover:shadow-xl"
                  >
                    レビュー
                  </button>
                  <button
                    type="button"
                    onClick={handlePublish}
                    className="px-8 py-3 bg-blue-500 hover:bg-blue-600 text-white font-semibold rounded-lg transform hover:-translate-y-1 transition-all duration-200 shadow-lg hover:shadow-xl"
                  >
                    配信
                  </button>
                </div>
              </form>
          </div>
        </div>
      </div>
    </div>
  );
}