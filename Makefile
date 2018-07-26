build:
	docker build -t wiki ./wiki.atvpc.com
	docker build -t haproxy ./haproxy
run:
	docker network create --subnet=172.18.0.0/16 dockernet
	docker run --net dockernet --ip 172.18.0.10 -d wiki -v ./wiki.atvpc.com/htdocs:/var/www/html
	docker run --net dockernet -p 80:80 -d haproxy

