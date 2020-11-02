#!/usr/bin/env sh

while ! nc -z elasticsearch 9200; do sleep 0.1; done;

while ! nc -z logstash 9601; do sleep 0.1; done;

while ! nc -zu logstash 9602; do sleep 0.1; done;

php /app/vendor/bin/phpunit --testdox