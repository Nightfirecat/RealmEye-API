<?php
/*
TODO: description
*/
declare(strict_types=1);

require_once('classes/Item.php');
require_once('classes/RealmEyeAPIUtils.php');

// Manager for item loading, parsing, holding item array, etc.

class ItemManager {
	private $definition_url = 'https://www.realmeye.com/s/y3/js/definition.js';
	private $definition_file = 'definition.php';
	private $item_definitions = null;
	private $last_seen_modified_file = 'definition-modified.txt';
	private $logger = null;
	// TODO: enum?

	// Load item definitions
	public function __construct() {
		// load utils, assign logger
		new RealmEyeAPIUtils();
		$this->$logger = RealmEyeAPIUtils::$logger;

		// set item definitions
		$this->$item_definitions = $this->loadDefinitions();
	}

	// Accepts an item name to look up in the loaded definitions
	// Returns the Item of that name if it exists, null otherwise
	public function getItemByName(string $item_name): ?Item {
		if array_key_exists($item_name, $this->$item_definitions) {
			return $this->$item_definitions[$item_name];
		}
		return null;
	}

	// TODO: doc
	// get headers, check if it's been updated since last seen
	private function definitionLastModified(): string {
		$definitions_headers = get_headers($this->$definition_url, 1);
		if ($definitions_headers) {
			$last_modified = $definitions_headers['Last-Modified'];
			if(is_array($last_modified)){
				// value must be fixed due to encountering HTTP redirects
				// ref: http://php.net/manual/en/function.get-headers.php#100113
				$last_modified = $last_modified[count($last_modified)-1];
			}
			$this->writeLastSeenModified($last_modified);
			return $last_modified;
		} else {
			// encountered failure, raise exception
			// TODO
		}
	}

	// TODO: doc
	private function writeLastSeenModified(string $last_modified) {
		file_put_contents(
			$this->$last_seen_modified_file,
			$last_modified
		);
	}

	// TODO: doc
	// note: returns true if definition file does not exist, or if old definition is stored
	private function definitionOutdated(): bool {
		$last_seen_modified = file_exists($this->$last_seen_modified_file) ?
		                      file_get_contents($this->$last_seen_modified_file) :
		                      false;
		return !file_exists($this->$definition_file) ||
		       !$last_seen_modified ||
		       $last_seen_modified !== $this->definitionLastModified();
	}

	// TODO: doc
	private function updateDefinitionFile(): bool {
		if (!$this->definitionOutdated()) {
			return false;
		}
		$definitions = parseJsDefinition(file_get_contents($this->$definition_url));
		$file_text = '$DEFINITIONS = [' . "\n";
		foreach ($definitions as $index => $definition) {
			$file_text .= "\t" . $index . '=>' .
			              $definition->constructorString() . ',' . "\n";
		}
		$file_text .= "];\n"
		file_put_contents(
			$this->$definition_file,
			$file_text
		);
		return true;
	}

	// TODO: doc
	private function loadDefinitions(): array {
		$this->updateDefinitionFile();
		require_once($this->definition_file);
		return $DEFINITIONS;
	}

	// TODO: doc, returns ["Item Name"->[...],...]
	// TODO: make it return ["Item Name"->Item,...]
	// TODO: ignore 3-index item arrays; these are skinned pet objects, not items
	private function parseJsDefinition(string $js_definition): array {
		$definition = [];
		preg_match_all($this->jsDefinitionParseRegex(), $js_definition, $matches);
		$num_matches = count(matches[0]);
		for ($i = 0; $i < $num_matches; $i++) {
			$source_key = (int) $matches[2][$i]; // item id
			$source_val = array_map(
				function ($item) {
					if (is_numeric($item)) {
						return (int) $item;
					}
					return $item;
				},
				explode(',', $matches[3][$i], 1)
			);
			$definition_key = $source_val[0]; // item name
			// TODO: create Item objects instead of using array
			$definition_val = array_merge(
				[$source_key],
				array_slice($source_val, 1)
			);
			$definition[$definition_key] = $definition_val;
		}
		return $definition;
	}

	// TODO: doc (helper function to explain how this is constructed)
	private function jsDefinitionParseRegex(): string {
		// /(["\'])?(\-?[\d]+(?:e[\d]+)?)\1?:\[([^\]]+)\],?/
		$r = '/' . // regex boundary
		     '(["\'])?' . // optional quote for "-1" (capture group 1)
		     '(' . // begin group for item id as object key (capture group 2)
		       '\-?[\d]+' . // optional negative, digits
		       '(?:e[\d]+)?' . // optional exponent in non-capturing group
		     ')' . // end capture group 2
		     '\1?' . // optional quote for "-1"
		     ':' . // key:value separator
		     '\[([^\]]+)\],?' . // array items (capture group 3)
		     '/'; // regex boundary
		return $r;
	}
}
