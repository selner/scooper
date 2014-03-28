<?php



/**
 * Wrapper function for accessing Pashua from PHP
 *
 * @param string $conf                Configuration string to pass to Pashua
 * @param string $encoding [optional] Configuration string's text encoding (default: "macroman")
 * @param string $apppath [optional]  Absolute filesystem path to directory containing Pashua
 *
 * @return array Associative array of values returned by Pashua
 * @throws Exception if array is not valid
 *
 * @author Carsten Bluem <carsten@bluem.net>
 */
function pashua_run($conf, $encoding = 'macroman', $apppath = null) {

    // Check for safe mode
    if (ini_get('safe_mode')) {
        $msg = "To use Pashua you will have to disable safe mode or ".
            "change pashua_run() to fit your environment.\n";
        fwrite(STDERR, $msg);
        exit(1);
    }

    // Write configuration string to temporary config file
    $configfile = tempnam('/tmp', 'Pashua_');
    if (false === $fp = @fopen($configfile, 'w')) {
        throw new Exception("Error trying to open $configfile");
    }
    fwrite($fp, $conf);
    fclose ($fp);

    // Try to figure out the path to pashua
    $bundlepath = "Pashua.app/Contents/MacOS/Pashua";
    $path       = '';

    if ($apppath) {

        // A directory path was given
        $path = str_replace('//', '/', $apppath.'/'.$bundlepath);
    } else {
        // Try find Pashua in one of the common places
        $paths = array(
            dirname(__FILE__).'/Pashua',
            dirname(__FILE__)."/$bundlepath",
            "./$bundlepath",
            "/Applications/$bundlepath",
            "$_SERVER[HOME]/Applications/$bundlepath"
        );
        // Then, look in each of these places
        foreach ($paths as $searchpath) {
            if (file_exists($searchpath) and
                is_executable($searchpath)) {
                // Looks like Pashua is in $dir --> exit the loop
                $path = $searchpath;
                break;
            }
        }
    }

    // Raise an error if we didn't find the application
    if (empty($path)) {
        throw new Exception('Unable to locate Pashua. Tried to find it in: '.join(', ', $paths));
    }

    // Call pashua binary with config file as argument and read result
    $cmd = escapeshellarg($path).' '.
        (preg_match('#^\w+$#', $encoding) ? "-e $encoding " : '').
        escapeshellarg($configfile);
    $result = shell_exec($cmd);

    @unlink($configfile);

    $parsed = array();

    // Parse result
    foreach (explode("\n", $result) as $line) {
        preg_match('/^(\w+)=(.*)$/', $line, $matches);
        if (empty($matches) or empty($matches[1])) {
            continue;
        }
        $parsed[$matches[1]] = $matches[2];
    }

    return $parsed;
}
?>