first-run:
	sudo apt update
	sudo apt upgrade
	sudo apt autoremove
	xargs -a package.list sudo apt install
	sudo rm /etc/fstab
	sudo stow -t / fstab

stow:
	sudo stow -t / nginx php git
	sudo systemctl restart php7.2-fpm nginx
