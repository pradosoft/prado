<?php
/**
 * $Id: SimpleTestFormatterElement.php 58 2006-04-28 14:41:04Z mrook $
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

require_once 'phing/tasks/ext/simpletest/SimpleTestPlainResultFormatter.php';
require_once 'phing/tasks/ext/simpletest/SimpleTestSummaryResultFormatter.php';
require_once 'phing/tasks/ext/simpletest/SimpleTestXmlResultFormatter.php';

/**
 * Child class of "FormatterElement", overrides setType to provide other
 * formatter classes for SimpleTest
 *
 * @author Michiel Rook <michiel@trendserver.nl>
 * @version $Id: SimpleTestFormatterElement.php 58 2006-04-28 14:41:04Z mrook $
 * @package phing.tasks.ext.simpletest
 * @since 2.2.0
 */
class SimpleTestFormatterElement
{
	protected $formatter = NULL;

	protected $type = "";

	protected $useFile = true;

	protected $toDir = ".";

	protected $outfile = "";

	function setType($type)
	{
		$this->type = $type;

		if ($this->type == "xml")
		{
			//$destFile = new PhingFile($this->toDir, 'testsuites.xml');
			$this->formatter = new SimpleTestXmlResultFormatter();
		}
		else
		if ($this->type == "plain")
		{
			$this->formatter = new SimpleTestPlainResultFormatter();
		}
		else
		if ($this->type == "summary")
		{
			$this->formatter = new SimpleTestSummaryResultFormatter();
		}
		else
		{
			throw new BuildException("Formatter '" . $this->type . "' not implemented");
		}
	}

	function setClassName($className)
	{
		$classNameNoDot = Phing::import($className);

		$this->formatter = new $classNameNoDot();
	}

	function setUseFile($useFile)
	{
		$this->useFile = $useFile;
	}

	function getUseFile()
	{
		return $this->useFile;
	}

	function setToDir($toDir)
	{
		$this->toDir = $toDir;
	}

	function getToDir()
	{
		return $this->toDir;
	}

	function setOutfile($outfile)
	{
		$this->outfile = $outfile;
	}

	function getOutfile()
	{
		if ($this->outfile)
		{
			return $this->outfile;
		}
		else
		{
			return $this->formatter->getPreferredOutfile() . $this->getExtension();
		}
	}

	function getExtension()
	{
		return $this->formatter->getExtension();
	}

	function getFormatter()
	{
		return $this->formatter;
	}
}
?>