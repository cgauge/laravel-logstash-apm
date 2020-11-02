FROM alpine:edge

RUN apk add php7 php7-dom php7-xmlwriter php7-xmlreader php7-tokenizer php7-session php7-xml php7-fileinfo composer

COPY . /app

RUN chmod +x /app/tests/test.sh

COPY ./docker /

WORKDIR /app

RUN composer install

CMD ["/app/tests/test.sh"]