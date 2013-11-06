#!/bin/bash
#
# Author: Ray Viljoen <ray@fubra.com>

APP="Fubra Analytics"

# Make sure we quit on error
set -e

echo "

This script will uninstall ${APP}:
    
    - Remove cron jobs
"

# Get install directory
INSTALL_DIR=$(dirname $0)

# Prompt to confirm install
read -p "Are you sure you wnt to uninstall ${APP} cron jobs? (y/n)" -n 1

    echo -e "\n"

    if [[ $REPLY =~ ^[Yy]$ ]]; then

        # Run cron uninstall
        $INSTALL_DIR/cron_uninstall.php

        # Done
        echo -e "\n\n UNINSTALL COMPLETE!\n"

    fi