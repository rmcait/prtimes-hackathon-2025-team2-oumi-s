.PHONY: init up down build clean install migrate seed test logs shell

# 初期セットアップ
init: build install migrate
	@echo "✅ 開発環境の初期化が完了しました"
	@echo "🚀 http://localhost:8000 でアクセスできます"

# Docker環境を構築して起動
up:
	docker-compose up -d

# Docker環境を停止
down:
	docker-compose down

# Dockerイメージをビルド
build:
	docker-compose build

# すべてのコンテナとボリュームを削除
clean:
	docker-compose down -v --rmi all
	docker system prune -f

# Composer依存関係をインストール
install: up
	docker-compose exec app composer install
	docker-compose exec app cp .env.example .env
	docker-compose exec app php artisan key:generate
	docker-compose exec app php artisan config:cache

# データベースマイグレーション
migrate:
	docker-compose exec app php artisan migrate

# シーダーを実行
seed:
	docker-compose exec app php artisan db:seed

# テストを実行
test:
	docker-compose exec app php artisan test

# ログを確認
logs:
	docker-compose logs -f

# アプリケーションコンテナにシェル接続
shell:
	docker-compose exec app bash

# データベースコンテナにシェル接続
db-shell:
	docker-compose exec db mysql -u laravel_user -ppassword laravel

# キャッシュクリア
clear-cache:
	docker-compose exec app php artisan cache:clear
	docker-compose exec app php artisan config:clear
	docker-compose exec app php artisan route:clear
	docker-compose exec app php artisan view:clear

# ストレージリンク作成
storage-link:
	docker-compose exec app php artisan storage:link

# 権限修正
fix-permissions:
	docker-compose exec app chown -R www-data:www-data /var/www/storage
	docker-compose exec app chown -R www-data:www-data /var/www/bootstrap/cache