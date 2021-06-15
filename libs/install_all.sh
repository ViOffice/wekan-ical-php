#!/usr/bin/env bash

# Enter 3rdparty library dir
mkdir -p ./3rdparty/
cd ./3rdparty/

# QR-Code
mkdir -p php-qrcode; cd php-qrcode
composer require chillerlan/php-qrcode
cd ../

# Leave 3rdparty-directory
cd ../

# EOF
