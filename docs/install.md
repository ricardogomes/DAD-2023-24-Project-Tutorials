---
outline: deep
prev:
  text: "Connect to VM"
  link: "./connect"
next:
  text: "Deploy Code"
  link: "./deploy"
---

# Install Architecture Components and Dependencies

The suggested technology stack for our project deployment is displayed in this diagram:

![DAD Project Infrastructure](./assets/project_components.png)

The following steps are the setup of these components.

::: danger NOTICE

All of the commands in this Tutorial are to run on the Virtual Machine in an SSH Session ([read Connecting Tutorial](./connect.md)).

:::

## Pre Tasks

In order to make sure we have the latest versions of our packages run the following commands (the `sudo` command will require the user password which was communicated via email):

```bash
sudo apt update
sudo apt upgrade
```

## NGINX

[NGINX](https://www.nginx.com/) is web server, reverse proxy and load balancer. In this setup we will be using the web server and reverse proxy features.

Let's start by installing it:

```bash
sudp apt install nginx
```

The Ubuntu OS server version can come preinstalled with the Apache web server, and we want NGINX to listen to port 80, in order to make user we have it available we«ll disable Apache before enabling NGINX:

::: warning Note
If the Apache2 services does not exist the first two commands with return an error, but in our case that also means we are free to enable NGINX, so does errors are not relevant.
:::

```bash
sudo systemctl stop apache2
sudo systemctl disable apache2
sudo systemctl enable nginx
sudo systemctl start nginx
```

### Validation

To check every thing went ok we can run this command:

```bash
sudo systemctl status nginx
```

Or navigate with a browser to [http://<VM_IP>](http://<VM_IP>)

## MySQL

[MySQL](https://www.mysql.com/) is the relational database we'll be using for the project. To install it on the VM run:

```bash
sudo apt install mysql-server
```

The version of MySQL on the Ubuntu repositories is >= 8.0 which has a default authentication mechanism that cannot be used by PHP, so to start we'll fix that by running the following command that sets both the authentication mechanism as well as sets the root user password to `DAD@MySQL2023`. Feel free to change the password to something else.

```bash
sudo mysql -e "ALTER USER 'root'@'localhost' IDENTIFIED WITH mysql_native_password BY 'DAD@MySQL2023';"
```

:::warning Note
Keep in mind the root user password defined in this step. We'll need it to configure Laravel
:::

After this we should run a tool from MySQL that improves the security of our instance. Run the following commmand:

```bash
sudo mysql_secure_installation
```

This tool will ask you a set of questions that should be intuitive but here are the suggested responses:

1. Validate Password Component: y -- sets complexity rules for passwords
   1.1. Validation Level: 1 -- MEDIUM
2. Change existing root password: n -- keeps the password provided in the previous step
3. Remove anonymous user: y
4. Disallow root login remotely: y -- we'll see how to still have remote access in a bit
5. Remove teste databases: y
6. Reload privilege tables: y

### Validation

To check if everything is ok we can run the following command, inserting the password when asked:

```bash
mysql -u root -p
```

The result should be mysql shell where we can run SQL commands like `show databases; `. To exit type `exit`.

### Remote Access

Since we prohibited remote root access we can't connect to the MySQL server from our machines, but we can circumvent this using an SSH tunnel. Some SQL clients allow you to define this in their UI, but can run the following command and then connect to the server like if it was a local one (the command needs to stay running while using the GUI client).

```bash
# using ssh-agent
ssh -L 3306:localhost:3306 dad@<VM_IP>
# using key paths
ssh -L 3306:localhost:3306 dad@<VM_IP> -i <PATH_TO_VM_PRIVATE_KEY>
```

The second `3306` is the local port, so if we want to use another we can just change it.

## PHP-FPM | Laravel

The provided VM has Ubuntu version 20.04, and that release does not have the proper PHP version on their repositories, so the first thing is to add a repository that has the version we want. Run the following command:

```bash
sudo apt install -y software-properties-common ca-certificates apt-transport-https
sudo LC_ALL=C.UTF-8 add-apt-repository ppa:ondrej/php
sudo apt update
```

After this we can install PHP and the necessary dependencies for Laravel.

```bash
sudo apt-get install -y php8.1 php8.1-common php8.1-cli php8.1-gd php8.1-curl php8.1-intl php8.1-mbstring php8.1-bcmath php8.1-imap php8.1-xml php8.1-zip php8.1 php8.1-xml php8.1-zip php8.1-fpm php8.1-mysql
```

### Validation

To validate the install run this command and check the version:

```bash
php -v
```

### Composer

[Composer](https://getcomposer.org/) is the PHP package manager, for us its needed to install the dependencies on the VM and for some troubleshooting steps (like running `composer dump-autoload`). To install it run the following command:

```bash
php -r "copy('https://getcomposer.org/installer', 'composer-setup.php');"
php -r "if (hash_file('sha384', 'composer-setup.php') === 'e21205b207c3ff031906575712edab6f13eb0b361f2085f1f1237b7126d785e826a450292b6cfd1d64d92e6563bbde02') { echo 'Installer verified'; } else { echo 'Installer corrupt'; unlink('composer-setup.php'); } echo PHP_EOL;"
php composer-setup.php
php -r "unlink('composer-setup.php');"

sudo mv composer.phar /usr/local/bin/composer
```

To check we can simply run the `composer` command and verify its outputs.

## NodeJS

[Node.js](https://nodejs.org/en) will be used for the Web Socket server. Although we're using it for VueJS we wont need it for that on the server because we'll be deploying the statically built application (more on that on the [Deploy Tutorial](./deploy.md)).

```bash
sudo apt-get install -y ca-certificates curl gnupg
sudo mkdir -p /etc/apt/keyrings
curl -fsSL https://deb.nodesource.com/gpgkey/nodesource-repo.gpg.key | sudo gpg --dearmor -o /etc/apt/keyrings/nodesource.gpg

NODE_MAJOR=18
echo "deb [signed-by=/etc/apt/keyrings/nodesource.gpg] https://deb.nodesource.com/node_$NODE_MAJOR.x nodistro main" | sudo tee /etc/apt/sources.list.d/nodesource.list

sudo apt-get update
sudo apt-get install nodejs -y
```

### Validating

To validate this step run the following command and check its output.

```bash
node -v
```

### PM2

In order to support process management (stop, start, restart on crash ...) of our Web Sockets Server we'll install [PM2](https://pm2.keymetrics.io/). To simplify the management of permissions for Node modules we first defined the global install location to be in our users' path.

```bash
mkdir ~/.npm_global
npm config set prefix '~/.npm_global'
echo 'export PATH=$PATH:~/.npm_global/bin' >> ~/.bashrc
source ~/.bashrc
```

Then we can install PM2 globally:

```bash
npm install --global pm2
```

To validate simply run the `pm2` command.

::: tip Adicional Resources

- [Package management | Ubuntu](https://ubuntu.com/server/docs/package-management)
- [nginx documentation](https://nginx.org/en/docs/)
- [MySQL :: MySQL Documentation](https://dev.mysql.com/doc/)
- [PHP: FastCGI Process Manager (FPM) - Manual](https://www.php.net/manual/en/install.fpm.php)
- [Introduction - Composer](https://getcomposer.org/doc/00-intro.md)
- [Documentation | Node.js](https://nodejs.org/en/docs)
- [PM2 - Quick Start](https://pm2.keymetrics.io/docs/usage/quick-start/)
  :::
