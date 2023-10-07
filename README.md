AbraFlexi Changes Processor
===========================


Process & notify changes collected by [AbraFlexi WebHook Acceptor](https://github.com/VitexSoftware/abraflexi-webhook-acceptor)

Second stage use set of [Plugins](src/AbraFlexi/Processor/Plugins)=discover the "Meta State".  

Third stage process collected Meta States using custom commands.

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
ABRAFLEXI_OST_ZAVAZEK=OST-ZAVAZEK  #typ-zavazku 
```

Example Environment for for subcommand:

```
export ID=2029
export URI=https://demo.flexibee.eu:5434/c/spoje_net_s_r_o_/adresar/code:JEDIDIAH
export META=create
export DISCOVERED=2022-11-17 23:42:40
export PROCESSED=
export CHANGEID=4751392
export DOCUMENTID=code:JEDIDIAH
export SUBJECT=adresar
export COMPANY=demo_de
export COMPANYURI=https://demo.flexibee.eu:5434/c/spoje_net_s_r_o_
export URL=https://demo.flexibee.eu:5434
export SCHEME=https
export HOST=demo.flexibee.eu
export PORT=5434
export PATH=/c/demo_de/adresar/code:JEDIDIAH
export LOGIN=winstrom
export PASSWORD=winstrom
export DONEID=4751454
export SOURCEID=1
export EMAIL=
export ABRAFLEXI_URL=https://demo.flexibee.eu:5434
export ABRAFLEXI_LOGIN=admin
export ABRAFLEXI_PASSWORD=winstrom
export ABRAFLEXI_COMPANY=demo_de
export EASE_MAILTO=
export EASE_LOGGER=syslog
export PATH=/usr/bin:/usr/local/bin
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
