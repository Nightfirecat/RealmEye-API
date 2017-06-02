<?php
/*
 * Realmeye Player Scraper
 * Scrapes character information for a specific player
 */

// Set user agent (for RealmEye calls) and API version header
ini_set("user_agent","Realmeye-API/0.4 (https://github.com/Nightfirecat/RealmEye-API)");
$api_version_file = 'rev.txt';
if (file_exists($api_version_file) && is_readable($api_version_file)) {
	$api_version = file_get_contents($api_version_file);
	$api_version_header = 'Realmeye-API-Version: ' . $api_version;
	header($api_version_header);
}

if(isset($_GET['player']) && $_GET['player']){
	$player = $_GET['player'];
} else if(isset($_GET['id']) && $_GET['id']){
	$player = $_GET['id'];
} else {
	$player = "";
}

if(preg_match("/^[a-z]{1,10}$/i",$player)===0 && preg_match("/^[a-z0-9]{11}$/i",$player)===0){
	//Sneaky little hobbitses.
	header("Content-Type: application/json; charset=utf-8");
	header("HTTP/1.1 400 Invalid Request");
	echo_json_and_exit(array("error"=>"Invalid player name"));
} else if(isset($_GET['callback'])&&!preg_match('/^[a-zA-Z$_][a-zA-Z0-9$_]*$/', $_GET['callback'])){
	//Possible XSS... Yikes!
	header("Content-Type: application/json; charset=utf-8");
	header("HTTP/1.1 400 Invalid Request");
	echo_json_and_exit(array("error"=>"Invalid callback name"));
}

require_once('items.php'); //import items definitions

//set up some initial vars
$final_output=array();
$callback = isset($_GET['callback']) ? $_GET['callback'] : false;
$url = "https://www.realmeye.com/player/{$player}/";

//set up xpath
$dom = new DOMDocument();
@$dom->loadHTMLFile($url);
$xpath = new DOMXPath($dom);
$nodelist = $xpath->query("//h1"); //grab the user's display name

if($nodelist->length==0){	//this player isn't on realmeye	
	header('Content-Type: application/json; charset=utf-8');
	echo_json_and_exit(array("error"=>"{$_GET['player']} could not be found!"));
} else {
	//set headers
	header('Content-Type: ' . ($callback ? 'application/javascript' : 'application/json') . '; charset=utf-8');

	$final_output["player"] = $nodelist->item(0)->nodeValue; //player's name as it appears ingame
	
	//enter donation status
	$nodelist = $xpath->query("//a[@class=\"donate\"]");
	$final_output["donator"] = $nodelist->length > 0;

	$nodelist = $xpath->query("//table[@class=\"summary\"]//tr"); //get the summary table (escaped quotes)
	foreach($nodelist as $node){
		$test1 = $node->childNodes->item(0)->nodeValue;
		$test2 = $node->childNodes->item(1)->nodeValue;
		$regex1 = "/^([\d]+).*/";		//strips fame or experience amount
		$regex2 = "/^.*?\(([\d]+).*$/";	//strips fame or experience ranking
		
		//sets data about the player
		if($test1=="Characters"){
			$final_output["chars"] = $test2==="0"||((int) $test2) ? ((int) $test2) : -1;
		} else if ($test1==='Skins') {
			$final_output['skins'] = ((int) preg_replace($regex1,"$1",$test2));
			$final_output['skins_rank'] = !strstr($test2, '(') ? -1 : ((int) preg_replace($regex2, "$1", $test2));
		}else if($test1=="Fame"){
			$final_output["fame"] = ((int) preg_replace($regex1,"$1",$test2));
			$final_output["fame_rank"] = !strstr($test2, '(') ? -1 : ((int) preg_replace($regex2, "$1", $test2));
		}else if($test1=="Exp"){
			$final_output["exp"] = ((int) preg_replace($regex1,"$1",$test2));
			$final_output["exp_rank"] = !strstr($test2, '(') ? -1 : ((int) preg_replace($regex2, "$1", $test2));
		}else if($test1=="Rank"){
			$final_output["rank"] = ((int) $test2);
		}else if($test1=="Account fame"){
			$final_output["account_fame"] = ((int) preg_replace($regex1,"$1",$test2));
			$final_output["account_fame_rank"] = !strstr($test2, '(') ? -1 : ((int) preg_replace($regex2, "$1", $test2));
		}else if($test1=="Guild"){
			$final_output["guild"] = $test2;
		}else if($test1=="Guild Rank"){
			$final_output["guild_rank"] = $test2;
		}else if($test1=="Created"){
			$final_output["created"] = $test2;
		}else if($test1=="Last seen"){
			$final_output["player_last_seen"] = $test2;
		}
	}
	$hidden_chars_attributes = [
		'chars',
		'skins',
		'skins_rank',
	];
	foreach ($hidden_chars_attributes as $hidden_attribute) {
		if (!isset($final_output[$hidden_attribute])) {
			$final_output[$hidden_attribute] = -1;
		}
	}
	$optional_player_attributes = [
		'guild',
		'guild_rank',
	];
	foreach ($optional_player_attributes as $optional_player_attribute) {
		if (!isset($final_output[$optional_player_attribute])) {
			$final_output[$optional_player_attribute] = '';
		}
	}
	
	//output description lines (1-3)
	for($i = 1; $i <= 3; $i++){
		$temp = $xpath->query("//div[contains(@class, 'line".$i."')]");
		$final_output["desc".$i] = ($temp->length > 0) ? $temp->item(0)->nodeValue : "";
	}
	
	$nodelist = $xpath->query("//table[@id]//th"); //get column headers to figure out what data's there
	
	//if they have no characters for some reason...
	if($nodelist->length==0){
		$final_output["characters"] = array();
		if($final_output["chars"]===-1){ // number of chars is N/A
			//hidden characters, output characters=>hidden
			// https://www.realmeye.com/player/wizgazelle
			$final_output['characters_hidden'] = true;
		}else{
			//no characters, output blank characters array...
			// https://www.realmeye.com/player/joanofarc
			$final_output['characters_hidden'] = false;
		}
	}else{ //they have characters, find out their bp/pet status (if no last seen, won't be added)
		$final_output['characters_hidden'] = false;
		$character_table;
		if($nodelist->length==13 || ($nodelist->length==11 && $nodelist->item(9)->nodeValue=="")){
			//bp+pet
			// https://www.realmeye.com/player/Fiddy
			$character_table = array("pet","character_dyes","class","level","cqc","fame","exp","place","equips","backpack","stats_maxed", "last_seen", "last_server");
		}else if($nodelist->length==12 || $nodelist->length==10){
			if($nodelist->item(8)->nodeValue==""){
				//they have a backpack, but no pets
				// https://www.realmeye.com/player/Stration
				$character_table = array("character_dyes","class","level","cqc","fame","exp","place","equips","backpack","stats_maxed", "last_seen", "last_server");
			}else{
				//they have a pet, but no backpacks
				// https://www.realmeye.com/player/ROTFamouse
				$character_table = array("pet","character_dyes","class","level","cqc","fame","exp","place","equips","stats_maxed", "last_seen", "last_server");
			}
		}else{
			//no bp and no pet
			// check recently-seen unnamed players
			$character_table = array("character_dyes","class","level","cqc","fame","exp","place","equips","stats_maxed", "last_seen", "last_server");
		}
		
		$nodelist = $xpath->query("//table[@id]/tbody/tr"); //the rows we want are inside of the only table with an id
		foreach($nodelist as $node){ //for each row of the character table
			for($j=0;$j<$node->childNodes->length;$j++){
					if($character_table[$j]){ //if the field is not ""
							if($character_table[$j]==="pet"){
								$has_pet = $node->childNodes->item($j)->hasChildNodes();
								$pet_data_id = $has_pet?$node->childNodes->item($j)->childNodes->item(0)->attributes->getNamedItem("data-item")->nodeValue:-1;
								$character["data_pet_id"] = (int) $pet_data_id;
								
								if($has_pet){
									$val = $ITEMS[$pet_data_id][0];
								} else {
									$val = "";
								}
							}else if($character_table[$j]==="stats_maxed"){
								$val = (int) $node->childNodes->item($j)->nodeValue;
								
								$stats_list = array("hp","mp","attack","defense","speed","vitality","wisdom","dexterity");
								$attrs = $node->childNodes->item($j)->childNodes->item(0)->attributes;
								$bonuses = $attrs->getNamedItem("data-bonuses")->nodeValue;
								$bonuses = explode(",", substr($bonuses, 1, -1));
								$total_stats = $attrs->getNamedItem("data-stats")->nodeValue;
								$total_stats = explode(",", substr($total_stats, 1, -1));
								
								$stats = array();
								$stats_length = count($stats_list);
								for($i = 0; $i < $stats_length; $i++){
									$stats[$stats_list[$i]] = $total_stats[$i] - $bonuses[$i];
								}
								
								$character["stats"] = $stats;
							}else if($character_table[$j]==="character_dyes"){
								$attrs = $node->childNodes->item($j)->childNodes->item(0)->attributes;
								$dye1 = $attrs->getNamedItem("data-clothing-dye-id")->nodeValue;
								$dye2 = $attrs->getNamedItem("data-accessory-dye-id")->nodeValue;
								$val = array();
								$val["data_clothing_dye"] = (int) $attrs->getNamedItem("data-dye1")->nodeValue;
								if (!isset($ITEMS[$dye1][0])) {
									$ITEMS[$dye1][0] = "";
								}
								$val["clothing_dye"] = $ITEMS[$dye1][0];
								$val["data_accessory_dye"] = (int) $attrs->getNamedItem("data-dye2")->nodeValue;
								if (!isset($ITEMS[$dye2][0])) {
									$ITEMS[$dye2][0] = "";
								}
								$val["accessory_dye"] = $ITEMS[$dye2][0];

								//class+skin data-vars
								$character["data_class_id"] = (int) $attrs->getNamedItem("data-class")->nodeValue;
								$character["data_skin_id"] = (int) $attrs->getNamedItem("data-skin")->nodeValue;
							}else if($character_table[$j]==="equips"){
								$item_indeces = array(
									'weapon',
									'ability',
									'armor',
									'ring'
								);
								$item_wrappers = $node->childNodes->item($j)->childNodes;
								$character_item_names = array();
								for ($i = 0; $i < count($item_indeces); $i++) {
									$item_href = $item_wrappers->item($i)->childNodes->item(0)->attributes->getNamedItem('href')->nodeValue;
									$split_href = explode('/', $item_href);
									$character_item_names[] = $split_href[count($split_href) - 1];
								}

								$val = array();
								foreach ($character_item_names as $character_index=>$character_item) {
									foreach ($ITEMS as $item_id=>$item_index) {
										$comparison = preg_replace('/^[^\/]+\//', '', $item_index[0]);
										$comparison = strtolower($comparison);
										$comparison = preg_replace('/[\W]+/', '-', $comparison);

										if ($comparison === $character_item) {
											$val['data_' . $item_indeces[$character_index] . '_id'] = (int) $item_id;
											$val[$item_indeces[$character_index]] = $item_index[0];
											break;
										}
									}
								}

								//backpack check
								$character["backpack"] = $item_wrappers->length === 5;
							}else if($character_table[$j]==="last_seen"){
								$val = $node->childNodes->item($j)->nodeValue;
							}else if($character_table[$j]==="last_server"){
								$val = $node->childNodes->item($j)->childNodes->item(0)->attributes->getNamedItem("title")->nodeValue;
							}else{
								// class, level, cqc, fame, exp, place, backpack
								$temp = $node->childNodes->item($j)->nodeValue;
								if($temp==="0" ||(int) $temp){
									$val = (int) $temp;
								} else {
									$val = $temp;
								}
							}
						$character[$character_table[$j]] = $val;
					}
			}
			//if the character has no pet, bp, last-seen or last-server index, set their values to "" as opposed to leaving the index out
			$optionalColumns = array("pet","backpack","last_seen","last_server");
			foreach($optionalColumns as $optional_column){
				if(!isset($character[$optional_column])) {
					$character[$optional_column] = "";
					if ($optional_column === 'pet') {
						$character["data_pet_id"] = -1;
					} else if ($optional_column === 'backpack') {
						$character[$optional_column] = false;
					}
				}
			}
			$final_output["characters"][] = $character;
		}
	}
	
	$final_output = ksort_recursive($final_output);
	$intersect_filter = create_filter($final_output);
	$final_output = array_intersect_key_recursive($final_output, $intersect_filter);
	
	//output and exit
	echo_json_and_exit($final_output);
}

//
//Function definitions
//

function create_filter($full_output){
	$filter = create_filter_base($full_output);
	if(isset($_GET['filter']) && strlen($_GET['filter'])){
		$filter_terms = $_GET['filter'];
		if($filter_terms[0]==="-"){
			$mode = "diff";
			$filter_terms = substr($filter_terms, 1);
		} else {
			$mode = "intersect";
		}
		$filter_terms = array_fill_keys(explode(" ",$filter_terms),null); //must be assoc. array; values don't matter
		$filter_method = "array_".$mode."_key";
		$filter = $filter_method($filter, $filter_terms);
	}
	return $filter;
}

function create_filter_base($full_output){
	$filter_base = array();
	foreach($full_output as $key => $value){
		if(!is_int($key)){
			$filter_base[$key] = $key;
		}
		if(is_array($value)){
			$filter_base = array_merge($filter_base,create_filter_base($value));
		}
	}
	return $filter_base;
}

function array_intersect_key_recursive($array1, $array2){
	$output = array();
	foreach($array1 as $key => $value){
		if(is_int($key) || in_array($key, $array2)){
			if(is_array($value)){
				$array_value = array_intersect_key_recursive($value, $array2);
				if(!is_int($key) || !empty($array_value)){
					$output[$key] = $array_value;
				}
			} else {
				$output[$key] = $value;
			}
		}
	}
	return $output;
}

// Returns a recursively-`ksort`ed $array
function ksort_recursive($array, $sort_flags = SORT_REGULAR) {
	foreach ($array as $key => $value) {
		if (is_array($array[$key])) {
			$array[$key] = ksort_recursive($array[$key], $sort_flags);
		}
	}
	ksort($array, $sort_flags);
	return $array;
}

function echo_json_and_exit($output_array){
	$default_options = JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_SLASHES;
	if (isset($_GET['callback']) && $_GET['callback']) {
		echo $GLOBALS['callback'] . '(';
	}
	if (isset($_GET['pretty'])) {
		echo json_encode($output_array, JSON_PRETTY_PRINT | $default_options);
	} else {
		echo json_encode($output_array, $default_options);
	}
	if (isset($_GET['callback']) && $_GET['callback']) {
		echo ')';
	}
	echo "\n";
	exit();
}
