<?php
/*
TODO: description
*/
declare(strict_types=1);

require_once('classes/RealmEyeAPIUtils.php');

class Item {
	public $data_id = null;
	public $feed_power = null;
	public $id = null;
	public $name = null;
	public $tier = null;
	public $type = null;
	private $logger = null;

	public function __construct(array $args) {
		// load utils, assign logger
		new RealmEyeAPIUtils();
		$logger = RealmEyeAPIUtils::$logger;

		// parse args to set properties
		$this->parseArgs($args);
	}

	// TODO: return "new Item(...)" string
	public function constructorString() {
		return '';
	}

	// TODO: doc
	private function parseArgs($args) {
		foreach ($args as $key => $value) {
			switch ($key) {
				case 'type':
					$this->$type = (int) $value;
				case 'name':
					$this->$name = $value;
				case 'feed_power':
					$this->$feed_power = (int) $value;
				case 'id':
					$this->$id = (int) $value;
				case 'data_id':
					$this->$data_id = (int) $value;
				case 'tier':
					$this->$tier = (int) $value;
				default:
					// TODO: raise exception?
			}
		}
	}
}
