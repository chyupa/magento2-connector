# Container image that runs your code
FROM debian:stable-slim

RUN apt-get update && apt-get install -y curl rsync zip
RUN	apt-get clean -y
RUN rm -rf /var/lib/apt/lists/*

# Copies your code file from your action repository to the filesystem path `/` of the container
COPY entrypoint.sh /entrypoint.sh
RUN chmod +x /entrypoint.sh

# Code file to execute when the docker container starts up (`entrypoint.sh`)
ENTRYPOINT ["/entrypoint.sh"]
