# Docker Network:
# 172.18.0.0 - 172.18.255.254
#
# wiki (dokuwiki):        172.18.0.10
# certbot (lets encrypt): 172.18.0.20
# mariadb (sql server):   172.18.0.30
# (www) atvpc             172.18.0.50
# magento (webstore):     172.18.0.60
# (www) cvr               172.18.0.70
# zabbix (frontend)       172.18.0.200
# zabbix (server)         172.18.0.250

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

build-www-atvpc:
ifneq ($(wildcard ./data/www/.),)
	cd data/www ; git pull
	cd data/www/xpaxle ; git pull
else
	git clone https://github.com/atvpc/atvpc.com.git data/www
	git clone https://github.com/atvpc/xpaxle-page.git data/www/xpaxle
endif

build-www-cvr:
ifneq ($(wildcard ./data/cvr/.),)
	cd data/cvr ; git pull
else
	git clone https://github.com/atvpc/cvrcorp.com.git data/cvr
endif

build-all: build-www build-www-cvr build-wiki build-haproxy


start-www-atvpc:
	docker start www || docker run -d --name www \
	--net dockernet --ip 172.18.0.50 \
	-v /srv/data/www:/var/www/html \
	php:apache

start-wiki:
	docker start wiki || docker run -d --name wiki \
	--net dockernet --ip 172.18.0.10 -h docker-wiki \
	-v /srv/wiki.atvpc.com/htdocs:/var/www/html \
	wiki:latest

start-www-cvr:
	docker start www-cvr || docker run -d --name www-cvr \
	--net dockernet --ip 172.18.0.70 -h docker-www-cvr \
	-v /srv/data/cvr:/var/www/html \
	wiki:latest

start-haproxy: stop-haproxy create-network
	docker start haproxy || docker run -d --name haproxy \
	--net dockernet -p 80:80 -p 443:443 \
	-v /srv/secrets/ssl:/etc/ssl \
	-v /srv/data/haproxy:/usr/local/etc/haproxy \
	haproxy:alpine

start-mariadb:
	docker start mariadb || docker run -d --name mariadb \
	--net dockernet --ip 172.18.0.30 \
	-e MYSQL_ROOT_PASSWORD=$(MYSQL_ROOT_PASS) \
	-v /srv/data/mariadb:/var/lib/mysql \
	mariadb:latest

start-zabbix-server:
	docker start zabbix-server || docker run -d --name zabbix-server \
	--net dockernet --ip 172.18.0.250 -p 10051:10051 \
	-e DB_SERVER_HOST=172.18.0.30 \
	-e MYSQL_ROOT_PASSWORD=$(MYSQL_ROOT_PASS) \
	-e MYSQL_DATABASE=$(ZABBIXDB_NAME) \
	-e MYSQL_USER=$(ZABBIXDB_USER) \
	-e MYSQL_PASSWORD=$(ZABBIXDB_PASS) \
	zabbix/zabbix-server-mysql:alpine-latest

start-zabbix-frontend:
	docker start zabbix-frontend || docker run -d --name zabbix-frontend \
	--net dockernet --ip 172.18.0.200 \
	-e ZBX_SERVER_HOST=172.18.0.250 \
	-e PHP_TZ=America/New_York \
	-e DB_SERVER_HOST=172.18.0.30 \
	-e MYSQL_DATABASE=$(ZABBIXDB_NAME) \
	-e MYSQL_USER=$(ZABBIXDB_USER) \
	-e MYSQL_PASSWORD=$(ZABBIXDB_PASS) \
	zabbix/zabbix-web-nginx-mysql:alpine-latest

start-magento: start-mariadb
	docker start magento || docker run -d --name magento \
	--net dockernet --ip 172.18.0.60 \
    -e MARIADB_HOST=172.18.0.30 \
    -e MARIADB_ROOT_PASSWORD=$(MYSQL_ROOT_PASS) \
	-e MAGENTO_DATABASE_NAME=$(MAGENTODB_NAME) \
	-e MAGENTO_DATABASE_USER=$(MAGENTODB_USER) \
	-e MAGENTO_DATABASE_PASSWORD=$(MAGENTODB_PASS) \
	-e MAGENTO_HOST=store.atvpc.com \
	-v /srv/data/magento:/bitnami \
	bitnami/magento:latest

start-zabbix: start-mariadb start-zabbix-server start-zabbix-frontend
start-all: start-www-atvpc start-www-cvr start-wiki start-mariadb start-magento start-zabbix start-haproxy


certbot-new:
	docker run -it --rm --name certbot \
	--net dockernet --ip 172.18.0.20 \
	-v "/srv/data/certbot:/etc/letsencrypt" \
	certbot/certbot certonly --standalone --non-interactive --expand --agree-tos --email admin@atvpc.com --http-01-port=8888 \
    -d atvpartsconnection.com \
    -d www.atvpartsconnection.com \
    -d atvpc.com \
    -d www.atvpc.com \
	-d wiki.atvpc.com \
	-d store.atvpc.com

	sudo bash -c "cat /srv/data/certbot/live/wiki.atvpc.com/fullchain.pem /srv/data/certbot/live/wiki.atvpc.com/privkey.pem > /srv/secrets/ssl/atvpc.com.pem"

certbot-renew: certbot-bin stop-haproxy run-haproxy

certbot-bin:
	sudo ./bin/certbot-renew
