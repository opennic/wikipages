while true; do
	git add -A
	git commit -m "$(TZ=Etc/UTC date -Is)" && git push --force origin master
	sleep 5
done

