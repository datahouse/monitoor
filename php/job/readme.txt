
/etc/cron.d
more /var/log/syslog | grep JobDispatcher

# mon cron tab jobs
# daily alert job 18.15
# immediate alert job every 15 Min.
# immediate push every 5 Min.
# shab download 6.15
# rotating log files weekly Sun. 2.55
# rotatng log files weekly Sun. 2.55
# awp move incoming files every min.
# import awp every 5 min
# awp reporting monthly first day 3.10


15 18 * * * root cd /srv/job/ && php ./JobDispatcher.php ProcessDailyAlert
*/15 * * * * root cd /srv/job/ && php ./JobDispatcher.php ProcessImmediateAlert
*/5 * * * * root cd /srv/job/ && php ./JobDispatcher.php ProcessPushAlert
15 06 * * 1-5 root cd /srv/job/src/Job/shab/ && php ./send_from_soap.php --config=live --date=now
55 2 * * 0 root cd /srv/job/log/ && cat mon_job.log | gzip -9 > mon_job_$(date +\%d\%m\%Y).log.gz && cat /dev/null >| mon_job.log
55 2 * * 0 root cd /srv/www/com.monitoor/php/api/log/ && cat mon.log | gzip -9 > mon_$(date +\%d\%m\%Y).log.gz && cat /dev/null >| mon.log
*/1 * * * * root cd /srv/job/src/Job/awp/ && ./move_awp_incomming.sh /srv/ftp/awp/input /srv/ftp/awp/pending_demo /srv/ftp/awp/pending_live
*/5 * * * * root cd /srv/job/src/Job/awp/ && php ./send_awp.php --config=live --dir-in=/srv/ftp/awp/pending_live --dir-out=/srv/ftp/awp/done_live
10 3 1 * * root cd /srv/job/ && php ./JobDispatcher.php AWPReport 268 4623
15 18 * * * root cd /srv/job_demo/ && php ./JobDispatcher.php ProcessDailyAlert
*/15 * * * * root cd /srv/job_demo/ && php ./JobDispatcher.php ProcessImmediateAlert
*/15 * * * * root cd /srv/job_demo/ && php ./JobDispatcher.php ProcessPushAlert
15 07 * * 1-5 root cd /srv/job_demo/src/Job/shab/ && php ./send_from_soap.php --config=test --date=now
*/5 * * * * root cd /srv/job_demo/src/Job/awp/ && php ./send_awp.php --config=test --dir-in=/srv/ftp/awp/pending_demo --dir-out=/srv/ftp/awp/done_demo




