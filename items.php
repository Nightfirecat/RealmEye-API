<?php
/*
Item loader; creates and includes item definitions file
*/

// definitions URL, PHP definition file
$definitions_url = 'https://www.realmeye.com/s/y3/js/definition.js';
$definitions_file = 'definition.php';

// get headers, check if it's been updated since last seen
$definitions_headers = get_headers($definitions_url, 1);
if($definitions_headers){
	// last-modified file
	$last_seen_modified_file = 'definition-modified.txt';
	if (!file_exists($last_seen_modified_file)) {
		file_put_contents($last_seen_modified_file, '');
	}
	$last_modified = $definitions_headers['Last-Modified'];

	// get last entry if redirects were necessary
	if(is_array($last_modified)){
		$last_modified = $last_modified[count($last_modified)-1];
	}

	// compare to locally cached timestamp (of last update)
	$last_seen_modified = file_get_contents($last_seen_modified_file);
	if(!$last_seen_modified || $last_seen_modified !== $last_modified){
		file_put_contents($last_seen_modified_file, $last_modified);
		update_definitions($definitions_file);
	}
}

// add the $ITEMS variable - loaded after potential definitions update above
require_once $definitions_file;

// translate JS array definitions to PHP array definitions and write to $file
function update_definitions($file){
	$js_definitions = file_get_contents($GLOBALS['definitions_url']);
	$js_capture_regex = '/((["\'])?\-?[\d]+(?:e[\d]+)?\2?):\[([^\]]+)\],?/';
	$php_substitution_pattern = '$1=>[$3],';
	preg_match_all($js_capture_regex, $js_definitions, $matches, PREG_PATTERN_ORDER);
	$php_definitions = '<?php $ITEMS=[' . "\n";
	for ($i = 0; $i < count($matches[0]); $i++) {
		$php_definitions .= "\t" . preg_replace($js_capture_regex, $php_substitution_pattern, $matches[0][$i]) . "\n";
	}
	$php_definitions .= '];';
	file_put_contents($file, $php_definitions);
}
