<?php
/**
 * TControl, TControlCollection, TEventParameter and INamingContainer class file
 *
 * @author Qiang Xue <qiang.xue@gmail.com>
 * @link https://github.com/pradosoft/prado
 * @license https://github.com/pradosoft/prado/blob/master/LICENSE
 */

namespace Prado\Web\UI;

/**
 * ITemplate interface
 *
 * ITemplate specifies the interface for classes encapsulating
 * parsed template structures.
 *
 * @author Qiang Xue <qiang.xue@gmail.com>
 * @since 3.0
 */
interface ITemplate
{
	/**
	 * Instantiates the template.
	 * Content in the template will be instantiated as components and text strings
	 * and passed to the specified parent control.
	 * @param \Prado\Web\UI\TControl $parent the parent control
	 */
	public function instantiateIn($parent);
    
    /**
     * TTemplateManager calls this method for caching the included file modification times.
     * @return array list of included external template files
     */
    public function getIncludedFiles();
}
