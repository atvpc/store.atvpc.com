# Docker Network:
# 172.18.0.0 - 172.18.255.254
#
# wiki (dokuwiki):        172.18.0.10
# certbot (lets encrypt): 172.18.0.20
# mariadb (sql server):   172.18.0.30
# www:                    172.18.0.50
# magento (webstore):     172.18.0.60

include secrets/passwords

create-network:
	-docker network create --subnet=172.18.0.0/16 dockernet


stop-haproxy:
	-docker container ls | grep haproxy | awk '{print $$1}' | xargs docker stop

stop-wiki:
	-docker container ls | grep wiki | awk '{print $$1}' | xargs docker stop

stop-www:
	-docker container ls | grep www | awk '{print $$1}' | xargs docker stop

stop-mariadb:
	-docker container ls | grep mariadb | awk '{print $$1}' | xargs docker stop

stop-all:
	-docker ps -aq | xargs docker stop


build-wiki:
	docker build -t wiki ./wiki.atvpc.com

build-www:
ifneq ($(wildcard ./data/www/.),)
	cd data/www ; git pull
	cd data/www/xpaxle ; git pull
else
	git clone https://github.com/atvpc/atvpc.com.git data/www
	git clone https://github.com/atvpc/xpaxle-page.git data/www/xpaxle
endif

build-all: build-www build-wiki build-haproxy


run-www: stop-www
	docker run -d --name www \
	--net dockernet --ip 172.18.0.50 \
	-v /srv/data/www:/var/www/html \
	php:apache

run-wiki: stop-wiki
	docker run --net dockernet --ip 172.18.0.10 -v /srv/wiki.atvpc.com/htdocs:/var/www/html -d wiki

run-haproxy: stop-haproxy create-network
	docker run -d --name haproxy \
	--net dockernet -p 80:80 -p 443:443 \
	-v /srv/secrets/ssl:/etc/ssl \
	-v /srv/data/haproxy:/usr/local/etc/haproxy \
	haproxy:alpine

run-mariadb:
	docker run -d --name mariadb \
	--net dockernet --ip 172.18.0.30 \
	-e MYSQL_ROOT_PASSWORD=$(MYSQL_ROOT_PASS) \
	-v /srv/data/mariadb:/var/lib/mysql \
	mariadb:latest

run-magento:
	docker run -d --name magento \
	--net dockernet --ip 172.18.0.60 \
    -e MARIADB_HOST=172.18.0.30 \
    -e MARIADB_ROOT_PASSWORD=$(MYSQL_ROOT_PASS) \
	-e MAGENTO_DATABASE_NAME=$(MAGENTODB_NAME) \
	-e MAGENTO_DATABASE_USER=$(MAGENTODB_USER) \
	-e MAGENTO_DATABASE_PASSWORD=$(MAGENTODB_PASS) \
	-e MAGENTO_HOST=store.atvpc.com \
	-v /srv/data/magento:/bitnami \
	bitnami/magento:latest

run-all: run-www run-wiki run-mariadb run-haproxy


certbot-new:
	docker run -it --rm --name certbot \
	--net dockernet --ip 172.18.0.20 \
	-v "/srv/data/certbot:/etc/letsencrypt" \
	certbot/certbot certonly --standalone --non-interactive --expand --agree-tos --email admin@atvpc.com --http-01-port=8888 \
    -d atvpartsconnection.com \
    -d atvpc.com \
	-d wiki.atvpc.com \
	-d store.atvpc.com

	sudo bash -c "cat /srv/data/certbot/live/wiki.atvpc.com/fullchain.pem /srv/data/certbot/live/wiki.atvpc.com/privkey.pem > /srv/secrets/ssl/atvpc.com.pem"

certbot-renew:
	docker run -it --rm --name certbot \
	--net dockernet --ip 172.18.0.20 \
	-v "/srv/data/certbot:/etc/letsencrypt" \
	certbot/certbot renew --force-renewal --tls-sni-01-port=8888

	sudo bash -c "cat /srv/data/certbot/live/wiki.atvpc.com/fullchain.pem /srv/data/certbot/live/wiki.atvpc.com/privkey.pem > /srv/secrets/ssl/atvpc.com.pem"

renew-cert: certbot-renew stop-haproxy build-haproxy run-haproxy
