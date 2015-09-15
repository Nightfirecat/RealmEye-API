<?php

//definitions URL, PHP definition file
//all directory locations are relative to script location (/0.1/, /0.2/, etc)
$definitions_url = "http://www.realmeye.com/s/y3/js/definition.js";
$definitions_file = "definition.php";

//get headers, check if it's been updated since last seen
$definitions_headers = @get_headers($definitions_url, 1);
if($definitions_headers){
	//last-modified file
	$last_seen_modified_file = "../modified.txt";
	$last_modified = $definitions_headers["Last-Modified"];
	
	//get last entry if redirects were necessary
	if(is_array($last_modified)){
		$last_modified = $last_modified[count($last_modified)-1];
	}
	
	//compare to local 
	$last_seen_modified = file_get_contents($last_seen_modified_file);
	if(!$last_seen_modified || $last_seen_modified !== $last_modified){
		file_put_contents($last_seen_modified_file, $last_modified);
		update_definitions($definitions_file);
	}
}

//add the $ITEMS variable - loaded after potential definitions update above
require "$definitions_file";

//translate JS array definitions to PHP array definitions and write to $file
function update_definitions($file){
	$js_definitions = file_get_contents("http://www.realmeye.com/s/y3/js/definition.js");
	$inter_definitions = preg_replace("/('\-[\d]+(?:e[\d]+)?'|\"\-[\d]+(?:e[\d]+)?\"|[\d]+(?:e[\d]+)?):\[([^]]+)\],?/", "\t$1=>Array($2),\n", $js_definitions);
	$php_definitions = preg_replace("/^items\=\{(.+),\n\};$/s", "<?php \$ITEMS=Array(\n$1\n);?>", $inter_definitions);
	file_put_contents($file, $php_definitions);
}

?>