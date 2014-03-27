## Scooper Change Log

# 03/26/2014 (Bryan Selner)
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






