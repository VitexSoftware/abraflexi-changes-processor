AbraFlexi Changes Processor
===========================


Process & notify chanches collected by [AbraFlexi WebHook Acceptor](https://github.com/VitexSoftware/abraflexi-webhook-acceptor)


.env file contents
------------------


```env
APP_NAME=AbraFlexiCHP
DEBUG=true
ABRAFLEXI_DEBUG=true
EASE_LOGGER=syslog
EMAIL_FROM=fbchanges@localhost
SEND_INFO_TO=admin@localhost

DB_TYPE=mysql
DB_HOST=localhost
DB_PORT=3306
DB_DATABASE=changes
DB_USERNAME=changes
DB_PASSWORD=changes
DB_SETUP=

CUT_FLEXIHISTORY_BY_LASTID=True
PROCESSING_ENABLED=True
```



Installation: 
-------------


```shell
wget -qO- https://repo.vitexsoftware.com/keyring.gpg | sudo tee /etc/apt/trusted.gpg.d/vitexsoftware.gpg
echo "deb [signed-by=/etc/apt/trusted.gpg.d/vitexsoftware.gpg]  https://repo.vitexsoftware.com  $(lsb_release -sc) main" | sudo tee /etc/apt/sources.list.d/vitexsoftware.list
sudo apt update

sudo apt install abraflexi-webhook-acceptor
```



Tools
-----

* [Order Status changes](https://github.com/Spoje-NET/abraflexi-order-status-changer) change order status according the keyword value in note
