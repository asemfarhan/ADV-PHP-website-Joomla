<?php
/**
 * Plugin Helper File
 *
 * @package         ReReplacer
 * @version         5.12.2
 *
 * @author          Peter van Westen <peter@nonumber.nl>
 * @link            http://www.nonumber.nl
 * @copyright       Copyright © 2014 NoNumber All Rights Reserved
 * @license         http://www.gnu.org/licenses/gpl-2.0.html GNU/GPL
 */

defined('_JEXEC') or die;

require_once JPATH_PLUGINS . '/system/nnframework/helpers/functions.php';
require_once JPATH_PLUGINS . '/system/nnframework/helpers/helper.php';

NNFrameworkFunctions::loadLanguage('plg_system_rereplacer');

/**
 * Plugin that replaces stuff
 */
class plgSystemReReplacerHelper
{
	var $helpers = array();
	var $counter = array();

	public function __construct(&$params)
	{
		require_once __DIR__ . '/helpers/helpers.php';
		$this->helpers = plgSystemReReplacerHelpers::getInstance($params);
	}

	public function onContentPrepare(&$article, &$context)
	{
		$items = $this->helpers->get('items')->getItemList('articles');
		$this->helpers->get('items')->filterItemList($items, $article);

		foreach ($items as $item)
		{
			if (!$item->enable_in_title)
			{
				$title = isset($article->title) ? $article->title : '';
			}

			NNFrameworkHelper::processArticle($article, $context, $this, 'replace', array($item));

			if (!$item->enable_in_title && $title)
			{
				$article->title = $title;
			}
		}
	}

	public function onAfterDispatch()
	{
		// FEED
		if (
			isset(JFactory::getDocument()->items)
			&& (
				JFactory::getDocument()->getType() == 'feed'
				|| JFactory::getApplication()->input->get('option') == 'com_acymailing'
			)
		)
		{
			$context = 'feed';
			$items = JFactory::getDocument()->items;
			foreach ($items as $item)
			{
				$this->onContentPrepare($item, $context);
			}
		}

		// only in html
		if (JFactory::getDocument()->getType() != 'html')
		{
			return;
		}

		$buffer = JFactory::getDocument()->getBuffer('component');

		if (empty($buffer) || is_array($buffer))
		{
			return;
		}

		$this->helpers->get('tag')->tagArea($buffer, 'component');

		JFactory::getDocument()->setBuffer($buffer, 'component');
	}

	public function onAfterRender()
	{
		$html = JResponse::getBody();

		if ($html == '')
		{
			return;
		}

		$this->helpers->get('replace')->replaceInAreas($html);

		$this->helpers->get('clean')->cleanLeftoverJunk($html);

		JResponse::setBody($html);
	}

	public function replace(&$string, $item)
	{
		$this->helpers->get('replace')->replace($string, $item);
	}
}
