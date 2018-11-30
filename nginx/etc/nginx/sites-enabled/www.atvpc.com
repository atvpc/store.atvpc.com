server {
	listen 80;
	listen [::]:80;
	server_name atvpc.com;
	return 301 http://www.atvpc.com$request_uri;

    listen [::]:443 ssl ipv6only=on; # managed by Certbot
    listen 443 ssl; # managed by Certbot
    ssl_certificate /etc/letsencrypt/live/atvpc.com/fullchain.pem; # managed by Certbot
    ssl_certificate_key /etc/letsencrypt/live/atvpc.com/privkey.pem; # managed by Certbot
    include /etc/letsencrypt/options-ssl-nginx.conf; # managed by Certbot
    ssl_dhparam /etc/letsencrypt/ssl-dhparams.pem; # managed by Certbot

}

server {
	listen 80;
	listen [::]:80;
	server_name atvpartsconnection.com;
	return 301 http://www.atvpc.com$request_uri;
}

server {
	listen 80;
	listen [::]:80;

	server_name www.atvpc.com;
	server_tokens off;

	root /srv/htdocs/atvpc.com;
	index index.php index.html;

	error_page 404 /index.php?404;

	# secure PicoCMS
	location ~ ^/((config|content|vendor|composer\.(json|lock|phar))(/|$)|(.+/)?\.(?!well-known(/|$))) {
		try_files /index.php$is_args$args =404;
	}

	location ~ \.php$ {
		include snippets/fastcgi-php.conf;
		fastcgi_pass unix:/run/php/php7.2-fpm.sock;
	}

	location / {
		try_files $uri $uri/ =404;
	}

    listen [::]:443 ssl; # managed by Certbot
    listen 443 ssl; # managed by Certbot
    ssl_certificate /etc/letsencrypt/live/atvpc.com/fullchain.pem; # managed by Certbot
    ssl_certificate_key /etc/letsencrypt/live/atvpc.com/privkey.pem; # managed by Certbot
    include /etc/letsencrypt/options-ssl-nginx.conf; # managed by Certbot
    ssl_dhparam /etc/letsencrypt/ssl-dhparams.pem; # managed by Certbot

}
