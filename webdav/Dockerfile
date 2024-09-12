# Dockerfile

FROM php:7.4-apache

# Habilitar módulos necessários do Apache
RUN a2enmod rewrite

# Copiar o arquivo de configuração do Apache para a imagem
COPY ./.htaccess /var/www/html/.htaccess

# Copiar script de inicialização
COPY ./entrypoint.sh /usr/local/bin/entrypoint.sh

# Tornar o script executável
RUN chmod +x /usr/local/bin/entrypoint.sh

# Definir o script de entrada
ENTRYPOINT ["/usr/local/bin/entrypoint.sh"]

# Expor a porta 80
EXPOSE 80
