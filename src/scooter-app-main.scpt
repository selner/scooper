-- main.scpt
-- Cocoa-AppleScript Applet
--
-- Copyright 2011 {Your Company}. All rights reserved.

-- This is the main script for a Cocoa-AppleScript Applet.
-- You can put the usual script applet handlers here.
on run what
	set paramstring to ""
	repeat with anItem in what
		set paramstring to paramstring & " " & anItem
	end repeat
	
	set cmd to "ls -al"
	do shell script cmd without altering line endings
	
	set cmd to "php " & (path to me) & "/run_scooper.php" & paramstring
	do shell script cmd without altering line endings
	
	do shell script "open scooper.log"
	
end run
