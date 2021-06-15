# wekan-ical-php

[![reuse compliant](https://reuse.software/badge/reuse-compliant.svg)](https://reuse.software/) [![Hosted on Codeberg](https://img.shields.io/badge/Codeberg-Main%20Repository-blue.svg)](https://codeberg.org/ViOffice/wekan-ical-php) [![Github Mirror](https://img.shields.io/badge/Github-Mirror-blue.svg)](https://github.com/ViOffice/wekan-ical-php) [![Latest Release](https://img.shields.io/badge/Latest-0.0.2-green.svg)](https://codeberg.org/ViOffice/wekan-ical-php/releases/tag/0.0.2)

Calendar Synchronisation for Wekan. Supports single ical files or webcal sync.

## Requirements:

* PHP (>=7)
    * `php-curl`
    * [`php-qrcode`](https://github.com/chillerlan/php-qrcode)
    * `php-mysql`

* Webserver with PHP support (e.g. Apache2)

* MySQL or MariaDB

## Install required dependencies

Ubuntu 20.04 Server (most likely also Debian 10):

```
# LAMP-stack
apt install apache2 php libapache2-mod-php mariadb-server php

# PHP modules
apt install php-curl php-mysql

# 3rdparty libraries
cd libs/
chmod +x ./install_all.sh
sudo -u www-data ./install_all.sh
```

## Version-Upgrade

If you are running wekan-ical-php straight from `main` branch:

```
git pull
```

If you are running from a specific release:

```
git checkout main
git pull
git checkout 0.0.2
```
Either way, please take a look at the changelog from last commits or releases
and update your configurations and translations in `conf/` accordingly.

## Maintainers

* [Jan Weymeirsch](https://jan.weymeirs.ch)
    * Contact: [dev-AT-vioffice-DOT-de](mailto:dev<AT>vioffice<DOT>de)

## Contribute

Any pull requests or suggestions are welcome on the main repository at
<https://codeberg.org/ViOffice/wekan-ical-php>, the Github-Mirror at
<https://github.com/ViOffice/wekan-ical-php> or via [e-mail to the
maintainers](#maintainers).

Please make sure, your changes are
[REUSE-compliant](https://git.fsfe.org/reuse/tool)

## License

Copyright (C) 2021 [Weymeirsch und Langer GbR](mailto:dev<AT>vioffice<DOT>de)

See Licenses [here](LICENSES).
