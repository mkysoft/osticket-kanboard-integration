# osticket-kanboard-integration
osTicket task to Kanboard integration

This integration php script getting ticket related open task for a department from osTicket then create them on Kanboard and create link to ticket.

We need some API improvements on osTicket side for making integration. 
You can use my two osTicket pull request for making these improvement.

task api: https://github.com/osTicket/osTicket/pull/4265

thread api: https://github.com/osTicket/osTicket/pull/4269

I am also adding this changes to osTicket_API folder.

**Installation**
1. Make improvement on osTicket for API.
2. Copy osticket-to-kanboard.php script to your server.
3. Add cron for scheduling.

_For adding cron:_ 

Edit /etc/crontab in server and add below line:
````
*/5 * * * * root /usr/bin/php /var/www/osticket-to-kanboard.php
````
**Roadmap**
- Create comment as link for Kanboard task on osTicket.
- Auto close ticket when task completed on Kanboard.
