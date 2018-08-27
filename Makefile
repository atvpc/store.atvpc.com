git:
ifneq ($(wildcard ./www.atvpc.com/htdocs/.),)
	cd www.atvpc.com/htdocs ; git pull
	cd www.atvpc.com/htdocs/xpaxle ; git pull
else
	git clone https://github.com/atvpc/atvpc.com.git www.atvpc.com/htdocs
	git clone https://github.com/atvpc/xpaxle-page.git www.atvpc.com/htdocs/xpaxle
endif

build: git
	docker build -t www ./www.atvpc.com
	docker build -t wiki ./wiki.atvpc.com
	docker build -t haproxy ./haproxy
run:
	-docker network create --subnet=172.18.0.0/16 dockernet
	docker run --net dockernet --ip 172.18.0.10 -v /srv/wiki.atvpc.com/htdocs:/var/www/html -d wiki 
	docker run --net dockernet --ip 172.18.0.50 -d www
	docker run --net dockernet -p 80:80 -d haproxy

stop:
	docker stop $(docker ps -aq)
