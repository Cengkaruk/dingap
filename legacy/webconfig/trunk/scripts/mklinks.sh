#!/bin/bash

mode="link"
base=$(cd $(dirname $0)/.. && pwd)

if [ "$1" == "--clean" ]; then
	mode="clean"
elif [ "$1" == "-h" -o "$1" == "--help" ]; then
	echo "Usage: $0"
	echo "Create or delete vendor symlinks."
	echo " $0 [--link]    Create vendor symlinks (default)."
	echo " $0 --clean     Delete vendor symlinks."
	exit 0
fi

if [ ! -d "$base/api" ]; then
	echo "The Webconfig API seems to be missing."
	exit 1
fi

if [ ! -d "$base/vendor" ]; then
	echo "The vendor directory seems to be missing."
	echo "Did you forget to SVN checkout a vendor configuration?"
	exit 1
fi

nodes=$(cd "$base/vendor" && find . -type d | sed -e 's/^\.\///g')
tmpdirs=$(mktemp -t mklinks.XXXXXXXXXX)

for node in $nodes; do
	[ "$node" == "." ] && continue
	echo $node | egrep -q '\.svn' && continue
	echo "Considering: $node"
	if [ ! -d "$base/$node" ]; then
		echo -n "  new directory: "; mkdir -vp "$base/$node"
	fi
	files=$(cd "$base/vendor" && find $node -type f -maxdepth 1)
	if [ -z "$files" ]; then
		echo "  no files found."
		continue
	fi
	for file in $files; do
		if [ -f "$base/$file" -o -h "$base/$file" ]; then
			if [ "$mode" == "clean" -a -h "$base/$file" ]; then
				echo -n "  "; rm -vf "$base/$file"
				continue
			elif [ "$mode" == "clean" -a -f "$base/$file" ]; then
				echo "  skipping, not a symlink: $file"
				continue
			fi
			echo "  skipping, already exists: $file"
			continue
		elif [ "$mode" == "link" ]; then
			echo -n "  "; ln -sv $base/vendor/$file $base/$file
		fi
	done
done

exit 0

# vi: ts=4
