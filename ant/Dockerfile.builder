FROM registry.datalan.ch/datahouse/it-docker-debian-php-apache:v8

ARG username
ARG uid
ARG gid

# Add some necessary build tools.
RUN apt-get update \
  && apt-get install -y --no-install-recommends \
      php5-xdebug datahouse-application-composer git \
  && apt-get clean \
  && rm -rf /var/lib/apt/lists/*

# Make kerberos work for our build user
COPY docker/krb5.conf /etc/
RUN apt-get update \
  && apt-get install -y --no-install-recommends krb5-user ssh-client \
  && apt-get clean \
  && rm -rf /var/lib/apt/lists/*

# Python stuff necessary for test running the python based backend
RUN apt-get update \
  && apt-get install -y --no-install-recommends \
    pylint \
    python-twisted-core \
    python-scrapy \
    python-psycopg2 \
    python-psutil \
    html2text \
    python-html2text \
    xsltproc \
    python-pdfminer \
    python-lxml \
    python-pyparsing \
  && apt-get clean \
  && rm -rf /var/lib/apt/lists/*

RUN groupadd -g $gid mygroup \
  && mkdir /home/$username \
  && useradd -g $gid -d /home/$username -u $uid -s /bin/bash $username

# This container is not intended to be run directly.
CMD /bin/false
