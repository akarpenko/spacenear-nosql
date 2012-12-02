#!/bin/sh
# mirror positions to other server(s)
while [ 1 ]; do
	scp /var/data/positions.json space-1.habhub.us:/var/data/
	sleep 20
done
