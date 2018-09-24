FROM php:apache

RUN apt-get update && apt-get install -y zabbix-agent
COPY conf/zabbix_agentd.conf /etc/zabbix/
RUN systemctl enable zabbix-agent && systemctl start zabbix-agent

RUN chown 33:33 -R /var/www/html
