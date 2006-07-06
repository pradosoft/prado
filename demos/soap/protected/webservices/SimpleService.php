<?php
class SimpleService
{
	private $errors = array();
	
	/**
	 * Highlights a string as php code
	 * @param string $input The php code to highlight
	 * @return string The highlighted text.
	 * @soapmethod
	 */
	public function highlight($input)
	{
		return highlight_string($input, true);
	}
	
	/**
	 * Given a left side, operation, and right side of an operation, will
	 * return the result of computing the equation.
	 * @param string $leftSide The left side of an equation
	 * @param string $operation The operation to perform 
	 * @param string $rightSide The right side of the equation
	 * @return ComputationResult The result of the computation
	 * @soapmethod
	 */
	public function compute($leftSide, $operation, $rightSide)
	{
		$result = new ComputationResult();
		$result->equation = "$leftSide $operation $rightSide";
		$result->result = $this->evaluateExpression($leftSide, $operation, $rightSide);
		if (count($this->errors)) {
			$result->success = false;
			$result->errors = $this->errors;
		}
		else {
			$result->success = true;
		}
		
		return $result;	
	}

	/**
	 * Simply add two operands
	 * @param int $a
	 * @param int $b
	 * @return int The result
	 * @soapmethod
	 */
	public function add($a, $b) {
	  return $a + $b;
	}
	
	/**
	 * This method evaluates the expression. It should be capable of handling any $op 
	 * passed to it.
	 * @return string the result of the evaluation
	 */
	private function evaluateExpression($left, $op, $right)
	{
		// Now, because we don't want to eval random code on the server that this is running
		// on, we're just going to highlight the string
		$evaluation = highlight_string("$left $op $right;", true);
		return $evaluation;
	}
}

class ComputationResult
{
	/**
	 * @var string The initial equation
	 */
	public $equation;
	
	/**
	 * @var string The computed result
	 */
	public $result;
	
	/**
	 * @var boolean Whether the computation succeeded
	 */
	public $success;
	
	/**
	 * @var array any errors that occured in the processing.
	 */
	public $errors;
}
?>