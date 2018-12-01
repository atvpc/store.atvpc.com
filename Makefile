HOSTNAME := $(shell hostname)

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
	sudo stow -t / all-hosts
	sudo grub-mkconfig --output /boot/grub/grub.cfg
	sudo systemctl restart netdata fail2ban
	sudo ufw allow ssh
	sudo ufw enable

www.atvpc.com:
	sudo stow -t / www.atvpc.com
	cd /srv/secrets; sudo stow -t / www.atvpc.com
	sudo systemctl restart php7.2-fpm nginx
	sudo ufw allow "Nginx Full"

	sudo certbot --nginx -d atvpc.com -d www.atvpc.com -d wiki.atvpc.com
	sudo systemctl restart nginx
