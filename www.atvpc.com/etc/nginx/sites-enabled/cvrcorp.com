server {
	listen 80;
	listen [::]:80;

	server_name cvrcorp.com www.cvrcorp.com cvrestoration.com www.cvrestoration.com;
	server_tokens off;

	root /srv/htdocs/cvrcorp.com;
	index index.html;

	location / {
		try_files $uri $uri/ =404;
	}
}
