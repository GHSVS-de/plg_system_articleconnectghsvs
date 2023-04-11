<?php
defined('_JEXEC') or die;
use Joomla\Registry\Registry;
JFormHelper::loadFieldClass('list');
class JFormFieldArticlesGhsvs extends JFormFieldList
{

 public $type = 'articlesghsvs';

 protected static $options = array();

 protected function getOptions()
 {
  $hash = md5(json_encode($this->element));

		if (!isset(static::$options[$hash]))
		{
			static::$options[$hash] = parent::getOptions();
			$options = array();
	 	$catid = array((int) $this->fieldname); //Bspw. "8_45", was fromcat_tocat ist.
   JArrayHelper::toInteger($catid);
   if (count($catid))
   {
		 	$db = JFactory::getDbo();
    $query = $db->getQuery(true);

    $separator = ', ';

			 $table = $db->qn('#__content', 'c');

			 $selects = array('c.state', 'cat.title', 'c.id');
			 $selects = implode(',', $db->qn($selects));

			 $value = $db->qn('c.id', 'value') . ', c.catid';

			 $text = $query->concatenate(
				 $db->qn(array('c.title', 'c.id')), $separator
				) . ' as text';

				/*$text = $db->qn('c.title') . ' as text';*/

			 $where = $db->qn('c.catid') . ' IN (' . implode(',', $catid) . ')';

			 $query->select($selects)
			  ->select($value)->select($text)->from($table)
     ->where($where);

			 $query->join('LEFT', $db->qn('#__categories', 'cat').' ON cat.id = c.catid');

    $db->setQuery($query);
    if ($options = $db->loadObjectList())
    {
					// Da im Suchfeld durch Quotes bspw. Welt nicht "Welt findet.
					foreach ($options as $option)
					{
						$option->text = trim(str_replace(array('"', "'", '-', '«', '»'), ' ', $option->text));
					}
					$this->element['label'] = JText::_('PLG_ARTICLECONNECTGHSVS_BEITRAGSVERKNUEPFUNG') . ': ';
	   	$this->element['label'] .= '<strong>' . $options[0]->title . '</strong> (Kategorie-ID: ' . $options[0]->catid . ')';
     static::$options[$hash] = array_merge(static::$options[$hash], $options);
    }
				else
				{
					$this->element['label'] = JText::_('PLG_ARTICLECONNECTGHSVS_BEITRAGSVERKNUEPFUNG');
				}
   }
			array_unshift(static::$options[$hash], JHtml::_('select.option', '0', JText::_('JNONE')));
			array_unshift(static::$options[$hash], JHtml::_('select.option', '', JText::_('------')));
		}
  return static::$options[$hash];
 }
}
