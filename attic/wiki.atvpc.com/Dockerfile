FROM php:apache
MAINTAINER timothy@atvpc.com

RUN apt-get update && \
	apt-get install -y zabbix-agent && \
	rm -rf /var/lib/apt/lists/*

COPY zabbix_agentd.conf /etc/zabbix/
RUN mkdir -p /var/run/zabbix && chown zabbix:zabbix /var/run/zabbix

RUN chown 33:33 -R /var/www/html

EXPOSE 10050
CMD /usr/sbin/zabbix_agentd -c /etc/zabbix/zabbix_agentd.conf ; apache2-foreground