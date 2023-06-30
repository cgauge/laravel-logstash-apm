FROM alpine:3.18

RUN apk add php81 php81-dom php81-xmlwriter php81-xmlreader php81-tokenizer php81-session php81-xml php81-fileinfo composer \
            php81-simplexml

RUN cp /usr/bin/php81 /usr/bin/php

COPY . /app

RUN chmod +x /app/tests/test.sh

COPY ./docker /

WORKDIR /app

RUN composer update --with=illuminate/support:^10.0

CMD ["/app/tests/test.sh"]
