<?php
/*
Realmeye player scraper
Scrapes character information for a specific player
*/
declare(strict_types=1);

require_once('classes/RealmEyeAPIUtils.php');
$utils = new RealmEyeAPIUtils();
$config = $utils->config;
$logger = $utils->logger;

// Set user agent (for RealmEye calls)
ini_set(
	'user_agent',
	'Realmeye-API/0.4 (https://github.com/Nightfirecat/RealmEye-API)'
);

// Emit RealmEye-API-Version header if config is absent or if
// `emit_version_header = true` in configuration file. This header will not be
// emitted if `emit_version_header = false` is set in the configuration file.
if (!isset($config['emit_version_header']) || $config['emit_version_header']) {
	$logger->debug('Attempting to emit version header');
	$api_version_file = 'rev.txt';
	if (file_exists($api_version_file) && is_readable($api_version_file)) {
		$api_version = trim(file_get_contents($api_version_file));
		$logger->debug('Emitting version header: ' . $api_version);
		$api_version_header = 'RealmEye-API-Version: ' . $api_version;
		header($api_version_header);
	} else {
		$logger->warn('Version file missing; not emitting version header.');
	}
}

$player = $_GET['player'] ?: $_GET['id'] ?: '';
if (!$player) {
	$logger->error('Player name/ID passed were blank!');
	header('Content-Type: application/json; charset=utf-8');
	header('HTTP/1.1 400 Invalid Request');
	echo_json_and_exit([
		'error' => 'Player name or character ID must be provided!'
	]);
} else {
	$logger->debug('Player name/ID: ' . $player);
}

if (
	!preg_match('/^[a-z]{1,10}$/i', $player) &&
	!preg_match('/^[a-z0-9]{11}$/i', $player)
) {
	// Sneaky little hobbitses.
	$logger->error('Player name/ID invalid.');
	header('Content-Type: application/json; charset=utf-8');
	header('HTTP/1.1 400 Invalid Request');
	echo_json_and_exit(['error' => 'Invalid player name']);
} else if (
	isset($_GET['callback']) &&
	!preg_match('/^[a-zA-Z$_][a-zA-Z0-9$_]*$/', $_GET['callback'])
) {
	// Possible XSS... Yikes!
	$logger->error('Callback invalid, possible XSS: ' . $_GET['callback']);
	header('Content-Type: application/json; charset=utf-8');
	header('HTTP/1.1 400 Invalid Request');
	echo_json_and_exit(['error' => 'Invalid callback name']);
}

require_once 'items.php'; // import items definitions

// set up some initial vars
$final_output = [];
$callback = isset($_GET['callback']) ? $_GET['callback'] : false;
$logger->debug('callback = ' . $callback);
$url = 'https://www.realmeye.com/player/' . $player . '/';

// set up xpath; ignore "Tag ... invalid" warnings
libxml_use_internal_errors(true);
$dom = new DOMDocument();
$logger->trace('Start of HTML loading: ' . microtime());
$dom->loadHTMLFile($url);
$logger->trace('End of HTML loading: ' . microtime());
foreach (libxml_get_errors() as $libxml_error) {
	if (!RealmEyeAPIUtils::libxml_error_is_tag_warning($libxml_error)) {
		$logger->warn(
			'DOMDocument::loadHTMLFile(): libXML error: ' .
			trim($libxml_error->message) . ' in ' . $libxml_error->file .
			', line ' . $libxml_error->line . ' col ' . $libxml_error->column
		);
	}
}
libxml_clear_errors();
libxml_use_internal_errors(false);
$xpath = new DOMXPath($dom);
$nodelist = $xpath->query('//h1'); // grab the user's display name

if ($nodelist->length === 0) {	// this player isn't on realmeye
	$logger->info('Player not found on RealmEye: ' . $player);
	header('Content-Type: application/json; charset=utf-8');
	echo_json_and_exit(['error' => $player . ' could not be found!']);
} else {
	// set headers
	header(
		'Content-Type: application/' . ($callback ? 'javascript' : 'json') .
		'; charset=utf-8'
	);

	$final_output['player'] = $nodelist->item(0)->nodeValue; // player's name as it appears ingame
	$logger->trace('player = ' . $final_output['player']);

	// enter donation status
	$nodelist = $xpath->query('//a[@class="donate"]');
	$final_output['donator'] = $nodelist->length > 0;
	$logger->trace('donator = ' . $final_output['donator']);

	$nodelist = $xpath->query('//table[@class="summary"]//tr'); // get the summary table (escaped quotes)
	foreach ($nodelist as $node) {
		$test1 = $node->childNodes->item(0)->nodeValue;
		$test2 = $node->childNodes->item(1)->nodeValue;
		$regex1 = '/^([\d]+).*/';		// strips fame or experience amount
		$regex2 = '/^.*?\(([\d]+).*$/';	// strips fame or experience ranking

		// sets data about the player
		if ($test1 === 'Characters') {
			$final_output['chars'] = $test2 === '0' || ((int) $test2) ? ((int) $test2) : -1;
			$logger->trace('chars = ' . $final_output['chars']);
		} else if ($test1 === 'Skins') {
			$final_output['skins'] = ((int) preg_replace($regex1, '$1', $test2));
			$final_output['skins_rank'] = !strstr($test2, '(') ? -1 : ((int) preg_replace($regex2, '$1', $test2));
			$logger->trace(
				'skins/skins_rank = ' . $final_output['skins'] . '/' .
				$final_output['skins_rank']
			);
		} else if ($test1 === 'Fame') {
			$final_output['fame'] = ((int) preg_replace($regex1, '$1', $test2));
			$final_output['fame_rank'] = !strstr($test2, '(') ? -1 : ((int) preg_replace($regex2, '$1', $test2));
			$logger->trace(
				'fame/fame_rank = ' . $final_output['fame'] . '/' .
				$final_output['fame_rank']
			);
		} else if ($test1 === 'Exp') {
			$final_output['exp'] = ((int) preg_replace($regex1, '$1', $test2));
			$final_output['exp_rank'] = !strstr($test2, '(') ? -1 : ((int) preg_replace($regex2, '$1', $test2));
			$logger->trace(
				'exp/exp_rank = ' . $final_output['exp'] . '/' .
				$final_output['exp_rank']
			);
		} else if ($test1 === 'Rank') {
			$final_output['rank'] = ((int) $test2);
			$logger->trace('rank = ' . $final_output['rank']);
		} else if ($test1 === 'Account fame') {
			$final_output['account_fame'] = ((int) preg_replace($regex1, '$1', $test2));
			$final_output['account_fame_rank'] = !strstr($test2, '(') ? -1 : ((int) preg_replace($regex2, '$1', $test2));
			$logger->trace(
				'account_fame/account_fame_rank = ' .
				$final_output['account_fame'] . '/' .
				$final_output['account_fame_rank']
			);
		} else if ($test1 === 'Guild') {
			$guild_anchor_node = $node->childNodes->item(1)->childNodes->item(0);
			$final_output['guild'] = $guild_anchor_node->nodeValue;
			$final_output['guild_confirmed'] = (bool)
				// checking for spelling error here --v
				$xpath->query('//i[@title="Not a confimed member"]')->length ||
				$xpath->query('//i[@title="Not a confirmed member"]')->length;
			$logger->trace(
				'guild/guild_confirmed = ' . $final_output['guild'] . '/' .
				$final_output['guild_confirmed']
			);
		} else if ($test1 === 'Guild Rank') {
			$final_output['guild_rank'] = $test2;
			$logger->trace('guild_rank = ' . $final_output['guild_rank']);
		} else if ($test1 === 'Created') {
			$final_output['created'] = $test2;
			$logger->trace('created = ' . $final_output['created']);
		} else if ($test1 === 'Last seen') {
			$final_output['player_last_seen'] = $test2;
			$logger->trace(
				'player_last_seen = ' . $final_output['player_last_seen']
			);
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
			$logger->trace(
				$hidden_attribute . ' = ' . $final_output[$hidden_attribute]
			);
		}
	}
	$optional_player_attributes = [
		'guild',
		'guild_rank',
	];
	foreach ($optional_player_attributes as $optional_player_attribute) {
		if (!isset($final_output[$optional_player_attribute])) {
			$final_output[$optional_player_attribute] = '';
			$logger->trace(
				$optional_player_attribute . ' = ' .
				$final_output[$optional_player_attribute]
			);
		}
	}

	// output description lines (1-3)
	for ($i = 1; $i <= 3; $i++) {
		$descNum = 'desc' . $i;
		$temp = $xpath->query('//div[contains(@class, \'line' . $i . '\')]');
		$final_output[$descNum] = ($temp->length > 0) ? $temp->item(0)->nodeValue : '';
		$logger->trace($descNum . ' = ' . $final_output[$descNum]);
	}

	$nodelist = $xpath->query('//table[@id]//th'); // get column headers to figure out what data's there

	// if they have no characters for some reason...
	if ($nodelist->length === 0) {
		$final_output['characters'] = [];
		$logger->trace('characters = []');
		if ($final_output['chars'] === -1) {
			// hidden characters, output characters_hidden => true
			// https://www.realmeye.com/player/WizGazelle
			$final_output['characters_hidden'] = true;
		} else {
			// no characters, output characters_hidden => false
			// https://www.realmeye.com/player/JoanOfArc
			$final_output['characters_hidden'] = false;
		}
		$logger->trace(
			'characters_hidden = ' . $final_output['characters_hidden']
		);
	} else { // they have characters, find out their bp/pet status (if no last seen, won't be added)
		$final_output['characters_hidden'] = false;
		$logger->trace(
			'characters_hidden = ' . $final_output['characters_hidden']
		);
		$character_table = [
			'pet',
			'character_dyes',
			'class',
			'level',
			'cqc',
			'fame',
			'exp',
			'place',
			'equips',
			'backpack',
			'stats_maxed',
			'last_seen',
			'last_server',
		];
		if (char_table_has_pet_and_backpack($nodelist)) {
			// bp+pet
			// https://www.realmeye.com/player/Fiddy
			//
			// no-op
		} else if (char_table_has_pet_or_backpack($nodelist)) {
			if (char_table_has_backpack($nodelist)) {
				// they have a backpack, but no pets
				// https://www.realmeye.com/player/Stration
				$character_table = array_diff($character_table, ['pet']);
			} else {
				// they have a pet, but no backpacks
				// https://www.realmeye.com/player/ROTFamouse
				$character_table = array_diff($character_table, ['backpack']);
			}
		} else {
			// no bp and no pet
			// check recently-seen unnamed players
			$character_table = array_diff($character_table, ['backpack', 'pet']);
		}
		$logger->trace('character table: ' . print_r($character_table, true));

		$nodelist = $xpath->query('//table[@id]/tbody/tr'); // the rows we want are inside of the only table with an id
		$logger->trace('Start of character table parsing: ' . microtime());
		foreach ($nodelist as $node) { // for each row of the character table
			for ($j = 0; $j < $node->childNodes->length; $j++) {
				if ($character_table[$j] === 'pet') {
					$pet_node = $node->childNodes->item($j);
					$has_pet = $pet_node->hasChildNodes();
					$pet_data_id = $has_pet ? $pet_node->childNodes->item(0)->attributes->getNamedItem('data-item')->nodeValue : -1;
					$character['data_pet_id'] = (int) $pet_data_id;
					$val = $has_pet ? $ITEMS[$pet_data_id][0] : '';
				} else if ($character_table[$j] === 'stats_maxed') {
					$val = (int) $node->childNodes->item($j)->nodeValue;
					$stats_list = [
						'hp',
						'mp',
						'attack',
						'defense',
						'speed',
						'vitality',
						'wisdom',
						'dexterity',
					];
					$attrs = $node->childNodes->item($j)->childNodes->item(0)->attributes;
					$total_stats = $attrs->getNamedItem('data-stats')->nodeValue;
					$total_stats = explode(',', substr($total_stats, 1, -1));
					$bonuses = $attrs->getNamedItem('data-bonuses')->nodeValue;
					$bonuses = explode(',', substr($bonuses, 1, -1));
					$logger->trace(
						'total stats: ' . print_r($total_stats, true) . "\n" .
						'bonuses: ' . print_r($bonuses, true)
					);

					$stats = [];
					$stats_length = count($stats_list);
					for ($i = 0; $i < $stats_length; $i++) {
						$stats[$stats_list[$i]] = $total_stats[$i] - $bonuses[$i];
					}

					$character['stats'] = $stats;
					$logger->trace(
						'character[stats] = ' . print_r($stats, true)
					);
				} else if ($character_table[$j] === 'character_dyes') {
					$attrs = $node->childNodes->item($j)->childNodes->item(0)->attributes;
					$dye1 = $attrs->getNamedItem('data-clothing-dye-id')->nodeValue;
					$dye2 = $attrs->getNamedItem('data-accessory-dye-id')->nodeValue;
					$val = [];
					$val['data_clothing_dye'] = (int) $attrs->getNamedItem('data-dye1')->nodeValue;
					if (!isset($ITEMS[$dye1][0])) {
						$ITEMS[$dye1][0] = '';
					}
					$val['clothing_dye'] = $ITEMS[$dye1][0];
					$val['data_accessory_dye'] = (int) $attrs->getNamedItem('data-dye2')->nodeValue;
					if (!isset($ITEMS[$dye2][0])) {
						$ITEMS[$dye2][0] = '';
					}
					$val['accessory_dye'] = $ITEMS[$dye2][0];

					// class+skin data-vars
					$character['data_class_id'] = (int) $attrs->getNamedItem('data-class')->nodeValue;
					$character['data_skin_id'] = (int) $attrs->getNamedItem('data-skin')->nodeValue;
				} else if ($character_table[$j] === 'equips') {
					$item_indeces = [
						'weapon',
						'ability',
						'armor',
						'ring',
					];
					$item_wrappers = $node->childNodes->item($j)->childNodes;
					$character_item_names = [];
					for ($i = 0; $i < count($item_indeces); $i++) {
						$item_container = $item_wrappers->item($i);
						$item_link_node = $item_container->childNodes->item(0);
						$item_href_node = $item_link_node->attributes->getNamedItem('href');
						if ($item_href_node !== NULL) {
							$item_href = $item_href_node->nodeValue;
							$split_href = explode('/', $item_href);
							$character_item_names[] = $split_href[count($split_href) - 1];
						} else {
							$character_item_names[] = 'empty-slot';
						}
					}

					$val = [];
					foreach ($character_item_names as $character_index => $character_item) {
						foreach ($ITEMS as $item_id => $item_index) {
							$comparison = strtolower($item_index[0]);
							$comparison = preg_replace('/[\W]+/', '-', $comparison);

							if ($comparison === $character_item) {
								$val['data_' . $item_indeces[$character_index] . '_id'] = (int) $item_id;
								$val[$item_indeces[$character_index]] = $item_index[0];
								break;
							}
						}
					}

					// backpack check
					$character['backpack'] = $item_wrappers->length === 5;
					$logger->trace('backpack set: ' . $character['backpack']);
				} else if ($character_table[$j] === 'last_seen') {
					$val = $node->childNodes->item($j)->nodeValue;
				} else if ($character_table[$j] === 'last_server') {
					$val = $node->childNodes->item($j)->childNodes->item(0)->attributes->getNamedItem('title')->nodeValue;
				} else {
					// class, level, cqc, fame, exp, place, backpack
					$temp = $node->childNodes->item($j)->nodeValue;
					if ($temp === '0' || (int) $temp) {
						$val = (int) $temp;
					} else {
						$val = $temp;
					}
				}
				$character[$character_table[$j]] = $val;
				$logger->trace(
					'character[' .$character_table[$j] . '] = ' . $val
				);
			}
			// if the character has no pet, bp, last-seen or last-server index, set their values to '' as opposed to leaving the index out
			$optionalColumns = [
				'pet',
				'backpack',
				'last_seen',
				'last_server',
			];
			foreach ($optionalColumns as $optional_column) {
				if (!isset($character[$optional_column])) {
					$character[$optional_column] = '';
					$logger->debug(
						$optional_column . ' not set, setting default'
					);
					if ($optional_column === 'pet') {
						$character['data_pet_id'] = -1;
					} else if ($optional_column === 'backpack') {
						$character[$optional_column] = false;
					}
				}
			}
			$final_output['characters'][] = $character;
		}
		$logger->trace('End of character table parsing: ' . microtime());
	}

	$final_output = RealmEyeAPIUtils::ksort_recursive($final_output);
	$intersect_filter = RealmEyeAPIUtils::apply_filter($final_output, $_GET['filter']);
	$final_output = RealmEyeAPIUtils::array_intersect_key_recursive($final_output, $intersect_filter);

	// output and exit
	echo_json_and_exit($final_output);
}

//
// Function definitions
//

function char_table_has_pet_and_backpack(DOMNodeList $char_table): bool {
	return $char_table->length === 13 || (
	         $char_table->length === 11 &&
	         $char_table->item(9)->nodeValue === ''
	       );
}

function char_table_has_pet_or_backpack(DOMNodeList $char_table): bool {
	return $char_table->length === 12 || $char_table->length === 10;
}

function char_table_has_backpack(DOMNodeList $char_table): bool {
	return $char_table->item(8)->nodeValue === '';
}

function echo_json_and_exit(array $output_array) {
	$default_options = JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_SLASHES;
	$output_options = $default_options;
	if (isset($_GET['pretty'])) {
		$GLOBALS['logger']->trace('Pretty print enabled');
		$output_options |= JSON_PRETTY_PRINT;
	}
	$output = json_encode($output_array, $output_options);
	if (!empty($GLOBALS['callback'])) {
		$output = $GLOBALS['callback'] . '(' . $output . ')';
	}
	$output .= "\n";
	echo $output;
	exit;
}
