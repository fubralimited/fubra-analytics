#!/bin/bash
#
# Author: Ray Viljoen <ray@fubra.com>

APP="Fubra Analytics"

# Make sure we quit on error
set -e

echo "

This script will install ${APP}:
    
    - Check for and create a config file
    - Install Composer dependancies
    - Create database tables
    - Install daily, weekly and monthly cron jobs
"
# Get install directory
INSTALL_DIR=$(dirname $0)

# Get install directory
HTACCESS_DIR=$INSTALL_DIR/../http/.htaccess

# Prompt to confirm install
read -p "Do you want to continue? (y/n)" -n 1

    echo -e "\n"

    if [[ $REPLY =~ ^[Yy]$ ]]; then
            
        # Continue with install

        # Check for .htaccess file
        if [ -f $HTACCESS_DIR ]; then

            echo -e "* .htaccess file found \n"

        # Create .htaccess file
        else
            echo -e "* Creating .htaccess file\n"
            echo "RewriteEngine on" >> $HTACCESS_DIR
            echo "RewriteCond %{REQUEST_FILENAME} !-f" >> $HTACCESS_DIR
            echo "RewriteRule ^(.*)$ index.php [QSA,L]" >> $HTACCESS_DIR
            echo -e "\n# Enable short tags" >> $HTACCESS_DIR
            echo "php_value short_open_tag 1" >> $HTACCESS_DIR

        fi
        
        # Check for config file and continue if found
        if [ -f $INSTALL_DIR/../config.ini ]; then

            echo -e "* Config file found \n"

            echo -e "* Updating composer \n"

            # Run local composer self update
            ./composer/composer.phar self-update

            echo -e "* Installing composer dependancies \n"

            # Run composer install
            ./composer/composer.phar update -d ./composer

            echo -e "* Creating database tables \n"

            # Run database setup
            $INSTALL_DIR/database_install.php

            echo -e "* Installing cron jobs \n"

            # Run cron setup
            $INSTALL_DIR/cron_install.php

            # Done
            echo -e "\n\n INSTALL COMPLETE!"
        
        # Else create from sample
        else

            cp $INSTALL_DIR/sample_config.ini $INSTALL_DIR/../config.ini
            
            echo "* Config file created"

            echo "
                #####################################################################################
                  Enter site details into newly created config file then re-run this install script
                #####################################################################################
                "
        fi
    
    # Else exit
    else exit

    fi

