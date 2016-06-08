# viw_symfony2

The repository is prepared to be auto-deployed for every commit in master pushed to Github repository using 
[Travis](https://travis-ci.org/TransmediaCatalonia/viw-project/)

If something goes wrong, you can deploy manually.

## Requirements

#### lftp
For installing it in Ubuntu:

    sudo apt-get install -y lftp

#### composer  

    curl -sS https://getcomposer.org/installer | php
    
    sudo mv composer.phar /usr/local/bin/composer

## Set environment variables

Add the following lines to your ~/.bashrc (or .zshrc) and replace the values properly:

    export VIW_FTP_HOST="host"
    export VIW_FTP_USER="user"
    export VIW_FTP_PASS="password"
    export VIW_LCD="local-path-to-the-project"

Load .bashrc file:

    source ~/.bashrc

## Deploy

Run the following command:

    scripts/deploy.sh
