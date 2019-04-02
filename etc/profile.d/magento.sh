function mage-flush () {
	DIR=$(pwd)
	cd /srv/htdocs/store.atvpc.com
	sudo -u www-data php7.1 bin/magento c:c
	sudo -u www-data php7.1 bin/magento c:f
	redis-cli flushall
	cd "$DIR"
}

alias mage='cd /srv/htdocs/store.atvpc.com && sudo -u www-data php7.1 bin/magento'
alias mage-scaleimg='mogrify -resize 800x600 *.jpg && jpegoptim -m90 *.jpg'
