#!/bin/bash
#
# Snort Report Shell Script for Webconfig 3.1/2
#
# Copyright 2005 Point Clark Networks.
#
# last modified: 01/12/2006 19:54
#
##############################################################################
#
# This program is free software; you can redistribute it and/or
# modify it under the terms of the GNU General Public License
# as published by the Free Software Foundation; either version 2
# of the License, or (at your option) any later version.
#
# This program is distributed in the hope that it will be useful,
# but WITHOUT ANY WARRANTY; without even the implied warranty of
# MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
# GNU General Public License for more details.
#
# You should have received a copy of the GNU General Public License
# along with this program; if not, write to the Free Software
# Foundation, Inc., 59 Temple Place - Suite 330, Boston, MA  02111-1307, USA.
#
##############################################################################
# trap ctrl-c and friends and do clean up
trap clean_up INT SIGHUP SIGINT SIGTERM

RAWLOGFILE=/var/log/secure*

REPORT_DIR=/var/webconfig/reports/snort
if [ ! -d "$REPORT_DIR" ]; then
        mkdir -p "$REPORT_DIR"
fi
CCVER=`cat /etc/release | gawk '{print $NF}'`
ZGREP='nice zgrep';
GREP='nice grep';
EGREP='nice egrep';

MIN=0
MAX=1000000

TMP=`mktemp -p $REPORT_DIR tmp.XXXXXXXXX` || exit 1
TMP1=`mktemp -p $REPORT_DIR tmp.XXXXXXXXX` || exit 1
TMP2=`mktemp -p $REPORT_DIR tmp.XXXXXXXXX` || exit 1
TMP3=`mktemp -p $REPORT_DIR tmp.XXXXXXXXX` || exit 1
TMP4=`mktemp -p $REPORT_DIR tmp.XXXXXXXXX` || exit 1

function clean_up {
	# Perform program exit housekeeping
	cd "$REPORT_DIR"
	find -name "tmp.*" | xargs rm &> /dev/null
	exit
}
parse_logfile () {
	if [ -n "$VERBOSE" ]; then
		echo "1->$1:2->$2"
	fi

	if [ -n "$2" ]; then

		MON=`date +%D -d "01 $1 2005" | gawk -F"/" '{print $1}'`

		if [ ! -d "$REPORT_DIR/$MON/$2" ]; then
		        mkdir -p "$REPORT_DIR/$MON/$2"
		fi



		LOGFILE=$REPORT_DIR/$MON/$2/log.gz
		ERR=$REPORT_DIR/$MON/$2/error
		SIDFILE=$REPORT_DIR/$MON/$2/sids
		DUPFILE=$REPORT_DIR/$MON/$2/repeats
		DETAILS=$REPORT_DIR/$MON/$2/details.gz
		PORTS=$REPORT_DIR/$MON/$2/ports
		SRC=$REPORT_DIR/$MON/$2/src
		DST=$REPORT_DIR/$MON/$2/dst
		DATES=$REPORT_DIR/$MON/$2/count

		if [ $CCVER == "3.1" ]; then
			#
			# parse out the raw log data 3.1
			#
			$ZGREP -hE "$1[ ]+$2[ ].*snort:" $RAWLOGFILE | $GREP -v sudo > $TMP2
			cat $TMP2 | sed 's/\[/|/g;s/\]/|/g;s/)/|/;s/(//;s/| |/|/;s/{/|/;s/}/|/;s/|: |/|/;s/Classification://;s/Priority//;s/|:/|/;s/| /|/g;s/ |/|/g;s/ -> /:s|d:/' | gawk -F"|" '{print NF"|"$0}' > $TMP3
			$ZGREP -Ev "^8" $TMP3 > $ERR
			$ZGREP -E "^8" $TMP3 | gzip -c > $LOGFILE
		else
			#
			# parse out the raw log data 3.2+
			#
			$ZGREP -hE "$1[ ]+$2[ ].*snort" $RAWLOGFILE | $GREP -v sudo > $TMP2
			cat $TMP2 | sed 's/\[/|/g;s/\]/|/g;s/)/|/;s/(//;s/| |/|/;s/{/|/;s/}/|/;s/|: |/|/;s/Classification://;s/Priority//;s/|:/|/;s/| /|/g;s/ |/|/g;s/ -> /:s|d:/' | gawk -F"|" '{print NF"|"$0}' > $TMP3
			$ZGREP -Ev "^10" $TMP3 > $ERR
			$ZGREP -E "^10" $TMP3 | gawk -F"|" '{print $1"|"$2":|"$4"|"$5"|"$6"|"$7"|"$9"|"$10"|"$11}'| gzip -c > $LOGFILE
		fi
		#
		# Collet details
		#
		zcat $LOGFILE | gawk -F"|" -v D=$2 '{print D"|"$3"|"$8"|"$9}' > $TMP2
		#
		# parse out "last message repeated X times" and add to details
		if [ $CCVER == "3.1" ]; then
			$ZGREP -hE "$1[ ]+$2[ ]" $RAWLOGFILE | $GREP -B1 repeated | $GREP -A1 snort | sed 's/--/++/'| $GREP -v "++"| sed 's/.*repeated /}/;s/times//;s/]/}/g;s/snort:.\[/}/' | sed '$!N;s/\n//' | gawk -v D=$2 -F} '{print D"|"$2"|"$6"|"$7}' | sed 's/ //g;s/->/:s|d:/'  > $DUPFILE
		else
			$ZGREP -hE "$1[ ]+$2[ ]" $RAWLOGFILE | $GREP -B1 repeated | $GREP -A1 snort | sed 's/--/++/'| $GREP -v "++"| sed 's/.*repeated /}/;s/times//;s/]/}/g;s/snort:.\[/}/;s/snort\[[[:digit:]]*\]:.\[/}/' | sed '$!N;s/\n//' | gawk -v D=$2 -F} '{print D"|"$2"|"$6"|"$7}' | sed 's/ //g;s/->/:s|d:/;s/:\[//'  > $DUPFILE
		fi

		while read DUPDATA
		do
			LINE=`echo "$DUPDATA" | gawk -F"|" '{print $1"|"$2"|"$3"|"$4}'`
			LIMIT=`echo "$DUPDATA" | gawk -F"|" '{print $5}'`
			a=0
			while [ $a -lt "$LIMIT" ]
			do
				a=$(($a+1))
				echo "$LINE" >> $TMP2
			done
		done < $DUPFILE
		cat $TMP2 | gzip -c > $DETAILS
		#
		# enumarate sids
		#
		SIDS=
		GRANDTOTAL=0
		zcat $LOGFILE | gawk -F"|" '{print $3"|"$4"|"$5"|"$6"|"$7}' | sort | uniq > $TMP3
		while read SIDDATA
		do
	        ID=`echo "$SIDDATA" | gawk -F"|" '{print $1}'`
	        SID=`echo "$ID" | gawk -F: '{print $2}'`
	        MAXVALID=`echo "$SID $MAX" | gawk '{print $1-$2}' | $GREP -c "-"`
	        MINVALID=`echo "$MIN $SID" | gawk '{print $1-$2}' | $GREP -c "-"`
	        if [ "$MINVALID" == "1" ]; then
		        if [ "$MAXVALID" == "1" ]; then
					VALID=1
			    else
			    	VALID=0
				fi
		    else
		    	VALID=0
			fi
			if [ "$VALID" == "1" ]; then
				SIDS=`echo "$SIDS $SID"`
		        COUNT=`$ZGREP -c $ID $LOGFILE`
		        DUPS=`$GREP $ID $DUPFILE | gawk -F"|" '{ sum += $5 }; END { print sum }'`
		        if [ -n "$DUPS" ]; then
		        	TOTAL=$(($COUNT + $DUPS))
		        else
		        	TOTAL=$COUNT
		        fi
		        echo "$SIDDATA|$TOTAL|$2"
		        GRANDTOTAL=$(($GRANDTOTAL + $TOTAL))
		    else
			   	# remove invalid SIDs
		    	$ZGREP -v $ID $LOGFILE | gzip -c > $TMP4
		    	rm $LOGFILE
		    	mv $TMP4 $LOGFILE
		    	$ZGREP -v $ID $DETAILS | gzip -c > $TMP4
		    	rm $DETAILS
		    	mv $TMP4 $DETAILS
			fi
		done < $TMP3 > $SIDFILE
		if [ -n "$VERBOSE" ]; then
			echo "$SIDFILE"
			cat $SIDFILE
		fi
		#
		# enumerate dates
		#
		SIDS=`echo $SIDS | sed 's/^ //g'`
		echo "$2|$GRANDTOTAL|$SIDS" > $DATES
		if [ -n "$VERBOSE" ]; then
			echo "$DATES"
			cat $DATES
		fi
		#
		# enumerate src
		#
		zcat $DETAILS | gawk -F"|" '{print $3}' | gawk -F: '{print $1}'| sort | uniq > $TMP3
		while read IP
		do
			REGEX=`echo "$IP.*:s"`
			COUNT=`$ZGREP -Ec "$REGEX" $DETAILS`
			SIDS=`$ZGREP -E "$REGEX" $DETAILS | gawk -F"|" '{print $2}' | sort | uniq | gawk -F: '{print $2}' | xargs echo`
			echo "$IP|$COUNT|$2|$SIDS"
		done < $TMP3 > $SRC
		if [ -n "$VERBOSE" ]; then
			echo "$SRC"
			cat $SRC
		fi
		#
		# enumerate dst
		#
		zcat $DETAILS | gawk -F"|" '{print $4}' | gawk -F: '{print $2}'| sort | uniq > $TMP3
		while read IP
		do
			REGEX=`echo "d:$IP"`
			COUNT=`$ZGREP -Ec "$REGEX" $DETAILS`
			SIDS=`$ZGREP -E "$REGEX" $DETAILS | gawk -F"|" '{print $2}' | sort | uniq | gawk -F: '{print $2}' | xargs echo`
			echo "$IP|$COUNT|$2|$SIDS"
		done < $TMP3 > $DST
		if [ -n "$VERBOSE" ]; then
			echo "$DST"
			cat $DST
		fi
       	#
       	# enumerate dst ports
       	#
       	zcat $DETAILS | gawk -F"|" '{print $4}' | gawk -F: '{print $3}' > $TMP3
       	for PORT in `cat $TMP3 | sort | uniq `
       	do
       		REGEX=`echo "d:.*:$PORT"`
       		COUNT=`$ZGREP -Ec $REGEX $DETAILS`
       		echo "$PORT|$COUNT|$2"
       	done  > $PORTS
		if [ -n "$VERBOSE" ]; then
			echo "$PORTS"
			cat $PORTS
		fi
	elif [ -n "$1" ]; then
		parse_month $1
	else
		for MONTH in `$ZGREP -h " snort" $RAWLOGFILE | gawk '{print $1}'| sort | uniq`
		do
			parse_month $MONTH
		done
	fi
}
parse_month () {
	$ZGREP -h " snort" $RAWLOGFILE | $GREP -v sudo | $EGREP "$1" | gawk '{print $1" "$2}'| sort | uniq  > $TMP
	while read DATEINFO
	do
		MONTH=`echo $DATEINFO | gawk '{print $1}'`
		DAY=`echo $DATEINFO | gawk '{print $2}'`
        parse_logfile $MONTH $DAY
	done < $TMP
}
update_ndx () {
	cd $REPORT_DIR/$1
	#
	# src ndx
	#
	find -name src | xargs cat > $TMP3
	for IP in `gawk -F"|" '{print $1}' $TMP3 | sort | uniq`
	do
		COUNT=`$GREP $IP $TMP3 | gawk -F"|" '{sum += $2}; END {print sum}'`
		DAYS=`$GREP $IP $TMP3 | gawk -F"|" '{print $3}' | sort | uniq | xargs echo `
		SIDS=`$GREP $IP $TMP3 | gawk -F"|" '{print $4}' | sed 's/ /\n/g'| sort | uniq | xargs echo`
		echo "$IP|$COUNT|$DAYS|$SIDS"
	done > $REPORT_DIR/$1/src.ndx
	if [ -n "$VERBOSE" ]; then
		echo "SRC NDX for $1"
		cat $REPORT_DIR/$1/src.ndx
	fi
	#
	# dst ndx
	#
	find -name dst | xargs cat > $TMP3
	for IP in `gawk -F"|" '{print $1}' $TMP3 | sort | uniq`
	do
		COUNT=`$GREP $IP $TMP3 | gawk -F"|" '{sum += $2}; END {print sum}'`
		DAYS=`$GREP $IP $TMP3 | gawk -F"|" '{print $3}' | sort | uniq | xargs echo `
		SIDS=`$GREP $IP $TMP3 | gawk -F"|" '{print $4}' | sed 's/ /\n/g'| sort | uniq | xargs echo`
		echo "$IP|$COUNT|$DAYS|$SIDS"
	done > $REPORT_DIR/$1/dst.ndx
	if [ -n "$VERBOSE" ]; then
		echo "DST NDX for $1"
		cat $REPORT_DIR/$1/dst.ndx
	fi
	#
	# port ndx
	#
	find -name ports | xargs cat > $TMP3
	for PORT in `gawk -F"|" '{print $1}' $TMP3 | sort | uniq`
	do
		COUNT=`$EGREP "^$PORT\|" $TMP3 | gawk -F"|" '{sum += $2}; END {print sum}'`
		DAYS=`$EGREP "^$PORT\|" $TMP3 | gawk -F"|" '{print $3}' | sort | uniq | xargs echo `
		echo "$PORT|$COUNT|$DAYS"
	done > $REPORT_DIR/$1/ports.ndx
	if [ -n "$VERBOSE" ]; then
		echo "PORTS NDX for $1"
		cat $REPORT_DIR/$1/ports.ndx
	fi
	#
	# sid ndx
	#
	find -name sids | xargs cat > $TMP3
	for SID in `gawk -F"|" '{print $1}' $TMP3 | sort | uniq`
	do
		SIDDATA=`$GREP -m1 $SID $TMP3 | gawk -F"|" '{print $1"|"$2"|"$3"|"$4"|"$5}'`
		COUNT=`$GREP $SID $TMP3 | gawk -F"|" '{sum += $6}; END {print sum}'`
		DAYS=`$GREP $SID $TMP3 | gawk -F"|" '{print $7}' | sort | uniq | xargs echo `
		echo "$SIDDATA|$COUNT|$DAYS"
	done > $REPORT_DIR/$1/sids.ndx
	if [ -n "$VERBOSE" ]; then
		echo "SID NDX for $1"
		cat $REPORT_DIR/$1/sids.ndx
	fi
	#
	# dates ndx
	#
	find -name count | xargs cat > $REPORT_DIR/$1/dates.ndx
	if [ -n "$VERBOSE" ]; then
		echo "DATES NDX for $1"
		cat $REPORT_DIR/$1/dates.ndx
	fi

	#
	# create new checksum
	#
	tar -c * | md5sum > $REPORT_DIR/$1.md5
}
verify_ndx () {
	cd $REPORT_DIR
	if [ `ls -A | wc -l`  == "0" ]; then
		if [ -n "$VERBOSE" ]; then
			echo "No Data yet..."
		fi
		return
	fi
	for MONTH in `ls -A * | $GREP : | sed 's/://'`
	do
		if [ -r "$REPORT_DIR/$MONTH.md5" ]; then
			cd $REPORT_DIR/$MONTH/
			MD5=`tar -c * | md5sum`
			LAST=`cat $REPORT_DIR/$MONTH.md5`
			if [ "$MD5" != "$LAST" ]; then
				update_ndx $MONTH
			fi
		else
			update_ndx $MONTH
		fi
	done
}
useage () {
	echo ""
	echo "Usage: `basename $0` options (-sanm:u)"
	echo "  -s generate spyware reports"
	echo "  -a process All data"
	echo "  -n create New report (same as -a, but report directory will be erased first)"
	echo "  -m process all report data for a month (1-12)"
	echo "       example: `basename $0` -m10"
	echo "  -u update, process ONLY today's data"
	echo "  -v be verbose, give some feedback"
	echo ""
	exit 65
}

if [ $# -eq 0 ]  # Script invoked with no command-line args?
then
	useage
fi
while getopts ":sanm:uv" option
do
  case $option in
  	s)	REPORT_DIR=/var/webconfig/reports/spyware
		if [ ! -d "$REPORT_DIR" ]; then
        	mkdir -p "$REPORT_DIR"
		fi
		MIN=1000001
		MAX=99999999999
		;;
    a)	parse_logfile
    	;;
    n) 	rm -rf $REPORT_DIR/*
    	parse_logfile
    	;;
    m) 	MONTH=`date -d $OPTARG/01/2005 +%b`
    	parse_logfile $MONTH
    	;;
    u)  MONTH=`date | gawk '{print $2}'`
  		DAY=`date | gawk '{print $3}'`
  		RAWLOGFILE=/var/log/secure
  		parse_logfile $MONTH $DAY
  		;;
  	v)	VERBOSE=1
  		;;
    *) 	echo ""
    	echo "Invalid option chosen."
    	useage
    ;;
  esac
done
shift $(($OPTIND - 1))
verify_ndx
clean_up

