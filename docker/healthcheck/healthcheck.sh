#!/bin/sh

while ! nc -z localhost 9601; do echo "waiting logstash" && sleep 10; done;

set -e

echo "Starting to ping logstash..."

while :
do
  nc localhost 9601 -z
  echo "logstash healthy!"
  sleep 60
done