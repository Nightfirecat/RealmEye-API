<?php
/*
 * Realmeye Player Scraper
 * Scrapes character information for a specific player
 */

//error handling (don't display errors, but still log them)
ini_set("display_errors",0);

header("access-control-allow-origin: *");

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
	echo_json(Array("error"=>"Invalid player name"));
	exit();
} else if(isset($_GET['callback'])&&!preg_match('/^[a-zA-Z$_][a-zA-Z0-9$_]*$/', $_GET['callback'])){
	//Possible XSS... Yikes!
	header("Content-Type: application/json; charset=utf-8");
	header("HTTP/1.1 400 Invalid Request");
	echo_json(Array("error"=>"Invalid callback name"));
	exit();
}

require_once('../items.php'); //import items definitions
ini_set("user_agent","Realmeye Scraper-API/0.1 (http://www.github.com/nightfirecat)");

//set up some initial vars
$final_output=Array();
$callback = isset($_GET['callback']) ? $_GET['callback'] : false;
$url = "https://www.realmeye.com/player/{$player}/";

//set up xpath
$dom = new DOMDocument();
@$dom->loadHTMLFile($url);
$xpath = new DOMXPath($dom);
$nodelist = $xpath->query("//h1"); //grab the user's display name

if($nodelist->length==0){	//this player isn't on realmeye	
	header('Content-Type: application/json; charset=utf-8');
	echo_json(Array("error"=>"{$_GET['player']} could not be found!"));
} else {
	//set headers
	header('Content-Type: ' . ($callback ? 'application/javascript' : 'application/json') . '; charset=utf-8');

	$final_output["player"] = $nodelist->item(0)->nodeValue; //player's name as it appears ingame

	$nodelist = $xpath->query("//table[@class=\"summary\"]//tr"); //get the summary table (escaped quotes)
	foreach($nodelist as $node){
		$test1=$node->childNodes->item(0)->nodeValue;
			$test2 = $node->childNodes->item(1)->nodeValue;
			$regex1 = "/^([\d]+).*/";		//strips fame or experience amount
			$regex2 = "/^.*?\(([\d]+).*$/";	//strips fame or experience ranking
			
			//sets data about the player
			if($test1=="Chars"){
				$final_output["chars"] = $test2==="0"||((int) $test2) ? ((int) $test2) : $test2;
			}else if($test1=="Fame"){
				$final_output["fame"] = ((int) preg_replace($regex1,"$1",$test2));
				$final_output["fame_rank"] = (($temp = preg_replace($regex2,"$1",$test2))==$test2 && !strstr($test2, '(')) ? 1 : ((int) $temp);
			}else if($test1=="Exp"){
				$final_output["exp"] = ((int) preg_replace($regex1,"$1",$test2));
				$final_output["exp_rank"] = (($temp = preg_replace($regex2,"$1",$test2))==$test2 && !strstr($test2, '(')) ? 1 : ((int) $temp);
			}else if($test1=="Rank"){
				$final_output["rank"] = ((int) $test2);
			}else if($test1=="Account fame"){
				$final_output["account_fame"] = ((int) preg_replace($regex1,"$1",$test2));
				$final_output["account_fame_rank"] = (($temp = preg_replace($regex2,"$1",$test2))==$test2 && !strstr($test2, '(')) ? 1 : ((int) $temp);
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

	$nodelist = $xpath->query("//table[@id]//th"); //get column headers to figure out what data's there
	
	//if they have no characters for some reason...
	if($nodelist->length==0){
		if($final_output["chars"]=="N/A"){ /*number of chars is N/A*/
			//hidden characters, output characters=>hidden
			// http://www.realmeye.com/player/wizgazelle
			$final_output["characters"] = "hidden";
		}else{
			//no characters, output blank characters array...
			// http://www.realmeye.com/player/yosei
			$final_output["characters"] = Array();
		}
	}else{ //they have characters, find out their bp/pet status (if no last seen, won't be added)
		$character_table;
		if($nodelist->length==13 || ($nodelist->length==11 && $nodelist->item(9)->nodeValue=="")){
			//bp+pet
			// http://www.realmeye.com/player/Wylem
			$character_table = Array("pet","character_dyes","class","level","cqc","fame","exp","place","equips","backpack","stats_maxed", "last_seen", "last_server");
		}else if($nodelist->length==12 || $nodelist->length==10){
			if($nodelist->item(8)->nodeValue==""){
				//they have a backpack, but no pets
				// http://www.realmeye.com/player/Stration
				$character_table = Array("character_dyes","class","level","cqc","fame","exp","place","equips","backpack","stats_maxed", "last_seen", "last_server");
			}else{
				//they have a pet, but no backpacks
				// http://www.realmeye.com/player/Yukiyan
				$character_table = Array("pet","character_dyes","class","level","cqc","fame","exp","place","equips","stats_maxed", "last_seen", "last_server");
			}
		}else{
			//no bp and no pet
			// check recently-seen unnamed players
			$character_table = Array("character_dyes","class","level","cqc","fame","exp","place","equips","stats_maxed", "last_seen", "last_server");
		}
		
		$nodelist = $xpath->query("//table[@id]/tbody/tr"); //the rows we want are inside of the only table with an id
		foreach($nodelist as $node){ //for each row of the character table
				for($j=0;$j<$node->childNodes->length;$j++){
						if($character_table[$j]){ //if the field is not ""
								if($character_table[$j]=="backpack"){
									$val = ($node->childNodes->item($j)->childNodes->item(0)->nodeType==1?"true":"false");
									
								//}else if($character_table[$j]=="cqc"){
								//}else if($character_table[$j]=="stats_maxed"){
								
								}else if($character_table[$j]=="pet"){
									if($node->childNodes->item($j)->hasChildNodes()){
										$val = $ITEMS[($node->childNodes->item($j)->childNodes->item(0)->attributes->getNamedItem("data-item")->nodeValue)][0];
									} else {
										$val = "";
									}
								}else if($character_table[$j]=="character_dyes"){
									$attrs = $node->childNodes->item($j)->childNodes->item(0)->attributes;
									$dye1 = $attrs->getNamedItem("data-clothing-dye-id")->nodeValue;
									$dye2 = $attrs->getNamedItem("data-accessory-dye-id")->nodeValue;
									$val = Array("clothing_dye"=>($temp=$ITEMS[$dye1][0])?$temp:"", "accessory_dye"=>($temp=$ITEMS[$dye2][0])?$temp:"");
								}else if($character_table[$j]=="equips"){
									$items = $node->childNodes->item($j)->childNodes;
									$item1 = $items->item(0)->attributes->getNamedItem("data-item")->nodeValue;
									$item2 = $items->item(1)->attributes->getNamedItem("data-item")->nodeValue;
									$item3 = $items->item(2)->attributes->getNamedItem("data-item")->nodeValue;
									$item4 = $items->item(3)->attributes->getNamedItem("data-item")->nodeValue;
									$val = Array("weapon"=>$ITEMS[$item1][0],"ability"=>$ITEMS[$item2][0],"armor"=>$ITEMS[$item3][0],"ring"=>$ITEMS[$item4][0]);
								}else if($character_table[$j]=="last_seen"){
									$val = $node->childNodes->item($j)->nodeValue;
								}else if($character_table[$j]=="last_server"){
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
			//if the character has no pet, bp, last-seen or last-server, set their values to "" as opposed to leaving their values out
			$optionalColumns = Array("pet","backpack","last_seen","last_server");
			foreach($optionalColumns as $optional_column){
				if(!$character[$optional_column]){
					$character[$optional_column] = "";
				}
			}
			$final_output["characters"][] = $character;
		}
	}
	
	echo_json($final_output);
	
}

//
//Function definitions
//

function echo_json($output_array){
	ob_start('ob_gzhandler');
	if($GLOBALS['callback']){
		echo $GLOBALS['callback'] . '(';
	}
	echo json_encode($output_array);
	if($GLOBALS['callback']){
		echo ')';
	}
}

?>