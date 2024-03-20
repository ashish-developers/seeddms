<?php
/**
 * Implementation of access restricitions
 *
 * @category   DMS
 * @package    SeedDMS
 * @license    GPL 2
 * @version    @version@
 * @author     Uwe Steinmann <uwe@steinmann.cx>
 * @copyright  Copyright (C) 2010-2012 Uwe Steinmann
 * @version    Release: @package_version@
 */

/**
 * Class to check certain access restrictions
 *
 * @category   DMS
 * @package    SeedDMS
 * @author     Uwe Steinmann <uwe@steinmann.cx>
 * @copyright  Copyright (C) 2010-2012 Uwe Steinmann
 * @version    Release: @package_version@
 */
class SeedDMS_AccessOperation {
	/**
	 * @var object $dms reference to dms
	 * @access protected
	 */
	private $dms;

	/**
	 * @var object $obj object being accessed
	 * @access protected
	 */
	private $obj;

	/**
	 * @var object $user user requesting the access
	 * @access protected
	 */
	private $user;

	/**
	 * @var object $settings SeedDMS Settings
	 * @access protected
	 */
	private $settings;

	/**
	 * @var array $legacy_access list of objects with access
	 * @access protected
	 */
	private $legacy_access;

	function __construct($dms, $obj, $user, $settings) { /* {{{ */
		$this->dms = $dms;
		$this->obj = $obj;
		$this->user = $user;
		$this->settings = $settings;
		$this->legacy_access['guest'] = array(
			'Calendar',
			'ErrorDlg',
			'Help',
			'Login',
			'Search',
			'ViewDocument',
			'ViewFolder',
		);
		$this->legacy_access['user'] = array(
			'AddDocument',
			'AddDocumentLink',
			'AddEvent',
			'AddFile',
			'AddSubFolder',
			'AddToTransmittal',
			'ApprovalSummary',
			'ApproveDocument',
			'Calendar',
			'CategoryChooser',
			'ChangePassword',
			'CheckInDocument',
			'Clipboard',
			'Dashboard',
			'DocumentAccess',
			'DocumentChooser',
			'DocumentNotify',
			'DocumentVersionDetail',
			'DropFolderChooser',
			'EditAttributes',
			'EditComment',
			'EditDocumentFile',
			'EditDocument',
			'EditEvent',
			'EditFolder',
			'EditOnline',
			'EditUserData',
			'ErrorDlg',
			'FolderAccess',
			'FolderChooser',
			'FolderNotify',
			'ForcePasswordChange',
			'GroupView',
			'Help',
			'KeywordChooser',
			'Login',
			'ManageNotify',
			'MoveDocument',
			'MoveFolder',
			'MyAccount',
			'MyDocuments',
			'OpensearchDesc',
			'OverrideContentStatus',
			'PasswordForgotten',
			'PasswordSend',
			'ReceiptDocument',
			'ReceiptSummary',
			'RemoveDocumentFile',
			'RemoveDocument',
			'RemoveEvent',
			'RemoveFolderFiles',
			'RemoveFolder',
			'RemoveTransmittal',
			'RemoveVersion',
			'RemoveWorkflowFromDocument',
			'ReturnFromSubWorkflow',
			'ReviewDocument',
			'ReviewSummary',
			'ReviseDocument',
			'RevisionSummary',
			'RewindWorkflow',
			'RunSubWorkflow',
			'Search',
			'Session',
			'SetExpires',
			'SetRecipients',
			'SetReviewersApprovers',
			'SetRevisors',
			'SetWorkflow',
			'SubstituteUser',
			'Tasks',
			'TransmittalMgr',
			'TriggerWorkflow',
			'UpdateDocument',
			'UserDefaultKeywords',
			'UserImage',
			'UsrView',
			'ViewDocument',
			'ViewEvent',
			'ViewFolder',
			'WorkflowGraph',
			'WorkflowSummary');
	} /* }}} */

	/**
	 * Check if editing of version is allowed
	 *
	 * This check can only be done for documents. Removal of versions is
	 * only allowed if this is turned on in the settings and there are
	 * at least 2 versions avaiable. Everybody with write access on the
	 * document may delete versions. The admin may even delete a version
	 * even if is disallowed in the settings.
	 */
	function mayEditVersion($vno=0) { /* {{{ */
		if($this->obj->isType('document')) {
			if($vno)
				$version = $this->obj->getContentByVersion($vno);
			else
				$version = $this->obj->getLatestContent();
			if (!isset($this->settings->_editOnlineFileTypes) || !is_array($this->settings->_editOnlineFileTypes) || (!in_array(strtolower($version->getFileType()), $this->settings->_editOnlineFileTypes) && !in_array(strtolower($version->getMimeType()), $this->settings->_editOnlineFileTypes)))
				return false;
			if ($this->obj->getAccessMode($this->user) == M_ALL || $this->user->isAdmin()) {
				return true;
			}
		}
		return false;
	} /* }}} */

	/**
	 * Check if removal of version is allowed
	 *
	 * This check can only be done for documents. Removal of versions is
	 * only allowed if this is turned on in the settings and there are
	 * at least 2 versions avaiable. Everybody with write access on the
	 * document may delete versions. The admin may even delete a version
	 * even if is disallowed in the settings.
	 */
	function mayRemoveVersion() { /* {{{ */
		if($this->obj->isType('document')) {
			$versions = $this->obj->getContent();
			if ((($this->settings->_enableVersionDeletion && ($this->obj->getAccessMode($this->user, 'removeVersion') == M_ALL)) || $this->user->isAdmin() ) && (count($versions) > 1)) {
				return true;
			}
		}
		return false;
	} /* }}} */

	/**
	 * Check if document status may be overwritten
	 *
	 * This check can only be done for documents. Overwriting the document
	 * status is
	 * only allowed if this is turned on in the settings and the current
	 * status is either 'releaѕed' or 'obsoleted'.
	 * The admin may even modify the status
	 * even if is disallowed in the settings.
	 */
	function mayOverwriteStatus() { /* {{{ */
		if($this->obj->isType('document')) {
			if($latestContent = $this->obj->getLatestContent()) {
				$status = $latestContent->getStatus();
				if ((($this->settings->_enableVersionModification && ($this->obj->getAccessMode($this->user) == M_ALL)) || $this->user->isAdmin()) && ($status["status"]==S_RELEASED || $status["status"]==S_OBSOLETE )) {
					return true;
				}
			}
		}
		return false;
	} /* }}} */

	/**
	 * Check if reviewers/approvers may be edited
	 *
	 * This check can only be done for documents. Overwriting the document
	 * reviewers/approvers is only allowed if version modification is turned on
	 * in the settings and the document has not been reviewed/approved by any
	 * user/group already.
	 * The admin may even set reviewers/approvers if is disallowed in the
	 * settings.
	 */
	function maySetReviewersApprovers() { /* {{{ */
		if($this->obj->isType('document')) {
			if($latestContent = $this->obj->getLatestContent()) {
				$status = $latestContent->getStatus();
				$reviewstatus = $latestContent->getReviewStatus();
				$hasreview = false;
				foreach($reviewstatus as $r) {
					if($r['status'] == 1 || $r['status'] == -1)
						$hasreview = true;
				}
				$approvalstatus = $latestContent->getApprovalStatus();
				$hasapproval = false;
				foreach($approvalstatus as $r) {
					if($r['status'] == 1 || $r['status'] == -1)
						$hasapproval = true;
				}
				if ((($this->settings->_enableVersionModification && ($this->obj->getAccessMode($this->user) == M_ALL)) || $this->user->isAdmin()) && (($status["status"]==S_DRAFT_REV && !$hasreview) || ($status["status"]==S_DRAFT_APP && !$hasreview && !$hasapproval))) {
					return true;
				}
			}
		}
		return false;
	} /* }}} */

	/**
	 * Check if workflow may be edited
	 *
	 * This check can only be done for documents. Overwriting the document
	 * workflow is only allowed if version modification is turned on
	 * in the settings and the document is in it's initial status.  The
	 * admin may even set the workflow if is disallowed in the
	 * settings.
	 */
	function maySetWorkflow() { /* {{{ */
		if($this->obj->isType('document')) {
			if($latestContent = $this->obj->getLatestContent()) {
				$workflow = $latestContent->getWorkflow();
				$workflowstate = $latestContent->getWorkflowState();
				if ((($this->settings->_enableVersionModification && ($this->obj->getAccessMode($this->user) == M_ALL)) || $this->user->isAdmin()) && (!$workflow || ($workflowstate && ($workflow->getInitState()->getID() == $workflowstate->getID())))) {
					return true;
				}
			}
		}
		return false;
	} /* }}} */

	/**
	 * Check if expiration date may be set
	 *
	 * This check can only be done for documents. Setting the documents
	 * expiration date is only allowed if the document has not been obsoleted.
	 */
	function maySetExpires() { /* {{{ */
		if($this->obj->isType('document')) {
			if($latestContent = $this->obj->getLatestContent()) {
				$status = $latestContent->getStatus();
				if ((($this->obj->getAccessMode($this->user) == M_ALL) || $this->user->isAdmin()) && ($status["status"]!=S_OBSOLETE)) {
					return true;
				}
			}
		}
		return false;
	} /* }}} */

	/**
	 * Check if comment may be edited
	 *
	 * This check can only be done for documents. Setting the documents
	 * comment date is only allowed if version modification is turned on in
	 * the settings and the document has not been obsoleted or expired.
	 * The admin may set the comment even if is
	 * disallowed in the settings.
	 */
	function mayEditComment() { /* {{{ */
		if($this->obj->isType('document')) {
			if($this->obj->getAccessMode($this->user) < M_READWRITE)
				return false;
			if($this->obj->isLocked()) {
				$lockingUser = $this->obj->getLockingUser();
				if (($lockingUser->getID() != $this->user->getID()) && ($this->obj->getAccessMode($this->user) != M_ALL)) {
					return false;
				}
			}
			if($latestContent = $this->obj->getLatestContent()) {
				$status = $latestContent->getStatus();
				if (($this->settings->_enableVersionModification || $this->user->isAdmin()) && !in_array($status["status"], array(S_OBSOLETE, S_EXPIRED))) {
					return true;
				}
			}
		}
		return false;
	} /* }}} */

	/**
	 * Check if attributes may be edited
	 *
	 * Setting the object attributes
	 * is only allowed if version modification is turned on in
	 * the settings or the document is still in an approval/review
	 * or intial workflow step.
	 */
	function mayEditAttributes() { /* {{{ */
		if($this->obj->isType('document')) {
			if($latestContent = $this->obj->getLatestContent()) {
				$status = $latestContent->getStatus();
				$workflow = $latestContent->getWorkflow();
				$workflowstate = $latestContent->getWorkflowState();
				if($this->obj->getAccessMode($this->user) < M_READWRITE)
					return false;
				if ($this->settings->_enableVersionModification || in_array($status["status"], array(S_DRAFT_REV, S_DRAFT_APP)) || ($workflow && $workflowstate && $workflow->getInitState()->getID() == $workflowstate->getID())) {
					return true;
				}
			}
		}
		return false;
	} /* }}} */

	/**
	 * Check if document content may be reviewed
	 *
	 * Reviewing a document content is only allowed if the document is in
	 * review. There are other requirements which are not taken into
	 * account here.
	 */
	function mayReview() { /* {{{ */
		if($this->obj->isType('document')) {
			if($latestContent = $this->obj->getLatestContent()) {
				$status = $latestContent->getStatus();
				if ($status["status"]==S_DRAFT_REV) {
					return true;
				}
			}
		}
		return false;
	} /* }}} */

	/**
	 * Check if a review maybe edited
	 *
	 * A review may only be updated by the user who originaly addedd the
	 * review and if it is allowed in the settings
	 */
	function mayUpdateReview($updateUser) { /* {{{ */
		if($this->obj->isType('document')) {
			if($this->settings->_enableUpdateRevApp && ($updateUser == $this->user) && !$this->obj->hasExpired()) {
				return true;
			}
		}
		return false;
	} /* }}} */

	/**
	 * Check if document content may be approved
	 *
	 * Approving a document content is only allowed if the document is either
	 * in approval status or released. In the second case the approval can be
	 * edited.
	 * There are other requirements which are not taken into
	 * account here.
	 */
	function mayApprove() { /* {{{ */
		if($this->obj->isType('document')) {
			if($latestContent = $this->obj->getLatestContent()) {
				$status = $latestContent->getStatus();
				if ($status["status"]==S_DRAFT_APP) {
					return true;
				}
			}
		}
		return false;
	} /* }}} */

	/**
	 * Check if a approval maybe edited
	 *
	 * An approval may only be updated by the user who originaly addedd the
	 * approval and if it is allowed in the settings
	 */
	function mayUpdateApproval($updateUser) { /* {{{ */
		if($this->obj->isType('document')) {
			if($this->settings->_enableUpdateRevApp && ($updateUser == $this->user) && !$this->obj->hasExpired()) {
				return true;
			}
		}
		return false;
	} /* }}} */

	public function allowLegacyAccess($access, $role) { /* {{{ */
		if($role == 'user' || $role == 'guest') {
			$this->legacy_access[$role][] = $access;
		}
	} /* }}} */

	protected function check_view_legacy_access($view, $get=array()) { /* {{{ */
		if($this->user->isAdmin())
			return true;

		if(is_string($view)) {
			$scripts = array($view);
		} elseif(is_array($view)) {
			$scripts = $view;
		} elseif(is_subclass_of($view, 'SeedDMS_View_Common')) {
			$scripts = array($view->getParam('class'));
		} else {
			return false;
		}

		if($this->user->isGuest()) {
			$user_allowed = $this->legacy_access['guest'];
		} else {
			$user_allowed = $this->legacy_access['user'];
		}

		if(array_intersect($scripts, $user_allowed))
			return true;

		return false;
	} /* }}} */

	/**
	 * Check for access permission on view
	 *
	 * This function will always return true because it was added to smooth
	 * migration from 5.1.x to 6.0.x
	 *
	 * @param mixed $view Instanz of view, name of view or array of view names
	 * @param string $get query parameters possible containing the element 'action'
	 * @return boolean true if access is allowed, false if access is disallowed
	 * no specific access right is set, otherwise false
	 */
	function check_view_access($view, $get=array()) { /* {{{ */
		return $this->check_view_legacy_access($view, $get);
	} /* }}} */

	/**
	 * Check for access permission on controller
	 *
	 * This function will always return true because it was added to smooth
	 * migration from 5.1.x to 6.0.x
	 *
	 * @param mixed $controller Instanz of controller, name of controller or array of controller names
	 * @param string $get query parameters
	 * @return boolean true if access is allowed otherwise false
	 */
	function check_controller_access($controller, $get=array()) { /* {{{ */
		return true;
	} /* }}} */
}
