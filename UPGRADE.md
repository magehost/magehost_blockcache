## Upgrade Manual for the MageHost_BlockCache extension

### Upgrade from JeroenVermeulen-BlockCache

Using [Modman](https://github.com/colinmollenhour/modman):

1. Replace old extension copy by symlinks (if installed using --force)<br />
  `modman deploy --force jeroenvermeulen-blockcache`
* Remove old extension<br />
  `modman remove jeroenvermeulen-blockcache`
* In Magento Admin: _Flush Cache Storage_ or via [N98](https://github.com/netz98/n98-magerun): `n98-magerun.phar cache:flush`
* Install new extension<br />
  `modman clone --copy --force https://github.com/magehost/magehost_blockcache.git`
* In Magento Admin: _Flush Cache Storage_ or via [N98](https://github.com/netz98/n98-magerun): `n98-magerun.phar cache:flush`
* Logout from Magento Admin, login again
* Restore configuration via: _System > Configuration > ADVANCED > MageHost BlockCache_

### Upgrade from older MageHost_BlockCache version

Using [Modman](https://github.com/colinmollenhour/modman):

1. `modman update --copy --force magehost_blockcache`
* In Magento Admin: _Flush Cache Storage_
