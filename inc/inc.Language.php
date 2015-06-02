<?php
//    MyDMS. Document Management System
//    Copyright (C) 2002-2005 Markus Westphal
//    Copyright (C) 2006-2008 Malcolm Cowe
//    Copyright (C) 2010-2013 Uwe Steinmann
//
//    This program is free software; you can redistribute it and/or modify
//    it under the terms of the GNU General Public License as published by
//    the Free Software Foundation; either version 2 of the License, or
//    (at your option) any later version.
//
//    This program is distributed in the hope that it will be useful,
//    but WITHOUT ANY WARRANTY; without even the implied warranty of
//    MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
//    GNU General Public License for more details.
//
//    You should have received a copy of the GNU General Public License
//    along with this program; if not, write to the Free Software
//    Foundation, Inc., 675 Mass Ave, Cambridge, MA 02139, USA.

$LANG = array();
$MISSING_LANG = array();
foreach(getLanguages() as $_lang) {
	if(file_exists($settings->_rootDir . "languages/" . $_lang . "/lang.inc")) {
		include $settings->_rootDir . "languages/" . $_lang . "/lang.inc";
		$LANG[$_lang] = $text;
	}
}
unset($text);

function getLanguages()
{
	GLOBAL $settings;
	
	$languages = array();
	
	$path = $settings->_rootDir . "languages/";
	$handle = opendir($path);
	
	while ($entry = readdir($handle) )
	{
		if ($entry == ".." || $entry == ".")
			continue;
		else if (is_dir($path . $entry))
			array_push($languages, $entry);
	}
	closedir($handle);

	asort($languages);
	return $languages;
}

/**
 * Get translation
 *
 * Returns the translation for a given key. It will replace markers
 * in the form [xxx] with those elements from the array $replace.
 * A default text can be gіven for the case, that there is no translation
 * available. The fourth parameter can override the currently set language
 * in the session or the default language from the configuration.
 *
 * @param string $key key of translation text
 * @param array $replace list of values that replace markers in the text
 * @param string $defaulttext text used if no translation can be found
 * @param string $lang use this language instead of the currently set lang
 */
function getMLText($key, $replace = array(), $defaulttext = "", $lang="") { /* {{{ */
	GLOBAL $settings, $LANG, $session, $MISSING_LANG;

	$trantext = '';
	if(0 && $settings->_otrance) {
		$trantext = '<form style="display: inline-block;" accept-charset="UTF-8" action="http://translate.seeddms.org/connector/index" target="_blank" method="post"><input type="hidden" value="" name="oTranceKeys['.$key.']"><input type="submit" value="submit" class="btn btn-mini"/></form>';
	}

	if(!$lang) {
		if($session)
			$lang = $session->getLanguage();
		else
			$lang = $settings->_language;
	}

	if(!isset($LANG[$lang][$key]) || !$LANG[$lang][$key]) {
		if (!$defaulttext) {
			$MISSING_LANG[$key] = $lang; //$_SERVER['SCRIPT_NAME'];
			if(!empty($LANG[$settings->_language][$key])) {
				$tmpText = $LANG[$settings->_language][$key];
			} else {
				$tmpText = '**'.$key.'**';
			}
		} else
			$tmpText = $defaulttext;
	} else
		$tmpText = $LANG[$lang][$key];

	if(0 && $settings->_otrance) {
		$_GLOBALS['used_langs'][$key] = $tmpText;
	}

	if (count($replace) == 0)
		return $tmpText.$trantext;
	
	$keys = array_keys($replace);
	foreach ($keys as $key)
		$tmpText = str_replace("[".$key."]", $replace[$key], $tmpText);
	
	return $tmpText;
} /* }}} */

function printMLText($key, $replace = array(), $defaulttext = "", $lang="") /* {{{ */
{
	print getMLText($key, $replace, $defaulttext, $lang);
}
/* }}} */

function printReviewStatusText($status, $date=0) { /* {{{ */
	if (is_null($status)) {
		print getMLText("status_unknown");
	}
	else {
		switch ($status) {
			case -2:
				print getMLText("status_reviewer_removed");
				break;
			case -1:
				print getMLText("status_reviewer_rejected").($date !=0 ? " ".$date : "");
				break;
			case 0:
				print getMLText("status_not_reviewed");
				break;
			case 1:
				print getMLText("status_reviewed").($date !=0 ? " ".$date : "");
				break;
			default:
				print getMLText("status_unknown");
				break;
		}
	}
} /* }}} */

function getReviewStatusText($status, $date=0) { /* {{{ */
	if (is_null($status)) {
		return getMLText("status_unknown");
	}
	else {
		switch ($status) {
			case -2:
				return getMLText("status_reviewer_removed");
				break;
			case -1:
				return getMLText("status_reviewer_rejected").($date !=0 ? " ".$date : "");
				break;
			case 0:
				return getMLText("status_not_reviewed");
				break;
			case 1:
				return getMLText("status_reviewed").($date !=0 ? " ".$date : "");
				break;
			default:
				return getMLText("status_unknown");
				break;
		}
	}
} /* }}} */

function printReceiptStatusText($status, $date=0) { /* {{{ */
	print getReceiptStatusText($status, $date);
} /* }}} */

function getReceiptStatusText($status, $date=0) { /* {{{ */
	if (is_null($status)) {
		return getMLText("status_unknown");
	}
	else {
		switch ($status) {
			case -2:
				return getMLText("status_recipient_removed");
				break;
			case -1:
				return getMLText("status_receipt_rejected").($date !=0 ? " ".$date : "");
				break;
			case 0:
				return getMLText("status_not_receipted");
				break;
			case 1:
				return getMLText("status_receipted").($date !=0 ? " ".$date : "");
				break;
			default:
				return getMLText("status_unknown");
				break;
		}
	}
} /* }}} */

function printRevisionStatusText($status, $date=0) { /* {{{ */
	print getRevisionStatusText($status, $date);
} /* }}} */

function getRevisionStatusText($status, $date=0) { /* {{{ */
	if (is_null($status)) {
		return getMLText("status_unknown");
	}
	else {
		switch ($status) {
			case -3:
				return getMLText("status_revision_sleeping");
				break;
			case -2:
				return getMLText("status_revisor_removed");
				break;
			case -1:
				return getMLText("status_revision_rejected").($date !=0 ? " ".$date : "");
				break;
			case 0:
				return getMLText("status_not_revised");
				break;
			case 1:
				return getMLText("status_revised").($date !=0 ? " ".$date : "");
				break;
			default:
				return getMLText("status_unknown");
				break;
		}
	}
} /* }}} */

function printApprovalStatusText($status, $date=0) { /* {{{ */
	if (is_null($status)) {
		print getMLText("status_unknown");
	}
	else {
		switch ($status) {
			case -2:
				print getMLText("status_approver_removed");
				break;
			case -1:
				print getMLText("status_approval_rejected").($date !=0 ? " ".$date : "");
				break;
			case 0:
				print getMLText("status_not_approved");
				break;
			case 1:
				print getMLText("status_approved").($date !=0 ? " ".$date : "");
				break;
			default:
				print getMLText("status_unknown");
				break;
		}
	}
} /* }}} */

function getApprovalStatusText($status, $date=0) { /* {{{ */
	if (is_null($status)) {
		return getMLText("status_unknown");
	}
	else {
		switch ($status) {
			case -2:
				return getMLText("status_approver_removed");
				break;
			case -1:
				return getMLText("status_approval_rejected").($date !=0 ? " ".$date : "");
				break;
			case 0:
				return getMLText("status_not_approved");
				break;
			case 1:
				return getMLText("status_approved").($date !=0 ? " ".$date : "");
				break;
			default:
				return getMLText("status_unknown");
				break;
		}
	}
} /* }}} */

function printOverallStatusText($status) { /* {{{ */
	print getOverallStatusText($status);
} /* }}} */

function getOverallStatusText($status) { /* {{{ */
	if (is_null($status)) {
		return getMLText("assumed_released");
	}
	else {
		switch($status) {
			case S_IN_WORKFLOW:
				return getMLText("in_workflow");
				break;
			case S_DRAFT_REV:
				return getMLText("draft_pending_review");
				break;
			case S_DRAFT_APP:
				return getMLText("draft_pending_approval");
				break;
			case S_RELEASED:
				return getMLText("released");
				break;
			case S_REJECTED:
				return getMLText("rejected");
				break;
			case S_OBSOLETE:
				return getMLText("obsolete");
				break;
			case S_EXPIRED:
				return getMLText("expired");
				break;
			case S_IN_REVISION:
				return getMLText("in_revision");
				break;
			case S_DRAFT:
				return getMLText("draft");
				break;
			default:
				return getMLText("status_unknown");
				break;
		}
	}
} /* }}} */

?>
