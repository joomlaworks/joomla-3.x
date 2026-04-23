Joomla 3.x
=============

## CHANGELOG

## Version 3.11 - released April 20.04.2026
Summary of changes:
- Joomla 3.x is now compatible with PHP up to version 8.5
- Includes security patches for CVEs reported after Joomla 3.10.20 eLTS was released
- Includes additional security patches & some quality-of-life improvements
- Works better with MySQL 8.x

For detailed changelog, please visit: https://github.com/joomlaworks/joomla-3.x/blob/main/CHANGELOG.md


## TO DO
- Create endpoints to easily update Joomla 3.x after version 3.11, from within the Joomla backend
- Maintain modern PHP compatibility and apply security patches when necessary


## HOW TO INSTALL / UPGRADE
To install: just extract the current version https://github.com/joomlaworks/joomla-3.x/releases/download/v3.11/joomla-3.11.zip were you want the site to be and then follow the installation process (as with Joomla in general).

To upgrade: Using your server's terminal or a file manager, extract the current version https://github.com/joomlaworks/joomla-3.x/releases/download/v3.11/joomla-3.11.zip on top of an existing Joomla 3.10.12 (or newer version) you wish to upgrade. Remember to remove the "/installation" folder and you're done.

In a typical Linux based server, you can easily do the upgrade using the following one-liner command (after you "cd" into your Joomla site's folder):
```
cd /path/to/joomla/site/
wget -qO- https://github.com/joomlaworks/joomla-3.x/archive/refs/heads/main.tar.gz | tar -xz --strip-components=1
```


## NOTES ON MYSQL & MARIADB
For Joomla 3.x to work flawlessly with MySQL versions 8.0 or newer, you need to have this setting enabled in your my.cnf configuration:
```
# For MySQL 8.0 only
default_authentication_plugin = mysql_native_password

# For MySQL 8.4+
mysql_native_password         = ON
```
Use one or the other, not both. The above settings do not apply to MariaDB.

We also recommend the following setting for maximum compatibility in both MySQL and MariaDB:
```
sql_mode = ""
```


## CONTRIBUTE
If you'd like to contribute meaningful upgrades to existing functionality or fix bugs, feel free to open an issue in this project.


## DISCUSS
The discussion forum is now open: https://github.com/joomlaworks/joomla-3.x/discussions

Use it to report bugs with this distribution of Joomla 3.x only - this includes functional bugs for any existing feature in Joomla 3.x itself.


## LONGTERM PLAN (as a different project)
A new fork is on the way, based on Joomla 3.x. This fork is WIP (but very, very active) and when released it will feature:
- A stripped down version of Joomla 3.x with all non-essential extensions removed.
- Fully compatible with PHP versions from 7.4 to 8.x and so on.
- Fully compatible with the latest versions of MySQL & MariaDB.
- The focus shifts to using K2 for content. This means that com_content (and anything related) is removed entirely. This way important content features are decoupled from the CMS base, which aims to be a solid platform for building sites, while maintaining true backwards compatibility with past releases (of the fork).
- Admin refresh.
- Gradual jQuery/Mootools removal - switch to modern JS only.
- Gradual codebase modernization to support future PHP & MySQL/MariaDB versions without much effort.
