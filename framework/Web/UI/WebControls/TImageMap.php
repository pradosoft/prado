<?php

/**
 * TImageMap and related class file.
 *
 * @author Qiang Xue <qiang.xue@gmail.com>
 * @link https://github.com/pradosoft/prado
 * @license https://github.com/pradosoft/prado/blob/master/LICENSE
 */

namespace Prado\Web\UI\WebControls;

use Prado\TPropertyValue;

/**
 * TImageMap class
 *
 * TImageMap represents an image on a page. Hotspot regions can be defined
 * within the image. Depending on the {@see setHotSpotMode HotSpotMode},
 * clicking on the hotspots may trigger a postback or navigate to a specified
 * URL. The hotspots defined may be accessed via {@see getHotSpots HotSpots}.
 * Each hotspot is described as a {@see \Prado\Web\UI\WebControls\THotSpot}, which can be a circle,
 * rectangle, polygon, etc. To add hotspot in a template, use the following,
 * ```php
 *  <com:TImageMap>
 *    <com:TCircleHotSpot ... />
 *    <com:TRectangleHotSpot ... />
 *    <com:TPolygonHotSpot ... />
 *  </com:TImageMap>
 * ```
 *
 * At render, each hot spot renders with its own HotSpotMode and Target, or else
 * the image map's. A HotSpotMode of NotSet on both renders as Navigate. The hot
 * spots keep their own values. In a disabled image map, every hot spot renders
 * as Inactive, without `href`, and registers no postback script.
 *
 * @author Qiang Xue <qiang.xue@gmail.com>
 * @since 3.0
 */
class TImageMap extends TImage implements \Prado\Web\UI\IPostBackEventHandler
{
	public const MAP_NAME_PREFIX = 'ImageMap';

	/**
	 * Processes an object that is created during parsing template.
	 * This method adds {@see \Prado\Web\UI\WebControls\THotSpot} objects into the hotspot collection
	 * of the imagemap.
	 * @param \Prado\TComponent|string $object text string or component parsed and instantiated in template
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
	 * @param \Prado\Web\UI\THtmlWriter $writer the writer used for the rendering purpose
	 */
	protected function addAttributesToRender($writer)
	{
		parent::addAttributesToRender($writer);
		if ($this->getHotSpots()->getCount() > 0) {
			$writer->addAttribute('usemap', '#' . self::MAP_NAME_PREFIX . $this->getClientID());
		}
		if ($this->getEnabled() && !$this->getEnabled(true)) {
			$writer->addAttribute('disabled', 'disabled');
		}
	}

	/**
	 * Renders this imagemap: the image, then a `<map>` of its hot spots.
	 * A PostBack hot spot renders with the ID `{ClientID}_{index}` and registers its postback script.
	 * @param \Prado\Web\UI\THtmlWriter $writer the writer used for the rendering purpose
	 */
	public function render($writer)
	{
		parent::render($writer);

		$hotspots = $this->getHotSpots();

		if ($hotspots->getCount() > 0) {
			$clientID = $this->getClientID();
			$writer->writeLine();
			$writer->addAttribute('name', self::MAP_NAME_PREFIX . $clientID);
			$writer->renderBeginTag('map');
			$writer->writeLine();
			$enabled = $this->getEnabled(true);
			$i = 0;
			foreach ($hotspots as $hotspot) {
				$area = $this->getRenderHotSpot($hotspot, $enabled);
				if ($area->getHotSpotMode() === THotSpotMode::PostBack) {
					$id = $clientID . '_' . $i;
					$writer->addAttribute('id', $id);
					$writer->addAttribute('href', '#' . $id); //create unique no-op url references
					$this->getPage()->getClientScript()->registerPostBackControl($this->getClientClassName(), [
						'EventTarget' => $this->getUniqueID(),
						'StopEvent' => true,
						'ID' => $id,
						'EventParameter' => "$i",
						'CausesValidation' => $area->getCausesValidation(),
						'ValidationGroup' => $area->getValidationGroup(),
					]);
				}
				$area->render($writer);
				$writer->writeLine();
				$i++;
			}
			$writer->renderEndTag();
		}
	}

	/**
	 * Returns a copy of a hot spot with the mode and target it renders with.
	 *
	 * | Condition | HotSpotMode of the copy |
	 * |---|---|
	 * | image map disabled | Inactive |
	 * | hot spot mode set | the hot spot's |
	 * | image map mode set | the image map's |
	 * | neither set | Navigate |
	 *
	 * The copy takes the image map's Target when its own is empty. The hot spot is unchanged.
	 * @param THotSpot $hotspot the hot spot
	 * @param bool $enabled whether the image map and its parents are enabled
	 * @return THotSpot the copy to render
	 * @since 4.4.0
	 */
	protected function getRenderHotSpot(THotSpot $hotspot, bool $enabled): THotSpot
	{
		$area = clone $hotspot;
		if (!$enabled) {
			$area->setHotSpotMode(THotSpotMode::Inactive);
		} elseif ($area->getHotSpotMode() === THotSpotMode::NotSet) {
			$mode = $this->getHotSpotMode();
			$area->setHotSpotMode($mode === THotSpotMode::NotSet ? THotSpotMode::Navigate : $mode);
		}
		if ($area->getTarget() === '') {
			$area->setTarget($this->getTarget());
		}
		return $area;
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
	 * This method is required by {@see \Prado\Web\UI\IPostBackEventHandler} interface.
	 * {@see onClick OnClick} is raised for a hot spot whose mode is PostBack, after
	 * the validators of the hot spot are invoked. A hot spot of any other mode
	 * raises no event.
	 * This method is mainly used by framework and control developers.
	 * @param string $param the index of the clicked hot spot
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
		$this->setViewState('HotSpotMode', TPropertyValue::ensureEnum($value, THotSpotMode::class), THotSpotMode::NotSet);
	}

	/**
	 * @return THotSpotCollection collection of hotspots defined in this imagemap.
	 */
	public function getHotSpots()
	{
		if (($hotspots = $this->getViewState('HotSpots', null)) === null) {
			$hotspots = new THotSpotCollection();
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
