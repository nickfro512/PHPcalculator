<?php 

/* PHP Calculator 
	by Nick Frogley 
	nickfro@gmail.com github.com/nickfro512
===========================================

8 digit calculator all in one PHP file.

Awful UI for testing purposes only sorry!

Roughly reporduces digital calculator input. Known bug is that after you hit an operation it displays 0 instead of still displaying last entry like calculator usually would.

buttons are:
add (+), sub (-), mul (*), div (/) - enter between numerals, then hit "eq" button for answer
dec = enter decimal point
pol = change polarity, like [+/-] button
eq = equals

*/

if(isset($_POST['the_submit']) and $_POST['the_submit'] == 'RESET')	// DEBUG RESET
{
	$_POST = array();
	$the_calculator = new Calculator();
}

if (isset($_POST['input_array']) and isset($_POST['current_entry_array']))
{
	$the_calculator = new Calculator(json_decode($_POST['input_array'], true), json_decode($_POST['current_entry_array'], true));
}
else
{
	$the_calculator = new Calculator(array());
}

if (isset($_POST['keystroke']))
{
	$the_calculator->processKeystroke($_POST['keystroke']);
}

$str_input_array = json_encode($the_calculator->getInputArray());
$str_current_entry = json_encode($the_calculator->getCurrentEntry());

if (isset($the_calculator->getCurrentEntry()['current_digits']))
{
	$display = $the_calculator->getCurrentEntry()['current_digits'];
	if ($the_calculator->getCurrentEntry()['digit_counter'] > 7)
	{
		print "<br>MAX DIGITS<br>";
	}
}
else
{
	$display = "_";
}
										

print '	<!DOCTYPE html>
		<html>
			<body>
				<form action="calculator.php" method="post">
					<div class="the_form">
						[ ' . $display . ' ]
						
						<input type="hidden" name="input_array" value="' . htmlspecialchars($str_input_array) . '">
						<input type="hidden" name="current_entry_array" value="' . htmlspecialchars($str_current_entry) . '">
						<br><br>
						
						<input type="submit" value="add" name="keystroke">
						<input type="submit" value="1" name="keystroke">
						<input type="submit" value="2" name="keystroke">
						<input type="submit" value="3" name="keystroke">
						
						<br>

						<input type="submit" value="sub" name="keystroke">
						<input type="submit" value="4" name="keystroke">
						<input type="submit" value="5" name="keystroke">
						<input type="submit" value="6" name="keystroke">
						<br>

						<input type="submit" value="mul" name="keystroke">
						<input type="submit" value="7" name="keystroke">
						<input type="submit" value="8" name="keystroke">
						<input type="submit" value="9" name="keystroke">
						<br>

						<input type="submit" value="div" name="keystroke">
						<input type="submit" value="dec" name="keystroke">
						<input type="submit" value="0" name="keystroke">
						<input type="submit" value="pol" name="keystroke">
						

						<br>
						<br>
						<input type="submit" value="eq" name="keystroke">
						<br>
						<br>
						<br>
						<br>
						<input type="submit" value="RESET" name="the_submit">

					</div>
				</form>
			</body>
		</html>
			
	';


class Calculator
{
	const OPERATION_ADD = "add";
	const OPERATION_SUB = "sub";
	const OPERATION_MUL = "mul";
	const OPERATION_DIV = "div";

	const KEY_EQ = "eq";			// equals key
	const KEY_DEC = "dec";			// decimal point key
	const KEY_POL = "pol";			// decimal point key

	const DIGIT_LIMIT = 8;			// max number of digits in a single entry
	
	public function __construct($starting_input_array = array(), $starting_current_entry = array())
	{	
		$this->input_array = $starting_input_array;
		if (count($starting_current_entry)) 
		{
			$this->current_entry_array = $starting_current_entry;
		}
		else
		{
			$this->_resetCurrentEntry();
		}
	}

	public function processKeystroke($keystroke)
	{
		$arr_arth_ops = array(	self::OPERATION_ADD,
								self::OPERATION_SUB,
								self::OPERATION_MUL,
								self::OPERATION_DIV
							);
		
		if ($keystroke == self::KEY_EQ)
		{
			$this->input_array[] = $this->current_entry_array['current_digits'];
			$result = $this->_equals();		// perform the entered operation and set it as the new display
			$this->_resetInputArray();
			$this->_resetCurrentEntry($result, $this->current_entry_array['digit_counter'] + 1);	
		}
		else if ($keystroke == self::KEY_DEC) // place decimal point after last numeral
		{
			$this->current_entry_array['decimal_place'] = $this->current_entry_array['digit_counter'];
		}
		else if ($keystroke == self::KEY_POL) // change polarity of current number
		{
			$this->current_entry_array['current_digits'] *= -1;		
		}
		else if ($this->current_entry_array['digit_counter'] >= self::DIGIT_LIMIT)	// enforce 8 digit limit
		{
			return null;	
		}
		else if (in_array($keystroke, $arr_arth_ops))	// operation
		{
			$this->input_array[] = $this->current_entry_array['current_digits'];
			$this->input_array[] = $keystroke;
			$this->_resetCurrentEntry();
		}
		else if (is_numeric($keystroke)) // numeral
		{
			if (($this->current_entry_array['decimal_place']) === null)		// we have not placed a demical point, so we multiply the last digits by 10 and the current numeral
			{
				$this->current_entry_array['current_digits'] = ($this->current_entry_array['current_digits'] * 10) + $keystroke;
			}
			else // we've got a decimal point, so now each numeral is being divided by 10^n to add to total
			{
				$precision = ($this->current_entry_array['digit_counter'] + 1) - $this->current_entry_array['decimal_place'];
				$divisor = pow(10, $precision);
			
				if ($divisor > 0)
				{
					$fraction = number_format(	(((float) $keystroke) / $divisor), $precision);	// limit number formatting to current number of decimal places
					$this->current_entry_array['current_digits'] = number_format(((float) $this->current_entry_array['current_digits'] + $fraction), $precision);
				}
			}
			
			$this->current_entry_array['digit_counter']++;
		}
	}

	private function _resetCurrentEntry($prefill_digits = 0, $init_digit_counter = 0)
	{
		$this->current_entry_array = array("current_digits" => $prefill_digits, "digit_counter" => $init_digit_counter, "decimal_place" => null);
	}

	private function _resetInputArray($prefill_inputs = array())
	{
		$this->input_array = $prefill_inputs;
	}

	private function _equals($starting_input_array = array())
	{
		if (count($starting_input_array))	// DEBUG 
		{
			$this->input_array = $starting_input_array;
		}
		else
		{
			//$this->input_array = array(5,"add",4);
		}

		$calculation = null;
		$left_num = null;
		$right_num =  null;
		$operation = null;
		$final_calculation = null;
		
		// process array of keystrokes entered thus far - this is only ever actually 2 numbers and an operation via the current UI but could be expanded to include more terms)
		foreach ($this->input_array as $symbol)	
		{
			if (is_numeric($symbol))	// it's a numeral, so let's store it on either left or right hand side of the operation
			{
				if ($left_num === null)
				{
					$left_num = $symbol;
				}
				else if ($right_num === null)
				{
					$right_num = $symbol;
					switch ($operation)	// determine and execute operation
					{
						case self::OPERATION_ADD:
							$calculation = $this->_add($left_num, $right_num);
							break;
						case self::OPERATION_SUB:
							$calculation = $this->_subtract($left_num, $right_num);
							break;
						case self::OPERATION_MUL:
							$calculation = $this->_multiply($left_num, $right_num);
							break;
						case self::OPERATION_DIV:
							$calculation = $this->_divide($left_num, $right_num);
							break;
						default:
							break;
					}
					
					$left_num = $calculation;
					$final_calculation = $calculation;

					// calculation done, reset vars
					$right_num =  null;
					$operation = null;
					$calculation = null;
				}
			}
			else // if it got here it's an operation
			{
				$operation = $symbol;
			}
		}

		return $final_calculation;
	}

	private function _add($a, $b)
	{
		return $a + $b;
	}

	private function _subtract($a, $b)
	{
		return $a - $b;
	}

	private function _multiply($a, $b)
	{
		return $a * $b;
	}

	private function _divide($a, $b)
	{
		return $a / $b;
	}


	public function getInputArray()
	{
		return $this->input_array;
	}

	public function getCurrentEntry()
	{
		return $this->current_entry_array;
	}
}