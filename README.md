##Scooper
=================
Author:  Selner (bry.int@bryanselner.com)

##What It Does
------------
Takes an input CSV of URLs and gathers site data about it from Quantcast, Moz.com and Crunchbase such as
estimated monthly uniques, company type & description, and domain authority

INPUT CSV FORMAT: 
	  Line 1:  "Company Name" or "Company URL" 
	  Line 2 and on:  1st record (e.g. "Microsoft" or "http:www.microsoft.com")
..etc

#Bugs
------------
* None reported.


#Near Term Changes
------------
* Add comments for publish

# Known Issues
------------
* 	Cannot use file names with hyphens in them due to a bug in the Pharse library (https://github.com/chrisallenlane/pharse/issues/3)
* 	Crunchbase Offices and other columns appear as "Array" in the final CSV; need to implode them into strings.
* Occasionally see timeout errors with the Crunchbase API.  Example: 'Error: ""---> Processing row#33: C2S Technologies  Error #28: Resolving timed out after 10000 milliseconds""'



##Future Ideas for Improvement
=================
* Add a single company or URL lookup mode that presents UI and doesn't require an input CSV
* Generalize the plugin PHPs to have a common class so that it's easy to drop a new plugin support file in
* If a data set is excluded by the command line, change the default values for those fields from 'N/A' to something else. Maybe use array array_replace_recursive ( array $array1 , array $array2 [, array $... ] )
* if company is mismatched in Crunch, Site Basics or Moz, it's still marked as TRUE and valid.  Need to clear. 
    * Look at first 15 characters of name?  
    * Look at LEN(name) - round(LEN(name)*15%) for match (always drop the last 15% of chars when matching)?      
    * Add a confidence indicator value instead of TRUE/FALSE?            
    * Look at computed URL vs. Crunchbase downloaded URL  if not same / near same domain part, then reject the row?
* add column for tracking whether company name or URL was a computed field, not entered. Maybe change the final results header row for those columns to say "Company Name (Computed/Guessed" or similar
* add support for asking for input file CSV if not set already
* add support string array fields such as "Offices" in Crunchbase; concatenate as much as you can into a string value.
* add Platypus build support for the app


