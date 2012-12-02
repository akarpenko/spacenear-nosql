spacenear-nosql
===============

Quick hack to make the spacenear.us tracker run without SQL DB using a flat JSON file.
Easy to deploy to Amazon EC2 and also allows load balancing using multiple instances
by synching positions.json between them unidirectionally.

Deploying to Amazon EC2
=======================

1. Start an Ubuntu 12.x instance
2. Login by doing ssh ubuntu@instance-ip -i instance.pem
3. Set hostname in /etc/hostname and add it to /etc/hosts
4. sudo apt-get update && sudo apt-get upgrade
5. Restart instance
6. sudo apt-get install nginx php5 php5-fpm
7. Put tracker into /srv/tracker
8. Create folder /var/data for the JSON files
9. Put nginx-site.conf to /etc/nginx/sites-available/default
10. Set "cgi.fix_pathinfo = 0;" in /etc/php5/fpm/php.ini
11. sudo service php5-fpm restart
12. sudo service nginx start

Adding another instance
=======================
1. Follow the steps above to setup a new EC2 server
2. Add server pem to main instance and add it to ~/.ssh/config
3. Modify and run /srv/tracker/utils/mirror.sh on the main instance
4. Make sure that habitat is uploading only to the main instance
5. Add both instances to Amazon Elastic Load Ballancer