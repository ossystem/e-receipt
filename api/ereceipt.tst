server {
    listen 80;
    listen 9200;	
    server_name ereceipt.tst;
    root /var/www/ereceipt.tst/client;

    location / {
        # try to serve file directly, fallback to index.php
        try_files $uri /index.php$is_args$args;
    }

    location ~ ^/index\.php(/|$) {
        fastcgi_pass unix:/var/run/php/php7.2-fpm.sock;
        fastcgi_split_path_info ^(.+\.php)(/.*)$;
        include fastcgi_params;
	fastcgi_read_timeout 3000;
	fastcgi_param SERVER_NAME $http_host;

        # optionally set the value of the environment variables used in the application
        fastcgi_param MYSQL_HOST localhost;
        fastcgi_param MYSQL_PORT 3306;
	fastcgi_param MYSQL_DB ereceipt;
	fastcgi_param MYSQL_USER root;
	fastcgi_param MYSQL_PASS 123456;

	fastcgi_param API_SERVER http://seleznyov9300.ossystem.ua/;

        # fastcgi_param APP_ENV prod;
        # fastcgi_param APP_SECRET <app-secret-id>;
        # fastcgi_param DATABASE_URL "mysql://db_user:db_pass@host:3306/db_name";

        # When you are using symlinks to link the document root to the
        # current version of your application, you should pass the real
        # application path instead of the path to the symlink to PHP
        # FPM.
        # Otherwise, PHP's OPcache may not properly detect changes to
        # your PHP files (see https://github.com/zendtech/ZendOptimizerPlus/issues/126
        # for more information).
        fastcgi_param SCRIPT_FILENAME $realpath_root$fastcgi_script_name;
        fastcgi_param DOCUMENT_ROOT $realpath_root;
        # Prevents URIs that include the front controller. This will 404:
        # http://domain.tld/index.php/some-path
        # Remove the internal directive to allow URIs like this
        internal;
    }


    # return 404 for all other php files not matching the front controller
    # this prevents access to other php files you don't want to be accessible.
    location ~ \.php$ {
        return 404;
    }

    error_log /var/log/nginx/ereceipt_client_error.log;
    access_log /var/log/nginx/ereceipt_client_access.log;
}

server {
    listen 80;
    listen 9300;
    server_name api.ereceipt.tst;
    root /var/www/ereceipt.tst/api;

    location / {
        # try to serve file directly, fallback to index.php
        try_files $uri /index.php$is_args$args;
    }

    location ~ ^/index\.php(/|$) {
        fastcgi_pass unix:/var/run/php/php7.2-fpm.sock;
        fastcgi_split_path_info ^(.+\.php)(/.*)$;
        include fastcgi_params;
	fastcgi_read_timeout 3000;

        # optionally set the value of the environment variables used in the application
        fastcgi_param FISCAL_SERVER http://80.91.165.208/er;
        fastcgi_param CRYPT_SERVER http://192.168.1.172;
	fastcgi_param CRYPT_SERVER_PORT 3100;
	fastcgi_param CONNECTION_TIMEOUT 20;

        # fastcgi_param APP_SECRET <app-secret-id>;
        # fastcgi_param DATABASE_URL "mysql://db_user:db_pass@host:3306/db_name";

        # When you are using symlinks to link the document root to the
        # current version of your application, you should pass the real
        # application path instead of the path to the symlink to PHP
        # FPM.
        # Otherwise, PHP's OPcache may not properly detect changes to
        # your PHP files (see https://github.com/zendtech/ZendOptimizerPlus/issues/126
        # for more information).
        fastcgi_param SCRIPT_FILENAME $realpath_root$fastcgi_script_name;
        fastcgi_param DOCUMENT_ROOT $realpath_root;
        # Prevents URIs that include the front controller. This will 404:
        # http://domain.tld/index.php/some-path
        # Remove the internal directive to allow URIs like this
        internal;
    }


    # return 404 for all other php files not matching the front controller
    # this prevents access to other php files you don't want to be accessible.
    location ~ \.php$ {
        return 404;
    }

    error_log /var/log/nginx/ereceipt_api_error.log;
    access_log /var/log/nginx/ereceipt_api_access.log;
}
