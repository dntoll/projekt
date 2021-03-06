<?php

namespace controller;

require_once realpath(dirname(__DIR__)).'/View/loginView.php';
require_once realpath(dirname(__DIR__)).'/View/HTMLPage.php';
require_once realpath(dirname(__DIR__)).'/Model/loginModel.php';
require_once realpath(dirname(__DIR__)).'/Model/loginDAL.php';
require_once realpath(dirname(__DIR__)).'/Model/member.php';
require_once realpath(dirname(__DIR__)).'/Model/event.php';

class loginController{
	/**
	 * @var \view\loginView
	 */
	private $loginView;
	
	/**
	 * @var \view\HTMLPage
	 */
	private $HTMLPage;
	
	/**
	 * @var \model\loginModel
	 */
	private $loginModel;
	
	/**
	 * @var \model\loginDAL
	 */
	private $loginDAL;
	
	/**
	 * @var string
	 */
	private $username;
	
	/**
	 * @var string
	 */
	private $password;
	
	/**
	 * @var int
	 */
	private $messageNr;
	
	/**
	 * @var string
	 */
	private $message;
	
	/**
	 * @var bool
	 */
	private $loggedIn;
	
	/**
	 * @var string
	 */
	private $browser; 
	
	/**
	 * @var bool
	 */
	private $saveCredentials;
	
	/**
	 * @var bool
	 */
	private $post;
	
	/**
	 * @var bool
	 */
	private $autoLogin;
	
	/**
	 * @var string
	 */
	private $cryptedPassword; 
	
	/**
	 * @var bool
	 */
	private $browserUsed;
	
	/**
	 * @var array
	 */
	private $members;
	
	/**
	 * @var array
	 */
	private $memberToShow;
	
	/**
	 * @var array
	 */
	private $numberOfMembers; 
	
	/**
	 * @var string
	 */
	private $notClickable = 'false';
	
	/**
	 * @var string
	 */
	private $clickable = 'true';
	
	/**
	 * @var \model\event
	 */
	private $event;
	
	/**
	 * @var array
	 */
	private $events;
	
	/**
	 * @var array
	 */
	private $eventToShow;
	
	public function __construct()
	{
		$this->loginView = new \view\loginView();
		$this->loginModel = new \model\loginModel();
		$this->HTMLPage = new \view\HTMLPage();
		$this->loginDAL = new \model\loginDAL();
	}
	
	public function userWantsToLogin()
	{
		$this->username = $this->loginView->getUsername();
		$this->password =  $this->loginView->getPassword();
		
		$this->loggedIn = $this->stayLoggedin();
		$this->browserUsed = $this->loginModel->checkBrowserUsed();
		
		//$this->saveCredentials = $this->loginView->canSaveCredentials();
		
		$this->post = $this->loginView->checkFormSent();
		
		$this->loginModel->getBrowser();
		
		$this->browser = $this->loginModel->checkBrowser();
		
		$this->logOut();
		
		//$this->checkStayLoggedIn();	
		if($this->logOut() == false){			
			
		
			if($this->saveCredentials &&$this->correctSavedCredentials() == false 
			   && $this->loggedIn == false && $this->browserUsed == false)
			{			
				if(!$this->browserUsed || !$this->loggedIn)
				{
					$this->messageNr = $this->loginModel->validSavedCredentialsMsg();
				}
				else{
					
					$this->messageNr = $this->loginModel->noMsg();
				}
			}	
			if ($this->saveCredentials && $this->correctSavedCredentials() && $this->loggedIn == false){
				$this->messageNr = $this->loginModel->setMsgSaveCredentials($this->saveCredentials);							
			}			
			if ($this->saveCredentials == false && $this->loggedIn == false && $this->post){
				
				$this->messageNr = $this->loginModel->checkMessageNr($this->username, $this->password);
			}
		}
		
		$this->message = $this->loginView->setMessage($this->messageNr);
		
		if($this->logOut())
		{
			$this->loginView->destroyCredentials();
		}
		$this->showPage();
	}

	public function adminWantsToAddMember()
	{
		$newMember = $this->loginView->setMember();
		$member = new \model\member($newMember[0], $newMember[1],
								$newMember[2], $newMember[3],
								$newMember[4], $newMember[5],
								$newMember[6], $newMember[7]);
		
		$pnr = $member->getPersonalNr();
		$existingPnr = $this->loginDAL->getMemberToShow($pnr);
		
		if($this->loginView->checkFormSent() && !isset($existingPnr)){
			$this->messageNr = $this->loginModel->checkUnvalidNewMember($member);
			$this->message = $this->loginView->setMessage($this->messageNr);
		}
		else if($this->loginView->checkFormSent() && isset($existingPnr)){
			$this->messageNr = $this->loginModel->alreadyExistingPnr();
			$this->message = $this->loginView->setMessage($this->messageNr);
		}
			
		if($this->loginModel->checkNewMemberValid($member) && !isset($existingPnr)){
			$this->loginDAL->addMember($member);			
		}		
	}
	
	public function adminWantsToShowMembers()
	{
		
		$this->numberOfMembers = $this->loginDAL->getNumberOfMembers($this->members);
		$newRow = $this->loginView->getNewRow();		
		$this->members = $this->loginDAL->getMembers($newRow);
		
	}
	
	public function adminWantsToShowPayingMembers()
	{
		
		$this->numberOfMembers = $this->loginDAL->getNumberOfMembers($this->members);
		$newRow = $this->loginView->getNewRow();		
		$this->members = $this->loginDAL->getPayingMembers($newRow);
		
	}
	
	public function adminWantsToShowNotPayingMembers()
	{
		
		$this->numberOfMembers = $this->loginDAL->getNumberOfMembers($this->members);
		$newRow = $this->loginView->getNewRow();		
		$this->members = $this->loginDAL->getNotPayingMembers($newRow);
		
	}
	public function adminWantsToAddEvent()
	{
		$newEvent = $this->loginView->setEvent();
		$this->event = new \model\event($newEvent[0], $newEvent[1],
								$newEvent[2], $newEvent[3]);
							
		$title = $this->event->getTitle();
		$existingTitle = $this->loginDAL->getEventToShow($title);
		
		/**$date = $this->event->getEventDate();
		$existingDate = $this->loginDAL->getEventDateToShow($date);
		
		$alreadyExists = $this->loginModel->eventExists($title,$date);*/
			
		if($this->loginView->checkFormSent() && !isset($existingTitle)){
			$this->messageNr = $this->loginModel->checkUnvalidEvent($this->event);
			$this->message = $this->loginView->setMessage($this->messageNr);
		}
		else if($this->loginView->checkFormSent() && isset($existingTitle)){
			$this->messageNr = $this->loginModel->alreadyExistingEvent();
			$this->message = $this->loginView->setMessage($this->messageNr);
		}
		
		if($this->loginModel->checkValidEvent($this->event) && !isset($existingTitle)){			
			$this->loginDAL->addEvent($this->event);
		}
	}
	
	
	public function memberWantsToShowMembers()
	{
		$newRow = $this->loginView->getNewRow();		
		$this->members = $this->loginDAL->getMembersSimple($newRow);
	}
	
	public function adminWantsToShowMember()
	{							
		$pnr = $this->loginView->getMemberAdminWantsToShow();
		$correctPnr = $this->loginDAL->getMemberToShow($pnr);
		if(isset($correctPnr)){
			$this->memberToShow = $this->loginDAL->getMember($correctPnr);
			
			$this->loginModel->savePnr($correctPnr);
		}
		else{
			$this->messageNr = $this->loginModel->unexistingPnr();
			$this->message = $this->loginView->setMessage($this->messageNr);
		}
	}
	
	public function adminWantsToUpdateMember()
	{							
		$pnr = $this->loginModel->getPnr();
		
		if ($this->loginView->isUpdatingFirstName()){
			$value = $this->loginView->getFirstName();
			$this->loginDAL->updateFirstNameMember($pnr, $value);
		}		
		if ($this->loginView->isUpdatingLastName()){
			$value = $this->loginView->getLastName();
			$this->loginDAL->updateLarstNameMember($pnr, $value);
		}
		if ($this->loginView->isUpdatingAddress()){
			$value = $this->loginView->getAddress();
			$this->loginDAL->updateAddressMember($pnr, $value);
		}
		if ($this->loginView->isUpdatingEmail()){
			$value = $this->loginView->getEmail();
			$this->loginDAL->updateEmailMember($pnr, $value);	
		}
		if ($this->loginView->isUpdatingPhonenr()){
			$value = $this->loginView->getPhonenr();
			$this->loginDAL->updatePhonenrMember($pnr, $value);
		}
		if ($this->loginView->isUpdatingClass()){
			$value = $this->loginView->getClass();
			$this->loginDAL->updateClassMember($pnr, $value);
		}
		
		$this->messageNr = $this->loginModel->memberUpdated();
		
		if ($this->loginView->isUpdatingPaydate()){
			$value = $this->loginView->getPaydate();
			
			if($this->loginModel->checkValidDateForUpdate($value)){
				$this->loginDAL->updatePaydateMember($pnr, $value);
				$this->messageNr = $this->loginModel->memberUpdated();
			}
			else{
				$this->messageNr = $this->loginModel->eventUpdatedDateFail();
			}			
		}		
		
		if($this->loginView->isSavingUpdatedMember())
		{
			$this->message = $this->loginView->setMessage($this->messageNr);	
		}
	}

	public function adminWantsToUpdateEvent()
		{							
			$title = $this->loginModel->getTitle();
			
			if ($this->loginView->isUpdatingDate()){
				$value = $this->loginView->getDate();
				
				if($this->loginModel->checkValidDateForUpdate($value)){
					$this->loginDAL->updateDateEvent($title, $value);
					$this->messageNr = $this->loginModel->eventUpdated();	
				}
				else{
					$this->messageNr = $this->loginModel->eventUpdatedDateFail();
				}
			}
			if ($this->loginView->isUpdatingTime()){
				$value = $this->loginView->getTime();
				
				if($this->loginModel->checkValidTimeForUpdate($value)){
					$this->loginDAL->updateTimeEvent($title, $value);
					$this->messageNr = $this->loginModel->eventUpdated();
				}
				else{
					$this->messageNr = $this->loginModel->eventUpdatedTimeFail();
				}
			}
			if ($this->loginView->isUpdatingInfo()){
				$value = $this->loginView->getInfo();
				$this->loginDAL->updateInfoEvent($title, $value);	
				$this->messageNr = $this->loginModel->eventUpdated();
			}	
			
			$this->message = $this->loginView->setMessage($this->messageNr);
		}
	
	public function adminWantsToDeleteMember()
	{
		$pnr = $this->loginModel->getPnr();
		$this->messageNr = $this->loginModel->memberDeleted();
		$this->message = $this->loginView->setMessage($this->messageNr);
		$this->loginDAL->deleteMember($pnr);
	}
	
	public function adminWantsToDeleteEvent()
	{
		$title = $this->loginModel->getTitle();
		$this->messageNr = $this->loginModel->eventDeleted();
		$this->message = $this->loginView->setMessage($this->messageNr);
		$this->loginDAL->deleteEvent($title);
	}
	
	public function adminWantsToShowEvent()
	{
		$title = $this->loginView->getEventAdminWantsToShow();
		$correctTitle = $this->loginDAL->getEventToShow($title);
		
		if(isset($correctTitle)){
			$this->eventToShow = $this->loginDAL->getEvent($correctTitle);
			
			$this->loginModel->saveTitle($correctTitle);
		}
		else{
			$this->messageNr = $this->loginModel->unexistingEvent();
			$this->message = $this->loginView->setMessage($this->messageNr);
		}		
	}
	
	/**
	 * @return array
	 */
	public function getUserInfoToShow()
	{
		$user = $this->loginView->getUserName();
		
		if($this->loginView->checkFormSent()){
			$this->loginModel->saveUsername($user);
		}
		
		$user = $this->loginModel->getUsername();
		$username =  $this->loginDAL->getUserToShow($user);
		
		return $this->loginDAL->getUserInfo($username);
	}
	
	public function checkIfUserCanLogIn($correctUsername, $username, $password)
	{
		if($this->loginModel->checkIfUserExists($correctUsername ,$username, $password)){
			$this->loginModel->userCanLogIn();
			return true;
		}
		
	}
	
	public function changePassword()
	{
		
		$username = $this->loginModel->getUsername();
		$newpassword = $this->loginView->getNewPassword();
		$repeatedpassword = $this->loginView->getRepeatedNewPassword();
		
		if($this->matchingPasswords($newpassword, $repeatedpassword)){
			$this->messageNr = $this->loginModel->correctChangeOfPasswords();
			
			$this->loginDAL->updatePassword($username, $newpassword);
		}
		else{
			$this->messageNr = $this->loginModel->incorrectChangeOfPasswords();
		}
		$this->message = $this->loginView->setMessage($this->messageNr);
	}
	
	/**
	 * @return boll
	 */
	public function matchingPasswords($newpass, $repeatedpass)
	{
		return $this->loginModel->comparePasswords($newpass, $repeatedpass);	
	}
	
	public function showEvents()
	{
		$newRow = $this->loginView->getNewRow();	
		$this->events = $this->loginDAL->getEvents($newRow);
	}
	
	public function showPage()
	{
		if($this->logOut())
		{
			$this->HTMLPage->getLogOutPage($this->message);				
			
		}
		
		if($this->checkIfUserCanLogIn($this->loginDAL->getUserName(),$this->loginView->getUsername(),
		$this->loginView->getPassword()) && $this->memberStayLoggedIn() ){
			$userInfo = $this->getUserInfoToShow();
			$username = $this->loginModel->getUsername();
			$this->HTMLPage->getLoggedInMemberPage($username, $userInfo, $this->message);		
		}
		else if($this->loginView->isAddingMember()&& $this->stayLoggedin())
		{
			$this->adminWantsToAddMember();
			$this->HTMLPage->getAddMemberPage($this->message);
		}	
		else if($this->loginView->isShowingEvents()&& $this->stayLoggedin() 
				|| $this->loginView->isShowingEvents() && $this->memberStayLoggedIn())	
		{
			$this->showEvents();
			$this->HTMLPage->getShowEventsPage($this->events);
		}
		else if($this->loginView->isSearchingEvent() && $this->stayLoggedin()){
			$this->adminWantsToShowEvent();
			if(isset($this->eventToShow)){
				$this->HTMLPage->getShowEventPage($this->message,$this->eventToShow,$this->clickable);
			}else{
			$this->HTMLPage->getShowEventPage($this->message,$this->eventToShow,$this->notClickable);
			}
		}
		else if($this->loginView->isAddingEvent() && $this->stayLoggedin())
		{
			$this->adminWantsToAddEvent();
								
			if($this->loginModel->checkValidEvent($this->event) == false){
				$this->HTMLPage->getAddEventPage($this->message);
			}
			else{
				$this->HTMLPage->getLoggedInPage($this->message);
			}
		}
		else if($this->loginView->isUpdatingEvent() && $this->stayLoggedin()){
				
				$this->adminWantsToUpdateEvent();
				$event = $this->loginModel->getTitle();
				$this->HTMLPage->getUpdateEventPage($this->message, $event);	
				
		} 
		else if($this->loginView->isWantingToAddEvent() && $this->stayLoggedin())
		{
			$this->HTMLPage->getAddEventPage($this->message);
		}
		else if($this->loginView->isWantingToUpdateEvent() && $this->stayLoggedin()){
			$this->HTMLPage->getShowEventPage('',$this->eventToShow,$this->notClickable);
		}
		else if($this->loginView->isDeletingEvent() && $this->stayLoggedin()){
					
				$this->adminWantsToDeleteEvent();
				$this->HTMLPage->getLoggedInPage($this->message);
				
		} 
		else if($this->loginView->isShowingMember()&& $this->stayLoggedin())
		{
			$this->HTMLPage->getShowMemberPage('','', $this->notClickable);
		}	
		else if($this->loginView->isShowingPayingMembers() && $this->stayLoggedin())
		{
			$this->adminWantsToShowPayingMembers();
			$this->HTMLPage->getShowMembersPage($this->numberOfMembers, $this->members);
		}
		else if($this->loginView->isShowingNotPayingMembers() && $this->stayLoggedin())
		{
			$this->adminWantsToShowNotPayingMembers();
			$this->HTMLPage->getShowMembersPage($this->numberOfMembers, $this->members);
		}	
		else if($this->loginView->isShowingMembers() && $this->stayLoggedin())
		{
			$this->adminWantsToShowMembers();
			$this->HTMLPage->getShowMembersPage($this->numberOfMembers,$this->members);
		}
		
		else if($this->loginView->isShowingMembersSimple() && $this->memberStayLoggedIn())
		{
			$this->memberWantsToShowMembers();
			$this->HTMLPage->getShowSimpleMembersPage($this->members);
		}	
		else if($this->loginView->isChangingPassword() && $this->memberStayLoggedIn()){
			$this->changePassword();
			$this->HTMLPage->getChangePasswordPage($this->message);
		}
		else if($this->loginView->isShowingChangingPassword() && $this->memberStayLoggedIn())
		{
			$this->HTMLPage->getChangePasswordPage('');
		}
		else if($this->loginView->isSearchingMember() && $this->stayLoggedin())
		{
			$this->adminWantsToShowMember();
			if($this->messageNr == 17){			
				$this->HTMLPage->getShowMemberPage($this->message,$this->memberToShow, $this->notClickable);
			}
			else{
				$this->HTMLPage->getShowMemberPage($this->message, $this->memberToShow, $this->clickable);
			}
			
		}	
		else if($this->loginView->isUpdatingMember() && $this->stayLoggedin()){
					
				$this->adminWantsToUpdateMember();
				$pnr = $this->loginModel->getPnr();
				$this->HTMLPage->getUpdateMemberPage($this->message, $pnr);	
				
		} 
		else if($this->loginView->isDeletingMember() && $this->stayLoggedin()){
					
				$this->adminWantsToDeleteMember();
				$pnr = $this->loginModel->getPnr();
				$this->HTMLPage->getLoggedInPage($this->message);
				
		} 
		else if($this->loginView->isWantingDeletingMember() && $this->stayLoggedin()){
					
				$pnr = $this->loginModel->getPnr();				
				$this->HTMLPage->getDeleteMemberPage($this->message, $pnr);	
		} 
		else if ($this->memberStayLoggedIn()){			
			$userInfo = $this->getUserInfoToShow();
			$username = $this->loginModel->getUsername();
			$this->HTMLPage->getLoggedInMemberPage($username, $userInfo, $this->message);	
		}
		else if($this->browser != true)
		{
			$this->HTMLPage->getPage($this->message);
		}	
		else if($this->logIn())
		{
			$this->HTMLPage->getLoggedInPage($this->message);
		}						
		else if($this->stayLoggedin())
		{
			$this->HTMLPage->getLoggedInPage($this->message);
		}
		else 
		{	
			$this->loginView->destroyCredentials();
			$this->HTMLPage->getPage($this->message);
		}	
	}
	
	/**
	 * @return bool
	 */
	public function logOut()
	{
		$checkToLogout = $this->loginView->checkLogout();
		
		$this->messageNr = $this->loginModel->setLogout($checkToLogout);
		
		return ($this->loginModel->checkLogout($checkToLogout));
	}
	
	/**
	 * @return bool
	 */
	public function stayLoggedin()
	{
		return $this->loginModel->checkLoggedIn();
	}
	
	/**
	 * @return bool
	 */
	public function memberStayLoggedIn()
	{
		return $this->loginModel->checkMemberLoggedIn();
	}
	
	public function checkStayLoggedIn()
	{
		$autoLogin = $this->loginView->checkAutoLogin();
		if($autoLogin && $this->logIn()){
			$this->loginModel->saveEndTime();
			$endTime = $this->loginModel->getEndTime();
		
			$this->loginView->autoLogin($this->username, $this->password, $endTime);
			
			$pass = $this->loginView->getCryptedPassword();		
		}
	}
	
	/**
	 * @return bool
	 */	
	public function logIn()
	{
		return $this->loginModel->checkLogin($this->username, $this->password);
	}
	
	/**
	 * @return bool
	 */	
	public function correctSavedCredentials()
	{
		$endTime = $this->loginModel->getEndTime();
		if($this->loginView->correctSavedCredentials($this->loginModel->getUser(), $endTime)){
			return true;
		}
		else {
			return false;
		}
	}
}
