<?php
/**
 * Implementation of WorkspaceMgr view
 *
 * @category   DMS
 * @package    SeedDMS
 * @license    GPL 2
 * @version    @version@
 * @author     Uwe Steinmann <uwe@steinmann.cx>
 * @copyright  Copyright (C) 2002-2005 Markus Westphal,
 *             2006-2008 Malcolm Cowe, 2010 Matteo Lucarelli,
 *             2010-2012 Uwe Steinmann
 * @version    Release: @package_version@
 */

/**
 * Include parent class
 */
//require_once("class.Bootstrap.php");

/**
 * Class which outputs the html page for WorkspaceMgr view
 *
 * @category   DMS
 * @package    SeedDMS
 * @author     Markus Westphal, Malcolm Cowe, Uwe Steinmann <uwe@steinmann.cx>
 * @copyright  Copyright (C) 2002-2005 Markus Westphal,
 *             2006-2008 Malcolm Cowe, 2010 Matteo Lucarelli,
 *             2010-2012 Uwe Steinmann
 * @version    Release: @package_version@
 */
class SeedDMS_View_WorkflowMgr extends SeedDMS_Theme_Style {

	function js() { /* {{{ */
		header('Content-Type: application/javascript; charset=UTF-8');
		parent::jsTranslations(array('js_form_error', 'js_form_errors'));
?>
function runValidation() {
	$("#form1").validate({
		rules: {
			name: {
				required: true
			},
			initstate: {
				required: true
			},
		},
		messages: {
			name: "<?php printMLText("js_no_name");?>",
		}
	});
}

function checkForm(num)
{
	msg = new Array();
	eval("var formObj = document.form" + num + ";");

	if (formObj.name.value == "") msg.push("<?php printMLText("js_no_name");?>");
	if (msg != "")
	{
  	noty({
  		text: msg.join('<br />'),
  		type: 'error',
      dismissQueue: true,
  		layout: 'topRight',
  		theme: 'defaultTheme',
			_timeout: 1500,
  	});
		return false;
	}
	else
		return true;
}

$(document).ready(function() {
	$('body').on('submit', '#form1', function(ev){
		if(checkForm()) return;
		ev.preventDefault();
	});
	$( "#selector" ).change(function() {
		$('div.ajax').trigger('update', {workflowid: $(this).val()});
	});
});
<?php
	} /* }}} */

	function info() { /* {{{ */
		$dms = $this->params['dms'];
		$user = $this->params['user'];
		$selworkflow = $this->params['selworkflow'];
		if($selworkflow && $selworkflow->getTransitions()) { ?>
<div id="workflowgraph">
<iframe src="out.WorkflowGraph.php?workflow=<?php echo $selworkflow->getID(); ?>" width="100%" height="670" style="border: 1px solid #e3e3e3; border-radius: 4px; margin: -1px;"></iframe>
</div>
<?php }
	} /* }}} */

	function actionmenu() { /* {{{ */
		$dms = $this->params['dms'];
		$user = $this->params['user'];
		$selworkflow = $this->params['selworkflow'];
		$workflows = $this->params['allworkflows'];
		$workflowstates = $this->params['allworkflowstates'];

		if($selworkflow && !$selworkflow->isUsed()) {
			$button = array(
				'label'=>getMLText('action'),
				'menuitems'=>array(
				)
			);
			if(!$selworkflow->isUsed()) {
				$button['menuitems'][] = array('label'=>'<i class="fa fa-remove"></i> '.getMLText("rm_workflow"), 'link'=>'../out/out.RemoveWorkflow.php?workflowid='.$selworkflow->getID());
			}
			self::showButtonwithMenu($button);
		}
	} /* }}} */

	function showWorkflowForm($workflow) { /* {{{ */
		$dms = $this->params['dms'];
		$user = $this->params['user'];
		$workflows = $this->params['allworkflows'];
		$workflowstates = $this->params['allworkflowstates'];
		$enablehiddenrevapp = $this->params['enablehiddenrevapp'];

		if($workflow) {
			$path = $workflow->checkForCycles();
			if($path) {
				$names = array();
				foreach($path as $state) {
					$names[] = htmlspecialchars($state->getName());
				}
				$this->errorMsg(getMLText('workflow_has_cycle').": ".implode(' <i class="fa fa-arrow-right"></i> ', $names));
			}

			$transitions = $workflow->getTransitions();
			$initstate = $workflow->getInitState();
			$hasinitstate = true;
			$hasreleased = true;
			$hasrejected = true;
			$missesug = false;
			if($transitions) {
				$hasinitstate = false;
				$hasreleased = false;
				$hasrejected = false;
				foreach($transitions as $transition) {
					$transusers = $transition->getUsers();
					$transgroups = $transition->getGroups();
					if(!$transusers && !$transgroups) {
						$missesug = true;
					}
					if($transition->getNextState()->getDocumentStatus() == S_RELEASED)
						$hasreleased = true;
					if($transition->getNextState()->getDocumentStatus() == S_REJECTED)
						$hasrejected = true;
					if($transition->getState()->getID() == $initstate->getID())
						$hasinitstate = true;
				}
			}
			if($missesug)
				$this->errorMsg(getMLText('workflow_transition_without_user_group'));
			if(!$hasinitstate)
				$this->errorMsg(getMLText('workflow_no_initial_state'));
			if(!$hasreleased)
				$this->errorMsg(getMLText('workflow_no_doc_released_state'));
			if(!$hasrejected)
				$this->errorMsg(getMLText('workflow_no_doc_rejected_state'));

			if($workflow->isUsed()) {
				$this->infoMsg(getMLText('workflow_in_use'));
			}
		}
?>
	<form class="form-horizontal" action="../op/op.WorkflowMgr.php" method="post" enctype="multipart/form-data" id="form1" name="form1">
<?php
		if($workflow) {
			echo createHiddenFieldWithKey('editworkflow');
?>
	<input type="hidden" name="workflowid" value="<?php print $workflow->getID();?>">
	<input type="hidden" name="action" value="editworkflow">
<?php
		} else {
			echo createHiddenFieldWithKey('addworkflow');
?>
		<input type="hidden" name="action" value="addworkflow">
<?php
		}
		$this->contentContainerStart();
		$this->formField(
			getMLText("workflow_name"),
			array(
				'element'=>'input',
				'type'=>'text',
				'id'=>'name',
				'name'=>'name',
				'value'=>($workflow ? htmlspecialchars($workflow->getName()) : '')
			)
		);
		$options = array();
		foreach($workflowstates as $workflowstate) {
			$options[] = array($workflowstate->getID(), htmlspecialchars($workflowstate->getName()), $workflow && $workflow->getInitState()->getID() == $workflowstate->getID());
		}
		$this->formField(
			getMLText("workflow_initstate"),
			array(
				'element'=>'select',
				'name'=>'initstate',
				'options'=>$options
			)
		);
		$this->contentContainerEnd();
		$this->formSubmit('<i class="fa fa-save"></i> '.getMLText("save"));
?>
	</form>
<?php
		if($workflow) {
		$actions = $dms->getAllWorkflowActions();
		if($actions) {
		$transitions = $workflow->getTransitions();
		echo "<table class=\"table table-condensed\"><thead>";
		echo "<tr><th>".getMLText('state_and_next_state')."</th><th>".getMLText('action')."</th><th>".getMLText('users_and_groups')."</th><th></th></tr></thead><tbody>";
		if($transitions) {
			foreach($transitions as $transition) {
				$state = $transition->getState();
				$nextstate = $transition->getNextState();
				$action = $transition->getAction();
				$transusers = $transition->getUsers();
				$transgroups = $transition->getGroups();
				echo "<tr";
				if(!$transusers && !$transgroups) {
					echo " class=\"error\"";
				}
				echo "><td>".'<i class="fa fa-circle'.($workflow->getInitState()->getId() == $state->getId() ? ' initstate' : ' in-workflow').'"></i> '.htmlspecialchars($state->getName())."<br />";
				$docstatus = $nextstate->getDocumentStatus();
				echo '<i class="fa fa-circle'.($docstatus == S_RELEASED ? ' released' : ($docstatus == S_REJECTED ? ' rejected' : ' in-workflow')).'"></i> '.htmlspecialchars($nextstate->getName());
				if($docstatus == S_RELEASED || $docstatus == S_REJECTED) {
					echo "<br /><i class=\"fa fa-arrow-right\"></i> ".getOverallStatusText($docstatus);
				}
				echo "</td>";
				echo "<td><i class=\"fa fa-sign-blank workflow-action\"></i> ".htmlspecialchars($action->getName())."</td>";
				echo "<td>";
				foreach($transusers as $transuser) {
					$u = $transuser->getUser();
					echo '<i class="fa fa-user"></i> '.htmlspecialchars($u->getLogin()." - ".$u->getFullName());
					echo "<br />";
				}
				foreach($transgroups as $transgroup) {
					$g = $transgroup->getGroup();
					echo '<i class="fa fa-group"></i> '.getMLText('at_least_n_users_of_group',
						array("number_of_users" => $transgroup->getNumOfUsers(),
							"group" => htmlspecialchars($g->getName())));
					echo "<br />";
				}
				echo "</td>";
				echo "<td>";
?>
<form class="form-inline" action="../op/op.RemoveTransitionFromWorkflow.php" method="post">
  <?php echo createHiddenFieldWithKey('removetransitionfromworkflow'); ?>
	<input type="hidden" name="workflow" value="<?php print $workflow->getID();?>">
	<input type="hidden" name="transition" value="<?php print $transition->getID(); ?>">
	<button type="submit" class="btn btn-mini btn-danger btn-sm"><i class="fa fa-remove"></i> <?php printMLText("delete");?></button>
</form>
<?php
				echo "</td>";
				echo "</tr>\n";
			}
		}
		echo "</tbody></table>";
?>
<form class="form-inline" action="../op/op.AddTransitionToWorkflow.php" method="post" id="form2" name="form2">
<?php
		echo "<table class=\"table table-condensed\"><thead></thead><tbody>";
			echo "<tr>";
			echo "<td>";
			echo "<select name=\"state\" class=\"form-control\">";
			$states = $dms->getAllWorkflowStates();
			foreach($states as $state) {
				echo "<option value=\"".$state->getID()."\">".htmlspecialchars($state->getName())."</option>";
			}
			echo "</select><br />";
			echo "<select name=\"nextstate\" class=\"form-control\">";
			$states = $dms->getAllWorkflowStates();
			foreach($states as $state) {
				echo "<option value=\"".$state->getID()."\">".htmlspecialchars($state->getName())."</option>";
			}
			echo "</select>";
			echo "</td>";
			echo "<td>";
			echo "<select name=\"action\" class=\"form-control\">";
			foreach($actions as $action) {
				echo "<option value=\"".$action->getID()."\">".htmlspecialchars($action->getName())."</option>";
			}
			echo "</select>";
			echo "</td>";
			echo "<td>";
      echo "<select class=\"chzn-select\" name=\"users[]\" multiple=\"multiple\" data-placeholder=\"".getMLText('select_users')."\" data-no_results_text=\"".getMLText('unknown_user')."\">";
			$allusers = $dms->getAllUsers();
			foreach($allusers as $usr) {
				if(!$enablehiddenrevapp && $usr->isHidden()) continue;
				print "<option value=\"".$usr->getID()."\">". htmlspecialchars($usr->getLogin()." - ".$usr->getFullName())."</option>";
			}
			echo "</select>";
			echo "<br />";
      echo "<select class=\"chzn-select\" name=\"groups[]\" multiple=\"multiple\" data-placeholder=\"".getMLText('select_groups')."\" data-no_results_text=\"".getMLText('unknown_group')."\">";
			$allgroups = $dms->getAllGroups();
			foreach($allgroups as $grp) {
				print "<option value=\"".$grp->getID()."\">". htmlspecialchars($grp->getName())."</option>";
			}
			echo "</select>";
			echo "</td>";
			echo "<td>";
?>
  <?php echo createHiddenFieldWithKey('addtransitiontoworkflow'); ?>
	<input type="hidden" name="workflow" value="<?php print $workflow->getID();?>">
	<input type="submit" class="btn btn-primary" value="<?php printMLText("add");?>">
<?php
			echo "</td>";
			echo "</tr>\n";
		echo "</tbody></table>";
?>
</form>
<?php
		}
		}
	} /* }}} */

	function form() { /* {{{ */
		$selworkflow = $this->params['selworkflow'];

		$this->showWorkflowForm($selworkflow);
	} /* }}} */

	function show() { /* {{{ */
		$dms = $this->params['dms'];
		$user = $this->params['user'];
		$selworkflow = $this->params['selworkflow'];
		$workflows = $this->params['allworkflows'];
		$workflowstates = $this->params['allworkflowstates'];

		$this->htmlAddHeader('<script type="text/javascript" src="../views/'.$this->theme.'/vendors/jquery-validation/jquery.validate.js"></script>'."\n", 'js');
		$this->htmlAddHeader('<script type="text/javascript" src="../views/'.$this->theme.'/styles/validation-default.js"></script>'."\n", 'js');

		$this->htmlStartPage(getMLText("admin_tools"));
		$this->globalNavigation();
		$this->contentStart();
		$this->pageNavigation(getMLText("admin_tools"), "admin_tools");
		$this->contentHeading(getMLText("workflow_management"));
		$this->rowStart();
		$this->columnStart(5);
?>
		<form class="form-horizontal">
<?php
		$options = array();
		$options[] = array("-1", getMLText("choose_workflow"));
		$options[] = array("0", getMLText("add_workflow"));
		foreach ($workflows as $currWorkflow) {
			$options[] = array($currWorkflow->getID(), htmlspecialchars($currWorkflow->getName()),$selworkflow && $currWorkflow->getID()==$selworkflow->getID());
		}
		$this->formField(
			null, //getMLText("selection"),
			array(
				'element'=>'select',
				'id'=>'selector',
				'class'=>'chzn-select',
				'options'=>$options
			)
		);
?>
		</form>
	  <div class="ajax" style="margin-bottom: 15px;" data-view="WorkflowMgr" data-action="actionmenu" <?php echo ($selworkflow ? "data-query=\"workflowid=".$selworkflow->getID()."\"" : "") ?>></div>
		<div class="ajax" data-view="WorkflowMgr" data-action="info" <?php echo ($selworkflow ? "data-query=\"workflowid=".$selworkflow->getID()."\"" : "") ?>></div>
<?php
		$this->columnEnd();
		$this->columnStart(7);
?>
		<div class="ajax" data-view="WorkflowMgr" data-action="form" data-afterload="()=>{runValidation();}" <?php echo ($selworkflow ? "data-query=\"workflowid=".$selworkflow->getID()."\"" : "") ?>></div>
<?php
		$this->columnEnd();
		$this->rowEnd();
		$this->contentEnd();
		$this->htmlEndPage();
	} /* }}} */
}
?>
