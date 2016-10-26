## Installation Manual for the MageHost_BlockCache extension 

Install methods:
[Just copy to Magento repo](#user-content-just-copy-to-magento-repo)
[Git submodule + N98](#user-content-git-submodule--n98)

## Just copy to Magento repo

This installation manual explains how to use `Cm_Cache_Backend_File` based caching.
If you have experience with Redis cache, you will understand how to do it for Redis.

1. Make sure you run Magento 1.7 or newer 
* Install [Modman](https://github.com/colinmollenhour/modman)
* `cd` to your Magento root dir
* `test -d .modman || modman init`
* `modman clone --copy --force https://github.com/magehost/magehost_blockcache`
* If you keep your Magento code in Git: Add `.modman` to your `.gitignore`
* Edit `app/etc/local.xml`: inside `<config><global>` add/update:<br /> `<cache><backend>MageHost_Cm_Cache_Backend_File</backend></cache>`
* In Magento Admin: _System > Configuration > ADVANCED > Developer > Template Settings_
  * Set `Allow Symlinks` to `Yes`
* In Magento Admin: Flush Cache Storage
* Log out from Magento Admin and log back in
* Configure via: _System > Configuration > ADVANCED > MageHost BlockCache_
* Test every different kind of page on your site
* The first hit the page will be slow because we just cleaned all caches. If you hit the URL it should be faster, if caching is enabled for that kind of page. 

## Git submodule + N98

1. Make sure you run Magento 1.7 or newer 
* `cd` to your Magento root dir
* `test -d .modman || modman init`
* `git submodule add https://github.com/magehost/magehost_blockcache .modman/magehost_blockcache`
* `modman deploy --force magehost_blockcache`
* Edit `app/etc/local.xml`: inside `<config><global>` add/update:<br /> `<cache><backend>MageHost_Cm_Cache_Backend_File</backend></cache>`
* `n98-magerun.phar dev:symlinks --global --on`
* `n98-magerun.phar cache:flush`
* Log out from Magento Admin and log back in
* Configure via: _System > Configuration > ADVANCED > MageHost BlockCache_
* Test every different kind of page on your site
* The first hit the page will be slow because we just cleaned all caches. If you hit the URL it should be faster, if caching is enabled for that kind of page. 
