# Installation
-----------------

This installation guide will help you install Polr 2.0, the latest iteration of Polr.

## Server Requirements

The following software is required on your server to run Polr 2.0.
In the case that you cannot fulfill the following requirements (e.g free shared hosting),
you may be interested in looking at a [legacy 1.x release](https://github.com/cydrobolt/polr/releases) of Polr (now unsupported).

 - Apache, nginx, IIS, or lighttpd (Apache preferred)
 - PHP >= 5.5.9
 - MariaDB or MySQL >= 5.5, SQLite alternatively
 - Composer (optional)
 - PHP requirements:
    - OpenSSL PHP Extension
    - PDO PHP Extension
    - PDO MySQL Driver (php-mysql on Debian & Ubuntu, php5x-pdo_mysql on FreeBSD)
    - Mbstring PHP Extension
    - Tokenizer PHP Extension
    - JSON PHP Extension

## Downloading the source code

```bash
$ sudo su
$ cd /var/www
$ git clone https://github.com/cydrobolt/polr.git -b 2.0-dev --single-branch
```

## Installing using `composer`

```bash
# download composer package
curl -sS https://getcomposer.org/installer | php
# update/install dependencies
php composer.phar install --no-dev -o
```

## Running Polr on...

### Apache

To run Polr on Apache, you will need to add a virtual host to your
`httpd-vhosts.conf` like so:

Replace `example.com` with your server's external address.

```apache
<VirtualHost *:80>
    ServerName example.com # Your external address
    ServerAlias example.com # Make this the same as ServerName
    DocumentRoot "/var/www/polr/public"
    <Directory "/var/www/polr/public">
        Require all granted # Used by Apache 2.4
        Options Indexes FollowSymLinks
        AllowOverride All
        Order allow,deny
        Allow from all
    </Directory>
</VirtualHost>
```

If `mod_rewrite` is not already enabled, you will need to enable it like so:

```bash
# enable mod_rewrite
a2enmod rewrite
# restart apache on Ubuntu
# sudo service apache2 restart
# restart apache on Fedora/CentOS
# sudo service httpd restart
```
### nginx

Replace `example.com` with your server's external address. You will need to install `php5-fpm`:

```
$ sudo apt-get install php5-fpm
```

Useful LEMP installation tutorial by [DigitalOcean](https://www.digitalocean.com/community/tutorials/how-to-install-linux-nginx-mysql-php-lemp-stack-on-ubuntu-12-04)

```nginx
# Upstream to abstract backend connection(s) for php
upstream php {
    server unix:/var/run/php-fpm.sock;
    server 127.0.0.1:9000;
}

server {
    listen   *:80;
    listen   *:443 ssl;
    ssl_certificate     /etc/ssl/my.crt;
    ssl_certificate_key /etc/ssl/private/my.key;
    root /var/www;
    server_name example.com; # Or whatever you want to use
    
    if ($scheme != "https") {
        return 301 https://$server_name$request_uri;
    }
    
    location / {
            try_files $uri $uri/ /index.php?$query_string;
            rewrite ^/([a-zA-Z0-9]+)/?$ /index.php?$1;
    }
    
    location ~ \.php$ { 
            try_files $uri =404;
            include /etc/nginx/fastcgi_params;
    
            fastcgi_pass    php;
            fastcgi_index   index.php;
            fastcgi_param   SCRIPT_FILENAME $document_root$fastcgi_script_name;
            
            fastcgi_param   HTTP_HOST       'example.com'; # Your site's domain name goes here.
    }
}
```
### Shared hosting/other

To run Polr on another HTTP server or on shared hosting, you will need to set the home
directory to `/PATH_TO_POLR/public`, not the root Polr folder.

## Create necessary databases

### MySQL

You must create a database for Polr to use before you can complete the setup script.
To create a database for Polr, you can log onto your `mysql` shell and run the following command:

```sql
CREATE DATABASE polrdatabasename;
```

Remember this database name, as you will need to provide it to Polr during setup.
Additionally, if you wish to create a new user with access to soloely this database, please look into MySQL's [GRANT](https://dev.mysql.com/doc/refman/5.7/en/grant.html) directive.

### SQLite

You may also use SQLite in place of MySQL for Polr. However, SQLite is not recommended for use with Polr.


## Option 1: Run automatic setup script

Once your server is properly set up, you will need to configure Polr and
enable it to access the database.

Head over to your new Polr instance, at the path `/setup/` to configure
your instance with the correct information.

This will automatically create your databases and write a configuration file to disk, `.env`. You may make changes later on by editing this file.

You should be set. You may go back to your Polr homepage and log in to perform
any other actions.

## Option 2: Write configuration and database manually

If you wish to configure and initialize Polr manually, you may do so using
your command line.

Rename `.env.example` to `.env` and update the values appropriately.
You may then run the following `artisan` command to populate the database.
You will also need to insert a admin user into the `users` table through `mysql`
or a graphical `sql` interface.

```bash
php artisan migrate
```

This should create the necessary databases.
