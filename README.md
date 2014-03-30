#Scooper
*Author:  Bryan Selner (dev@recoilvelocity.com)
*(Issues) [https://github.com/selner/scooper/issues]

##What It Does
Gather and export website data from Moz.com, Crunchbase and Quantcast for any given company name or website URL.

##Requirements
* Moz.com data:  (Moz.com API account)[http://moz.com/products/api].
* Crunchbase data:  (Crunchbase API account)[http://developer.crunchbase.com].

Simply set -mozid, -mozkey and -cbid switche to match your account's specific values.  Alternatively, you can edit the src/config.pho file to set those values in the code directly.

###Options:
* --lookup-name, -ln : The name of the company to lookup. 
* --lookup-url, -lu : The website URL for the company to lookup.
*   --inputfile, -i : Full file path of the CSV file to use as the input data.
*  --outputfile, -o : (optional) Output path or full file path and name for writing the results.
* --exclude-moz, -em : Include moz.com data in the final result set.
*--exclude-quantcast, -eq : Include quantcast.com uniq visitors data in the final result set.
*--exclude-crunchbase, -ec : Include TechCrunch's Crunchbase data in the final result set.
*--moz-access-id, -mozid : Your Moz.com API access ID value.  If you do not have one, Moz data will be excluded.  Learn more about Moz.com access IDs at [http://moz.com/products/api].
*--moz-secret-key, -mozkey : Your Moz.com API secret key value.  If you do not have one, Moz data will be excluded.  Learn more about Moz.com access IDs at [http://moz.com/products/api].
* --crunchbase-api-id, -n : Your Crunchbase API key value.  If you do not have one, Crunchbase data will be excluded.  Learn more about Moz.com access IDs at [http://developer.crunchbase.com].
*  --verbose, -v : Show debug statements and other information.
*  --verbose-api-calls, -va : Show API calls in verbose mode.
*  --help, -h: Display this help banner


###Bulk Company Searches 
With the -i switch, you can specify a list of names or URLs to look up in batch.

*Input CSV File Format:*
* Line 1:  "Company Name" or "Company URL" 
* Line 2 and on:  1st record (e.g. "Microsoft" or "http:www.microsoft.com")
*.etc

*Input CSV FIle Example:*
```
"Company Name", "Company URL"
     "Apple", "http://www.apple.com"
     "Microsoft", "www.microsoft.com"
     "Google", "google.com"
```

### Enabling Logging
If you would like the script to output to log files, download the "Klogger v0.1" version from [http://codefury.net/projects/klogger/]. Extract the contents toa new folder in /lib called "KLogger".  The script should pick it up automatically the next time you run.

