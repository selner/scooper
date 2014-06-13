#Scooper
Bulk export Crunchbase, Moz Quantcast data to comma-separated value (CSV) files using either the command-line or a list of records from an input file.  Very handy for doing research about competitors or potential partners.  

###With Scooper You Can Easily Export Company Data to CSV by:
* a single company's information using their name
* a single company's information using their website URL
* many companies by name in bulk
* many companies by website address in bulk

You can even bulk export the data returned from [any Crunchbase API call](https://developer.crunchbase.com/docs).


##Requirements
* If you want to export Crunchbase data, Scooper requires you to set the user_key from a  [Crunchbase API account](http://developer.crunchbase.com). 
* If you want to export Moz.com data, Scooper requires a Moz API Access ID and secret key from a  [Moz.com API account](http://moz.com/products/api).

You can specify those values via an INI file with the -ini flag or directly on the command line wtih the -mozid, -mozkey and -cbid switches.

###Parameters:
* --lookup-name, -ln : The name of the company to lookup. 
* --lookup-url, -lu : The website URL for the company to lookup.
* --crunchbase_api_url, -cb : The Crunchbase API call to export to CSV
* --inputfile, -i : Full file path of the CSV file to use as the input data for batch company name or company website lookups

* --exclude-moz, -em : Exclude Moz.com data from the final result set.
* --exclude-quantcast, -eq : Exclude Quantcast.com uniq visitors data in the final result set.
* --exclude-crunchbase, -ec : Exclude TechCrunch's Crunchbase data in the final result set.

* --moz-access-id, -mozid : Your Moz.com API access ID value.  If you do not have one, Moz data will be excluded.  Learn more at [http://moz.com/products/api].
* --moz-secret-key, -mozkey : Your Moz.com API secret key value.  If you do not have one, Moz data will be excluded.  Learn more at [http://moz.com/products/api].
* --crunchbase-api-id, -cbid : Your Crunchbase API key value.  If you do not have one, Crunchbase data will be excluded.  Learn more at [http://developer.crunchbase.com].

* --verbose, -v : Show debug statements and other information.
* --verbose-api-calls, -va : Show API calls in verbose mode.
* --help, -h: Display this help banner


###Input File Format for Batch Lookups 
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

#Other Stuff
* Version:  v2.0.1
* Author:  Bryan Selner (dev at recoilvelocity dot com)
* Platforms:  I've only really tested it on Mac OS/X 10.9.2 with PHP 5.4.24.  Your mileage could definitely vary on any other platform or version.  
* Issues/Bugs:  See [https://github.com/selner/scooper/issues](https://github.com/selner/scooper/issues)
 
##License
This product is licensed under the GPL (http://www.gnu.org/copyleft/gpl.html). It comes with no warranty, expressed or implied.
