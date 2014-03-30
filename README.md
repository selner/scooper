##Scooper
Author:  Bryan Selner (dev@recoilvelocity.com)

##What It Does
Gather and export website data from Moz.com, Crunchbase and Quantcast for any given company name or website URL.

Options:
* --lookup-name, -ln <s>: The name of the company to lookup. (Requires --outputfile.)
* --lookup-url, -lu <s>: The website URL for the company to lookup. (Requires --outputfile.)
*  --suppressUI, -q <i>: Show user interface.
*     --verbose, -v <i>: Show debug statements and other information.
 --verbose-api-calls, -va <i>: Show API calls in verbose mode.
*   --inputfile, -i <s>: Full file path of the CSV file to use as the input data.
*  --outputfile, -o <s>: (optional) Output path or full file path and name for writing the results.
       --exclude-moz, -em <i>: Include moz.com data in the final result set.
 --exclude-quantcast, -eq <i>: Include quantcast.com uniq visitors data in the final result set.
--exclude-crunchbase, -ec <i>: Include TechCrunch's Crunchbase data in the final result set.
  --moz-access-id, -mozid <s>: Your Moz.com API access ID value.  If you do not have one, Moz data will be excluded.  Learn more about Moz.com access IDs at http://moz.com/products/api.
--moz-secret-key, -mozkey <s>: Your Moz.com API secret key value.  If you do not have one, Moz data will be excluded.  Learn more about Moz.com access IDs at http://moz.com/products/api.
*            --help, -h: Display this help banner

##Bulk Company Searches 
With the -i switch, you can specify a list of names or URLs to look up in batch.

--INPUT CSV FORMAT-- 
* Line 1:  "Company Name" or "Company URL" 
* Line 2 and on:  1st record (e.g. "Microsoft" or "http:www.microsoft.com")
*.etc

There are some same CSVs in the 'Input Examples' diretory.


## Enabling Logging
If you would like the script to output to log files, download the "Klogger v0.1" version from (http://codefury.net/projects/klogger/). Extract the contents to
a new folder in /lib called "KLogger".  The script should pick it up automatically the next time you run.

# Bugs & Issues
* https://github.com/selner/scooper/issues


##Future Ideas for Improvement
* Generalize the plugin PHPs to have a common class so that it's easy to drop a new plugin support file in
* add Platypus build support for the app


