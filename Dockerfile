FROM wordpress:latest

RUN sed -i 's#http://.*.debian.org#http://ftp.cn.debian.org#g' /etc/apt/sources.list && \  
    apt-get update && apt-get install -y zip && \
    cp /usr/src/wordpress/wp-config-sample.php /usr/src/wordpress/wp-config.php
RUN curl -o sqlite-plugin.zip https://downloads.wordpress.org/plugin/sqlite-integration.1.8.1.zip && \
    unzip sqlite-plugin.zip -d /usr/src/wordpress/wp-content/plugins/ && \
    cp /usr/src/wordpress/wp-content/plugins/sqlite-integration/db.php /usr/src/wordpress/wp-content && \
    rm -rf sqlite-plugin.zip