FROM opensearchproject/logstash-oss-with-opensearch-output-plugin:7.13.4 as logstash

USER logstash

COPY --chown=logstash:logstash ./amazon.logstash.conf /usr/share/logstash/config/logstash.conf

RUN /usr/share/logstash/bin/logstash-plugin install logstash-output-amazon_es

CMD ["/usr/share/logstash/bin/logstash", "-f", "/usr/share/logstash/config/logstash.conf"]