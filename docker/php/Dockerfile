FROM debian:jessie

RUN apt-get update && \
apt-get update && \
apt-get upgrade -y && \
BUILD_PACKAGES="supervisor php5-fpm php5-cli php5-mysql php-apc php5-curl php5-gd php5-intl php5-mcrypt php5-memcache php5-sqlite php5-tidy php5-xmlrpc php5-xsl php5-pgsql php5-mongo php5-ldap" && \
apt-get -y install $BUILD_PACKAGES && \
apt-get clean && \
apt-get autoclean

RUN apt-get install -y git net-tools telnet curl wget

ADD conf/supervisord.conf /etc/supervisord.conf
ADD conf/php-fpm.conf /etc/php5/fpm/php-fpm.conf

ADD start.sh /start.sh
RUN chmod 755 /start.sh

CMD ["/bin/bash", "/start.sh"]
