## Upgrade Manual for the MageHost_BlockCache extension

### Upgrade from JeroenVermeulen-BlockCache

Using [Modman](https://github.com/colinmollenhour/modman):

    # Replace old extension copy by symlinks (if installed using --force)
    modman deploy --force jeroenvermeulen-blockcache
    # Remove old extension
    modman remove jeroenvermeulen-blockcache
    # Install new extension
    modman install --copy --force https://github.com/magehost/magehost_blockcache.git

### Upgrade from older MageHost_BlockCache version

Using [Modman](https://github.com/colinmollenhour/modman):

    modman update magehost_blockcache
