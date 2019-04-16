#! /bin/bash
if [ -z $3 ]
then
    echo USAGE: $0 ursprungsorder zielorder_1 zielordner_2 
    exit 1
fi
find "$1" -maxdepth 1 -mmin +3 -type f | while read infile
do
    cp "$infile" "$2"
    mv "$infile" "$3"
done
