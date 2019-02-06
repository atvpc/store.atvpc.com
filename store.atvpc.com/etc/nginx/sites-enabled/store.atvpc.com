upstream fastcgi_backend {
    server   unix:/var/run/php/php7.1-fpm.sock;
}

server {
    server_name store.atvpc.com;

    set $MAGE_ROOT /srv/htdocs/store.atvpc.com;

    location ~ \.css {
       add_header Content-Type text/css;
    }

    location ~ \.js {
       add_header Content-Type application/x-javascript;
    }

    fastcgi_param HTTPS on;

    include /srv/htdocs/store.atvpc.com/nginx.conf.sample;

    # start certbot
    listen [::]:443 ssl ipv6only=on; # managed by Certbot
    listen 443 ssl; # managed by Certbot
    ssl_certificate /etc/letsencrypt/live/store.atvpc.com/fullchain.pem; # managed by Certbot
    ssl_certificate_key /etc/letsencrypt/live/store.atvpc.com/privkey.pem; # managed by Certbot
    include /etc/letsencrypt/options-ssl-nginx.conf; # managed by Certbot
    ssl_dhparam /etc/letsencrypt/ssl-dhparams.pem; # managed by Certbot
    # end certbot
}


server {
    if ($host = store.atvpc.com) {
        return 301 https://$host$request_uri;
    } # managed by Certbot


    listen 80;
    listen [::]:80;

    server_name store.atvpc.com;
    return 404; # managed by Certbot


}
