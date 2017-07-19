while true; do
	sleep 30m
	TZ=Etc/UTC date -Is
	cd /var/www/html/dokuwiki/
	rm -f /var/www/html/dokuwiki/.git/index.lock
	git add -A
	git commit -m "$(TZ=Etc/UTC date -Is)" && git push --force origin master
done

