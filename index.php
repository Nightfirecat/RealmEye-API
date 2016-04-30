<?php
/*
 * Realmeye Player Scraper
 * Scrapes character information for a specific player
 */

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
ini_set("user_agent","Realmeye-API/0.3 (https://github.com/Nightfirecat/RealmEye-API)");
$api_version = '$Id$';
$api_version = str_replace('$', '', $api_version);
$api_version = str_replace('Id:', '', $api_version);
$api_version = trim($api_version);
if (preg_match('/^[a-fA-F0-9]+$/', $api_version)) {
	$api_version_header = 'Realmeye-API-Version: ' . $api_version;
	header($api_version_header);
}

//set up some initial vars
$final_output=array();
$callback = isset($_GET['callback']) ? $_GET['callback'] : false;
if(!isset($_GET['data_vars'])){
	$data_vars = false;
} else if($_GET['data_vars']==="true"){
	$data_vars = true;
} else {
	echo_json_and_exit(array("error"=>"Invalid `data_vars` value"));
}
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
	$final_output["donator"] = ($nodelist->length > 0) ? "true" : "false";

	$nodelist = $xpath->query("//table[@class=\"summary\"]//tr"); //get the summary table (escaped quotes)
	foreach($nodelist as $node){
		$test1 = $node->childNodes->item(0)->nodeValue;
		$test2 = $node->childNodes->item(1)->nodeValue;
		$regex1 = "/^([\d]+).*/";		//strips fame or experience amount
		$regex2 = "/^.*?\(([\d]+).*$/";	//strips fame or experience ranking
		
		//sets data about the player
		if($test1=="Chars"){
			$final_output["chars"] = $test2==="0"||((int) $test2) ? ((int) $test2) : $test2;
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
			$final_output["last_seen"] = $test2;
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
		if($final_output["chars"]==="N/A"){ /*number of chars is N/A*/
			//hidden characters, output characters=>hidden
			// https://www.realmeye.com/player/wizgazelle
			$final_output["characters"] = "hidden";
		}else{
			//no characters, output blank characters array...
			// https://www.realmeye.com/player/yosei
			$final_output["characters"] = array();
		}
	}else{ //they have characters, find out their bp/pet status (if no last seen, won't be added)
		$character_table;
		if($nodelist->length==13 || ($nodelist->length==11 && $nodelist->item(9)->nodeValue=="")){
			//bp+pet
			// https://www.realmeye.com/player/Wylem
			$character_table = array("pet","character_dyes","class","level","cqc","fame","exp","place","equips","backpack","stats_maxed", "last_seen", "last_server");
		}else if($nodelist->length==12 || $nodelist->length==10){
			if($nodelist->item(8)->nodeValue==""){
				//they have a backpack, but no pets
				// https://www.realmeye.com/player/Stration
				$character_table = array("character_dyes","class","level","cqc","fame","exp","place","equips","backpack","stats_maxed", "last_seen", "last_server");
			}else{
				//they have a pet, but no backpacks
				// https://www.realmeye.com/player/Yukiyan
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
									if($data_vars){
										$character["data_pet_id"] = (int) $pet_data_id;
									}
									
									if($has_pet){
										$val = $ITEMS[$pet_data_id][0];
									} else {
										$val = "";
									}
									
								//}else if($character_table[$j]==="cqc"){
								
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
									if($data_vars){ $val["data_clothing_dye"] = (int) $attrs->getNamedItem("data-dye1")->nodeValue; }
									if (!isset($ITEMS[$dye1][0])) {
										$ITEMS[$dye1][0] = "";
									}
									$val["clothing_dye"] = $ITEMS[$dye1][0];
									if($data_vars){ $val["data_accessory_dye"] = (int) $attrs->getNamedItem("data-dye2")->nodeValue; }
									if (!isset($ITEMS[$dye2][0])) {
										$ITEMS[$dye2][0] = "";
									}
									$val["accessory_dye"] = $ITEMS[$dye2][0];
									
									//class+skin data-var check
									if($data_vars){
										$character["data_class_id"] = (int) $attrs->getNamedItem("data-class")->nodeValue;
										$character["data_skin_id"] = (int) $attrs->getNamedItem("data-skin")->nodeValue;
									}
								}else if($character_table[$j]==="equips"){
									$items = $node->childNodes->item($j)->childNodes;
									$item1 = $items->item(0)->attributes->getNamedItem("data-item")->nodeValue;
									$item2 = $items->item(1)->attributes->getNamedItem("data-item")->nodeValue;
									$item3 = $items->item(2)->attributes->getNamedItem("data-item")->nodeValue;
									$item4 = $items->item(3)->attributes->getNamedItem("data-item")->nodeValue;
									$val = array();
									if($data_vars){ $val["data_weapon_id"] = (int) $item1; }
									$val["weapon"] = $ITEMS[$item1][0];
									if($data_vars){ $val["data_ability_id"] = (int) $item2; }
									$val["ability"] = $ITEMS[$item2][0];
									if($data_vars){ $val["data_armor_id"] = (int) $item3; }
									$val["armor"] = $ITEMS[$item3][0];
									if($data_vars){ $val["data_ring_id"] = (int) $item4; }
									$val["ring"] = $ITEMS[$item4][0];
									
									//backpack check
									$character["backpack"] = ($node->childNodes->item($j)->childNodes->length==5?"true":"false");
								}else if($character_table[$j]==="last_seen"){
									$val = $node->childNodes->item($j)->nodeValue;
								}else if($character_table[$j]==="last_server"){
									$val = $node->childNodes->item($j)->childNodes->item(0)->attributes->getNamedItem("title")->nodeValue;
								}else{
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
				}
			}
			$final_output["characters"][] = $character;
		}
	}
	
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

function echo_json_and_exit($output_array){
	if($GLOBALS['callback']){
		echo $GLOBALS['callback'] . '(';
	}
	if (isset($_GET['pretty'])) {
		echo json_encode($output_array, JSON_PRETTY_PRINT);
	} else {
		echo json_encode($output_array);
	}
	if($GLOBALS['callback']){
		echo ')';
	}
	echo "\n";
	exit();
}
