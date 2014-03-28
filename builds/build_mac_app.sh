#!/usr/bin/bash
rm -fr Scooper_v$1_$2.app/*.*
cp -R template_scooper.app Scooper_v$1_$2.app
cat info.plist.first > Scooper_v$1_$2.app/Contents/Info.plist
echo -n $2 >> Scooper_v$1_$2.app/Contents/Info.plist
cat info.plist.second > Scooper_v$1_$2.app/Contents/Info.plist
echo -n $1 >> Scooper_v$1_$2.app/Contents/Info.plist
cat info.plist.third > Scooper_v$1_$2.app/Contents/Info.plist
