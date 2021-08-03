#!/bin/bash -e

# Let's give Logstash a 5 minute head start before we start pinging it
sleep 300

while true; do
  nc logstash 9601
  sleep 60
done