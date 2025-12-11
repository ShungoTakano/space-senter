FROM php:8.2-apache

# 必要な拡張機能のインストール
RUN apt-get update && apt-get install -y \
    libcurl4-openssl-dev \
    && docker-php-ext-install curl

# Apacheのmod_rewriteを有効化
RUN a2enmod rewrite

# 作業ディレクトリの設定
WORKDIR /var/www/html

# アプリケーションファイルをコピー
COPY . /var/www/html/

# dataディレクトリの権限設定
RUN mkdir -p /var/www/html/data && \
    chown -R www-data:www-data /var/www/html/data && \
    chmod -R 755 /var/www/html/data

# ポート80を公開
EXPOSE 80

# Apacheをフォアグラウンドで実行
CMD ["apache2-foreground"]
