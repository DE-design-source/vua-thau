# PHP + Apache serving site/ (mounted, so `git pull` updates the site without a rebuild).
# HTTPS on :8443 with a self-signed cert, reachable only from Cloudflare (see apache.conf).
# Mail: PHP mail() -> msmtp -> SMTP account in deploy/msmtprc.
FROM php:8.3-apache
RUN apt-get update && apt-get install -y --no-install-recommends msmtp ca-certificates openssl \
 && rm -rf /var/lib/apt/lists/* \
 && a2enmod remoteip headers ssl rewrite \
 && printf 'sendmail_path = "/usr/bin/msmtp -t"\nexpose_php = Off\n' > /usr/local/etc/php/conf.d/vuathau.ini \
 && openssl req -x509 -nodes -newkey rsa:2048 -days 3650 -subj /CN=vuathau.com.vn \
      -keyout /etc/ssl/private/vuathau-origin.key -out /etc/ssl/certs/vuathau-origin.crt
COPY deploy/apache.conf /etc/apache2/conf-enabled/vuathau.conf
