## Scooper Change Log

# 03/27/2014 (BJS)
	* Added Pashua.app binary for Mac UI dialogs
	* Removed pashua_wrapper files and created new classes for Mac UI in ui/mac/ directory.  
	* Added draft template_Scooper.app and related build files to the repository.  Builds are not yet functional. 
	* Changed main.php to scooper.php. Added new run_scooper.php called from Pashua.
	
	
# 03/27/2014 (BJS)
	* Added new single record lookup modes:  --lookup_name/-ln and --lookup_url/-lu.  These options do not require an input CSV.
	* Fixed: calls to previously deleted function "GetObjectsFromAPI"
	* Minor fixes to output file headers such as making quantcast plugin header names consistent with other data sources.
	* Moved user options parsing out of main.php and into common.php so it can be reused in UI and command line modes


# 03/27/2014 (BJS)
	* Crunchbase: combined entity facts common across namespaces to use same key naming patter of cb.<field>.
	* Added KLogger v0.1 support for logging each run to a log file.  Continues to print to stdout if KLogger is not found.
	* Fixed:  missing multiple column headers for Crunchbase Service Providers
	* Added input source CSV file examples


# 03/27/2014 (BJS)
	* Fixed: Quantcast displays as the very last column.
	* Removed unused constant C__OUTPUT_HEADERKEY_LISTS_TO_FILE

# 03/26/2014 (BJS)
	* Added support for Moz API values to be passed via the command line or by changing values in a new, separate config.php file.
	* Merged the two curlWrap functions into a single new APICallWrapperClass
	* Renamed the SimpleBaseFileClass as SimpleScooterCSVFileClass
    * Fixed: If company name is not present in the file or the website cannot be found, then a bunch of blank N/A rows appears in the results (consultants)
    * Fixed: Blank rows in the CSV are actually loaded as record rows, but then just fail later on.  Should be scrubbed up front.
	* Fixed: If inputfile contains a filename only, output files are incorrectly to the root directory of the user's drive.
	* Fixed: Blank rows in the input CSV file cause bad and error records to be produced in the output file.

# 03/26/2014 (BJS)
	* Initial version uploaded to Github.  Forked to v0.1dev.
	* Fixed url-only input issues.  Company name is now computed based on the actual site's domain when possible.
    * Removed obsolete get_basicDataFacts() call
	* Updated CrunchbasePlugin getArt
	* Added new helper function isRecordFieldNullOrNotSet()
	* Changed name of 'url' column in results to be 'input_source_url'
	* Removed 'valid_site' column. Replaced with new 'result_accuracy' column which describes any key details about how accurate that row's data might be
	* Removed untested and unused array flattening functions.
	* Moved IsRealSite function into BaseFactsPluginClass (the only place it was used.)
	* Added developer/debugger helper function __debug__var_dump_exit__()
	* Fixed: Input source CSV key does not case-insensitive match "company name"






