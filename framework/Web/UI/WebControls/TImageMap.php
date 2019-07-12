<?php
/**
 * TImageMap and related class file.
 *
 * @author Qiang Xue <qiang.xue@gmail.com>
 * @link https://github.com/pradosoft/prado
 * @license https://github.com/pradosoft/prado/blob/master/LICENSE
 * @package Prado\Web\UI\WebControls
 */

namespace Prado\Web\UI\WebControls;

use Prado\TPropertyValue;

/**
 * TImageMap class
 *
 * TImageMap represents an image on a page. Hotspot regions can be defined
 * within the image. Depending on the {@link setHotSpotMode HotSpotMode},
 * clicking on the hotspots may trigger a postback or navigate to a specified
 * URL. The hotspots defined may be accessed via {@link getHotSpots HotSpots}.
 * Each hotspot is described as a {@link THotSpot}, which can be a circle,
 * rectangle, polygon, etc. To add hotspot in a template, use the following,
 * <code>
 *  <com:TImageMap>
 *    <com:TCircleHotSpot ... />
 *    <com:TRectangleHotSpot ... />
 *    <com:TPolygonHotSpot ... />
 *  </com:TImageMap>
 * </code>
 *
 * @author Qiang Xue <qiang.xue@gmail.com>
 * @package Prado\Web\UI\WebControls
 * @since 3.0
 */
class TImageMap extends TImage implements \Prado\Web\UI\IPostBackEventHandler
{
	const MAP_NAME_PREFIX = 'ImageMap';

	/**
	 * Processes an object that is created during parsing template.
	 * This method adds {@link THotSpot} objects into the hotspot collection
	 * of the imagemap.
	 * @param string|TComponent $object text string or component parsed and instantiated in template
	 */
	public function addParsedObject($object)
	{
		if ($object instanceof THotSpot) {
			$this->getHotSpots()->add($object);
		}
	}

	/**
	 * Adds attribute name-value pairs to renderer.
	 * This overrides the parent implementation with additional imagemap specific attributes.
	 * @param THtmlWriter $writer the writer used for the rendering purpose
	 */
	protected function addAttributesToRender($writer)
	{
		parent::addAttributesToRender($writer);
		if ($this->getHotSpots()->getCount() > 0) {
			$writer->addAttribute('usemap', '#' . self::MAP_NAME_PREFIX . $this->getClientID());
			$writer->addAttribute('id', $this->getUniqueID());
		}
		if ($this->getEnabled() && !$this->getEnabled(true)) {
			$writer->addAttribute('disabled', 'disabled');
		}
	}

	/**
	 * Renders this imagemap.
	 * @param THtmlWriter $writer
	 */
	public function render($writer)
	{
		parent::render($writer);

		$hotspots = $this->getHotSpots();

		if ($hotspots->getCount() > 0) {
			$clientID = $this->getClientID();
			$cs = $this->getPage()->getClientScript();
			$writer->writeLine();
			$writer->addAttribute('name', self::MAP_NAME_PREFIX . $clientID);
			$writer->renderBeginTag('map');
			$writer->writeLine();
			if (($mode = $this->getHotSpotMode()) === THotSpotMode::NotSet) {
				$mode = THotSpotMode::Navigate;
			}
			$target = $this->getTarget();
			$i = 0;
			$options['EventTarget'] = $this->getUniqueID();
			$options['StopEvent'] = true;
			$cs = $this->getPage()->getClientScript();
			foreach ($hotspots as $hotspot) {
				if ($hotspot->getHotSpotMode() === THotSpotMode::NotSet) {
					$hotspot->setHotSpotMode($mode);
				}
				if ($target !== '' && $hotspot->getTarget() === '') {
					$hotspot->setTarget($target);
				}
				if ($hotspot->getHotSpotMode() === THotSpotMode::PostBack) {
					$id = $clientID . '_' . $i;
					$writer->addAttribute('id', $id);
					$writer->addAttribute('href', '#' . $id); //create unique no-op url references
					$options['ID'] = $id;
					$options['EventParameter'] = "$i";
					$options['CausesValidation'] = $hotspot->getCausesValidation();
					$options['ValidationGroup'] = $hotspot->getValidationGroup();
					$cs->registerPostBackControl($this->getClientClassName(), $options);
				}
				$hotspot->render($writer);
				$writer->writeLine();
				$i++;
			}
			$writer->renderEndTag();
		}
	}

	/**
	 * Gets the name of the javascript class responsible for performing postback for this control.
	 * This method overrides the parent implementation.
	 * @return string the javascript class name
	 */
	protected function getClientClassName()
	{
		return 'Prado.WebUI.TImageMap';
	}

	/**
	 * Raises the postback event.
	 * This method is required by {@link IPostBackEventHandler} interface.
	 * This method is mainly used by framework and control developers.
	 * @param TEventParameter $param the event parameter
	 */
	public function raisePostBackEvent($param)
	{
		$postBackValue = null;
		if ($param !== '') {
			$index = TPropertyValue::ensureInteger($param);
			$hotspots = $this->getHotSpots();
			if ($index >= 0 && $index < $hotspots->getCount()) {
				$hotspot = $hotspots->itemAt($index);
				if (($mode = $hotspot->getHotSpotMode()) === THotSpotMode::NotSet) {
					$mode = $this->getHotSpotMode();
				}
				if ($mode === THotSpotMode::PostBack) {
					$postBackValue = $hotspot->getPostBackValue();
					if ($hotspot->getCausesValidation()) {
						$this->getPage()->validate($hotspot->getValidationGroup());
					}
				}
			}
		}
		if ($postBackValue !== null) {
			$this->onClick(new TImageMapEventParameter($postBackValue));
		}
	}

	/**
	 * @return THotSpotMode the behavior of hotspot regions in this imagemap when they are clicked. Defaults to THotSpotMode::NotSet.
	 */
	public function getHotSpotMode()
	{
		return $this->getViewState('HotSpotMode', THotSpotMode::NotSet);
	}

	/**
	 * Sets the behavior of hotspot regions in this imagemap when they are clicked.
	 * If an individual hotspot has a mode other than 'NotSet', the mode set in this
	 * imagemap will be ignored. By default, 'NotSet' is equivalent to 'Navigate'.
	 * @param THotSpotMode $value the behavior of hotspot regions in this imagemap when they are clicked.
	 */
	public function setHotSpotMode($value)
	{
		$this->setViewState('HotSpotMode', TPropertyValue::ensureEnum($value, 'Prado\\Web\\UI\\WebControls\\THotSpotMode'), THotSpotMode::NotSet);
	}

	/**
	 * @return THotSpotCollection collection of hotspots defined in this imagemap.
	 */
	public function getHotSpots()
	{
		if (($hotspots = $this->getViewState('HotSpots', null)) === null) {
			$hotspots = new THotSpotCollection;
			$this->setViewState('HotSpots', $hotspots);
		}
		return $hotspots;
	}

	/**
	 * @return string  the target window or frame to display the new page when a hotspot region is clicked within the imagemap. Defaults to ''.
	 */
	public function getTarget()
	{
		return $this->getViewState('Target', '');
	}

	/**
	 * @param string $value the target window or frame to display the new page when a hotspot region is clicked within the imagemap.
	 */
	public function setTarget($value)
	{
		$this->setViewState('Target', TPropertyValue::ensureString($value), '');
	}

	/**
	 * Raises <b>OnClick</b> event.
	 * This method is invoked when a hotspot region is clicked within the imagemap.
	 * If you override this method, be sure to call the parent implementation
	 * so that the event handler can be invoked.
	 * @param TImageMapEventParameter $param event parameter to be passed to the event handlers
	 */
	public function onClick($param)
	{
		$this->raiseEvent('OnClick', $this, $param);
	}
}
