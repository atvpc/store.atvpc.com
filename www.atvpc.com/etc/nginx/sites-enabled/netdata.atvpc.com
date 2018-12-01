upstream netdata-backend {
	server 127.0.0.1:19999;
	keepalive 64;
}

server {
	listen 80;
	listen [::]:80;

	server_name netdata.atvpc.com;
	server_tokens off;

	auth_basic "Protected";
	auth_basic_user_file passwords;

	location / {
		proxy_set_header X-Forwarded-Host $host;
		proxy_set_header X-Forwarded-Server $host;
		proxy_set_header X-Forwarded-For $proxy_add_x_forwarded_for;
		proxy_pass http://netdata-backend;
		proxy_http_version 1.1;
		proxy_pass_request_headers on;
		proxy_set_header Connection "keep-alive";
	        proxy_store off;
	}
}
