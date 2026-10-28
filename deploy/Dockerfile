# PHP + Apache serving site/ (mounted, so `git pull` updates the site without a rebuild).
# Mail: PHP mail() -> msmtp -> SMTP account in deploy/msmtprc.
FROM php:8.3-apache
RUN apt-get update && apt-get install -y --no-install-recommends msmtp ca-certificates \
 && rm -rf /var/lib/apt/lists/* \
 && a2enmod remoteip headers \
 && printf 'sendmail_path = "/usr/bin/msmtp -t"\nexpose_php = Off\n' > /usr/local/etc/php/conf.d/vuathau.ini
COPY deploy/apache.conf /etc/apache2/conf-enabled/vuathau.conf
