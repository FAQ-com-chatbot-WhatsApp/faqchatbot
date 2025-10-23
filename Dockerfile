FROM php:8.1-apache

# Instalar dependências do sistema
RUN apt-get update && apt-get install -y \
    libpng-dev \
    libjpeg-dev \
    libfreetype6-dev \
    libzip-dev \
    libonig-dev \
    unzip \
    && rm -rf /var/lib/apt/lists/*

# Configurar e instalar extensões PHP
RUN docker-php-ext-configure gd --with-freetype --with-jpeg \
    && docker-php-ext-install -j$(nproc) \
        gd \
        mysqli \
        pdo \
        pdo_mysql \
        zip \
        mbstring \
        exif \
        pcntl \
        bcmath

# Habilitar mod_rewrite do Apache
RUN a2enmod rewrite

# Configurar DocumentRoot do Apache
ENV APACHE_DOCUMENT_ROOT /var/www/html

# Copiar arquivo de configuração personalizada do PHP
COPY docker/php.ini /usr/local/etc/php/

# Script de inicialização
COPY docker/init.sh /usr/local/bin/
RUN chmod +x /usr/local/bin/init.sh

# Definir diretório de trabalho
WORKDIR /var/www/html

# Expor porta 80
EXPOSE 80

# Executar script de inicialização
CMD ["/usr/local/bin/init.sh"]