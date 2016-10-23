## Upgrade Manual for the MageHost_BlockCache extension

### Upgrade from JeroenVermeulen-BlockCache

Using [Modman](https://github.com/colinmollenhour/modman):

    # 1. Replace old extension copy by symlinks (if installed using --force)
    modman deploy --force jeroenvermeulen-blockcache
    # 2. Remove old extension
    modman remove jeroenvermeulen-blockcache
    # 3. Install new extension
    modman clone --copy --force https://github.com/magehost/magehost_blockcache.git
    # 4. Clean cache

### Upgrade from older MageHost_BlockCache version

Using [Modman](https://github.com/colinmollenhour/modman):

    modman update magehost_blockcache
