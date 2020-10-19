FROM php:7.4.10-cli

RUN apt-get update && apt-get install -y git zip unzip

RUN git clone https://github.com/FriendsOfPHP/PHP-CS-Fixer.git /app

COPY --from=composer:latest /usr/bin/composer /usr/bin/composer

RUN mkdir /app/src/Fixer/TypeHint
ADD src/Fixer/FunctionTypeHintFixer.php /app/src/Fixer/TypeHint
ADD src/Fixer/TypeHintReturnFixer.php /app/src/Fixer/TypeHint

RUN cd /app && composer install --no-dev  --no-interaction --no-progress --optimize-autoloader

ADD config.php /app

ADD entrypoint.sh /

ENTRYPOINT ["sh", "/entrypoint.sh"]
