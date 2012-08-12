<?php
use \pUnit\Assert as Assert;

class MyClass {
	
	/**
	 * undocumented function
	 *
	 * @return void 
	 *   @do
	 *  @author Shani Elharrar 
	 *   @more 		 Trimming  
	 * @cache
	 **/
	public function MyFunction() {
		
	}
	
}

/**
 * A tests class for CommentParser.
 *
 **/
class CommentParserTests {
	
	private function GetTags() {
		// Arrange
		$obj = new MyClass();
		$commentParser = new \EggCup\CommentParser($obj);
		// Act
		$tags = $commentParser->GetTags('MyFunction');
		
		return $tags;
	}
	
	/**
	 * Checks that getting tags works
	 *
	 * @return void
	 **/
	public function Should_Get_Return_Tag(){ 
		$tags = $this->GetTags();
		// Assert
		Assert::IsTrue(isset($tags['return']));
		Assert::AreEqual('void', $tags['return']);
	}
	
	/**
	 * Checks getting the author tag with trim works
	 *
	 * @return void
	 **/
	public function Should_Get_Author_Tag_With_Trim() {
		$tags = $this->GetTags();
		// Assert
		Assert::IsTrue(isset($tags['author']));
		Assert::AreEqual('Shani Elharrar', $tags['author']);	
	}
	
	/**
	 * Checks that getting spaced tag works
	 *
	 * @return void
	 **/
	public function Should_Get_Spaced_Tag()
	{
		$tags = $this->GetTags();
		// Assert
		Assert::IsTrue(isset($tags['more']));
		Assert::AreEqual('Trimming', $tags['more']);	
	}

	/**
	 * Checks that getting empty tag works
	 *
	 * @return void
	 **/
	public function Should_Get_Empty_Tag() {
		$tags = $this->GetTags();
		// Assert
		Assert::IsTrue(isset($tags['do']));
		Assert::AreEqual('', $tags['do']);	
	}

	
	/**
	 * Checks that getting the last tag works
	 *
	 * @return void
	 **/
	public function Should_Get_Last_Tag() {
		$tags = $this->GetTags();
		// Assert
		Assert::IsTrue(isset($tags['cache']));
		Assert::AreEqual('', $tags['cache']);	
	}
	
	
}
