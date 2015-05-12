<?php
//    MyDMS. Document Management System
//    Copyright (C) 2002-2005  Markus Westphal
//    Copyright (C) 2006-2008 Malcolm Cowe
//    Copyright (C) 2010 Matteo Lucarelli
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

include("../inc/inc.Settings.php");
include("../inc/inc.LogInit.php");
include("../inc/inc.Utils.php");
include("../inc/inc.Language.php");
include("../inc/inc.Init.php");
include("../inc/inc.Extension.php");
include("../inc/inc.ClassEmail.php");
include("../inc/inc.DBInit.php");
include("../inc/inc.Authentication.php");
include("../inc/inc.ClassUI.php");

/* Check if the form data comes for a trusted request */
if(!checkFormKey('revisedocument')) {
	UI::exitError(getMLText("document_title", array("documentname" => getMLText("invalid_request_token"))),getMLText("invalid_request_token"));
}

if (!isset($_POST["documentid"]) || !is_numeric($_POST["documentid"]) || intval($_POST["documentid"])<1) {
	UI::exitError(getMLText("document_title", array("documentname" => getMLText("invalid_doc_id"))),getMLText("invalid_doc_id"));
}

$documentid = $_POST["documentid"];
$document = $dms->getDocument($documentid);

if (!is_object($document)) {
	UI::exitError(getMLText("document_title", array("documentname" => getMLText("invalid_doc_id"))),getMLText("invalid_doc_id"));
}

if ($document->getAccessMode($user) < M_READ) {
	UI::exitError(getMLText("document_title", array("documentname" => $document->getName())),getMLText("access_denied"));
}

if (!isset($_POST["version"]) || !is_numeric($_POST["version"]) || intval($_POST["version"])<1) {
	UI::exitError(getMLText("document_title", array("documentname" => $document->getName())),getMLText("invalid_version"));
}

$version = $_POST["version"];
$content = $document->getContentByVersion($version);

if (!is_object($content)) {
	UI::exitError(getMLText("document_title", array("documentname" => $document->getName())),getMLText("invalid_version"));
}

// operation is only allowed for the last document version
$latestContent = $document->getLatestContent();
if ($latestContent->getVersion()!=$version) {
	UI::exitError(getMLText("document_title", array("documentname" => $document->getName())),getMLText("invalid_version"));
}

// verify if document has expired
if ($document->hasExpired()){
	UI::exitError(getMLText("document_title", array("documentname" => $document->getName())),getMLText("access_denied"));
}

if (!isset($_POST["revisionStatus"]) || !is_numeric($_POST["revisionStatus"]) ||
		(intval($_POST["revisionStatus"])!=1 && intval($_POST["revisionStatus"])!=-1)) {
	UI::exitError(getMLText("document_title", array("documentname" => $document->getName())),getMLText("invalid_revision_status"));
}

if ($_POST["revisionType"] == "ind") {
	$comment = $_POST["comment"];
	$revisionLogID = $latestContent->setRevision($user, $user, $_POST["revisionStatus"], $comment);
} elseif ($_POST["revisionType"] == "grp") {
	$comment = $_POST["comment"];
	$group = $dms->getGroup($_POST['revisionGroup']);
	$revisionLogID = $latestContent->setRevision($group, $user, $_POST["revisionStatus"], $comment);
}

if(0 > $revisionLogID) {
	UI::exitError(getMLText("document_title", array("documentname" => $document->getName())),getMLText("revision_update_failed"));
} else {
	// Send an email notification to the document updater.
	if($notifier) {
		$nl=$document->getNotifyList();
		$folder = $document->getFolder();

		$subject = "revision_submit_email_subject";
		$message = "revision_submit_email_body";
		$params = array();
		$params['name'] = $document->getName();
		$params['version'] = $version;
		$params['folder_path'] = $folder->getFolderPathPlain();
		$params['status'] = getRevisionStatusText($_POST["revisionStatus"]);
		$params['comment'] = $comment;
		$params['username'] = $user->getFullName();
		$params['url'] = "http".((isset($_SERVER['HTTPS']) && (strcmp($_SERVER['HTTPS'],'off')!=0)) ? "s" : "")."://".$_SERVER['HTTP_HOST'].$settings->_httpRoot."out/out.ViewDocument.php?documentid=".$document->getID();
		$params['sitename'] = $settings->_siteName;
		$params['http_root'] = $settings->_httpRoot;
		$notifier->toList($user, $nl["users"], $subject, $message, $params);
		foreach ($nl["groups"] as $grp) {
			$notifier->toGroup($user, $grp, $subject, $message, $params);
		}
		$notifier->toIndividual($user, $content->getUser(), $subject, $message, $params);
	}
}

/* Check to see if the overall status for the document version needs
 * to be updated.
 */
if ($_POST["revisionStatus"] == -1){

	if($content->setStatus(S_REJECTED,$comment,$user)) {
		// Send notification to subscribers.
		if($notifier) {
			$nl=$document->getNotifyList();
			$folder = $document->getFolder();
			$subject = "document_status_changed_email_subject";
			$message = "document_status_changed_email_body";
			$params = array();
			$params['name'] = $document->getName();
			$params['folder_path'] = $folder->getFolderPathPlain();
			$params['status'] = getRevisionStatusText(S_REJECTED);
			$params['username'] = $user->getFullName();
			$params['url'] = "http".((isset($_SERVER['HTTPS']) && (strcmp($_SERVER['HTTPS'],'off')!=0)) ? "s" : "")."://".$_SERVER['HTTP_HOST'].$settings->_httpRoot."out/out.ViewDocument.php?documentid=".$document->getID();
			$params['sitename'] = $settings->_siteName;
			$params['http_root'] = $settings->_httpRoot;
			$notifier->toList($user, $nl["users"], $subject, $message, $params);
			foreach ($nl["groups"] as $grp) {
				$notifier->toGroup($user, $grp, $subject, $message, $params);
			}
			$notifier->toIndividual($user, $content->getUser(), $subject, $message, $params);
		}
	}

} else {

	$docRevisionStatus = $content->getRevisionStatus();
	if (is_bool($docRevisionStatus) && !$docRevisionStatus) {
		UI::exitError(getMLText("document_title", array("documentname" => $document->getName())),getMLText("cannot_retrieve_revision_snapshot"));
	}
	$revisionCT = 0;
	$revisionTotal = 0;
	foreach ($docRevisionStatus as $drstat) {
		if ($drstat["status"] == 1) {
			$revisionCT++;
		}
		if ($drstat["status"] != -2) {
			$revisionTotal++;
		}
	}
	// If all revisions have been received and there are no rejections,
	// then release the document otherwise put it back into revision workflow
	if ($revisionCT == $revisionTotal) {
		$newStatus=S_RELEASED;
		if ($content->finishRevision($user, $newStatus, '', getMLText("automatic_status_update"))) {
			// Send notification to subscribers.
			if($notifier) {
				$nl=$document->getNotifyList();
				$folder = $document->getFolder();
				$subject = "document_status_changed_email_subject";
				$message = "document_status_changed_email_body";
				$params = array();
				$params['name'] = $document->getName();
				$params['folder_path'] = $folder->getFolderPathPlain();
				$params['status'] = getRevisionStatusText($newStatus);
				$params['username'] = $user->getFullName();
				$params['url'] = "http".((isset($_SERVER['HTTPS']) && (strcmp($_SERVER['HTTPS'],'off')!=0)) ? "s" : "")."://".$_SERVER['HTTP_HOST'].$settings->_httpRoot."out/out.ViewDocument.php?documentid=".$document->getID();
				$params['sitename'] = $settings->_siteName;
				$params['http_root'] = $settings->_httpRoot;
				$notifier->toList($user, $nl["users"], $subject, $message, $params);
				foreach ($nl["groups"] as $grp) {
					$notifier->toGroup($user, $grp, $subject, $message, $params);
				}
			}
		}
	} else {
		/* Setting the status to S_IN_REVISION though it is already in that
		 * status doesn't harm, as setStatus() will catch it.
		 */
		$newStatus=S_IN_REVISION;
		if($content->setStatus($newStatus,$comment,$user)) {
			// Send notification to subscribers.
			if($notifier) {
				$nl=$document->getNotifyList();
				$folder = $document->getFolder();
				$subject = "document_status_changed_email_subject";
				$message = "document_status_changed_email_body";
				$params = array();
				$params['name'] = $document->getName();
				$params['folder_path'] = $folder->getFolderPathPlain();
				$params['status'] = getRevisionStatusText($newStatus);
				$params['username'] = $user->getFullName();
				$params['url'] = "http".((isset($_SERVER['HTTPS']) && (strcmp($_SERVER['HTTPS'],'off')!=0)) ? "s" : "")."://".$_SERVER['HTTP_HOST'].$settings->_httpRoot."out/out.ViewDocument.php?documentid=".$document->getID();
				$params['sitename'] = $settings->_siteName;
				$params['http_root'] = $settings->_httpRoot;
				$notifier->toList($user, $nl["users"], $subject, $message, $params);
				foreach ($nl["groups"] as $grp) {
					$notifier->toGroup($user, $grp, $subject, $message, $params);
				}
				$notifier->toIndividual($user, $content->getUser(), $subject, $message, $params);
			}
		}
	}
}

header("Location:../out/out.ViewDocument.php?documentid=".$documentid);

?>
