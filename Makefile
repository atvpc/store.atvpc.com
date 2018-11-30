first-run: update pkginstall firewall

update:
	sudo apt update
	sudo apt upgrade
	sudo apt autoremove

pkginstall:
	xargs -a package.list sudo apt install
	sudo rm /etc/fstab
	sudo stow -t / fstab nginx php git ssh

	# not version controlled
	cd /srv/secrets; sudo stow -t / nginx

	sudo systemctl restart php7.2-fpm nginx netdata fail2ban

firewall:
	sudo ufw allow ssh
	sudo ufw allow "Nginx Full"
	sudo ufw enable
