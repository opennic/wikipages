# wiki.opennic.org disaster recovery plan
```
apt install --no-install-recommends curl gnupg2 ca-certificates lsb-release htop php7.0-fpm apt install php7.0-gd php7.0-ldap php7.0-mbstring php7.0-xml git-core
echo "deb http://nginx.org/packages/mainline/debian `lsb_release -cs` nginx" > /etc/apt/sources.list.d/nginx.list
curl -fsSL https://nginx.org/keys/nginx_signing.key | apt-key add -
apt update
apt install --no-install-recommends nginx
git clone https://github.com/opennic/wikipages.git /wiki
mkdir /wiki/data/cache
chown -R nginx:nginx /wiki
echo "tmpfs /wiki/data/cache tmpfs rw,noatime,nodiratime,uid=nginx,gid=nginx 0 0" >> /etc/fstab
mount /wiki/data/cache
cp /wiki/drp/php-fpm.conf /etc/php/7.0/fpm/php-fpm.conf
cp /wiki/drp/wiki.opennic.org.conf /etc/nginx/conf.d/wiki.opennic.org.conf
systemctl restart php7.0-fpm nginx
```

