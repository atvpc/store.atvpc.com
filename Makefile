first-run:
	sudo apt update
	sudo apt upgrade
	sudo apt autoremove

install-packages:
	sudo apt update
	xargs -a package.list sudo apt install
