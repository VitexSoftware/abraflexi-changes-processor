#
# Regular cron jobs for the abraflexi-changes-processor package
# See dh_installcron(1) and crontab(5).
#
0 4	* * *	root	[ -x /usr/bin/abraflexi-changes-processor_maintenance ] && /usr/bin/abraflexi-changes-processor_maintenance
