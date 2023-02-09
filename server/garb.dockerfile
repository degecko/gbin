FROM ubuntu:22.04

ENV DEBIAN_FRONTEND noninteractive

RUN apt-get update && apt-get install -y galera-arbitrator-4

ENTRYPOINT ["garbd", "--group", "galera_cluster", "--address", "gcomm://152.228.224.37,194.242.15.95,78.46.37.185,213.133.99.249"]
