<?php

	namespace Eggcup;

	abstract class Cacheable {
		public $_cup = null;
		public function __construct() {
			$this->_cup = $this;
		}
	}

?>
