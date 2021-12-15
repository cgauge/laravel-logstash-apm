FROM docker.elastic.co/logstash/logstash-oss:7.9.3 as logstash

FROM alpine:3.15 as patch

COPY --from=logstash /usr/share/logstash /logstash-patch

RUN apk add zip

RUN zip -q -d /logstash-patch/logstash-core/**/*/log4j-core-2.* org/apache/logging/log4j/core/lookup/JndiLookup.class

FROM logstash

COPY --from=patch /logstash-patch /usr/share/logstash

RUN /usr/share/logstash/bin/logstash-plugin install logstash-output-amazon_es

COPY ./amazon.logstash.conf /etc/logstash/conf.d/logstash.conf

CMD ["logstash", "-f", "/etc/logstash/conf.d/logstash.conf"]