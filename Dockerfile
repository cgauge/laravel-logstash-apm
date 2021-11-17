FROM alpine:3.14

RUN apk add php7 php7-dom php7-xmlwriter php7-xmlreader php7-tokenizer php7-session php7-xml php7-fileinfo composer \
            php7-simplexml

COPY . /app

RUN chmod +x /app/tests/test.sh

COPY ./docker /

WORKDIR /app

RUN composer install

CMD ["/app/tests/test.sh"]