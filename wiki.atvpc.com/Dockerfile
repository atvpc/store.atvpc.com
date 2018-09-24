FROM php:apache

RUN apt-get update && \
	DEBIAN_FRONTEND=noninteractive apt-get install -yq zabbix-agent && \
	rm -rf /var/lib/apt/lists/*

COPY conf/zabbix_agentd.conf /etc/zabbix/

RUN /etc/init.d/zabbix_agent -c /etc/zabbix/zabbix_agentd.conf

RUN chown 33:33 -R /var/www/html
