FROM alpine:3.19

RUN apk add php php-dom php-xmlwriter php-xmlreader php-tokenizer php-session php-xml php-fileinfo composer \
            php-simplexml

RUN cp /usr/bin/php /usr/bin/php

COPY . /app

RUN chmod +x /app/tests/test.sh

COPY ./docker /

WORKDIR /app

RUN composer install

CMD ["/app/tests/test.sh"]
