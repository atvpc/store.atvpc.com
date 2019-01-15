HOSTNAME := $(shell hostname)
USER := $(shell whoami)

first-run: update all $(HOSTNAME)

update:
	xargs -a ./pkginstall/remove.list sudo apt purge
	sudo apt autoremove

	sudo apt update
	sudo apt upgrade

	xargs -a ./pkginstall/package.list sudo apt install
	xargs -a ./pkginstall/$(HOSTNAME).list sudo apt install

	sudo apt autoremove

all:
	stow -t /home/$(USER) user

	sudo rm /etc/ssh/sshd_config
	sudo rm /etc/default/motd-news
	sudo stow -t / all-hosts

	sudo systemctl restart netdata fail2ban sshd
	sudo ufw allow ssh
	sudo ufw enable

www.atvpc.com:
	sudo stow -t / www.atvpc.com
	cd /srv/secrets; sudo stow -t / www.atvpc.com
	sudo systemctl restart php7.2-fpm nginx
	sudo ufw allow "Nginx Full"

	sudo certbot --nginx -d atvpc.com -d www.atvpc.com -d wiki.atvpc.com
	sudo systemctl restart nginx

store.atvpc.com:
	sudo stow -t / store.atvpc.com
	sudo chown www-data:www-data -R /srv/htdocs/store.atvpc.com
	sudo chmod g+rw -R /srv/htdocs/store.atvpc.com
#	sudo -u www-data php7.1 /srv/htdocs/store.atvpc.com/bin/magento setup:static-content:deploy -f

	sudo add-apt-repository ppa:ondrej/php
	sudo apt update
	xargs -a ./pkginstall/magento-php7.1-workaround.list sudo apt install

	sudo systemctl enable nginx mariadb php7.1-fpm redis-server
	sudo systemctl restart nginx mariadb php7.1-fpm redis-server

	sudo ufw allow "Nginx Full"
	sudo certbot --nginx -d store.atvpc.com
	sudo systemctl restart nginx
