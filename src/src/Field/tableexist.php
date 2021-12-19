<?php
/*
GHSVS 2015-01-19
Prüft, ob Tabelle in DB befindlich.
Tabelle per Parameter
table="#__xyz"
im Formularfeld eintragen!
*/

\defined('_JEXEC') or die;

class PlgSystemArticleConnectGhsvsFormFieldTableExist extends JFormField
{
	protected $type = 'tableexist';

	protected function getInput()
	{

	 $table = !empty($this->element['table']) ? str_replace('#__', JFactory::getApplication()->get('dbprefix'), $this->element['table']) : '';

  if ($table && in_array($table, JFactory::getDbo()->getTableList()))
  {
   return '<strong>Tabelle ist bereits angelegt.</strong> Sie können die Einstellung auf Nein setzen. Tabelle "' . $this->element['table'] . '" ("' . $table . '") in Datenbank gefunden.';
  }
	 else
	 {
	  return '<strong>Tabelle ist noch nicht angelegt.</strong> Tabelle "' . $this->element['table'] . '" ("' . $table . '") in Datenbank NICHT gefunden. Setzen Sie Einstellung auf Ja. Speichern Sie das AKTIVIERTE Plugin, um Tabelle anzulegen.';
	 }
	}
}
