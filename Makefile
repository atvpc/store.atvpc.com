create-network:
	-docker network create --subnet=172.18.0.0/16 dockernet

build-haproxy:
	docker build -t haproxy ./haproxy

build-wiki:
	docker build -t wiki ./wiki.atvpc.com

build-www:
ifneq ($(wildcard ./www.atvpc.com/htdocs/.),)
	cd www.atvpc.com/htdocs ; git pull
	cd www.atvpc.com/htdocs/xpaxle ; git pull
else
	git clone https://github.com/atvpc/atvpc.com.git www.atvpc.com/htdocs
	git clone https://github.com/atvpc/xpaxle-page.git www.atvpc.com/htdocs/xpaxle
endif

	-docker build -t www ./www.atvpc.com

run-www: run-haproxy
	-docker run --net dockernet --ip 172.18.0.50 -d www

run-wiki: run-haproxy
	-docker run --net dockernet --ip 172.18.0.10 -v /srv/wiki.atvpc.com/htdocs:/var/www/html -d wiki

run-haproxy: create-network
	-docker run --net dockernet -p 80:80 -d haproxy

build-all: build-www build-wiki build-haproxy
	
run-all: run-www run-wiki run-haproxy

stop-all:
	-docker ps -aq | xargs docker stop

stop-haproxy:
	docker container ls | grep haproxy | awk '{print $1}' | xargs docker stop

certbot-new:
	docker run -it --rm --name certbot --net dockernet --ip 172.18.0.20 -v "/srv/certbot/etc:/etc/letsencrypt" \
	certbot/certbot \
	certonly --standalone -d wiki.atvpc.com --non-interactive --agree-tos --email admin@atvpc.com --http-01-port=80
	
	cat /srv/certbot/etc/live/wiki.atvpc.com/fullchain.pem /srv/certbot/etc/live/wiki.atvpc.com/privkey.pem | sudo tee /srv/certbot/ssl/wiki.atvpc.com.pem

certbot-renew:
	docker run -it --rm --name certbot --net dockernet --ip 172.18.0.20 -v "/srv/certbot/etc:/etc/letsencrypt" \
	certbot/certbot \
	renew --force-renewal --tls-sni-01-port=80

	cat /srv/certbot/etc/live/wiki.atvpc.com/fullchain.pem /srv/certbot/etc/live/wiki.atvpc.com/privkey.pem | sudo tee /srv/certbot/ssl/wiki.atvpc.com.pem

renew-cert: certbot-renew stop-haproxy build-haproxy run-haproxy
