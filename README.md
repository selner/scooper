#Scooper
Gather and quickly export Moz.com, Crunchbase and Quantcast data for any company name or website address.  Very handy for doing research about competitors or potential partners.  

Scooper can be run for just a single name or URL or you can pass it a CSV file with a whole list of them.  

##Requirements
* Moz.com data:  [Moz.com API account](http://moz.com/products/api).
* Crunchbase data:  [Crunchbase API account](http://developer.crunchbase.com).

Simply set -mozid, -mozkey and -cbid switches to match your account's specific values.  Alternatively, you can edit the /config.php file to set those values directly for all script.

###Options:
* --lookup-name, -ln : The name of the company to lookup. 
* --lookup-url, -lu : The website URL for the company to lookup.
* --inputfile, -i : Full file path of the CSV file to use as the input data.
* --outputfile, -o : (optional) Output path or full file path and name for writing the results.
* --exclude-moz, -em : Include Moz.com data in the final result set.
* --exclude-quantcast, -eq : Include Quantcast.com uniq visitors data in the final result set.
* --exclude-crunchbase, -ec : Include TechCrunch's Crunchbase data in the final result set.
* --moz-access-id, -mozid : Your Moz.com API access ID value.  If you do not have one, Moz data will be excluded.  Learn more at [http://moz.com/products/api].
* --moz-secret-key, -mozkey : Your Moz.com API secret key value.  If you do not have one, Moz data will be excluded.  Learn more at [http://moz.com/products/api].
* --crunchbase-api-id, -cbid : Your Crunchbase API key value.  If you do not have one, Crunchbase data will be excluded.  Learn more at [http://developer.crunchbase.com].
* --verbose, -v : Show debug statements and other information.
* --verbose-api-calls, -va : Show API calls in verbose mode.
* --help, -h: Display this help banner


###Bulk Company Searches 
With the -i switch, you can specify a list of names or URLs to look up in batch.

*Input CSV File Format:*
* Line 1:  "Company Name" or "Company URL" 
* Line 2+:  1st record (e.g. "Microsoft" or "http:www.microsoft.com")

*Input CSV FIle Example:*
```
"Company Name", "Company URL"
     "Apple", "http://www.apple.com"
     "Microsoft", "www.microsoft.com"
     "Google", "google.com"
```

Example files are available in the /example directory.

If your Input CSV file has additional columns, those values will be copied into the resulting rows for each company as well.

###Enabling Logging
If you would like the script to output to log files, download the "Klogger v0.1" version from (http://codefury.net/projects/klogger/). Drop the  "KLogger".  The script should pick it up automatically the next time you run.

#Other Stuff
* Issues/Bugs:  See [https://github.com/selner/scooper/issues](https://github.com/selner/scooper/issues)
* Author:  Bryan Selner (dev at recoilvelocity dot com)
 
##License
This product is licensed under the GPL (http://www.gnu.org/copyleft/gpl.html). It comes with no warranty, expressed or implied.
