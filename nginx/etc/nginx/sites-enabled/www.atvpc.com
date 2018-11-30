server {
	listen 80;
	listen [::]:80;
	server_name atvpc.com;
	return 301 http://www.atvpc.com$request_uri;
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
}
