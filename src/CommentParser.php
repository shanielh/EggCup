<?php
namespace EggCup;	

/**
 * Comment Parser for given object
 *
 * @package default
 **/
class CommentParser {
	
	private $_reflection;
	
	const TAGS_REGEX = "/\s+\@(?P<key>\S*)\s*(?P<value>.*?)\s+$/im";
	
	/**
	 * Creates an instance of CommentParser for the given object.
	 *
	 * @return CommentParser
	 **/
	public function __construct($obj) {
		
		$this->_reflection = new \ReflectionClass($obj);
		
	}
	
	public function GetTags($methodName) {
		
		$method = $this->_reflection->getMethod($methodName);
		$comments = trim($method->getDocComment());
		
		preg_match_all(self::TAGS_REGEX, $comments, $matches, PREG_SET_ORDER);
		
		$retVal = array();
		
		foreach ($matches as $match) {
			$retVal[$match['key']] = $match['value'];
		}
		
		return $retVal;
	}
	
}