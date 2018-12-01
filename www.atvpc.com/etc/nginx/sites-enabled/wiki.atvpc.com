server {
	listen 80;
	listen [::]:80;
	server_name wiki.atvpc.com;
	return 301 https://$host$request_uri;
}

server {
	listen [::]:443 ssl; # managed by Certbot
	listen 443 ssl; # managed by Certbot

	server_name wiki.atvpc.com;
	server_tokens off;

	root /srv/htdocs/wiki.atvpc.com;
	index index.php;

	location / {
		try_files $uri $uri/ =404;
	}

	location ~ \.php$ {
		include snippets/fastcgi-php.conf;
		fastcgi_pass unix:/run/php/php7.2-fpm.sock;
	}

	location ~ /\.ht {
		deny all;
	}

	# secure dokuwiki
	location ~ /(data|conf|bin|inc)/ {
		deny all;
	}

    ssl_certificate /etc/letsencrypt/live/atvpc.com/fullchain.pem; # managed by Certbot
    ssl_certificate_key /etc/letsencrypt/live/atvpc.com/privkey.pem; # managed by Certbot
    include /etc/letsencrypt/options-ssl-nginx.conf; # managed by Certbot
    ssl_dhparam /etc/letsencrypt/ssl-dhparams.pem; # managed by Certbot


}

