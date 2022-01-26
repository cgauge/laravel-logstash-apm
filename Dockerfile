FROM alpine:3.15

RUN apk add php8 php8-dom php8-xmlwriter php8-xmlreader php8-tokenizer php8-session php8-xml php8-fileinfo composer \
            php8-simplexml

RUN cp /usr/bin/php8 /usr/bin/php

COPY . /app

RUN chmod +x /app/tests/test.sh

COPY ./docker /

WORKDIR /app

RUN composer update --with=illuminate/support:^9.0

CMD ["/app/tests/test.sh"]
