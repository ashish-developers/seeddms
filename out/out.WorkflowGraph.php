<?php
//    MyDMS. Document Management System
//    Copyright (C) 2002-2005 Markus Westphal
//    Copyright (C) 2006-2008 Malcolm Cowe
//    Copyright (C) 2010 Matteo Lucarelli
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

if(!isset($settings))
	require_once("../inc/inc.Settings.php");
require_once("inc/inc.Utils.php");
require_once("inc/inc.LogInit.php");
require_once("inc/inc.Language.php");
require_once("inc/inc.Init.php");
require_once("inc/inc.Extension.php");
require_once("inc/inc.DBInit.php");
require_once("inc/inc.ClassUI.php");
require_once("inc/inc.Authentication.php");

$tmp = explode('.', basename($_SERVER['SCRIPT_FILENAME']));
$view = UI::factory($theme, $tmp[1], array('dms'=>$dms, 'user'=>$user));
$accessop = new SeedDMS_AccessOperation($dms, null, $user, $settings);

$workflow = $dms->getWorkflow($_GET['workflow']);
if (is_bool($workflow)) {
	UI::exitError(getMLText("admin_tools"),getMLText("internal_error"));
}

if(isset($_GET['documentid']) && $_GET['documentid']) {
	$document = $dms->getDocument($_GET['documentid']);
	if (is_bool($document)) {
		UI::exitError(getMLText("admin_tools"),getMLText("internal_error"));
	}
} else {
	$document = null;
}

$transitions = array();
if(isset($_GET['transitions']) && $_GET['transitions']) {
	foreach($_GET['transitions'] as $tr) {
		$transition = $dms->getWorkflowTransition($tr);
		if ($transition)
			$transitions[] = $transition;
	}
}

if($view) {
	$view->setParam('workflow', $workflow);
	$view->setParam('transitions', $transitions);
	$view->setParam('document', $document);
	$view->setParam('accessobject', $accessop);
	$view($_GET);
	exit;
}