#
# Regular cron jobs for the abraflexi-changes-processor package
# See dh_installcron(1) and crontab(5).
#
* *	* * *	root	[ -x /usr/bin/abraflexi-changes-processor ] && /usr/bin/abraflexi-changes-processor