FROM docker.elastic.co/logstash/logstash-oss:7.9.3

RUN /usr/share/logstash/bin/logstash-plugin install logstash-output-amazon_es

COPY ./amazon.logstash.conf /etc/logstash/conf.d/logstash.conf

CMD ["logstash", "-f", "/etc/logstash/conf.d/logstash.conf"]