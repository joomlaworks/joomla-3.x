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


## HOW TO USE / UPGRADE
To upgrade: Grab the project zip https://github.com/joomlaworks/joomla-3.x/archive/refs/heads/main.zip (or the one for a specific version) and using your server's terminal or file manager, extract on top of an existing Joomla 3.10.12 (or newer version) you wisth to upgrade. Remember to remove the "/installation" folder and you're done.

To install from scratch: just extract and follow the installation process as you always did.


## CONTRIBUTE
If you'd like to contribute meaningful upgrades to existing functionality or fix bugs, feel free to open an issue in this project.


## DISCUSS
The discussion forum is now open: https://github.com/joomlaworks/joomla-3.x/discussions

Use it to report bugs with this distribution of Joomla 3.x only - this includes functional bugs for any existing feature in Joomla 3.x itself.


## LONGTERM PLAN AS A DIFFERENT PROJECT
A new fork is on the way, based on Joomla 3.x. This fork is WIP (but very, very active) and it will feature:
- A stripped down version of Joomla 3.x with all non-essential extensions removed
- The focus shifts to K2 and com_content (and anything related) is removed entirely - this way important content features are decoupled from the CMS base which aims to be a solid platform for building sites, maintaining true backwards compatibility for future releases
- Complete jQuery/Mootools removal - switch to modern JS.
- Codebase gradually modernized to support future PHP & MySQL/MariaDB versions
