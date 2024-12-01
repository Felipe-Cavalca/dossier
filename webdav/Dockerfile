# Dockerfile

FROM php:7.4-apache

# Copia os arquivos do servidor web para a imagem
COPY . /var/www/html

# Habilitar módulos necessários do Apache
RUN a2enmod rewrite

# Copiar o arquivo de configuração do Apache para a imagem
COPY ./.htaccess /var/www/html/.htaccess

# Configurando o php.ini para aumentar o tamanho máximo de upload e POST
RUN echo "upload_max_filesize = 1G" >> /usr/local/etc/php/php.ini
RUN echo "post_max_size = 1G" >> /usr/local/etc/php/php.ini
RUN echo "memory_limit = 1G" >> /usr/local/etc/php/php.ini

# Definir o script de entrada
ENTRYPOINT ["apache2-foreground"]

# Expor a porta 80
EXPOSE 80
