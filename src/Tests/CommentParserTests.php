<?php
use \pUnit\Assert as Assert;

class MyClass {
	
	/**
	 * undocumented function
	 *
	 * @return void 
	 *  @author Shani Elharrar 
	 *   @more 		 Trimming  
	 **/
	public function MyFunction() {
		
	}
	
}

/**
 * A tests class for CommentParser.
 *
 **/
class CommentParserTests {
	
	/**
	 * Checks that getting tags works
	 *
	 * @return void
	 **/
	public function GetTags(){ 
		// Arrange
		$obj = new MyClass();
		$commentParser = new \EggCup\CommentParser($obj);
		// Act
		$tags = $commentParser->GetTags('MyFunction');
		// Assert
		Assert::IsTrue(isset($tags['return']));
		Assert::IsTrue(isset($tags['author']));
		Assert::IsTrue(isset($tags['more']));
		Assert::AreEqual('void', $tags['return']);
		Assert::AreEqual('Shani Elharrar', $tags['author']);
		Assert::AreEqual('Trimming', $tags['more']);
	}
	
}
