<?php
//    MyDMS. Document Management System
//    Copyright (C) 2002-2005  Markus Westphal
//    Copyright (C) 2006-2008 Malcolm Cowe
//    Copyright (C) 2010-2016 Uwe Steinmann
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
include("../inc/inc.Utils.php");
include("../inc/inc.LogInit.php");
include("../inc/inc.Language.php");
include("../inc/inc.Init.php");
include("../inc/inc.Extension.php");
include("../inc/inc.DBInit.php");
include("../inc/inc.ClassUI.php");
include("../inc/inc.ClassController.php");
include("../inc/inc.Authentication.php");

$tmp = explode('.', basename($_SERVER['SCRIPT_FILENAME']));
$controller = Controller::factory($tmp[1], array('dms'=>$dms, 'user'=>$user));

if (!isset($_GET["documentid"]) || !is_numeric($_GET["documentid"]) || intval($_GET["documentid"])<1) {
	UI::exitError(getMLText("document_title", array("documentname" => getMLText("invalid_doc_id"))),getMLText("invalid_doc_id"));
}
$documentid = $_GET["documentid"];
$document = $dms->getDocument($documentid);

if (!is_object($document)) {
	UI::exitError(getMLText("document_title", array("documentname" => getMLText("invalid_doc_id"))),getMLText("invalid_doc_id"));
}

$folder = $document->getFolder();
$docPathHTML = getFolderPathHTML($folder, true). " / <a href=\"../out/out.ViewDocument.php?documentid=".$documentid."\">".$document->getName()."</a>";

if ($document->getAccessMode($user) < M_ALL) {
	UI::exitError(getMLText("document_title", array("documentname" => $document->getName())),getMLText("access_denied"));
}

/* Check if the form data comes from a trusted request */
/* FIXME: Currently GET request are allowed. */
if(!checkFormKey('documentaccess', 'GET')) {
	UI::exitError(getMLText("document_title", array("documentname" => $document->getName())),getMLText("invalid_request_token"));
}

$mode = '';
switch ($_GET["action"]) {
	case "setowner":
	case "delaccess":
	case "inherit":
		$action = $_GET["action"];
		break;
	case "setdefault":
	case "editaccess":
	case "addaccess":
		$action = $_GET["action"];
		if (!isset($_GET["mode"]) || !is_numeric($_GET["mode"]) || $_GET["mode"]<M_ANY || $_GET["mode"]>M_ALL) {
			UI::exitError(getMLText("document_title", array("documentname" => $document->getName())),getMLText("invalid_access_mode"));
		}
		$mode = $_GET["mode"];
		break;
	case "notinherit":
		$action = $_GET["action"];
		if (strcasecmp($_GET["mode"], "copy") && strcasecmp($_GET["mode"], "empty")) {
			UI::exitError(getMLText("document_title", array("documentname" => $document->getName())),getMLText("invalid_access_mode"));
		}
		$mode = $_GET["mode"];
		break;
	default:
		UI::exitError(getMLText("document_title", array("documentname" => $document->getName())),getMLText("invalid_action"));
		break;
}

$userid = '';
if (isset($_GET["userid"])) {
	if (!is_numeric($_GET["userid"])) {
		UI::exitError(getMLText("document_title", array("documentname" => $document->getName())),getMLText("unknown_user"));
	}
	
	if (!strcasecmp($action, "addaccess") && $_GET["userid"]==-1) {
		$userid = -1;
	}
	else {
		if (!is_object($dms->getUser($_GET["userid"]))) {
			UI::exitError(getMLText("document_title", array("documentname" => $document->getName())),getMLText("unknown_user"));
		}
		$userid = $_GET["userid"];
	}
}

$groupid = '';
if (isset($_GET["groupid"])) {
	if (!is_numeric($_GET["groupid"])) {
		UI::exitError(getMLText("document_title", array("documentname" => $document->getName())),getMLText("unknown_group"));
	}
	if (!strcasecmp($action, "addaccess") && $_GET["groupid"]==-1) {
		$groupid = -1;
	}
	else {
		if (!is_object($dms->getGroup($_GET["groupid"]))) {
			UI::exitError(getMLText("document_title", array("documentname" => $document->getName())),getMLText("unknown_group"));
		}
		$groupid = $_GET["groupid"];
	}
}

$newowner = null;
if($action == 'setowner') {
	if (!$user->isAdmin()) {
		UI::exitError(getMLText("document_title", array("documentname" => $document->getName())),getMLText("access_denied"));
	}
	if (!isset($_GET["ownerid"]) || !is_numeric($_GET["ownerid"]) || $_GET["ownerid"]<1) {
		UI::exitError(getMLText("document_title", array("documentname" => $document->getName())),getMLText("unknown_group"));
	}
	if (!($newowner = $dms->getUser($_GET["ownerid"]))) {
		UI::exitError(getMLText("document_title", array("documentname" => $document->getName())),getMLText("unknown_group"));
	}
	$oldowner = $document->getOwner();
}

$controller->setParam('document', $document);
$controller->setParam('folder', $folder);
$controller->setParam('settings', $settings);
$controller->setParam('action', $action);
$controller->setParam('mode', $mode);
$controller->setParam('userid', $userid);
$controller->setParam('groupid', $groupid);
$controller->setParam('newowner', $newowner);
if(!$controller()) {
	UI::exitError(getMLText("folder_title", array("foldername" => htmlspecialchars($foldername))),getMLText("error_change_access"));
}

// Change owner -----------------------------------------------------------
if ($action == "setowner") {
	if($notifier) {
		$notifier->sendChangedDocumentOwnerMail($document, $user, $oldowner);
	}
	if($oldowner->getID() != $newowner->getID()) {
		$session->setSplashMsg(array('type'=>'success', 'msg'=>getMLText('splash_setowner')));
	}
}

// Change to not inherit ---------------------------------------------------
else if ($action == "notinherit") {

	if($notifier) {
		$notifier->sendChangedDocumentAccessMail($document, $user);
	}
	$session->setSplashMsg(array('type'=>'success', 'msg'=>getMLText('splash_notinherit_access')));
}

// Change to inherit-----------------------------------------------------
else if ($action == "inherit") {
	if($notifier) {
		$notifier->sendChangedDocumentAccessMail($document, $user);
	}
	$session->setSplashMsg(array('type'=>'success', 'msg'=>getMLText('splash_inherit_access')));
}

// Set default permissions ----------------------------------------------
else if ($action == "setdefault") {
	if($notifier) {
		$notifier->sendChangedDocumentAccessMail($document, $user);
	}
	$session->setSplashMsg(array('type'=>'success', 'msg'=>getMLText('splash_set_default_access')));
} elseif($action == "delaccess") {
	if($notifier) {
		$notifier->sendChangedDocumentAccessMail($document, $user);
	}
	$session->setSplashMsg(array('type'=>'success', 'msg'=>getMLText('splash_delete_access')));
} elseif($action == "addaccess") {
	if($notifier) {
		$notifier->sendChangedDocumentAccessMail($document, $user);
	}
	$session->setSplashMsg(array('type'=>'success', 'msg'=>getMLText('splash_add_access')));
} elseif($action == "editaccess") {
	if($notifier) {
		$notifier->sendChangedDocumentAccessMail($document, $user);
	}
	$session->setSplashMsg(array('type'=>'success', 'msg'=>getMLText('splash_edit_access')));
}

add_log_line("");

header("Location:../out/out.DocumentAccess.php?documentid=".$documentid);

?>
