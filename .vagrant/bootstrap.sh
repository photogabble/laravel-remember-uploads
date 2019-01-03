#!/usr/bin/env bash

Update () {
    echo "-- Update packages --"
    sudo apt-get update
    sudo apt-get upgrade
}
Update

echo "-- Install PPA's --"
sudo add-apt-repository ppa:ondrej/php
Update

echo "-- Install packages --"
sudo apt-get install -y --force-yes curl git git-core
sudo apt-get install -y --force-yes php7.1-common php7.1-dev php7.1-json php7.1-opcache php7.1-cli php7.1 php7.1-mysql php7.1-fpm php7.1-curl php7.1-gd php7.1-mcrypt php7.1-mbstring php7.1-bcmath php7.1-zip php7.1-xml

echo "-- Install Composer --"
curl -s https://getcomposer.org/installer | php
sudo mv composer.phar /usr/local/bin/composer
sudo chmod +x /usr/local/bin/composer
