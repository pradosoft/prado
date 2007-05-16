<?php
/**
 * $Id: SimpleTestPlainResultFormatter.php 59 2006-04-28 14:49:47Z mrook $
 *
 * THIS SOFTWARE IS PROVIDED BY THE COPYRIGHT HOLDERS AND CONTRIBUTORS
 * "AS IS" AND ANY EXPRESS OR IMPLIED WARRANTIES, INCLUDING, BUT NOT
 * LIMITED TO, THE IMPLIED WARRANTIES OF MERCHANTABILITY AND FITNESS FOR
 * A PARTICULAR PURPOSE ARE DISCLAIMED. IN NO EVENT SHALL THE COPYRIGHT
 * OWNER OR CONTRIBUTORS BE LIABLE FOR ANY DIRECT, INDIRECT, INCIDENTAL,
 * SPECIAL, EXEMPLARY, OR CONSEQUENTIAL DAMAGES (INCLUDING, BUT NOT
 * LIMITED TO, PROCUREMENT OF SUBSTITUTE GOODS OR SERVICES; LOSS OF USE,
 * DATA, OR PROFITS; OR BUSINESS INTERRUPTION) HOWEVER CAUSED AND ON ANY
 * THEORY OF LIABILITY, WHETHER IN CONTRACT, STRICT LIABILITY, OR TORT
 * (INCLUDING NEGLIGENCE OR OTHERWISE) ARISING IN ANY WAY OUT OF THE USE
 * OF THIS SOFTWARE, EVEN IF ADVISED OF THE POSSIBILITY OF SUCH DAMAGE.
 *
 * This software consists of voluntary contributions made by many individuals
 * and is licensed under the LGPL. For more information please see
 * <http://phing.info>.
 */

require_once 'phing/tasks/ext/simpletest/SimpleTestResultFormatter.php';

/**
 * Prints plain text output of the test to a specified Writer.
 *
 * @author Michiel Rook <michiel@trendserver.nl>
 * @version $Id: SimpleTestPlainResultFormatter.php 59 2006-04-28 14:49:47Z mrook $
 * @package phing.tasks.ext.simpletest
 * @since 2.2.0
 */
class SimpleTestXmlResultFormatter extends SimpleTestResultFormatter
{
	private $results=array();
	private $currentSuite;
	private $currentTest;
	private $methodCounts=0;
	private $methodTime=0;

	function paintFooter($test_name)
	{
		if($test_name=='GroupTest')
			 $this->printXml($test_name);
    }

	protected function printXml($test_name)
	{
		$suites = $this->printXmlSuites($this->results);
$content = <<<EOD
<?xml version="1.0" encoding="UTF-8"?>
<testsuites total="{$this->getRunCount()}" name="{$test_name}">
$suites
</testsuites>
EOD;
		$this->out->write($content);
	}

	protected function printXmlSuites($results)
	{
		$contents = '';
		foreach($results as $suiteName => $suite)
		{
			$tests = $this->printXmlTests($suite['tests'],$suiteName);
$contents .= <<<EOD
<testsuite name="{$suiteName}" tests="{$suite['total']}" failures="{$suite['failures']}" errors="{$suite['errors']}" time="{$suite['time']}">
		$tests
</testsuite>
EOD;
		}
		return $contents;
	}

	protected function printXmlTests($tests,$suiteName)
	{
		$contents = '';
		foreach($tests as $name => $result)
		{
			if(count($result['results'])==0)
			{
				$contents .= <<<EOD
<testcase name="{$name}" class="{$suiteName}" result="success" time="{$result['time']}"/>
EOD;
			}
			else
			{
				$type = strtolower($result['results']['type']);
				$message = htmlspecialchars($result['results']['message']);
$contents .= <<<EOD
<testcase name="{$name}" class="{$suiteName}" result="{$type}" time="{$result['time']}">
	<{$type}>$message</{$type}>
</testcase>
EOD;
			}
		}
		return $contents;
	}

	function paintCaseStart($test_name)
	{
		parent::paintCaseStart($test_name);
		$this->results[$test_name] = array('tests'=>array());
		$this->currentSuite=$test_name;
		$this->methodCounts=0;
	}

	function paintCaseEnd($test_name)
	{
		parent::paintCaseEnd($test_name);
		$details = 	array(
			'total' => $this->methodCounts,
			'failures' => $this->getFailureCount(),
			'errors' => $this->getErrorCount(),
			'time' => $this->getElapsedTime());

		$this->results[$test_name] = array_merge($this->results[$test_name],$details);
	}

	function paintMethodStart($test_name)
	{
		$this->currentTest=$test_name;
		parent::paintMethodStart($test_name);
		$this->results[$this->currentSuite]['tests'][$test_name]['results'] = array();
		$this->methodCounts++;
		$this->methodTime = new Timer();
		$this->methodTime->start();
	}

	function paintMethodEnd($test_name)
	{
		parent::paintMethodEnd($test_name);
		$this->methodTime->stop();
		$this->results[$this->currentSuite]['tests'][$test_name]['time'] = $this->methodTime->getElapsedTime();
	}

	function paintError($message)
	{
		parent::paintError($message);
		$this->formatError("ERROR", $message);
	}

	function paintFail($message)
	{
		parent::paintFail($message);
		$this->formatError("FAILED", $message);
	}

	private function formatError($type, $message)
	{
		$result = array('type'=>$type, 'message' => $message);
		$this->results[$this->currentSuite]['tests'][$this->currentTest]['results'] =
			$result;
	}
}
?>