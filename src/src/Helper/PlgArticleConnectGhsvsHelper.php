<?php
/*
2015-09-10
Benütige ich ebenfalls in Modulen!!!
*/
defined('_JEXEC') or die;

use Joomla\Registry\Registry;
use Joomla\Utilities\ArrayHelper;

class PlgArticleConnectGhsvsHelper
{

	protected static $loaded = array();

	public static function countRowIdentifiers($table, $state = array(1))
	{
		$db = JFactory::getDbo();
		$query = $db->getQuery(true)
		->select('count(row_identifier) as counts, row_identifier')
		->where('state IN (' . implode(',', $state) . ')')
		->from($table)->group($db->qn('row_identifier'))
		;
		$db->setQuery($query);
		$identsCount = $db->loadAssocList('row_identifier');
		return $identsCount;
	}

	public static function changeRowIdentifierState($table, $rowIdentifier, $state = '0')
	{
		$db = JFactory::getDbo();
		$query = $db->getQuery(true)
		->update($table)
		->set('state = ' . $db->quote($state))
		->where('row_identifier = ' . $db->quote($rowIdentifier));
		$db->setQuery($query);
		$db->execute();
	}

	public static function newRowIdentifier($rowindex = 0)
	{
		return str_replace(array('.', ' ', ','), '', microtime() . $rowindex);
	}


 public static function isNotEmptyObject($object)
 {
		return is_object($object) && count(get_object_vars($object));
	}

	/*
	connect_id: Ist die Beitrags-Id des angezeigten Artikels.
	*/
	public static function getList($params = null)
	{
		$list = [];

		if (is_null($params))
		{
			$params = new Registry;
		}

		$sig = $params->toArray();
		ksort($sig);
		$sig = md5(serialize($sig));

  if (isset(static::$loaded[__METHOD__][$sig]) && is_array(static::$loaded[__METHOD__][$sig]))
		{
			return static::$loaded[__METHOD__][$sig];
		}

		static::$loaded[__METHOD__][$sig] = $list;

		$app = JFactory::getApplication();
  $option = $app->input->get('option');
		$view = $app->input->get('view');
		if ($option == 'com_content' && $view == 'article')
		{
		 $article_id = (integer) $app->input->get('id');
		 $catid = (integer) $app->input->get('catid');
		}
		else
		{
			return array();
		}

		if (!$article_id)
		{
			return array();
		}

		$mode = $params->get('mode', 'both');

#$mode = 'bothallsuperheavy';


  $where = array();
		switch ($mode)
		{
			case 'both' :
			case 'bothall' :
			case 'bothallheavy' :
			case 'bothallsuperheavy' :
			 $where[] = 'content_id = ' . $article_id; //suche left
			 $where[] = 'connect_id = ' . $article_id; //suche right
			break;
			case 'rtl' :
			case 'rtl_ltr' :
			 $where[] = 'connect_id = ' . $article_id; //suche right
			break;
			case 'ltr' :
			case 'ltr_rtl' :
			 $where[] = 'content_id = ' . $article_id; //suche left
			break;
		}

		$db = JFactory::getDbo();
  $query = $db->getQuery(true);
		$selects = array();
		$query->select(array($db->qn('content_id', 'left'), $db->qn('connect_id', 'right')))
		 ->from($db->qn('#__articleconnectghsvs'));

		$query->where($where, 'OR');
		;

		$db->setQuery($query);
		$results = $db->loadObjectList();

  $lefts = ArrayHelper::getColumn($results, 'left');
		ArrayHelper::toInteger($lefts);
		$rights = ArrayHelper::getColumn($results, 'right');
		ArrayHelper::toInteger($rights);


		if ($mode == 'both')
		{
			$articleids = array_unique(array_merge($lefts, $rights, array($article_id)));
		}
  elseif ($mode == 'bothall')
		{
			$articleids = array_unique(array_merge($lefts, $rights, array($article_id)));
			if (empty($articleids))
			{
				return array();
			}
			$where = array();
			$query->clear('where');
			$where[] = 'connect_id IN (' . implode(',', $articleids) . ')';
			$where[] = 'content_id IN (' . implode(',', $articleids) . ')';
			$query->where($where, 'OR');
			$db->setQuery($query);
			$results = $db->loadObjectList();
			$lefts = ArrayHelper::getColumn($results, 'left');
			$rights = ArrayHelper::getColumn($results, 'right');
			$articleids = array_unique(array_merge($lefts, $rights, $articleids));
		}
  elseif ($mode == 'bothallheavy')
		{
			$articleids = array_unique(array_merge($lefts, $rights, array($article_id)));
			if (empty($articleids))
			{
				return array();
			}
			$where = array();
			$query->clear('where');
			$where[] = 'connect_id IN (' . implode(',', $articleids) . ')';
			$where[] = 'content_id IN (' . implode(',', $articleids) . ')';
			$query->where($where, 'OR');
			$db->setQuery($query);
			$results = $db->loadObjectList();
			$lefts = ArrayHelper::getColumn($results, 'left');
			$rights = ArrayHelper::getColumn($results, 'right');
			$articleids = array_unique(array_merge($lefts, $rights, $articleids));
			if (empty($articleids))
			{
				return array();
			}
			$where = array();
			$query->clear('where');
			$where[] = 'connect_id IN (' . implode(',', $articleids) . ')';
			$where[] = 'content_id IN (' . implode(',', $articleids) . ')';
			$query->where($where, 'OR');
			$db->setQuery($query);
			$results = $db->loadObjectList();
			$lefts = ArrayHelper::getColumn($results, 'left');
			$rights = ArrayHelper::getColumn($results, 'right');
			$articleids = array_unique(array_merge($lefts, $rights, $articleids));
		}
  elseif ($mode == 'bothallsuperheavy')
		{
			$articleids = array_unique(array_merge($lefts, $rights, array($article_id)));
			if (empty($articleids))
			{
				return array();
			}
			$where = array();
			$query->clear('where');
			$where[] = 'connect_id IN (' . implode(',', $articleids) . ')';
			$where[] = 'content_id IN (' . implode(',', $articleids) . ')';
			$query->where($where, 'OR');
			$db->setQuery($query);
			$results = $db->loadObjectList();
			$lefts = ArrayHelper::getColumn($results, 'left');
			$rights = ArrayHelper::getColumn($results, 'right');
			$articleids = array_unique(array_merge($lefts, $rights, $articleids));
			if (empty($articleids))
			{
				return array();
			}
			$where = array();
			$query->clear('where');
			$where[] = 'connect_id IN (' . implode(',', $articleids) . ')';
			$where[] = 'content_id IN (' . implode(',', $articleids) . ')';
			$query->where($where, 'OR');
			$db->setQuery($query);
			$results = $db->loadObjectList();
			$lefts = ArrayHelper::getColumn($results, 'left');
			$rights = ArrayHelper::getColumn($results, 'right');
			$articleids = array_unique(array_merge($lefts, $rights, $articleids));
			if (empty($articleids))
			{
				return array();
			}
			$where = array();
			$query->clear('where');
			$where[] = 'connect_id IN (' . implode(',', $articleids) . ')';
			$where[] = 'content_id IN (' . implode(',', $articleids) . ')';
			$query->where($where, 'OR');
			$db->setQuery($query);
			$results = $db->loadObjectList();
			$lefts = ArrayHelper::getColumn($results, 'left');
			$rights = ArrayHelper::getColumn($results, 'right');
			$articleids = array_unique(array_merge($lefts, $rights, $articleids));
		}
		elseif ($mode == 'rtl')
		{
			$articleids = array_unique($lefts); // gebe linke aus
		}
		elseif ($mode == 'ltr')
		{
			$articleids = array_unique($rights); // gebe rechte aus
		}
		elseif ($mode == 'ltr_rtl')
		{
			$articleids = array_unique($rights); // IDs rechte gefunden
			if (empty($articleids))
			{
				return array();
			}
			// und suche nach links mit allen IDs, die rechts gefunden
			// wurden.
			$query->clear('where');
			$query->where('connect_id IN (' . implode(',', $articleids) . ')');
			$db->setQuery($query);
			$results = $db->loadObjectList();
			$lefts = ArrayHelper::getColumn($results, 'left');
			$articleids = array_unique(array_merge($lefts, $articleids));
		}
		elseif ($mode == 'rtl_ltr')
		{
			$articleids = array_unique($lefts); // IDs links gefunden
			if (empty($articleids))
			{
				return array();
			}
			$query->clear('where');
			$query->where('content_id IN (' . implode(',', $articleids) . ')');
			$db->setQuery($query);
			$results = $db->loadObjectList();
			$rights = ArrayHelper::getColumn($results, 'right');
			$articleids = array_unique(array_merge($rights, $articleids));
		}
//echo '4654sd48sa7d98sD81s8d71dsa '.print_r((string) $query,true);exit;
		if (!$params->get('show_current', 0))
		{
			$articleids = array_diff($articleids, array($article_id));
		}

		if (empty($articleids))
		{
			return array();
		}

		JLoader::register('ContentHelperRoute', JPATH_ROOT . '/components/com_content/helpers/route.php');

  JModelLegacy::addIncludePath(JPATH_SITE . '/components/com_content/models', 'ContentModel');

		$articles = JModelLegacy::getInstance('Articles', 'ContentModel', array('ignore_request' => true));
		$access = !JComponentHelper::getParams('com_content')->get('show_noauth');
		$authorised = JAccess::getAuthorisedViewLevels(JFactory::getUser()->get('id'));
		$appParams = $app->getParams();

		$articles->setState('params', $appParams);
		$articles->setState('list.start', 0);
		$articles->setState('list.limit', (int) $params->get('count', 10));
		$articles->setState('filter.published', 1);

		$articles->setState('filter.access', $access);
		$articles->setState('list.ordering', $params->get('article_ordering', 'a.creaated'));
		$articles->setState('list.direction', $params->get('article_ordering_direction', 'DESC'));
  $articles->setState('filter.article_id', $articleids);
		$articles->setState('filter.article_id.include', true);
		$articles->setState('filter.language', $app->getLanguageFilter());

		$list = $articles->getItems();
#echo '4654sd48sa7d98sD81s8d71dsa '.print_r($list,true);exit;
		foreach ($list as &$item)
		{
			$item->title = trim(str_replace(array('"', "'", '-', '«', '»'), ' ', $item->title));
			$item->title = preg_replace('/\s\s+/', ' ', $item->title);

			$item->slug = $item->id . ':' . $item->alias;
			$item->catslug = $item->catid . ':' . $item->category_alias;
			if ($access || in_array($item->access, $authorised))
			{
				$item->link = JRoute::_(ContentHelperRoute::getArticleRoute($item->slug, $item->catid, $item->language));
			}
			else
			{
				$menu = $app->getMenu();
				$menuitems = $menu->getItems('link', 'index.php?option=com_users&view=login');
				if (isset($menuitems[0]))
				{
					$Itemid = $menuitems[0]->id;
				}
				elseif ($app->input->getInt('Itemid') > 0)
				{
					$Itemid = $app->input->getInt('Itemid');
				}

				$item->link = JRoute::_('index.php?option=com_users&view=login&Itemid=' . $Itemid);
			}
			$item->active = ($item->id == $article_id ? 'active' : '');
			if ($item->catid)
			{
				$item->categorylink = JRoute::_(ContentHelperRoute::getCategoryRoute($item->catid));
			}
			if ($item->parent_id > 1) //1:ROOT
			{
				if ((int) $item->parent_id > 1 && $item->parent_id != $item->catid)
				{
     $item->parentlink = JRoute::_(ContentHelperRoute::getCategoryRoute($item->parent_id));
					$item->parentslug = $item->parent_id . ':' . $item->parent_alias;
				}
			}


			$item->introtext = JHtml::_('content.prepare', $item->introtext, '', 'plg_articleconnectghsvs.content');

			// Merkwürdig! Seite /programmierer-schnipsel/sonstige/124-glyphicons-font-bootstrap-uebersicht-css-klassen
			// Merkwürdig! Diese Zeile
			//$item->fulltext = JHtml::_('content.prepare', $item->fulltext, '', 'mod_articlesconnectghsvs.content');
			//entfernt irgendwie, irgendwo das JS des LVSpoiler
			// aus dem Template-HEAD, obwohl hiernach
			// $scripts = JFactory::getDocument()->_scripts; anzeigt, dass geladen wurde.
			/* <script src="/plugins/content/LVSpoiler/assets/jquery/ddaccordion.js"></script> */
			// Evtl. liegt das an {loadposition icomoonclasses} in $item->fulltext,
			// das ja auch den LVSpoiler drinnen hat.
			#$item->fulltext = JHtml::_('content.prepare', $item->fulltext, '', 'plg_articleconnectghsvs');
			// Ende-Merkwürdig!
			#echo '4654sd48sa7d98sD81s8d71dsa '.print_r($item->fulltext,true);exit;


			$item->displayCategoryTitle = '';
			if (
				$item->parent_id > 1 &&
				($item->displayCategoryTitle = trim($item->parent_title))
			){
				$item->displayCategoryTitle .= ' / ';
			}
			$item->displayCategoryTitle .= $item->category_title;

		} //ENDE foreach ($list as &$item)

		// mod_articleconnect Layout linksonly.php will's z.B. gruppiert
		if ($params->get('groupbycategory', 0))
		{
		 // So Art Gruppierung durch Sortierung:
		 $sortby = array('displayCategoryTitle', 'created');
		 $sortdir = array(1, -1);
		 $caseSensitive = array(false, false);
		 $list = ArrayHelper::sortObjects($list, $sortby, $sortdir, $caseSensitive);
		}
		static::$loaded[__METHOD__][$sig] = $list;
		return static::$loaded[__METHOD__][$sig];
 }
}
