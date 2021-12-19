<?php
defined('_JEXEC') or die;
use Joomla\Registry\Registry;
use Joomla\Utilities\ArrayHelper;
use Joomla\CMS\Plugin\CMSPlugin;
use Joomla\CMS\Form\Form;
use Joomla\CMS\Language\Text;
use Joomla\CMS\HTML\HTMLHelper;

JLoader::register('PlgArticleConnectGhsvsHelper',
	__DIR__ . '/Helper/PlgArticleConnectGhsvsHelper.php');

class PlgSystemArticleConnectGhsvs extends CMSPlugin
{
	protected $app;
	protected $db;
	protected $autoloadLanguage = true;
	protected $MapTable = '#__articleconnectghsvs';
	protected $new_rows = 2;

	// Schnellcheck-Muster, ob was in attribs zu finden.
	// { bei Objects. [ bei Arrays.
	protected $quickCheck = '"articleconnect":{"';

	protected $connectedCats = null;
	protected $execute = true;

	function __construct(&$subject, $config = array())
	{
		parent::__construct($subject, $config);
		$this->MapTable = $this->db->qn($this->MapTable);
		self::createMapTable();
	}

	/*
	Vor Laden des Formulars:
	- Es wird zuerst onContentPrepareData durchlaufen.
	- Beim Laden ist $data Object.
	- $data ist wie in onContentPrepareData manipuliert.

	Beim Speichern des Formulars:
	- Beim Speichern ist $data leeres Array.
	- Beim Speichern wird onContentPrepareData danach durchlaufen.
	*/
	public function onContentPrepareForm(Form $form, $data)
	{
		if ($this->execute !== true)
		{
			return;
		}

		$formName = $form->getName();

		## Bin in diesem Plugin selbst? - START
		$isMe = $this->app->isClient('administrator')
			&& $formName === 'com_plugins.plugin'
			&& $form->getField('articleconnectghsvsplugin', 'params');

		if ($isMe)
		{
			// Object with already saved connection possibilities.
			$catconnect = $this->params->get('catconnect');

			// Prepare some form fields inclusive sprintf placeholders for "readonly".
			$content_categories = '<field name="content_categories" type="category"'
			. ' extension="com_content" %s multiple="true"'
			. ' label="' . Text::_('PLG_ARTICLECONNECTGHSVS_CATEGORY_FROM') . '"'
			. ' description="' . Text::_('PLG_ARTICLECONNECTGHSVS_CATEGORY_FROM_DESC')
			. '"/>';
			$connect_childs = '<field name="connect_childs" type="list" default="1"'
			. ' filter="integer" %s'
			. ' label="' . Text::_('PLG_ARTICLECONNECTGHSVS_CATEGORY_CONNECT_CHILDS')
			. '" description="'
			. Text::_('PLG_ARTICLECONNECTGHSVS_CATEGORY_CONNECT_CHILDS_DESC') . '">'
			. ' <option value="0">JNO</option><option value="1">JYES</option>'
			. '</field>';
			$connect_category = '<field name="connect_category" type="category"'
			. ' extension="com_content" %s'
			. ' label="' . Text::_('PLG_ARTICLECONNECTGHSVS_CATEGORY_TO') . '"'
			. ' description="' . Text::_('PLG_ARTICLECONNECTGHSVS_CATEGORY_TO_DESC')
			. '">'
			. '<option value="">JSELECT</option></field>';

			// Start form to be displayed in this plugin.
			$addform = ['<?xml version="1.0" encoding="utf-8"?><form>'];
			$addform[] = '<fields name="params"><fieldset name="catconnect">';
			$addform[] = '<fields name="catconnect">';

			## Collect already set configurations - START.
			if (PlgArticleConnectGhsvsHelper::isNotEmptyObject($catconnect))
			{
				$identsCount = PlgArticleConnectGhsvsHelper::countRowIdentifiers(
					$this->MapTable);

				foreach ($catconnect as $rowIdentifier => $datas)
				{
					// Already saved configurations of rows get a readonly.
					$readonly = $readonly2 = '';
					$label = '----- Auswahl ' . $rowIdentifier . '';

					if (isset($identsCount[$rowIdentifier])
						&& $identsCount[$rowIdentifier]['counts'] > 0)
					{
						$readonly = ' readonly="true"';
						$label .= ' (' . $identsCount[$rowIdentifier]['counts']
							. ' Beiträge verknüpft)';

						if ($datas->connect_childs)
						{
							$readonly2 = $readonly;
						}
					}

					$label .= ' -----';

					$addform[] = '<field name="spacer' . $rowIdentifier . '1"'
					. ' type="spacer" label="' . $label . '"/>';
					$addform[] = '<fields name="' . $rowIdentifier . '">';
					$addform[] = sprintf($content_categories, $readonly);
					$addform[] = sprintf($connect_childs, $readonly2);
					$addform[] = sprintf($connect_category, $readonly);
					$addform[] = '<field name="force_delete" type="checkbox"'
					. ' label="' . Text::_('PLG_ARTICLECONNECTGHSVS_FORCE_DELETE') . '"'
					. ' description="'
					. Text::_('PLG_ARTICLECONNECTGHSVS_FORCE_DELETE_DESC') . '"/>';
					$addform[] = '</fields>'; //$rowIdentifier
					$addform[] = '<field name="spacer' .$rowIdentifier. '2"'
					. ' type="spacer" hr="true" />';
				}
			}
			## Collect already set configurations - END.

			## Add some empty new settings lines - START.
			for ($i = 1; $i <= $this->new_rows; $i++)
			{
				$addform[] = '<field name="spacer' .$i. '1" type="spacer"'
				. ' label="-----Auswahl new_row_' . $i . '------" />';
				$addform[] = '<fields name="new_row_' . $i . '">';
				$addform[] = str_replace('%s', '', $content_categories);
				$addform[] = str_replace('%s', '', $connect_childs);;
				$addform[] = str_replace('%s', '', $connect_category);
				$addform[] = '</fields>';
				$addform[] = '<field name="spacer' .$i. '2" type="spacer" hr="true" />';
			}
			## Add some empty new settings lines - END.

			// Close form and load it into Joomla display.
			$addform[] = '</fields>';
			$addform[] = '</fieldset></fields>';
			$addform[] = '</form>';
			$addform = implode('', $addform);
			$form->load($addform, false);
		}
		## Bin in diesem Plugin selbst? - END

		## Oder bin in einem Artikel? - START
		if($formName !== 'com_content.article')
		{
			return;
		}

		// Ich will $data nicht manipulieren
		$data_ = $data;

		$isSaving = false;

		if (is_array($data_) && (!isset($data_['catid']) || !isset($data_['id'])))
		{
			// Speichern
			$data_ = (object) $this->app->input->get('jform', array(), 'array');

			// Sonst bei Umschalten der Kategorie ggf. ein
			// Warnung Feld benötigt: tocat
			$isSaving = true;
		}
		elseif (is_array($data_) && isset($data_['catid']) && isset($data_['id']))
		{
			$data_ = (object) $data_;
		}

		// Aufbereitete Plugineinstellungen in array $this->connectedCats.
		self::collectConnectedCats();

		if (count($this->connectedCats))
		{
			$thisCat = $data_->catid; //des aktuellen Beitrags.
			$rows = $this->connectedCats;

			foreach ($rows as $rowIdentifier => $row)
			{
				if (!in_array($thisCat, $row['allcats']))
				{
					unset($rows[$rowIdentifier]);
				}
			}

			if (count($rows))
			{
				// Zugehörige SELECT-Class tocatchosen unten setzen
				HTMLHelper::_('formbehavior.chosen', 'select.tocatchosen',
					null, ['width' => '75%']);

				//jform[attribs][articleconnect][45_8]
				$addform = new SimpleXMLElement('<form />');
				$fields1 = $addform->addChild('fields');
				$fields1->addAttribute('name', 'attribs');

				$fields = $fields1->addChild('fields');
				$fields->addAttribute('name', 'articleconnect');
				$fieldset = $fields->addChild('fieldset');
				$fieldset->addAttribute('name', 'ARTICLECONNECT');
				$fieldset->addAttribute('addfieldpath', '/plugins/system/'
					. $this->_name . '/src/Field');
				$fieldset->addAttribute('description',
					'COM_CONTENT_ARTICLECONNECT_FIELDSET_DESC');

				foreach ($rows as $rowIdentifier => $row)
				{
					$field = $fieldset->addChild('field');
					// Unterstrich. Dann passt das auch gleich zu Labels jform_attribs_articleconnect_8_45-lbl.
					// So belassen 8_45. Kommst du in DB-Abfrage per (int) direkt an catid mit zu ladenden Beiträgen.
					// Da man auch die selbe Beziehung mehrfach wählen kann auch noch $key im Feld.
					// DAS IST MURKS. WENN EINE ZEILE GELÖSCHT ODER ZWISCHENGESCHOBEN WIRD
					// IST DER KEY NICHT MEHR KORREKT.
					$field->addAttribute('name', '' . $row['rightcat'] . '_' . $thisCat
						. '_' . $rowIdentifier);
					$field->addAttribute('type', 'articlesghsvs');
					$field->addAttribute('label', 'tocat');
					$field->addAttribute('class', 'tocatchosen');

					if ($row['required'] && !$isSaving)
					{
						$field->addAttribute('required', 'true');
					}
				} //ENDE foreach ($rows as $key => $row)

				$form->load($addform, false);
			}
		}
	}

 /*
  Die Beitrags-Kategorie könnte zwischen Laden und Speichern geändert worden sein.
 */
 public function onContentBeforeSave($context, $article, $isNew)
 {
  $allowed_context = array('com_content.article');
  $layout = $this->app->input->get('layout', '', 'cmd');
		if (
   //$this->app->isAdmin() &&
   in_array($context, $allowed_context) &&
   $layout == 'edit'
			&& !$isNew
  ){
			// Kategorie des zu speichernden Beitrags. Könnte geändert sein.
			$thisCat = (int) $article->catid;
			$thisId = (int) $article->id;

			// Zu speichernde Artikeleinstellungen
   $attribs = new Registry;
   $attribs->loadString($article->attribs);

			// Aufbereitete Plugineinstellungen in $this->connectedCats.
			self::collectConnectedCats();

			if(empty($this->connectedCats))
			{
				$attribs->set('articleconnect', '');
			}
			else
			{
				$query = $this->db->getQuery(true)
				->select('catid')->from('#__content')->where('id = ' . $thisId)
				;
				$this->db->setQuery($query);
				$thisCatOld = (int) $this->db->loadResult();
				if ($thisCatOld !== $thisCat)
				{
					$allCats = array();
					foreach ($this->connectedCats as $row)
					{
						$allCats = array_merge($allCats, $row['allcats']);
					}
					if (!in_array($thisCat, $allCats))
					{
						$attribs->set('articleconnect', '');
					}
					// Dann Aufforderung, Einstellungen zu prüfen und neu zu speichern.
					if (in_array($thisCatOld, $allCats) || in_array($thisCat, $allCats))
					{
      $this->app->enqueueMessage(JText::_('PLG_ARTICLECONNECTGHSVS_CATEGORY_CHANGED_MSG'), 'warning');
					}
				}
			}

			//Doppelte Reihen ausfiltern
/*
    [8_8_1] => 28
    [8_8_2] => 39
    [8_8_3] => 39

*/
			if (PlgArticleConnectGhsvsHelper::isNotEmptyObject($attribs->get('articleconnect')))
			{
				$checker = array();
				$articleconnect = $attribs->get('articleconnect');
				foreach ($articleconnect as $k => $connectedId)
				{
					if (!$connectedId) //JNONE (hier schon 0) oder Leerstring
					{
						if (!$this->connectedCats[$rowIdentifier]['required'])
						{
							$articleconnect->$k = ''; // Wenn nicht required, braucht auch keinen DB-Eintrag mit 0
						}
						continue;
					}
					list($left, $right, $rowIdentifier) = explode('_', $k);
					$chck = implode('_', array($left, $right, $connectedId));
					// Dann doppelt
					if (in_array($chck, $checker))
					{
						if ($this->connectedCats[$rowIdentifier]['required'])
						{
							$articleconnect->$k = '0'; //Explizites JNONE
						}
						else
						{
							$articleconnect->$k = '';
						}
					}
					$checker[] = $chck;
				}
				$attribs->set('articleconnect', $articleconnect);
			}
			$article->attribs = $attribs->toString();
			return true;
		}
 } # onContentBeforeSave

 /**
 Zusätzlich zu den bereits in $article->attribs gespeichertem articleconnect
 wird hier noch in extra Tabelle
 gespeichert, da dies die Abfrage erleichtert.
 */
 public function onContentAfterSave($context, $article, $isNew)
 {
  if (
   $context != 'com_content.article'
   || empty($article->attribs)
  ){
   return true;
  }

  // Neue Werte aus gerade gespeicherter $article->attribs
  $Paras = new Registry($article->attribs);
		// Sicherheitsabfrage, um ggf. durch versehentliches Deaktivieren des Plugins,
		// die Map-Table, die ja ursprünglich zur Sicherheit war, NICHT zu löschen!!
		// Nur, wenn sich beim aktuellen Speichervorgang was in attribs befinedet,
		// ist Plugin auch aktiv (bzw. das Aliasfeld).
		if (strpos($article->attribs, $this->quickCheck) !== false)
		{
   self::deleteArticleConnection($article);
   $articleconnect = $Paras->get('articleconnect');
			// Keine Ahnung, warum bei Object empty() fehlschlägt.
			if (PlgArticleConnectGhsvsHelper::isNotEmptyObject($articleconnect))
			{
				$checkdouble = array();
				foreach ($articleconnect as $rowIdentifier => $connectedId)
				{
					// Das lässt JNONE durch
					if (
					 (!$connectedId && (string) $connectedId !== '0')
						|| in_array($connectedId, $checkdouble)
					){
						unset($articleconnect->$rowIdentifier);
						continue;
					}
					$checkdouble[] = $connectedId;
				}
			}

			// Keine Ahnung, warum bei Object empty() fehlschlägt.
			if (PlgArticleConnectGhsvsHelper::isNotEmptyObject($articleconnect))
			{
				$query = $this->db->getQuery(true)
				->insert($this->MapTable)
				->columns(
					array(
						$this->db->qn('content_id'),
						$this->db->qn('connect_id'),
						$this->db->qn('row_identifier')
					)
				);
				foreach ($articleconnect as $key => $connectedId)
				{
					//Neu. Auch die row_identifier müssen gespeichert werden.
					// $connectedId ist bspw.8_17_01315489979
					$parts = explode('_', $key);

					$query->values(
						(int) $article->id . ',' . (int) $connectedId . ',' . $this->db->q($parts[2])
					);
				}
				$this->db->setQuery($query);
				$this->db->execute();
			} // ende $notEmpty
		}
  return true;
 } # onContentAfterSave


/**
Allgemeine Beschreibung des Events:
Falls man dann im Plugin die letztendlich aktiven Module haben möchte
und für JModuleHelper manipulieren möchte.
Beachte, dass auch Module enthalten sind, für die ggf. gar keine
Position im Template ("Auf allen Seiten anzeigen").

Falls mod_articleconnectghsvs frühzeitig mit return abbricht, auch nicht in Position anzeigen.
*/
 public function onAfterCleanModuleList(&$modules)
	{

		if (!$this->execute) return;

  // Prüfe, ob Modul bspw. durch ein return, leeren Inhalt hat.
  $doc = JFactory::getDocument();
		// Falls Modul beim Rendern, was in den HEAD packt, hinterher
		// wieder zurücksetzen.
  $headnow = $doc->getHeadData();
		$modulesToChck = array('mod_articleconnectghsvs');
  foreach ($modules as $key=>$modul)
		{
   if (in_array($modul->module, $modulesToChck))
			{
				// Hier gehört noch eine Headbereinigung rein, falls
				// Modul JS, CSS etc. lädt.
				if (!trim(JModuleHelper::renderModule($modul)))
				{
					unset($modules[$key]);
				}
			}
  }
		$modules = array_values($modules);
		$doc->setHeadData($headnow);
 }

 public function onBeforeCompileHead()
	{
		if ($this->app->isAdmin())
		{
		 $css = '
#attrib-ARTICLECONNECT .control-label{
	float:none;
	width:100%;
}
#attrib-ARTICLECONNECT .controls{
	margin-left:0;
}';
		 JFactory::getDocument()->addStyleDeclaration($css);
		}
 }

 /**
  * Attribs-Sicherungen in DB löschen.
  */
 public function onContentAfterDelete($context, $article)
 {
  if (
   $context != 'com_content.article'
   || empty($article->attribs)
   || strpos($article->attribs, $this->quickCheck) === false
  ){
   return true;
  }
  self::deleteArticleConnection($article);
		return true;
 } # onContentAfterDelete

	/**
	Bevor die Parameter dieses Plugins gespeichert werden
	*/
 public function onExtensionBeforeSave($context, $table, $isNew)
 {
		if (
		$context == 'com_plugins.plugin'
		&& $table->element == $this->_name
		&& $table->type == 'plugin'
		&& $table->folder == 'system'
		&& !$isNew //??
		){
			// Neu zu speichernde
			$tableparams = new Registry($table->params);
			$new = $tableparams->get('catconnect');
			// $old = $this->params->get('catconnect');
			if (PlgArticleConnectGhsvsHelper::isNotEmptyObject($new))
			{
				for ($i = 1; $i <= $this->new_rows; $i++)
				{
					$key = 'new_row_' . $i;
					if (PlgArticleConnectGhsvsHelper::isNotEmptyObject($new->$key))
					{
						if (
							empty($new->$key->content_categories)
						|| empty($new->$key->connect_category)
						){
							unset($new->$key);
						}
						else
						{
							$newKey = PlgArticleConnectGhsvsHelper::newRowIdentifier($i);
							$new->$newKey = $new->$key;
							unset($new->$key);
						}
					}
					else
					{
						unset($new->$key);
					}
				} //for ($i = 1; $i <= $this->new_rows; $i++)

				$identsCount = PlgArticleConnectGhsvsHelper::countRowIdentifiers($this->MapTable);
				if (ArrayHelper::getColumn((array)$new, 'force_delete'))
				{
					foreach ($new as $rowIdentifier => $connectGroup)
					{
						if (!empty($connectGroup->force_delete))
						{
							if (isset($identsCount[$rowIdentifier]) && (int) $identsCount[$rowIdentifier]['counts'] > 0)
							{
								//DB
								PlgArticleConnectGhsvsHelper::changeRowIdentifierState($this->MapTable, $rowIdentifier);
								unset($new->$rowIdentifier);
							}
							else
							{
								unset($new->$rowIdentifier);
							}
						}
						// Vorsicht! Wenn es die Property gar nicht mehr gibt, wird sie neu erzeugt!
						//unset($new->$rowIdentifier->force_delete);
					}
				} //if (ArrayHelper::getColumn((array)$new, 'force_delete'))

				$tableparams->set('catconnect', $new);
				$table->params = $tableparams->toString();
			} //if (PlgArticleConnectGhsvsHelper::isNotEmptyObject($new))
		} //ENDE falls dieses Plugin selbst.
		return true;
	}

	protected function deleteArticleConnection($article)
 {
  // Alte Einträge des $article löschen, auch damit ggf. aufgefrischtes INSERT möglich.
  $query = $this->db->getQuery(true)
  ->delete($this->MapTable)
  ->where($this->db->qn('content_id') . ' = ' . (int) $article->id);
  $this->db->setQuery($query);
  $this->db->execute();
 } # deleteArticleConnection

	protected function createMapTable()
	{
		if ($this->db->getServerType() !== 'mysql')
		{
			$this->execute = false;
			$this->app->enqueueMessager(
				'Wrong database server type detected in plg_system_articleconnectghsvs.
				Only type mysql is supported.', 'error');
			return;
		}

		if ($this->params->get('tableCreate', 1))
		{
			$sql = 'CREATE TABLE IF NOT EXISTS ' . $this->MapTable . ' (
			`content_id` int(11) unsigned NOT NULL COMMENT \'Beitrag, aus dem verknuepft wird.\',
			`connect_id`	varchar(12) 	NOT NULL COMMENT \'Ziel mit dem verknuepft wird.\',
			`row_identifier`	varchar(255) 	NOT NULL COMMENT \'row_identifier in Plugineinstellungen (Repeatable field)\',
			`state` tinyint(3) unsigned NOT NULL DEFAULT \'1\',
			UNIQUE KEY `ContentConnectId` (`content_id`,`connect_id`)
			) ENGINE=InnoDB DEFAULT CHARSET=utf8
			COMMENT=\'Plugin System articleconnectghsvs.\';';
			$this->db->setQuery($sql);
			$this->db->execute();
		}
	}

	/*
	Sammelt zusätzliche Infos zu Plugineinstellungen, bspw. Unterkategorien.
	*/
	protected function collectConnectedCats()
	{
		$rows = [];

		if (is_null($this->connectedCats))
		{
			$catconnect = $this->params->get('catconnect');

			if (PlgArticleConnectGhsvsHelper::isNotEmptyObject($catconnect))
			{
				foreach ($catconnect as $rowIdentifier => $datas)
				{
					$leftCats = $datas->content_categories;

					if (!empty($leftCats) && is_array($leftCats))
					{
						$rightCat = $datas->connect_category;

						if (!empty($rightCat))
						{
							$includeChildCats = $datas->connect_childs;
							$rows[$rowIdentifier]['rightcat'] = $rightCat;
							$rows[$rowIdentifier]['parentcount'] = count($leftCats);
							$rows[$rowIdentifier]['childscount'] = 0;

							#foreach ($leftCats as $row => $parentCatsArr)
							{
								foreach ($leftCats as $key => $parentCat)
								{
									$rows[$rowIdentifier]['parent'][$key] = $parentCat;
									$rows[$rowIdentifier]['allcats'][] = $parentCat;
									$rows[$rowIdentifier]['childs'][$key] = array();

									if ($includeChildCats)
									{
										$options = array();
										$options['published'] = false;
										$options['access'] = false;

										$categories = JCategories::getInstance('Content', $options);
										$parent = $categories->get($parentCat);
										if (($rows[$rowIdentifier]['childscount'] = $parent->hasChildren()))
										{
											$items = $parent->getChildren(true);
											if ($items)
											{
												foreach ($items as $category)
												{
													$rows[$rowIdentifier]['childs'][$key][] = $category->id;
													$rows[$rowIdentifier]['allcats'][] = $category->id;
												} //ENDE foreach ($items as $category)
											} //ENDE if ($items)
										} //ENDE if ($parent->hasChildren())
									} //ENDE if ($includeChildCats[$row])
								} //ENDE foreach ($parentCatsArr as $key => $parentCat)
							} //ENDE foreach ($leftCats as $row => $parentCatsArr)
						}
					} //ENDE if (!empty($leftCats) && is_array($leftCats))
				}
			}
			$this->connectedCats = $rows;
		} //ENDE if (is_null($this->connectedCats))
	}
}
