#!/usr/bin/bash
rm -rf  Scooter_v$1_$2
mkdir Scooter_v$1_$2
cp -R template/ScooperApplet.app Scooter_v$1_$2
mv Scooter_v$1_$2/ScooperApplet.app Scooter_v$1_$2/Scooper_v$1_$2.app
mkdir Scooter_v$1_$2/Scooper_v$1_$2.app/Contents/Resources/Scripts
cp -R ../src Scooter_v$1_$2/Scooper_v$1_$2.app/Contents/Resources/Scripts/src
cp ../run_scooper.php Scooter_v$1_$2/Scooper_v$1_$2.app/Contents/Resources/Scripts
mv Scooter_v$1_$2/Scooper_v$1_$2.app/Contents/Resources/Scripts/src/scooter-app-main.scpt Scooter_v$1_$2/Scooper_v$1_$2.app/Contents/Resources/Scripts/main.scpt 


