server {
	listen 80;
	listen [::]:80;
	server_name cctv.atvpc.com;

	root /srv/htdocs/cctv.atvpc.com;
	index index.php;

	location / {
	if ($remote_addr = 50.249.20.86) {
		rewrite ^ http://cctv.atvpc.com/local.php break;
	}
	}

	location ~ \.php$ {
		include snippets/fastcgi-php.conf;
		fastcgi_pass unix:/run/php/php7.2-fpm.sock;
	}
}
