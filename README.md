Joomla 3.x UTD (up-to-date)
===========================

According to [market share estimates](https://w3techs.com/technologies/details/cm-joomla) (as of May 2026), Joomla 3.x is currently used on more than 50% of all installed Joomla sites worldwide.

However, official support for Joomla 3.x was ended on February 2025 (counting the eLTS program).

So we're actively developing Joomla 3.x UTD as an up-to-date distribution of the Joomla 3.x content management system, built to ensure code security and support modern PHP & MySQL/MariaDB versions.

---

## CHANGELOG

## Version 3.12 - released May 21st, 2026
Summary of changes:
- Built-in update server: sites running 3.12 or newer can now receive updates directly via the Joomla backend updater
- Removed legacy bundled items: `eos310` & `phpversioncheck` quickicon plugins, `beez3` frontend template, `hathor` backend template
- Further PHP 8.x compatibility fixes, extending coverage to previously missed files
- Additional security patches backported from Joomla 4/5/6
- Fixed the getModuleById method in JModuleHelper to correctly return a module's data using its ID.

Please note that if you had `beez3` or `hathor` as one of your frontend or backend (respectively) default templates, upgrading to this version will set `protostar` and `isis` as your new defaults (respectively). If you use another frontend template, it will (of course) not be updated...

## Version 3.11 - released April 20th, 2026
Summary of changes:
- Joomla 3.x is now compatible with PHP up to version 8.5
- Includes security patches for CVEs reported after Joomla 3.10.20 eLTS was released
- Includes additional security patches & some quality-of-life improvements
- Works better with MySQL 8.x

For detailed changelog, please visit: https://github.com/joomlaworks/joomla-3.x/blob/main/CHANGELOG.md


## TO DO
- Maintain modern PHP compatibility and apply security patches when necessary


## HOW TO INSTALL / UPGRADE
To install: just extract the latest rolling release https://github.com/joomlaworks/joomla-3.x/releases/download/rolling/joomla-latest.zip where you want the site to be and then follow the normal Joomla installation process.

To upgrade manually: using your server's terminal or a file manager, extract the latest rolling release https://github.com/joomlaworks/joomla-3.x/releases/download/rolling/joomla-latest.zip on top of an existing Joomla 3.10.12 (or newer) installation. Remember to remove the `/installation` folder afterwards.

In a typical Linux based server, you can easily do the upgrade using the following one-liner command (after you "cd" into your Joomla site's folder):
```
wget -qO- https://github.com/joomlaworks/joomla-3.x/archive/refs/heads/main.tar.gz | tar -xz --strip-components=1 && rm -rf installation .github .gitignore *.md
```

### Upgrading via the Joomla Update Component (from v3.12 onwards)

> **⚠ IMPORTANT — Action required for sites already running v3.11 or earlier:**
>
> Starting with v3.12, this distribution ships its own update feed. Sites on v3.12 or newer will receive future updates automatically through the Joomla updater (`Components → Joomla Update`).
>
> If your site is currently running **v3.11 or earlier**, you must point the Joomla updater to this distribution's update feed **once** before in-panel updates will work.
>
> Adjust the options for the Joomla Update Component (either from the component's "Options" or through Joomla's "Global Configuration") and use the following update URL in the "Custom URL" field, along with these settings:
>
> - Update Channel: Custom Url
> - Minimum Stability: Stable
> - Custom URL: `https://joomlaworks.github.io/joomla-3.x/list.xml`
>
> Once updated to v3.12 (or newer), this step is no longer necessary — future updates will be detected and applied automatically.


## NOTES ON MYSQL & MARIADB
For Joomla 3.x to work flawlessly with MySQL versions 8.0 or newer, you need to have this setting enabled in your my.cnf configuration:
```
# For MySQL 8.0 only
default_authentication_plugin = mysql_native_password

# For MySQL 8.4+
mysql_native_password         = ON
authentication_policy         = mysql_native_password
```
Use one or the other, not both. The above settings do not apply to MariaDB.

We also recommend the following setting for maximum compatibility in both MySQL and MariaDB:
```
sql_mode = ""
```


## NOTES ON OPERATING SYSTEM SUPPORT
This distribution is built solely for Linux based systems, cause let's be honest, you'll be hosting this on some Linux flavour, not Windows or MacOS. As such, we don't test on Windows or MacOS. Things ***should*** work just fine if you use something like XAMPP or MAMP respectively, but just know that we don't test against these OSes.


## CONTRIBUTE
If you'd like to contribute meaningful upgrades to existing functionality or fix bugs, feel free to open an issue in this project.


## DISCUSS
The discussion forum is now open: https://github.com/joomlaworks/joomla-3.x/discussions

Use it to report bugs with this distribution of Joomla 3.x only - this includes functional bugs for any existing feature in Joomla 3.x itself.


## CODE DOCUMENTATION (AI Generated)
[![Ask DeepWiki](https://deepwiki.com/badge.svg)](https://deepwiki.com/joomlaworks/joomla-3.x) [![zread](https://img.shields.io/badge/Ask_Zread-_.svg?style=flat&color=00b0aa&labelColor=000000&logo=data%3Aimage%2Fsvg%2Bxml%3Bbase64%2CPHN2ZyB3aWR0aD0iMTYiIGhlaWdodD0iMTYiIHZpZXdCb3g9IjAgMCAxNiAxNiIgZmlsbD0ibm9uZSIgeG1sbnM9Imh0dHA6Ly93d3cudzMub3JnLzIwMDAvc3ZnIj4KPHBhdGggZD0iTTQuOTYxNTYgMS42MDAxSDIuMjQxNTZDMS44ODgxIDEuNjAwMSAxLjYwMTU2IDEuODg2NjQgMS42MDE1NiAyLjI0MDFWNC45NjAxQzEuNjAxNTYgNS4zMTM1NiAxLjg4ODEgNS42MDAxIDIuMjQxNTYgNS42MDAxSDQuOTYxNTZDNS4zMTUwMiA1LjYwMDEgNS42MDE1NiA1LjMxMzU2IDUuNjAxNTYgNC45NjAxVjIuMjQwMUM1LjYwMTU2IDEuODg2NjQgNS4zMTUwMiAxLjYwMDEgNC45NjE1NiAxLjYwMDFaIiBmaWxsPSIjZmZmIi8%2BCjxwYXRoIGQ9Ik00Ljk2MTU2IDEwLjM5OTlIMi4yNDE1NkMxLjg4ODEgMTAuMzk5OSAxLjYwMTU2IDEwLjY4NjQgMS42MDE1NiAxMS4wMzk5VjEzLjc1OTlDMS42MDE1NiAxNC4xMTM0IDEuODg4MSAxNC4zOTk5IDIuMjQxNTYgMTQuMzk5OUg0Ljk2MTU2QzUuMzE1MDIgMTQuMzk5OSA1LjYwMTU2IDE0LjExMzQgNS42MDE1NiAxMy43NTk5VjExLjAzOTlDNS42MDE1NiAxMC42ODY0IDUuMzE1MDIgMTAuMzk5OSA0Ljk2MTU2IDEwLjM5OTlaIiBmaWxsPSIjZmZmIi8%2BCjxwYXRoIGQ9Ik0xMy43NTg0IDEuNjAwMUgxMS4wMzg0QzEwLjY4NSAxLjYwMDEgMTAuMzk4NCAxLjg4NjY0IDEwLjM5ODQgMi4yNDAxVjQuOTYwMUMxMC4zOTg0IDUuMzEzNTYgMTAuNjg1IDUuNjAwMSAxMS4wMzg0IDUuNjAwMUgxMy43NTg0QzE0LjExMTkgNS42MDAxIDE0LjM5ODQgNS4zMTM1NiAxNC4zOTg0IDQuOTYwMVYyLjI0MDFDMTQuMzk4NCAxLjg4NjY0IDE0LjExMTkgMS42MDAxIDEzLjc1ODQgMS42MDAxWiIgZmlsbD0iI2ZmZiIvPgo8cGF0aCBkPSJNNCAxMkwxMiA0TDQgMTJaIiBmaWxsPSIjZmZmIi8%2BCjxwYXRoIGQ9Ik00IDEyTDEyIDQiIHN0cm9rZT0iI2ZmZiIgc3Ryb2tlLXdpZHRoPSIxLjUiIHN0cm9rZS1saW5lY2FwPSJyb3VuZCIvPgo8L3N2Zz4K&logoColor=ffffff)](https://zread.ai/joomlaworks/joomla-3.x)


## LONGTERM PLAN (as a different project)
A new fork is on the way, based on Joomla 3.x. This fork is WIP (but very, very active) and when released it will feature:
- A stripped down version of Joomla 3.x with all non-essential extensions removed.
- Fully compatible with PHP versions from 7.4 to 8.x and so on.
- Fully compatible with the latest versions of MySQL & MariaDB.
- The focus shifts to using K2 for content. This means that com_content (and anything related) is removed entirely. This way important content features are decoupled from the CMS base, which aims to be a solid platform for building sites, while maintaining true backwards compatibility with past releases (of the fork).
- Admin refresh.
- Gradual jQuery/Mootools removal - switch to modern JS only.
- Gradual codebase modernization to support future PHP & MySQL/MariaDB versions without much effort.

***

Copyright &copy; 2005 - 2025 [Open Source Matters, Inc.](https://www.opensourcematters.org), 2026 [JoomlaWorks Ltd.](https://www.joomlaworks.net)
