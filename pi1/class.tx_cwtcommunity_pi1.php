<?php
/**
 * Copyright notice
 *
 *   (c) 2003 sebastian (s.faulhaber@web-sol.de)
 *   All rights reserved
 *
 *   This script is part of the Typo3 project. The Typo3 project is
 *   free software; you can redistribute it and/or modify
 *   it under the terms of the GNU General Public License as published by
 *   the Free Software Foundation; either version 2 of the License, or
 *   (at your option) any later version.
 *
 *   The GNU General Public License can be found at
 *   http://www.gnu.org/copyleft/gpl.html.
 *
 *   This script is distributed in the hope that it will be useful,
 *   but WITHOUT ANY WARRANTY; without even the implied warranty of
 *   MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 *   GNU General Public License for more details.
 *
 *   This copyright notice MUST APPEAR in all copies of the script!
 */
/**
 * Plugin 'CWT Community' for the 'cwt_community' extension.
 *
 * @author sebastian <s.faulhaber@web-sol.de>
 */

require_once(PATH_tslib."class.tslib_pibase.php");
include_once(PATH_typo3conf."ext/cwt_feedit/pi1/class.tx_cwtfeedit_pi1.php");

class tx_cwtcommunity_pi1 extends tslib_pibase {
    var $prefixId = "tx_cwtcommunity_pi1"; // Same as class name
    var $scriptRelPath = "pi1/class.tx_cwtcommunity_pi1.php"; // Path to this script relative to the extension dir.
    var $extKey = "cwt_community"; // The extension key.
    var $orig_templateCode = null; //Holds template code.
    var $cObj; //Reference to the calling cObj
    var $debug = false; //Global debug switch. Change to 'true' for debugging information.
    var $sysfolderList = null; //Contains the 'Starting Point' PIDs
    var $uploadDir = "uploads/tx_cwtcommunity/"; //Upload directory for fe_users' images
    var $iconReplacement = true; //Determines if the text shall be parsed for  icon replacement.
	var $flexform = null; //Contains the flexform configuration for the plugin.

    /**
     * [Put your description here]
     */
    function main($content, $conf){
        $this -> conf = $conf;
        $this -> pi_setPiVarDefaults();
        $this -> pi_loadLL();
		//Init config flexform
		$this->pi_initPIflexForm();
		$this->flexform = $this->cObj->data['pi_flexform'];
        
		// Debugging
        if ($this -> debug) {
            t3lib_div :: print_array($conf);
            // Information about fe_user
            // t3lib_div::print_array(get_object_vars($GLOBALS["TSFE"]->fe_user));
             t3lib_div::print_array(get_object_vars($GLOBALS['TSFE']));
        }
        
		// Disable Caching
        $GLOBALS["TSFE"] -> set_no_cache();

        //Kill duplicate user sessions
        $this->killDuplicateUserSessions();

        /*
        * GET CONFIGURATION
        */		
        // Get the 'Starting Point' PIDs
        if ($this -> cObj -> data["pages"] != null) {
            $this -> sysfolderList = $this -> cObj -> data["pages"];
        }
        // If no starting point is given, then take the pid of the plugin page
        else {
            $this -> sysfolderList = $GLOBALS['TSFE'] -> id;
        }
        //Which view shall be displayed
        $CODE = $this->pi_getFFvalue($this->flexform, "field_code");
        if ($this -> debug) {
            echo "<br>FLEXFORM:";
			t3lib_div::print_array($this->flexform);			
			echo "<br>";
        }

        /*
        * INIT ICON REPLACEMENT
        */
        if ($conf["iconReplacement"]){
            $this->iconReplacement = true;
        }
        else{
            $this->iconReplacement = false;
        }

        /*
        * INIT THE TEMPLATE
		*/
        // Initialize new cObj
        $cObj = t3lib_div :: makeInstance("tslib_cObj");
        $this -> cObj = $cObj;

        /*
		* CONTROLLER
		*/
        // Get action
        $action = t3lib_div :: GPvar("action");
        // Check for log in
        if ($GLOBALS['TSFE'] -> loginUser != 1) {
			$content .= htmlspecialchars($this->pi_getLL('CWT_PLEASE_LOGIN'));
        } else {
            if ($CODE == null) {
				//SO what....here should go some help text, which describes what CODE values to use.
				$content .= htmlspecialchars($this->pi_getLL('CWT_CODE_ERRMSG'));
			}
            elseif ($CODE == "WELCOME")	{
                //Get the user id
                $session_user_uid = $this->doGetLoggedInUserUID();
                // Read the file
                $this -> orig_templateCode = $this -> cObj -> fileResource($conf["template_welcome"]);
                //Get the user information
                $user_info = $this->doGetUserInfo($session_user_uid);
                //Get the users new messages
                $messages = $this->doGetNewMessages($session_user_uid);
                $count = sizeof($messages);
                //Generate the view
                $content .= $this->getViewWelcome($user_info, $count);
            }
            elseif ($CODE == "MESSAGES") {
                //Now check, if the user views his own guestbook
                $session_user_uid = $this->doGetLoggedInUserUID();
                // Read the file
                $this -> orig_templateCode = $this -> cObj -> fileResource($conf["template_messages"]);
                //Get action
                $action = t3lib_div::GPvar("action");
                //Check for post vars
                $submitPressed = $this->piVars["submit_button"];
                $cancelPressed = $this->piVars["cancel_button"];

                //Decide what to do
                if ($action == null || $action == "getviewmessages" || $cancelPressed != null){
                    //Get the model
                    $messages = $this->doGetMessages($session_user_uid);
                    //Generate the view
                    $content .= $this->getViewMessages($messages);
                }
                elseif ($action == "getviewmessagesdelete"){
                    //Get the msg uid
                    $msg_uid = t3lib_div::GPvar("msg_uid");
                    //Delete the message
                    $res = $this->doDeleteMessage($msg_uid);
                    //Get the model
                    $messages = $this->doGetMessages($session_user_uid);
                    //Generate the view
                    $content .= $this->getViewMessages($messages);
                }
                elseif ($action == "getviewmessagesnew"){
                        //Get the recipient uid
                        $recipient_uid = t3lib_div::GPvar("recipient_uid");
                        //Check if the user filled in all fields
                        $subject = htmlspecialchars($this->piVars["subject"]);
                        $body = htmlspecialchars($this->piVars["body"]);
                        //user wants to submit
                        if ($submitPressed != null){
                            //Everything ok, send the msg
                           if ($subject != null && $body != null){
                               //Send msg
                               $res = $this->doSendMessage($session_user_uid, $recipient_uid, $subject, $body);
                               //Display result view
                               $content .= $this->getViewMessagesNewResult($session_user_uid, $recipient_uid);
                           }
                           //Not okay...display new view
                           else{
                                $content .= $this->getViewMessagesNew($session_user_uid, $recipient_uid, null);
                           }
                        }
                        //NO post vars
                        else{
                             //Check if the user wants to answer a mail
                             $answer_uid = t3lib_div::GPvar("answer_uid");
                             //User wants to answer
                             if ($answer_uid != null){
                                //Get the message from database
                                $query = $this->doGetMessagesSingle($session_user_uid, $answer_uid);
                                //Get the subject, of mail to answer
								$subject = htmlspecialchars($this->pi_getLL('CWT_REPLY_ABBREV'));

                                //Get the bodytext
                                $body = "\n-------------------------".$query["body"];
                             }
                             //Generate the view
                             $content .= $this->getViewMessagesNew($session_user_uid, $recipient_uid, $subject, $body);
                        }
                }
                elseif ($action == "getviewmessagessingle"){
                    //Get the msg uid
                    $msg_uid = t3lib_div::GPvar("msg_uid");
                    //Get the model
                    $message = $this->doGetMessagesSingle($session_user_uid, $msg_uid);
                    //Generate the view
                    $content .= $this->getViewMessagesSingle($session_user_uid, $message);
                }
            }
			elseif ($CODE == "GUESTBOOK") {
				//Now check, if the user views his own guestbook
				$session_user_uid = $this->doGetLoggedInUserUID();
       	        // Read the file
   	            $this -> orig_templateCode = $this -> cObj -> fileResource($conf["template_guestbook"]);
                //if no uid is given, then take session user uid
                $uid = t3lib_div :: GPvar("uid");
                if ($uid == null){
                    $uid = $this->doGetLoggedInUserUID();
                }

				//FOR THE GUESTBOOK OWNER
				if ($session_user_uid == $uid) {
					//At first, check for POST vars
					//User wants to activate guestbook
					if ($this->piVars["open_guestbook"] != null) {
						//so...open it ;-)
					    $res = $this->doOpenGuestbook($session_user_uid);
					}
					//User wants to close guestbook
					elseif($this->piVars["lock_guestbook"] != null){
						//so...lock it!
						$res = $this->doLockGuestbook($session_user_uid);
					}

					//Now check, if the user has enabled his gb
					$status = $this->doGetGuestbookStatus($session_user_uid);
					//Then ...Check, if guestbook is locked or open
					if ($status == "0") {
						$isLocked = false;
					}
					elseif ($status == "1"){
						$isLocked = true;
					}

					//Then check for action, in case the user wants to delete something.
	                // Get action
                	$action = t3lib_div :: GPvar("action");
					$item = t3lib_div::GPvar("item");
					if ($action == "getviewguestbookdeleteitem") {
						//Delete guestbook item
						$res = $this->doDeleteGuestbookItem($item);
					}
					elseif ($action == "getviewguestbookdeleteall"){
						//Delete the whole guestbook
						$res = $this->doDeleteGuestbook($session_user_uid);
					}

        		    // Get the model -> guestbook
  			        $guestbook = $this -> doGetGuestbook($session_user_uid);
					//Generate the view
				    $content .= $this->getViewGuestbookLoggedIn($session_user_uid, $isLocked, $guestbook);
				}
				//FOR OTHER USERS
				else{
					//Now check, if the user has enabled his gb
					$status = $this->doGetGuestbookStatus(t3lib_div :: GPvar("uid"));

					//ENABLED !! Everything is fine ;-)
					if ($status == "0") {
		                // Get action
	                	$action = t3lib_div :: GPvar("action");
            	    	// Decide what to do
        	    	    if ($action == "getviewguestbookadd") {
    	    	            // Get fe user uid
	    	                $uid = t3lib_div :: GPvar("uid");
    	                	$text = htmlspecialchars($this -> piVars["text"]);
							$submitPressed = $this->piVars["submit_button"];
							$cancelPressed = $this->piVars["cancel_button"];
                		    // Check for add record
            		        // ADD RECORD
        		            if ($text != null && $submitPressed != null) {
    		                    // UID of fe user logged in
		                        $user_uid = $this -> doGetLoggedInUserUID();
	                        	// INsert into db
                    	    	$res = $this -> doInsertGuestbookData($user_uid, $text, $uid);
                	    	    // Generate the view
            	    	        $content .= $this -> getViewGuestbookAddResult($uid);
        	    	        }
							//CANCEL
							elseif ($cancelPressed != null){
								//Display normal guestbook view
			                    // Get fe user uid
    	                		$uid = t3lib_div :: GPvar("uid");
        	        		    // Get the model -> guestbook
            			        $guestbook = $this -> doGetGuestbook($uid);
        	    		        // Generate the view
    	    	        	    $content .= $this -> getViewGuestbook($guestbook, $uid);

							}
    		                // DISPLAY INPUT FORM
		                    else {
                        		// Generate the view
                    		    $content .= $this -> getViewGuestbookAdd($uid);
                		    }
            		    }
						else {
			                    // Get fe user uid
	                    		$uid = t3lib_div :: GPvar("uid");
	                		    // Get the model -> user
	            		        $guestbook = $this -> doGetGuestbook($uid);
	        		            // Generate the view
	    		                $content .= $this -> getViewGuestbook($guestbook, $uid);
			                }
					}
					//ELSE --> DISABLED ;-/
					else{
						//Generate view
						$content .= $this->getViewGuestbookDisabled();
					}
				}
            }
			elseif ($CODE == "PROFILE") {
                // Read the file
                $this -> orig_templateCode = $this -> cObj -> fileResource($conf["template_profile"]);
                // Get action
                $letter = t3lib_div :: GPvar("action");
                // Decide what to do
                // Get fe user uid
                $uid = t3lib_div :: GPvar("uid");
                //if no uid is given, then take session user uid
                if ($uid == null){
                    $uid = $this->doGetLoggedInUserUID();
                }
                // Get the model -> user
                $user = $this->doGetUser($uid);
                // Generate the view
                $content .= $this -> getViewProfile($user);
            }
			elseif ($CODE == "SEARCH") {
                // Read the file
                $this -> orig_templateCode = $this -> cObj -> fileResource($conf["template_search"]);
                // Decide what to do
            }
			elseif ($CODE == "BUDDYLIST") {
                // Read the file
                $this -> orig_templateCode = $this -> cObj -> fileResource($conf["template_buddylist"]);
                //Get the uid of logged in user
                $uid = $this->doGetLoggedInUserUID();
                //Get action
                $action = t3lib_div::GPvar("action");
                //Decide what to do
                if ($action == null || $action == "getviewbuddylist"){
                    //Get the model
                    $buddylist = $this->doGetBuddylist($uid);
                    //Generate the view
                    $content .= $this->getViewBuddylist($buddylist);
                }
                elseif($action == "getviewbuddylistadd"){
                    //get buddy uid, which should be added
                    $buddy_uid = t3lib_div::GPvar("buddy_uid");
                    //Add it to list
                    $res = $this->doAddBuddy($uid, $buddy_uid);
                    //Get the model
                    $buddylist = $this->doGetBuddylist($uid);
                    //Generate the view
                    $content .= $this->getViewBuddylist($buddylist);
                }
                elseif($action == "getviewbuddylistdelete"){
                    //get buddy uid, which should be added
                    $buddy_uid = t3lib_div::GPvar("buddy_uid");
                    //Add it to list
                    $res = $this->doDeleteBuddy($uid, $buddy_uid);
                    //Get the model
                    $buddylist = $this->doGetBuddylist($uid);
                    //Generate the view
                    $content .= $this->getViewBuddylist($buddylist);
                }
            }
			elseif ($CODE == "USERLIST") {
                // Read the file
                $this -> orig_templateCode = $this -> cObj -> fileResource($conf["template_userlist"]);
                // Decide what to do
                if ($action == null || $action == "getviewuserlist") {
                    // Get letter
                    $letter = t3lib_div :: GPvar("letter");
                    // Get the model -> user
                    $users = $this -> doGetUserlist($letter);
                    // Generate the view
                    $content .= $this -> getViewUserlist($users);
                }
            }
            elseif ($CODE == "PROFILE_EDIT"){
				//Get the TCA from fe_users
				//START CONFIG FOR FEEDIT
				$table = "fe_users";
                //Create Item array
                $items['username']['label'] = htmlspecialchars($this->pi_getLL('CWT_PROFILE_EDIT_USERNAME_LABEL'));
                $items['username']['helptext'] = htmlspecialchars($this->pi_getLL('CWT_PROFILE_EDIT_USERNAME_HELPTEXT'));
                $items['username']['error_msg'] = htmlspecialchars($this->pi_getLL('CWT_PROFILE_EDIT_USERNAME_ERRMSG'));
                $items['username']['type'] = "preview";
                $items['name']['label'] = htmlspecialchars($this->pi_getLL('CWT_PROFILE_EDIT_NAME_LABEL'));
                $items['name']['helptext'] = htmlspecialchars($this->pi_getLL('CWT_PROFILE_EDIT_NAME_HELPTEXT'));
                $items['name']['error_msg'] = htmlspecialchars($this->pi_getLL('CWT_PROFILE_EDIT_NAME_ERRMSG'));
                $items['password']['label'] = htmlspecialchars($this->pi_getLL('CWT_PROFILE_EDIT_PWD_LABEL'));
                $items['password']['label_again'] = htmlspecialchars($this->pi_getLL('CWT_PROFILE_EDIT_PWD_LABELAGAIN'));
                $items['password']['helptext'] = htmlspecialchars($this->pi_getLL('CWT_PROFILE_EDIT_PWD_HELPTEXT'));
                $items['password']['helptext_again'] = htmlspecialchars($this->pi_getLL('CWT_PROFILE_EDIT_PWD_HELPTEXTAGAIN'));
                $items['password']['error_msg'] = htmlspecialchars($this->pi_getLL('CWT_PROFILE_EDIT_PWD_ERRMSG'));
                $items["password"]["type"] = "password";
                $items["password"]["eval"] = "twice";
                $items['address']['label'] = htmlspecialchars($this->pi_getLL('CWT_PROFILE_EDIT_ADDRESS_LABEL'));
                $items['address']['helptext'] = htmlspecialchars($this->pi_getLL('CWT_PROFILE_EDIT_ADDRESS_HELPTEXT'));
                $items['address']['error_msg'] = htmlspecialchars($this->pi_getLL('CWT_PROFILE_EDIT_ADDRESS_ERRMSG'));
				$items['zip']['label'] = htmlspecialchars($this->pi_getLL('CWT_PROFILE_EDIT_ZIP_LABEL'));
				$items['zip']['helptext'] = htmlspecialchars($this->pi_getLL('CWT_PROFILE_EDIT_ZIP_HELPTEXT'));
				$items['zip']['error_msg'] = htmlspecialchars($this->pi_getLL('CWT_PROFILE_EDIT_ZIP_ERRMSG'));
				$items['city']['label'] = htmlspecialchars($this->pi_getLL('CWT_PROFILE_EDIT_CITY_LABEL'));
				$items['city']['helptext'] = htmlspecialchars($this->pi_getLL('CWT_PROFILE_EDIT_CITY_HELPTEXT'));
				$items['city']['error_msg'] = htmlspecialchars($this->pi_getLL('CWT_PROFILE_EDIT_CITY_ERRMSG'));
				$items['country']['label'] = htmlspecialchars($this->pi_getLL('CWT_PROFILE_EDIT_COUNTRY_LABEL'));
				$items['country']['helptext'] = htmlspecialchars($this->pi_getLL('CWT_PROFILE_EDIT_COUNTRY_HELPTEXT'));
				$items['country']['error_msg'] = htmlspecialchars($this->pi_getLL('CWT_PROFILE_EDIT_COUNTRY_ERRMSG'));
				$items['company']['label'] = htmlspecialchars($this->pi_getLL('CWT_PROFILE_EDIT_COMPANY_LABEL'));
				$items['company']['helptext'] = htmlspecialchars($this->pi_getLL('CWT_PROFILE_EDIT_COMPANY_HELPTEXT'));
				$items['company']['error_msg'] = htmlspecialchars($this->pi_getLL('CWT_PROFILE_EDIT_COMPANY_ERRMSG'));
                $items['telephone']['label'] = htmlspecialchars($this->pi_getLL('CWT_PROFILE_EDIT_PHONE_LABEL'));
                $items['telephone']['helptext'] = htmlspecialchars($this->pi_getLL('CWT_PROFILE_EDIT_PHONE_HELPTEXT'));
                $items['telephone']['error_msg'] = htmlspecialchars($this->pi_getLL('CWT_PROFILE_EDIT_PHONE_ERRMSG'));
                $items['fax']['label'] = htmlspecialchars($this->pi_getLL('CWT_PROFILE_EDIT_FAX_LABEL'));
                $items['fax']['helptext'] = htmlspecialchars($this->pi_getLL('CWT_PROFILE_EDIT_FAX_HELPTEXT'));
                $items['fax']['error_msg'] = htmlspecialchars($this->pi_getLL('CWT_PROFILE_EDIT_FAX_ERRMSG'));
                $items['email']['label'] = htmlspecialchars($this->pi_getLL('CWT_PROFILE_EDIT_EMAIL_LABEL'));
                $items['email']['helptext'] = htmlspecialchars($this->pi_getLL('CWT_PROFILE_EDIT_EMAIL_HELPTEXT'));
                $items['email']['error_msg'] = htmlspecialchars($this->pi_getLL('CWT_PROFILE_EDIT_EMAIL_ERRMSG'));
                $items["email"]["eval"] = "email";
                $items['www']['label'] = htmlspecialchars($this->pi_getLL('CWT_PROFILE_EDIT_WWW_LABEL'));
                $items['www']['helptext'] = htmlspecialchars($this->pi_getLL('CWT_PROFILE_EDIT_WWW_HELPTEXT'));
                $items['www']['error_msg'] = htmlspecialchars($this->pi_getLL('CWT_PROFILE_EDIT_WWW_ERRMSG'));
                $items['tx_cwtcommunityuser_image']['label'] = htmlspecialchars($this->pi_getLL('CWT_PROFILE_EDIT_IMAGE_LABEL'));
                $items['tx_cwtcommunityuser_image']['helptext'] = htmlspecialchars($this->pi_getLL('CWT_PROFILE_EDIT_IMAGE_HELPTEXT'));
                $items['tx_cwtcommunityuser_image']['error_msg'] = htmlspecialchars($this->pi_getLL('CWT_PROFILE_EDIT_IMAGE_ERRMSG'));
                $items['tx_cwtcommunityuser_sex']['label'] = htmlspecialchars($this->pi_getLL('CWT_PROFILE_EDIT_SEX_LABEL'));
                $items['tx_cwtcommunityuser_sex']['helptext'] = htmlspecialchars($this->pi_getLL('CWT_PROFILE_EDIT_SEX_HELPTEXT'));
                $items['tx_cwtcommunityuser_sex']['error_msg'] = htmlspecialchars($this->pi_getLL('CWT_PROFILE_EDIT_SEX_ERRMSG'));
				//
				$cruser_id = $this->doGetLoggedInUserUID();
	   			$record_uid = $cruser_id;

                //Create form object
                $form = new tx_cwtfeedit_pi1($table, $items, $record_uid, $cruser_id, $GLOBALS["TSFE"]->id, array(), $this, array("cwt_community_user"));
                //Generate content
                $content.= "<table>";
                $content.= $form->getFormHeader();
                $content.= $form->getElement("username");
                $content.= $form->getElement("name");
                $content.= $form->getElement("password");
                $content.= $form->getElement("address");
#  mher: zip, city, country, company not in original
                $content.= $form->getElement("zip");
                $content.= $form->getElement("city");
                $content.= $form->getElement("country");
                $content.= $form->getElement("company");
                $content.= $form->getElement("telephone");
                $content.= $form->getElement("fax");
                $content.= $form->getElement("email");
                $content.= $form->getElement("www");
				$content.= $form->getElement("tx_cwtcommunityuser_image");
                $content.= $form->getElement("tx_cwtcommunityuser_sex");
                $content.= $form->getFormFooter();
                $content.= "</table>";
            }
        }
		
        //Return the generated content!
        return $this -> pi_wrapInBaseClass($content);
    }

    /* getViewUserlist($letter="a")
	*
	*  Displays an alphabetically list of frontend users.
	*
	*  @param $letter Only displays usernames with this as first letter.
	*  @return String The generated HTML source for this view.
	*/
    function getViewUserlist($users){
        // Init Vars
        $action = "getviewuserlist";
        $content = "";
        $cObj = $this -> cObj; //Holds a typo content object
        $templateCode = null; //Hold the template source code.
        $conf["subpartMarker"] = "VIEW_USERLIST"; //Holds a subpart marker.
        $subSub_1 = "RECORD";
        $subSub_1_alt = "RECORD_ALT"; //For alternating colors
        $subSub_2 = "LETTERS";
		$subSub_3 = "LISTHEADER";  // mher
        // Get the html source between subpart markers from template file
        $templateCode = $cObj -> getSubpart($this -> orig_templateCode, "###" . $conf["subpartMarker"] . "###");

        /*
		* HANDLE USER_RECORD
		*/ 
        // Init subpart content
        $subpartContent = null;
		//Alternating switch
		$switch = true;

        for ($i = 0; $i < sizeof($users); $i++) {
            // Determine if user is online
            if ($this->isUserActive($users[$i]['uid'])){
                $status = $this -> cObj -> cImage($this -> conf["icon_userlist_status_online"], array("alttext" => $this->pi_getLL("icon_userlist_status_online")));
            } else {
                $status = $this -> cObj -> cImage($this -> conf["icon_userlist_status_offline"], array("alttext" => $this->pi_getLL("icon_userlist_status_offline")));
            }
            //Get link to buddy list add
            $buddy_add = $this -> pi_getPageLink($this -> conf['pid_buddylist'], "", array("action" => "getviewbuddylistadd", "buddy_uid" => $users[$i]['uid']));
            $buddy_icon = $this -> cObj -> cImage($this -> conf["icon_userlist_addbuddy"], array("alttext" => $this->pi_getLL("icon_userlist_addbuddy")));
            //Get new message and message icon
            $messages_add = $this -> pi_getPageLink($this -> conf['pid_messages'], "", array("action" => "getviewmessagesnew", "recipient_uid" => $users[$i]['uid']));
            $messages_icon = $this -> cObj -> cImage($this -> conf["icon_messages_new"], array("alttext" => $this->pi_getLL("icon_messages_new")));
            // Get link to profile
            $linkToProfile = $this -> pi_getPageLink($this -> conf['pid_profile'], "", array("action" => "getviewprofile", "uid" => $users[$i]['uid']));
			
            // Create Marker Array
            $markerArray = array();
            $markerArray["###USERNAME###"] = $cObj -> stdWrap($users[$i]['username'], "");
            $markerArray["###SEX###"] = $this->doGetSexIcon($users[$i]['uid']);
            $markerArray["###CITY###"] = $cObj -> stdWrap($users[$i]['city'], "");
            $markerArray["###NAME###"] = $cObj -> stdWrap($users[$i]['name'], "");
            $markerArray["###COUNTRY###"] = $cObj -> stdWrap($users[$i]['country'], "");
	        $markerArray["###WWW###"] = $cObj->getTypoLink($users[$i]['www'],$users[$i]['www'], array(),'_blank');			
            $markerArray["###STATUS###"] = $status;
            $markerArray["###LINK_TO_PROFILE###"] = $cObj -> stdWrap($linkToProfile, "");
            $markerArray["###LINK_TO_BUDDYADD###"] = $cObj -> stdWrap($buddy_add, "");
            $markerArray["###BUDDYICON###"] = $cObj -> stdWrap($buddy_icon, "");
            $markerArray["###LINK_TO_MESSAGESADD###"] = $cObj -> stdWrap($messages_add, "");
            $markerArray["###MESSAGESICON###"] = $cObj -> stdWrap($messages_icon, "");
			$markerArray["###DETAILS###"] = $cObj -> stdWrap(htmlspecialchars($this->pi_getLL('CWT_USERLIST_DETAILS')), ""); // mher

            // Substitute the markers in the given sub sub part.
			if ($switch) {
			    $switch = false;
	            $subpartContent .= $cObj -> substituteMarkerArray($cObj -> getSubpart($templateCode, "###" . $subSub_1 . "###"), $markerArray);
			}
			else{
				$switch = true;
	            $subpartContent .= $cObj -> substituteMarkerArray($cObj -> getSubpart($templateCode, "###" . $subSub_1_alt . "###"), $markerArray);				
			}

        }
        // Substitute the template code with the given subpartcontent.
		$templateCode = $this -> cObj -> substituteSubpart($templateCode, $subSub_1_alt, "");		
        $templateCode = $this -> cObj -> substituteSubpart($templateCode, $subSub_1, $subpartContent);
		
        /*
		* HANDLE LETTERS
		*/ 
        // Init subpart content
        $subpartContent = null;
        // Create the row
        $row = null;
        $row .= $this -> pi_linkToPage("Alle", $GLOBALS['TSFE'] -> id, "", array("action" => "getviewuserlist")) . "&nbsp|&nbsp;";
        $row .= $this -> pi_linkToPage("A", $GLOBALS['TSFE'] -> id, "", array("action" => "getviewuserlist", "letter" => "a")) . "&nbsp|&nbsp;";
        $row .= $this -> pi_linkToPage("B", $GLOBALS['TSFE'] -> id, "", array("action" => "getviewuserlist", "letter" => "b")) . "&nbsp|&nbsp;";
        $row .= $this -> pi_linkToPage("C", $GLOBALS['TSFE'] -> id, "", array("action" => "getviewuserlist", "letter" => "c")) . "&nbsp|&nbsp;";
        $row .= $this -> pi_linkToPage("D", $GLOBALS['TSFE'] -> id, "", array("action" => "getviewuserlist", "letter" => "d")) . "&nbsp|&nbsp;";
        $row .= $this -> pi_linkToPage("E", $GLOBALS['TSFE'] -> id, "", array("action" => "getviewuserlist", "letter" => "e")) . "&nbsp|&nbsp;";
        $row .= $this -> pi_linkToPage("F", $GLOBALS['TSFE'] -> id, "", array("action" => "getviewuserlist", "letter" => "f")) . "&nbsp|&nbsp;";
        $row .= $this -> pi_linkToPage("G", $GLOBALS['TSFE'] -> id, "", array("action" => "getviewuserlist", "letter" => "g")) . "&nbsp|&nbsp;";
        $row .= $this -> pi_linkToPage("H", $GLOBALS['TSFE'] -> id, "", array("action" => "getviewuserlist", "letter" => "h")) . "&nbsp|&nbsp;";
        $row .= $this -> pi_linkToPage("I", $GLOBALS['TSFE'] -> id, "", array("action" => "getviewuserlist", "letter" => "i")) . "&nbsp|&nbsp;";
        $row .= $this -> pi_linkToPage("J", $GLOBALS['TSFE'] -> id, "", array("action" => "getviewuserlist", "letter" => "j")) . "&nbsp|&nbsp;";
        $row .= $this -> pi_linkToPage("K", $GLOBALS['TSFE'] -> id, "", array("action" => "getviewuserlist", "letter" => "k")) . "&nbsp|&nbsp;";
        $row .= $this -> pi_linkToPage("L", $GLOBALS['TSFE'] -> id, "", array("action" => "getviewuserlist", "letter" => "l")) . "&nbsp|&nbsp;";
        $row .= $this -> pi_linkToPage("M", $GLOBALS['TSFE'] -> id, "", array("action" => "getviewuserlist", "letter" => "m")) . "&nbsp|&nbsp;";
        $row .= $this -> pi_linkToPage("N", $GLOBALS['TSFE'] -> id, "", array("action" => "getviewuserlist", "letter" => "n")) . "&nbsp|&nbsp;";
        $row .= $this -> pi_linkToPage("O", $GLOBALS['TSFE'] -> id, "", array("action" => "getviewuserlist", "letter" => "o")) . "&nbsp|&nbsp;";
        $row .= $this -> pi_linkToPage("P", $GLOBALS['TSFE'] -> id, "", array("action" => "getviewuserlist", "letter" => "p")) . "&nbsp|&nbsp;";
        $row .= $this -> pi_linkToPage("Q", $GLOBALS['TSFE'] -> id, "", array("action" => "getviewuserlist", "letter" => "q")) . "&nbsp|&nbsp;";
        $row .= $this -> pi_linkToPage("R", $GLOBALS['TSFE'] -> id, "", array("action" => "getviewuserlist", "letter" => "r")) . "&nbsp|&nbsp;";
        $row .= $this -> pi_linkToPage("S", $GLOBALS['TSFE'] -> id, "", array("action" => "getviewuserlist", "letter" => "s")) . "&nbsp|&nbsp;";
        $row .= $this -> pi_linkToPage("T", $GLOBALS['TSFE'] -> id, "", array("action" => "getviewuserlist", "letter" => "t")) . "&nbsp|&nbsp;";
        $row .= $this -> pi_linkToPage("U", $GLOBALS['TSFE'] -> id, "", array("action" => "getviewuserlist", "letter" => "u")) . "&nbsp|&nbsp;";
        $row .= $this -> pi_linkToPage("V", $GLOBALS['TSFE'] -> id, "", array("action" => "getviewuserlist", "letter" => "v")) . "&nbsp|&nbsp;";
        $row .= $this -> pi_linkToPage("W", $GLOBALS['TSFE'] -> id, "", array("action" => "getviewuserlist", "letter" => "w")) . "&nbsp|&nbsp;";
        $row .= $this -> pi_linkToPage("X", $GLOBALS['TSFE'] -> id, "", array("action" => "getviewuserlist", "letter" => "x")) . "&nbsp|&nbsp;";
        $row .= $this -> pi_linkToPage("Y", $GLOBALS['TSFE'] -> id, "", array("action" => "getviewuserlist", "letter" => "y")) . "&nbsp|&nbsp;";
        $row .= $this -> pi_linkToPage("Z", $GLOBALS['TSFE'] -> id, "", array("action" => "getviewuserlist", "letter" => "z"));
        // Create Marker Array
        $markerArray = array();
        $markerArray["###ROW###"] = $cObj -> stdWrap($row, "");
        // Substitute the markers in the given sub sub part.
        $subpartContent .= $cObj -> substituteMarkerArray($cObj -> getSubpart($templateCode, "###" . $subSub_2 . "###"), $markerArray);
        // Substitute the template code with the given subpartcontent.
        $templateCode = $this -> cObj -> substituteSubpart($templateCode, $subSub_2, $subpartContent); 

        /*
		* HANDLE LISTHEADER (mher)
		*/ 
        // Init subpart content
        $subpartContent = null;
        // Create Marker Array
		$markerArray = array();
		$markerArray["###USERNAME_HEADER###"] = $cObj->stdWrap(htmlspecialchars($this->pi_getLL('CWT_USERLIST_USERNAME')), "");
        $markerArray["###NAME_HEADER###"] = $cObj->stdWrap(htmlspecialchars($this->pi_getLL('CWT_USERLIST_NAME')), "");
        $markerArray["###CITY_HEADER###"] = $cObj->stdWrap(htmlspecialchars($this->pi_getLL('CWT_USERLIST_CITY')), "");
        $markerArray["###COUNTRY_HEADER###"] = $cObj->stdWrap(htmlspecialchars($this->pi_getLL('CWT_USERLIST_COUNTRY')), "");
		$markerArray["###WWW_HEADER###"] = $cObj->stdWrap(htmlspecialchars($this->pi_getLL('CWT_PROFILE_WWW')), "");		
        // Substitute the markers in the given sub sub part.
		$subpartContent .= $cObj->substituteMarkerArray($cObj->getSubpart($templateCode, "###" . $subSub_3 . "###"), $markerArray);
        // Substitute the template code with the given subpartcontent.
        $templateCode = $this -> cObj -> substituteSubpart($templateCode, $subSub_3, $subpartContent); 

        // Return the generated content
        $content = $templateCode;
        return $content;
    } 

    /* getViewProfile($user)
	* 
	*  Displays the user profile page for a user.
	*
	*  @param $user Associative array with user attributes.
	*  @return String The generated HTML source for this view.
	*/
    function getViewProfile($user)
    {
        // Init Vars
        $action = "getviewprofile";
        $content = "";
        $cObj = $this -> cObj; //Holds a typo content object
        $templateCode = null; //Hold the template source code.
        $conf["subpartMarker"] = "VIEW_PROFILE"; //Holds a subpart marker.
        $subSub_1 = "RECORD";
        //Generate link to image
        $pathToImage = "uploads/tx_cwtcommunityuser/".$user['tx_cwtcommunityuser_image'];
        $lConf = $this->conf['imagePopup.'];
        $lConf['alttext']= "";

        // Get the html source between subpart markers from template file
        $templateCode = $cObj -> getSubpart($this -> orig_templateCode, "###" . $conf["subpartMarker"] . "###");

        /*
		* HANDLE USER
		*/
        // Determine if user is online
        if ($this->isUserActive($user['uid'])) {
              $status = $this -> cObj -> cImage($this -> conf["icon_userlist_status_online"], array("alttext" => $this->pi_getLL("icon_userlist_status_online")));
          } else {
              $status = $this -> cObj -> cImage($this -> conf["icon_userlist_status_offline"], array("alttext" => $this->pi_getLL("icon_userlist_status_offline")));
          }

        // Init subpart content
        $subpartContent = null; 

        // Create Marker Array
        $markerArray = array();
		# mher start
		$markerArray["###USERNAME_LABEL###"] = $cObj->stdWrap(htmlspecialchars($this->pi_getLL('CWT_PROFILE_USERNAME')), "");
		$markerArray["###STATUS_LABEL###"] = $cObj->stdWrap(htmlspecialchars($this->pi_getLL('CWT_PROFILE_STATUS')), "");
		$markerArray["###MEMBER_SINCE_LABEL###"] = $cObj->stdWrap(htmlspecialchars($this->pi_getLL('CWT_PROFILE_MEMBERSINCE')), "");
		$markerArray["###NAME_LABEL###"] = $cObj->stdWrap(htmlspecialchars($this->pi_getLL('CWT_PROFILE_NAME')), "");
		$markerArray["###COMPANY_LABEL###"] = $cObj->stdWrap(htmlspecialchars($this->pi_getLL('CWT_PROFILE_COMPANY')), "");
		$markerArray["###ADDRESS_LABEL###"] = $cObj->stdWrap(htmlspecialchars($this->pi_getLL('CWT_PROFILE_ADDRESS')), "");
		$markerArray["###PHONE_LABEL###"] = $cObj->stdWrap(htmlspecialchars($this->pi_getLL('CWT_PROFILE_PHONE')), "");
		$markerArray["###FAX_LABEL###"] = $cObj->stdWrap(htmlspecialchars($this->pi_getLL('CWT_PROFILE_FAX')), "");
		$markerArray["###EMAIL_LABEL###"] = $cObj->stdWrap(htmlspecialchars($this->pi_getLL('CWT_PROFILE_EMAIL')), "");
		$markerArray["###WWW_LABEL###"] = $cObj->stdWrap(htmlspecialchars($this->pi_getLL('CWT_PROFILE_WWW')), "");
		# mher end
        $markerArray["###USERNAME###"] = $cObj -> stdWrap($user['username'], "");
        $markerArray["###SEX###"] = $this->doGetSexIcon($user['uid']);		
		$markerArray["###COMPANY###"] = $cObj -> stdWrap($user['company'], ""); # mher
		$markerArray["###ADDRESS###"] = $cObj -> stdWrap($user['address'], "");
        $markerArray["###ZIP###"] = $cObj -> stdWrap($user['zip'], "");
        $markerArray["###CITY###"] = $cObj -> stdWrap($user['city'], "");
        $markerArray["###COUNTRY###"] = $cObj -> stdWrap($user['country'], "");
        $markerArray["###TELEPHONE###"] = $cObj -> stdWrap($user['telephone'], "");
        $markerArray["###FAX###"] = $cObj -> stdWrap($user['fax'], "");
        $markerArray["###EMAIL###"] = $cObj -> stdWrap($cObj->typolink($user['email'],array ('parameter' => $user['email'])), "");
        $markerArray["###WWW###"] = $cObj->getTypoLink($user['www'],$user['www'], array(),'_blank');
        $markerArray["###IMAGE###"] = $cObj -> cImage($pathToImage, $lConf);
        $markerArray["###STATUS###"] = $status;
        $markerArray["###NAME###"] = $cObj -> stdWrap($user['name'], "");
        $markerArray["###MEMBER_SINCE###"] = $cObj -> stdWrap(date($this->pi_getLL('CWT_DATE_FORMAT'), $user['crdate']), "");  # mher
        // Substitute the markers in the given sub sub part.
        $subpartContent .= $cObj -> substituteMarkerArray($cObj -> getSubpart($templateCode, "###" . $subSub_1 . "###"), $markerArray);
        // Substitute the template code with the given subpartcontent.
        $templateCode = $this -> cObj -> substituteSubpart($templateCode, $subSub_1, $subpartContent);
        // Return the generated content
        $content = $templateCode;
        return $content;
    }

    /* getViewGuestbook($guestbook,$uid)
	*
	*  Displays the user profile page for a user.
	*
	*  @param $guestbook
	*  @param fe user uid
	*  @return String The generated HTML source for this view.
	*/
    function getViewGuestbook($guestbook, $uid)
    {
        // Init Vars
        $action = "getviewguestbook";
        $content = "";
        $cObj = $this -> cObj; //Holds a typo content object
        $templateCode = null; //Hold the template source code.
        $conf["subpartMarker"] = "VIEW_GUESTBOOK"; //Holds a subpart marker.
        $subSub_1 = "RECORD";
		$subSub_1_alt = "RECORD_ALT";
        $subSub_2 = "ADDITIONAL";
        // Get the html source between subpart markers from template file
        $templateCode = $cObj -> getSubpart($this -> orig_templateCode, "###" . $conf["subpartMarker"] . "###");

        /*
		* HANDLE RECORD
		*/
        // Init subpart content
        $subpartContent = null;
		
		//Switch for alternating colors
		$switch = false;

        for ($i = 0; $i < sizeof($guestbook); $i++) {
            // Get creation date and time
            $creationDate = date($this->pi_getLL('CWT_DATE_FORMAT'), $guestbook[$i]['crdate']);
            $creationTime = date($this->pi_getLL('CWT_TIME_FORMAT'), $guestbook[$i]['crdate']);
            // Get link to profile
            $linkToProfile = $this -> pi_getPageLink($this -> conf['pid_profile'], "", array("action" => "getviewprofile", "uid" => $guestbook[$i]['cruser_id']));
            // Create Marker Array
            $markerArray = array();
            $markerArray["###USERNAME###"] = $cObj -> stdWrap($guestbook[$i]['username'], "");
            $markerArray["###SEX###"] = $this->doGetSexIcon($guestbook[$i]['uid']);			
            $markerArray["###CREATION_DATE###"] = $cObj -> stdWrap($creationDate, "");
            $markerArray["###CREATION_TIME###"] = $cObj -> stdWrap($creationTime, "");
            $markerArray["###TEXT###"] = $cObj -> stdWrap($this->parseIcons($guestbook[$i]['text']), "");
            $markerArray["###LINK_TO_PROFILE###"] = $cObj -> stdWrap($linkToProfile, "");

            // Substitute the markers in the given sub sub part.
			if ($switch) {
			    $switch = false;
	            $subpartContent .= $cObj -> substituteMarkerArray($cObj -> getSubpart($templateCode, "###" . $subSub_1 . "###"), $markerArray);                        
			}
			else{
				$switch = true;			
	            $subpartContent .= $cObj -> substituteMarkerArray($cObj -> getSubpart($templateCode, "###" . $subSub_1_alt . "###"), $markerArray);				
			}	
        }
						
        // Substitute the template code with the given subpartcontent.
		$templateCode = $this -> cObj -> substituteSubpart($templateCode, $subSub_1_alt, "");
        $templateCode = $this -> cObj -> substituteSubpart($templateCode, $subSub_1, $subpartContent);



        /*
		* HANDLE ADDITIONAL
		*/
        // Init subpart content
        $subpartContent = null;
        // Get link to add new guestbook record
        $linkToGuestbookAdd = $this -> pi_getPageLink($GLOBALS['TSFE'] -> id, "", array("action" => "getviewguestbookadd", "uid" => $uid));
        // Create Marker Array
        $markerArray = array();
        $markerArray["###LINK_TO_GUESTBOOK_ADD###"] = $cObj -> stdWrap($linkToGuestbookAdd, "");
		$markerArray["###ADD_TO_GUESTBOOK_LINKTEXT###"] = $cObj->stdWrap(htmlspecialchars($this->pi_getLL('CWT_GUESTBOOK_NEWENTRY')), ""); # mher
        // Substitute the markers in the given sub sub part.
        $subpartContent .= $cObj -> substituteMarkerArray($cObj -> getSubpart($templateCode, "###" . $subSub_2 . "###"), $markerArray);
        // Substitute the template code with the given subpartcontent.
        $templateCode = $this -> cObj -> substituteSubpart($templateCode, $subSub_2, $subpartContent);
        // Return the generated content
        $content = $templateCode;
        return $content;
    }

    /* getViewGuestbookAdd($uid)
	*
	*  Displays the user profile page for a user.
	*
	*  @param $uid fe user uid
	*  @return String The generated HTML source for this view.
	*/
    function getViewGuestbookAdd($uid)
    {
        // Init Vars
        $action = "getviewguestbookdd";
        $content = "";
        $cObj = $this -> cObj; //Holds a typo content object
        $templateCode = null; //Hold the template source code.
        $conf["subpartMarker"] = "VIEW_GUESTBOOK_ADD"; //Holds a subpart marker.
        $subSub_1 = "EDIT";
        // Get the html source between subpart markers from template file
        $templateCode = $cObj -> getSubpart($this -> orig_templateCode, "###" . $conf["subpartMarker"] . "###");

        /*
		* HANDLE EDIT
		*/
        // Init subpart content
        $subpartContent = null;
        // Generate HTML
        $form_action = $this -> pi_getPageLink($GLOBALS['TSFE'] -> id, "_self", array("action" => "getviewguestbookadd", "uid" => $uid));
        $form_text_name = $this -> prefixId . "[text]";
        $form_text_value = htmlspecialchars($this -> piVars["text"]);
        $form_submit_button_name = $this -> prefixId . "[submit_button]";
        $form_submit_button_value = htmlspecialchars($this -> pi_getLL("FORM_SUBMIT_BUTTON"));
        $form_cancel_button_name = $this -> prefixId . "[cancel_button]";
        $form_cancel_button_value = htmlspecialchars($this -> pi_getLL("FORM_CANCEL_BUTTON"));
        $form_errormsg = $errormsg;
        // Create Marker Array
        $markerArray = array();
        $markerArray["###FORM_HEADER###"] = $cObj -> stdWrap(htmlspecialchars($this->pi_getLL('CWT_GUESTBOOK_NEWENTRY')), ""); # mher
        $markerArray["###FORM_ACTION###"] = $cObj -> stdWrap($form_action, "");
        $markerArray["###FORM_TEXT_NAME###"] = $cObj -> stdWrap($form_text_name, "");
        $markerArray["###FORM_TEXT_VALUE###"] = $cObj -> stdWrap($form_text_value, "");
        $markerArray["###FORM_SUBMIT_BUTTON_NAME###"] = $cObj -> stdWrap($form_submit_button_name, "");
        $markerArray["###FORM_SUBMIT_BUTTON_VALUE###"] = $cObj -> stdWrap($form_submit_button_value, "");
        $markerArray["###FORM_CANCEL_BUTTON_NAME###"] = $cObj -> stdWrap($form_cancel_button_name, "");
        $markerArray["###FORM_CANCEL_BUTTON_VALUE###"] = $cObj -> stdWrap($form_cancel_button_value, "");
        $markerArray["###FORM_ERRORMSG###"] = $cObj -> stdWrap($form_errormsg, "");
        // Substitute the markers in the given sub sub part.
        $subpartContent .= $cObj -> substituteMarkerArray($cObj -> getSubpart($templateCode, "###" . $subSub_1 . "###"), $markerArray);
        // Substitute the template code with the given subpartcontent.
        $templateCode = $this -> cObj -> substituteSubpart($templateCode, $subSub_1, $subpartContent);
        // Return the generated content
        $content = $templateCode;
        return $content;
    }

    /* getViewGuestbook($guestbook,$uid)
	*
	*  Displays the user profile page for a user.
	*
	*  @param $guestbook
	*  @param fe user uid
	*  @return String The generated HTML source for this view.
	*/
    function getViewGuestbookAddResult($uid)
    {
        // Init Vars
        $action = "getviewguestbookaddresult";
        $content = "";
        $cObj = $this -> cObj; //Holds a typo content object
        $templateCode = null; //Hold the template source code.
        $conf["subpartMarker"] = "VIEW_GUESTBOOK_ADD_RESULT"; //Holds a subpart marker.
        $subSub_1 = "ADDITIONAL";

        // Get the html source between subpart markers from template file
        $templateCode = $cObj -> getSubpart($this -> orig_templateCode, "###" . $conf["subpartMarker"] . "###");

        /*
		* HANDLE ADDITIONAL
		*/
        // Init subpart content
        $subpartContent = null;
        // Get link to profile
        $linkToProfile = $this -> pi_getPageLink($this -> conf['pid_profile'], "", array("action" => "getviewprofile", "uid" => $uid));
        // Create Marker Array
        $markerArray = array();
        $markerArray["###LINK_TO_PROFILE###"] = $cObj -> stdWrap($linkToProfile, "");
		$markerArray["###MSG###"] = $cObj->stdWrap(htmlspecialchars($this->pi_getLL('CWT_GUESTBOOK_ENTRYADDED')), ""); # mher
		$markerArray["###BACK###"] = $cObj->stdWrap(htmlspecialchars($this->pi_getLL('CWT_GUESTBOOK_BACK')), ""); # mher
        // Substitute the markers in the given sub sub part.
        $subpartContent .= $cObj -> substituteMarkerArray($cObj -> getSubpart($templateCode, "###" . $subSub_1 . "###"), $markerArray);
        // Substitute the template code with the given subpartcontent.
        $templateCode = $this -> cObj -> substituteSubpart($templateCode, $subSub_1, $subpartContent);
        // Return the generated content
        $content = $templateCode;
        return $content;
    }

	/* getViewGuestbookDisabled()
	*
	*  Display a message page
	*
	*  @return String The generated HTML source for this view.
	*/
    function getViewGuestbookDisabled()
    {
        // Init Vars
        $action = "getviewguestbookdisabled";
        $content = "";
        $cObj = $this -> cObj; //Holds a typo content object
        $templateCode = null; //Hold the template source code.
        $conf["subpartMarker"] = "VIEW_GUESTBOOK_DISABLED"; //Holds a subpart marker.
        $subSub_1 = "ADDITIONAL";  # mher

        // Get the html source between subpart markers from template file
        $templateCode = $cObj -> getSubpart($this -> orig_templateCode, "###" . $conf["subpartMarker"] . "###");
		
        /*
		* HANDLE ADDITIONAL   mher
		*/
        // Init subpart content
        $subpartContent = null;
        $markerArray = array();
		$markerArray["###MSG###"] = $cObj->stdWrap(htmlspecialchars($this->pi_getLL('CWT_GUESTBOOK_DISABLEDMSG')), ""); # mher
        // Substitute the markers in the given sub sub part.
        $subpartContent .= $cObj -> substituteMarkerArray($cObj -> getSubpart($templateCode, "###" . $subSub_1 . "###"), $markerArray);
        // Substitute the template code with the given subpartcontent.
        $templateCode = $this -> cObj -> substituteSubpart($templateCode, $subSub_1, $subpartContent);

		// Return the generated content
        $content = $templateCode;
        return $content;
    } 

    /* getViewGuestbookLoggedIn($uid, $isLocked, $guestbook)
	*
	*  Displays the guestbook view for a logged in fe users' guestbook. In this view
	*  the fe user is able to enable/ disable his/her guestbook. Furthermore this view
	*  provides the feature to delete specific guestbook items.
	*
	*  @param fe user uid, currently logged in.
	*  @param $isLocked Boolean. True if Guestbook is locked/ False, if guestbook is open.
	*  @param $guestbook guestbook item from function
	*  @return String The generated HTML source for this view.
	*/
    function getViewGuestbookLoggedIn($uid, $isLocked, $guestbook){
        // Init Vars
        $action = "getviewguestbookloggedin";
        $content = "";
        $cObj = $this -> cObj; //Holds a typo content object
        $templateCode = null; //Hold the template source code.
        $conf["subpartMarker"] = "VIEW_GUESTBOOK_LOGGED_IN"; //Holds a subpart marker.
        $subSub_1 = "DEACTIVATE";
        $subSub_2 = "ACTIVATE";
		$subSub_3 = "RECORD";
		$subSub_3_alt = "RECORD_ALT";
		$subSub_4 = "ADDITIONAL";

        // Get the html source between subpart markers from template file
        $templateCode = $cObj -> getSubpart($this -> orig_templateCode, "###" . $conf["subpartMarker"] . "###");

        /*
		* HANDLE CLOSE_GUESTBOOK / OPEN_GUESTBOOK
		*/
        // Init subpart content
        $subpartContent = null;

		//Create button html
		//Display open button
		if ($isLocked) {
	        $form_action = $this -> pi_getPageLink($GLOBALS['TSFE'] -> id, "_self", array("action" => "getviewguestbookloggedin", "uid" => $uid));
			$open_guestbook_button_name = $this->prefixId."[open_guestbook]";
			$open_guestbook_button_value = htmlspecialchars($this -> pi_getLL("OPEN_GUESTBOOK_BUTTON"));

    	    // Create Marker Array
	        $markerArray = array();
	        $markerArray["###FORM_ACTION###"] = $cObj -> stdWrap($form_action, "");
        	$markerArray["###OPEN_GUESTBOOK_BUTTON_NAME###"] = $cObj -> stdWrap($open_guestbook_button_name, "");
    	    $markerArray["###OPEN_GUESTBOOK_BUTTON_VALUE###"] = $cObj -> stdWrap($open_guestbook_button_value, "");
			$markerArray["###INACTIVE_MSG###"] = $cObj->stdWrap(htmlspecialchars($this->pi_getLL('CWT_GUESTBOOK_INACTIVEMSG')), ""); # mher
	        // Substitute the markers in the given sub sub part.
        	$subpartContent .= $cObj -> substituteMarkerArray($cObj -> getSubpart($templateCode, "###" . $subSub_2 . "###"), $markerArray);
    	    // Substitute the template code with the given subpartcontent.
			$templateCode = $this -> cObj -> substituteSubpart($templateCode, $subSub_1, "");
	        $templateCode = $this -> cObj -> substituteSubpart($templateCode, $subSub_2, $subpartContent);
		}
		//Display Lock button
		else{
	        $form_action = $this -> pi_getPageLink($GLOBALS['TSFE'] -> id, "_self", array("action" => "getviewguestbookloggedin", "uid" => $uid));
			$lock_guestbook_button_name = $this->prefixId."[lock_guestbook]";
			$lock_guestbook_button_value = htmlspecialchars($this -> pi_getLL("LOCK_GUESTBOOK_BUTTON"));

    	    // Create Marker Array
	        $markerArray = array();
	        $markerArray["###FORM_ACTION###"] = $cObj -> stdWrap($form_action, "");
        	$markerArray["###LOCK_GUESTBOOK_BUTTON_NAME###"] = $cObj -> stdWrap($lock_guestbook_button_name, "");
    	    $markerArray["###LOCK_GUESTBOOK_BUTTON_VALUE###"] = $cObj -> stdWrap($lock_guestbook_button_value, "");
			$markerArray["###ACTIVE_MSG###"] = $cObj->stdWrap(htmlspecialchars($this->pi_getLL('CWT_GUESTBOOK_ACTIVEMSG')), ""); # mher
	        // Substitute the markers in the given sub sub part.
        	$subpartContent .= $cObj -> substituteMarkerArray($cObj -> getSubpart($templateCode, "###" . $subSub_1 . "###"), $markerArray);
    	    // Substitute the template code with the given subpartcontent.
	        $templateCode = $this -> cObj -> substituteSubpart($templateCode, $subSub_1, $subpartContent);
			$templateCode = $this -> cObj -> substituteSubpart($templateCode, $subSub_2, "");
		}

        /*
		* HANDLE RECORD
		*/
        // Init subpart content
        $subpartContent = null;
		
		//Switch for alternating colors
		$switch = false;

        for ($i = 0; $i < sizeof($guestbook); $i++) {
            // Get creation date and time
			$creationDate = date($this->pi_getLL('CWT_DATE_FORMAT'), $guestbook[$i]['crdate']);
			$creationTime = date($this->pi_getLL('CWT_TIME_FORMAT'), $guestbook[$i]['crdate']);
            // Get link to profile
            $linkToProfile = $this -> pi_getPageLink($this -> conf['pid_profile'], "", array("action" => "getviewprofile", "uid" => $guestbook[$i]['cruser_id']));
			//Get link to delete item
			$linkToDeleteItem = $this -> pi_getPageLink($this -> conf['pid_profile'], "", array("action" => "getviewguestbookdeleteitem", "uid" => $uid, "item" => $guestbook[$i]['uid']));
			//Delete Icon
			$delete_icon = $this->cObj->cImage($this->conf['icon_guestbook_delete'], array("alttext" => $this->pi_getLL("icon_guestbook_delete")));

            // Create Marker Array
            $markerArray = array();
            $markerArray["###USERNAME###"] = $cObj -> stdWrap($guestbook[$i]['username'], "");
            $markerArray["###CREATION_DATE###"] = $cObj -> stdWrap($creationDate, "");
            $markerArray["###CREATION_TIME###"] = $cObj -> stdWrap($creationTime, "");
            $markerArray["###TEXT###"] = $cObj -> stdWrap($this->parseIcons($guestbook[$i]['text']), "");
            $markerArray["###LINK_TO_PROFILE###"] = $cObj -> stdWrap($linkToProfile, "");
            $markerArray["###LINK_TO_DELETE_ITEM###"] = $cObj -> stdWrap($linkToDeleteItem, "");
            $markerArray["###DELETE_ICON###"] = $cObj -> stdWrap($delete_icon, "");
            
			// Substitute the markers in the given sub sub part.
			if ($switch) {
			    $switch = false;
	            $subpartContent .= $cObj -> substituteMarkerArray($cObj -> getSubpart($templateCode, "###" . $subSub_3 . "###"), $markerArray);                        
			}
			else{
				$switch = true;			
	            $subpartContent .= $cObj -> substituteMarkerArray($cObj -> getSubpart($templateCode, "###" . $subSub_3_alt . "###"), $markerArray);				
			}	
        }
        // Substitute the template code with the given subpartcontent.
        $templateCode = $this -> cObj -> substituteSubpart($templateCode, $subSub_3_alt, "");		
        $templateCode = $this -> cObj -> substituteSubpart($templateCode, $subSub_3, $subpartContent);

        /*
		* HANDLE ADDITIONAL
		*/
        // Init subpart content
        $subpartContent = null;
        // Get link to profile
        $linkToDeleteAll = $this->pi_getPageLink($this -> conf['pid_profile'], "", array("action" => "getviewguestbookdeleteall", "uid" => $uid));
        // Create Marker Array
        $markerArray = array();
        $markerArray["###LINK_TO_DELETE_ALL###"] = $cObj -> stdWrap($linkToDeleteAll, "");
        $markerArray["###DELETE_ALL_TEXT###"] = $cObj -> stdWrap(htmlspecialchars($this->pi_getLL('CWT_GUESTBOOK_DELETEALL')), ""); # mher
        // Substitute the markers in the given sub sub part.
        $subpartContent .= $cObj -> substituteMarkerArray($cObj -> getSubpart($templateCode, "###" . $subSub_4 . "###"), $markerArray);
        // Substitute the template code with the given subpartcontent.
        $templateCode = $this -> cObj -> substituteSubpart($templateCode, $subSub_4, $subpartContent);

        // Return the generated content
        $content = $templateCode;
        return $content;
    }

    /* getViewBuddylist($buddylist)
    *
    *  Displays the buddylist of a fe user.
    *
    *  @param $guestbook guestbook item from function
    *  @return String The generated HTML source for this view.
    */
    function getViewBuddylist($buddylist){
        // Init Vars
        $action = "getviewbuddylist";
        $content = "";
        $cObj = $this -> cObj; //Holds a typo content object
        $templateCode = null; //Hold the template source code.
        $conf["subpartMarker"] = "VIEW_BUDDYLIST"; //Holds a subpart marker.
        $subSub_1 = "RECORD";
        $subSub_2 = "ADDITIONAL";


        // Get the html source between subpart markers from template file
        $templateCode = $cObj -> getSubpart($this -> orig_templateCode, "###" . $conf["subpartMarker"] . "###");

        /*
        * HANDLE RECORD
        */
        // Init subpart content
        $subpartContent = null;

        for ($i = 0; $i < sizeof($buddylist); $i++) {
            // Get link to profile
            $linkToProfile = $this -> pi_getPageLink($this -> conf['pid_profile'], "", array("action" => "getviewprofile", "uid" => $buddylist[$i]['buddy_uid']));
            //Get link to delete item
            $linkToDeleteItem = $this -> pi_getPageLink($this -> conf['pid_buddylist'], "", array("action" => "getviewbuddylistdelete", "buddy_uid" => $buddylist[$i]['buddy_uid']));
            //Delete Icon
            $delete_icon = $this->cObj->cImage($this->conf['icon_guestbook_delete'], array("alttext" => $this->pi_getLL("icon_buddylist_delete")));
            //Get new message and message icon
            $messages_add = $this -> pi_getPageLink($this -> conf['pid_messages'], "", array("action" => "getviewmessagesnew", "recipient_uid" => $buddylist[$i]['buddy_uid']));
            $messages_icon = $this -> cObj -> cImage($this -> conf["icon_messages_new"], array("alttext" => $this->pi_getLL("icon_messages_new")));
            // Determine if user is online
            $online_uids = $this->doGetOnlineUids();
            $isOnline = null;
            $needle = $buddylist[$i]['buddy_uid'];
            if (in_array($needle, $online_uids)) {
	              $status = $this -> cObj -> cImage($this -> conf["icon_userlist_status_online"], array("alttext" => $this->pi_getLL("icon_userlist_status_online")));
	          } else {
	              $status = $this -> cObj -> cImage($this -> conf["icon_userlist_status_offline"], array("alttext" => $this->pi_getLL("icon_userlist_status_offline")));
	          }

            // Create Marker Array
            $markerArray = array();
            $markerArray["###USERNAME###"] = $cObj -> stdWrap($buddylist[$i]['username'], "");
	        $markerArray["###SEX###"] = $this->doGetSexIcon($buddylist[$i]['uid']);					
            $markerArray["###STATUS###"] = $status;
            $markerArray["###LINK_TO_PROFILE###"] = $cObj -> stdWrap($linkToProfile, "");
            $markerArray["###LINK_TO_DELETE_ITEM###"] = $cObj -> stdWrap($linkToDeleteItem, "");
            $markerArray["###DELETE_ICON###"] = $cObj -> stdWrap($delete_icon, "");
            $markerArray["###LINK_TO_MESSAGESADD###"] = $cObj -> stdWrap($messages_add, "");
            $markerArray["###MESSAGESICON###"] = $cObj -> stdWrap($messages_icon, "");
            // Substitute the markers in the given sub sub part.
            $subpartContent .= $cObj -> substituteMarkerArray($cObj -> getSubpart($templateCode, "###" . $subSub_1 . "###"), $markerArray);
        }
        // Substitute the template code with the given subpartcontent.
        $templateCode = $this -> cObj -> substituteSubpart($templateCode, $subSub_1, $subpartContent);

        /*
		* HANDLE ADDITIONAL mher
		*/
        // Init subpart content
        $subpartContent = null;
        // Create Marker Array
        $markerArray = array();
        $markerArray["###HEADER###"] = $cObj -> stdWrap(htmlspecialchars($this->pi_getLL('CWT_BUDDYLIST_HEADER')), ""); # mher
        // Substitute the markers in the given sub sub part.
        $subpartContent .= $cObj -> substituteMarkerArray($cObj -> getSubpart($templateCode, "###" . $subSub_2 . "###"), $markerArray);
        // Substitute the template code with the given subpartcontent.
        $templateCode = $this -> cObj -> substituteSubpart($templateCode, $subSub_2, $subpartContent);

        // Return the generated content
        $content = $templateCode;
        return $content;
    }

    /* getViewMessages($messages)
    *
    *  Displays the message box of a user.
    *
    *  @param $messages Array of messages.
    *  @return String The generated HTML source for this view.
    */
    function getViewMessages($messages){
        // Init Vars
        $action = "getviewmessages";
        $content = "";
        $cObj = $this -> cObj; //Holds a typo content object
        $templateCode = null; //Hold the template source code.
        $conf["subpartMarker"] = "VIEW_MESSAGES"; //Holds a subpart marker.
        $subSub_1 = "RECORD";
        $subSub_1_alt = "RECORD_ALT";		
        $subSub_2 = "ADDITIONAL";


        // Get the html source between subpart markers from template file
        $templateCode = $cObj -> getSubpart($this -> orig_templateCode, "###" . $conf["subpartMarker"] . "###");

        /*
        * HANDLE RECORD
        */
        // Init subpart content
        $subpartContent = null;
		
		//Switch for alternating colors
		$switch = true;
		
        for ($i = 0; $i < sizeof($messages); $i++) {
            // Get link to profile
            $linkToProfile = $this -> pi_getPageLink($this -> conf['pid_profile'], "", array("action" => "getviewprofile", "uid" => $messages[$i]['cruser_id']));
            //Get link to delete item
            $linkToDeleteItem = $this -> pi_getPageLink($GLOBALS['TSFE']->id, "", array("action" => "getviewmessagesdelete", "msg_uid" => $messages[$i]['uid']));
            //Get link to single message view
            $linkToSingleMessage = $this -> pi_getPageLink($GLOBALS['TSFE']->id, "", array("action" => "getviewmessagessingle", "msg_uid" => $messages[$i]['uid']));
            //Get new message and message icon
            $messages_answer = $this -> pi_getPageLink($this -> conf['pid_messages'], "", array("action" => "getviewmessagesnew", "recipient_uid" => $messages[$i]['cruser_id'], "answer_uid" =>$messages[$i]['uid']));
            $messages_answer_icon = $this -> cObj -> cImage($this -> conf["icon_messages_answer"], array("alttext" => $this->pi_getLL("icon_messages_answer")));

            //Delete Icon
            $delete_icon = $this->cObj->cImage($this->conf['icon_guestbook_delete'], array("alttext" => $this->pi_getLL("icon_messages_delete")));
            //Get Status icon
            if ($messages[$i]['status'] == "0"){
                //UNREAD ICON
                $status_icon = $this->cObj->cImage($this->conf['icon_messages_unread'], array("alttext" => $this->pi_getLL("icon_messages_unread")));
            }
            elseif ($messages[$i]['status'] == "1"){
                //READ ICON
                $status_icon = $this->cObj->cImage($this->conf['icon_messages_read'], array("alttext" => $this->pi_getLL("icon_messages_read")));
            }

            // Create Marker Array
            $markerArray = array();
            $markerArray["###USERNAME###"] = $cObj -> stdWrap($messages[$i]['username'], "");
            $markerArray["###SEX###"] = $this->doGetSexIcon($messages[$i]['uid']);			
            $markerArray["###DATE###"] = $cObj -> stdWrap(date("d.m.Y", $messages[$i]['crdate']), "");
            $markerArray["###SUBJECT###"] = $cObj -> stdWrap($messages[$i]['subject'], "");
            $markerArray["###STATUS_ICON###"] = $cObj -> stdWrap($status_icon, "");
            $markerArray["###LINK_TO_PROFILE###"] = $cObj -> stdWrap($linkToProfile, "");
            $markerArray["###LINK_TO_DELETE_ITEM###"] = $cObj -> stdWrap($linkToDeleteItem, "");
            $markerArray["###LINK_TO_SINGLE_MESSAGE###"] = $cObj -> stdWrap($linkToSingleMessage, "");
            $markerArray["###DELETE_ICON###"] = $cObj -> stdWrap($delete_icon, "");
            $markerArray["###LINK_TO_MESSAGESANSWER###"] = $cObj -> stdWrap($messages_answer, "");
            $markerArray["###MESSAGESANSWERICON###"] = $cObj -> stdWrap($messages_answer_icon, "");

            // Substitute the markers in the given sub sub part.
			if ($switch) {
			    $switch = false;
	            $subpartContent .= $cObj -> substituteMarkerArray($cObj -> getSubpart($templateCode, "###" . $subSub_1 . "###"), $markerArray);
			}
			else{
				$switch = true;			
	            $subpartContent .= $cObj -> substituteMarkerArray($cObj -> getSubpart($templateCode, "###" . $subSub_1_alt . "###"), $markerArray);				
			}

        }
        // Substitute the template code with the given subpartcontent.
		$templateCode = $this -> cObj -> substituteSubpart($templateCode, $subSub_1_alt, "");
        $templateCode = $this -> cObj -> substituteSubpart($templateCode, $subSub_1, $subpartContent);


        /*
		* HANDLE ADDITIONAL mher
		*/
        // Init subpart content
        $subpartContent = null;
        // Create Marker Array
        $markerArray = array();
        $markerArray["###SUBJECT_HEADER###"] = $cObj -> stdWrap(htmlspecialchars($this->pi_getLL('CWT_MESSAGES_SUBJECT')), ""); # mher
        $markerArray["###DATE_HEADER###"] = $cObj -> stdWrap(htmlspecialchars($this->pi_getLL('CWT_MESSAGES_DATE')), ""); # mher
        $markerArray["###FROM_HEADER###"] = $cObj -> stdWrap(htmlspecialchars($this->pi_getLL('CWT_MESSAGES_FROM')), ""); # mher
        // Substitute the markers in the given sub sub part.
        $subpartContent .= $cObj -> substituteMarkerArray($cObj -> getSubpart($templateCode, "###" . $subSub_2 . "###"), $markerArray);
        // Substitute the template code with the given subpartcontent.
        $templateCode = $this -> cObj -> substituteSubpart($templateCode, $subSub_2, $subpartContent);

        // Return the generated content
        $content = $templateCode;
        return $content;
    }

    /* getViewMessagesSingle($message)
    *
    *  Displays the message box of a user.
    *
    *  @param $uid Session user's uid
    *  @param $message One message
    *  @return String The generated HTML source for this view.
    */
    function getViewMessagesSingle($uid, $message){
        // Init Vars
        $action = "getviewmessagessingle";
        $content = "";
        $cObj = $this -> cObj; //Holds a typo content object
        $templateCode = null; //Hold the template source code.
        $conf["subpartMarker"] = "VIEW_MESSAGES_SINGLE"; //Holds a subpart marker.
        $subSub_1 = "RECORD";
        $subSub_2 = "ADDITIONAL";

        // Get the html source between subpart markers from template file
        $templateCode = $cObj -> getSubpart($this -> orig_templateCode, "###" . $conf["subpartMarker"] . "###");

        /*
        * HANDLE RECORD
        */
        // Init subpart content
        $subpartContent = null;

        // Get link to profile
        $linkToProfile = $this -> pi_getPageLink($this -> conf['pid_profile'], "", array("action" => "getviewprofile", "uid" => $message['cruser_id']));
        //Get link to delete item
        $linkToDeleteItem = $this -> pi_getPageLink($GLOBALS['TSFE']->id, "", array("action" => "getviewmessagesdelete", "msg_uid" => $message['uid']));
        //Delete Icon
        $delete_icon = $this->cObj->cImage($this->conf['icon_guestbook_delete'], array("alttext" => $this->pi_getLL("icon_messages_delete")));
        // Get link to messages
        $linkToMessages = $this -> pi_getPageLink($GLOBALS['TSFE']->id, "", array("action" => "getviewmessages", "uid" => $uid));
        //Get new message and message icon
        $messages_answer = $this -> pi_getPageLink($this -> conf['pid_messages'], "", array("action" => "getviewmessagesnew", "recipient_uid" => $message['cruser_id'], "answer_uid" =>$message['uid']));
        $messages_answer_icon = $this -> cObj -> cImage($this -> conf["icon_messages_answer"], array("alttext" => $this->pi_getLL("icon_messages_answer")));

        // Create Marker Array
        $markerArray = array();
		$markerArray["###FROM_LABEL###"] = $cObj->stdWrap(htmlspecialchars($this->pi_getLL('CWT_MESSAGES_FROM')), ""); # mher
        $markerArray["###USERNAME###"] = $cObj -> stdWrap($message['username'], "");
		$markerArray["###DATE_LABEL###"] = $cObj->stdWrap(htmlspecialchars($this->pi_getLL('CWT_MESSAGES_DATE')), ""); # mher
        $markerArray["###DATE###"] = $cObj -> stdWrap(date($this->pi_getLL('CWT_DATE_FORMAT'), $message['crdate']), "");
		$markerArray["###SUBJECT_LABEL###"] = $cObj->stdWrap(htmlspecialchars($this->pi_getLL('CWT_MESSAGES_SUBJECT')), ""); # mher
        $markerArray["###SUBJECT###"] = $cObj -> stdWrap($message['subject'], "");
		$markerArray["###BODY_LABEL###"] = $cObj->stdWrap(htmlspecialchars($this->pi_getLL('CWT_MESSAGES_BODY')), ""); # mher
        $markerArray["###BODY###"] = $cObj -> stdWrap($this->parseIcons($message['body']), "");
		$markerArray["###BACK###"] = $cObj->stdWrap(htmlspecialchars($this->pi_getLL('CWT_MESSAGES_BACK')), ""); # mher
        $markerArray["###LINK_TO_PROFILE###"] = $cObj -> stdWrap($linkToProfile, "");
        $markerArray["###LINK_TO_DELETE_ITEM###"] = $cObj -> stdWrap($linkToDeleteItem, "");
        $markerArray["###LINK_TO_MESSAGES###"] = $cObj -> stdWrap($linkToMessages, "");
        $markerArray["###DELETE_ICON###"] = $cObj -> stdWrap($delete_icon, "");
		$markerArray["###DELETE_TEXT###"] = $cObj->stdWrap(htmlspecialchars($this->pi_getLL('CWT_MESSAGES_DELETE')), ""); # mher
        $markerArray["###LINK_TO_MESSAGESANSWER###"] = $cObj -> stdWrap($messages_answer, "");
        $markerArray["###MESSAGESANSWERICON###"] = $cObj -> stdWrap($messages_answer_icon, "");
		$markerArray["###ANSWER_TEXT###"] = $cObj->stdWrap(htmlspecialchars($this->pi_getLL('CWT_MESSAGES_ANSWER')), ""); # mher

        // Substitute the markers in the given sub sub part.
        $subpartContent .= $cObj -> substituteMarkerArray($cObj -> getSubpart($templateCode, "###" . $subSub_1 . "###"), $markerArray);

        // Substitute the template code with the given subpartcontent.
        $templateCode = $this -> cObj -> substituteSubpart($templateCode, $subSub_1, $subpartContent);

        // Return the generated content
        $content = $templateCode;
        return $content;
    }

    /* getViewMessagesNew($uid, $recipient_uid, $subject, $body = null)
    *
    *  Displays a form for sending messages.
    *
    *  @param $uid fe user uid
    *  @param $recipient_uid fe_users uid, of the recipient.
    *  @param $subject In case of an answer this subject is used.
    *  @param $body In case of an answer this body is used.
    *  @return String The generated HTML source for this view.
    */
    function getViewMessagesNew($uid, $recipient_uid, $subject, $body = null)
    {
        // Init Vars
        $action = "getviewmessagesnew";
        $content = "";
        $cObj = $this -> cObj; //Holds a typo content object
        $templateCode = null; //Hold the template source code.
        $conf["subpartMarker"] = "VIEW_MESSAGES_NEW"; //Holds a subpart marker.
        $subSub_1 = "EDIT";
        // Get the html source between subpart markers from template file
        $templateCode = $cObj -> getSubpart($this -> orig_templateCode, "###" . $conf["subpartMarker"] . "###");

        /*
        * HANDLE EDIT
        */
        // Init subpart content
        $subpartContent = null;
        // Generate HTML
        $form_action = $this -> pi_getPageLink($GLOBALS['TSFE'] -> id, "_self", array("action" => "getviewmessagesnew", "uid" => $uid, "recipient_uid" => $recipient_uid));
        $form_subject_name = $this -> prefixId . "[subject]";
        //Check, if this is an answer
        if ($subject == null && $body == null){
                $form_subject_value = htmlspecialchars($this -> piVars["subject"]);
                $form_body_value = htmlspecialchars($this -> piVars["body"]);
        }
        //Answer case
        else{
            $form_subject_value = $subject;
            $form_body_value = $body;
        }
        $form_body_name = $this -> prefixId . "[body]";
        $form_submit_button_name = $this -> prefixId . "[submit_button]";
        $form_submit_button_value = htmlspecialchars($this -> pi_getLL("FORM_SUBMIT_BUTTON"));
        $form_cancel_button_name = $this -> prefixId . "[cancel_button]";
        $form_cancel_button_value = htmlspecialchars($this -> pi_getLL("FORM_CANCEL_BUTTON"));
        $form_errormsg = $errormsg;
        // Create Marker Array
        $markerArray = array();
        $markerArray["###FORM_ACTION###"] = $cObj -> stdWrap($form_action, "");
		$markerArray["###FORM_SUBJECT_LABEL###"] = $cObj->stdWrap(htmlspecialchars($this->pi_getLL('CWT_MESSAGES_SUBJECT')), "");  # mher
        $markerArray["###FORM_SUBJECT_NAME###"] = $cObj -> stdWrap($form_subject_name, "");
        $markerArray["###FORM_SUBJECT_VALUE###"] = $cObj -> stdWrap($form_subject_value, "");
		$markerArray["###FORM_BODY_LABEL###"] = $cObj->stdWrap(htmlspecialchars($this->pi_getLL('CWT_MESSAGES_BODY')), "");  # mher
        $markerArray["###FORM_BODY_NAME###"] = $cObj -> stdWrap($form_body_name, "");
        $markerArray["###FORM_BODY_VALUE###"] = $cObj -> stdWrap($form_body_value, "");
        $markerArray["###FORM_SUBMIT_BUTTON_NAME###"] = $cObj -> stdWrap($form_submit_button_name, "");
        $markerArray["###FORM_SUBMIT_BUTTON_VALUE###"] = $cObj -> stdWrap($form_submit_button_value, "");
        $markerArray["###FORM_CANCEL_BUTTON_NAME###"] = $cObj -> stdWrap($form_cancel_button_name, "");
        $markerArray["###FORM_CANCEL_BUTTON_VALUE###"] = $cObj -> stdWrap($form_cancel_button_value, "");
        $markerArray["###FORM_ERRORMSG###"] = $cObj -> stdWrap($form_errormsg, "");
        // Substitute the markers in the given sub sub part.
        $subpartContent .= $cObj -> substituteMarkerArray($cObj -> getSubpart($templateCode, "###" . $subSub_1 . "###"), $markerArray);
        // Substitute the template code with the given subpartcontent.
        $templateCode = $this -> cObj -> substituteSubpart($templateCode, $subSub_1, $subpartContent);
        // Return the generated content
        $content = $templateCode;
        return $content;
    }

    /* getViewMessagesNewResult($uid, $recipient_uid)
    *
    *  Displays a result page, when a message has been sent
    *
    *  @param fe user uid
    *  @return String The generated HTML source for this view.
    */
    function getViewMessagesNewResult($uid, $recipient_uid)
    {
        // Init Vars
        $action = "getviewmessagesnewresult";
        $content = "";
        $cObj = $this -> cObj; //Holds a typo content object
        $templateCode = null; //Hold the template source code.
        $conf["subpartMarker"] = "VIEW_MESSAGES_NEW_RESULT"; //Holds a subpart marker.
        $subSub_1 = "ADDITIONAL";

        // Get the html source between subpart markers from template file
        $templateCode = $cObj -> getSubpart($this -> orig_templateCode, "###" . $conf["subpartMarker"] . "###");

        /*
        * HANDLE ADDITIONAL
        */
        // Init subpart content
        $subpartContent = null;
        // Get link to messages
        $linkToMessages = $this -> pi_getPageLink($GLOBALS['TSFE']->id, "", array("action" => "getviewmessages", "uid" => $uid));
        // Create Marker Array
        $markerArray = array();
        $markerArray["###LINK_TO_MESSAGES###"] = $cObj -> stdWrap($linkToMessages, "");
		$markerArray["###SENT_MSG###"] = $cObj->stdWrap(htmlspecialchars($this->pi_getLL('CWT_MESSAGES_SENT')), "");  # mher
 		$markerArray["###BACK###"] = $cObj->stdWrap(htmlspecialchars($this->pi_getLL('CWT_MESSAGES_BACK')), "");  # mher
        // Substitute the markers in the given sub sub part.
        $subpartContent .= $cObj -> substituteMarkerArray($cObj -> getSubpart($templateCode, "###" . $subSub_1 . "###"), $markerArray);
        // Substitute the template code with the given subpartcontent.
        $templateCode = $this -> cObj -> substituteSubpart($templateCode, $subSub_1, $subpartContent);
        // Return the generated content
        $content = $templateCode;
        return $content;
    }

    /* getViewWelcome($user_info, $count)
    *
    *  Displays the welcome page for a user.
    *
    *  @param $user_info All the information that shall be displayed.
    *  @param $count The number of new messages
    *  @return String The generated HTML source for this view.
    */
    function getViewWelcome($user_info, $count){
        // Init Vars
        $content = "";
        $cObj = $this -> cObj; //Holds a typo content object
        $templateCode = null; //Hold the template source code.
        $conf["subpartMarker"] = "VIEW_WELCOME"; //Holds a subpart marker.
        $subSub_1 = "FIRST";
//        $subSub_2 = "ADDITIONAL";

        // Get the html source between subpart markers from template file
        $templateCode = $cObj -> getSubpart($this -> orig_templateCode, "###" . $conf["subpartMarker"] . "###");

        /*
        * HANDLE FIRST
        */
        // Init subpart content
        $subpartContent = null;

        // Get link to Messages
        $linkToMessages = $this -> pi_getPageLink($this -> conf['pid_messages'], "", array("action" => "getviewmessages", "uid" => $user_info['cruser_id']));
        //Get mail icon
        if ($count == "0"){
            //No new mails icon
            $mail_icon = $this->cObj->cImage($this->conf['icon_welcome_nonewmail'], array("alttext" => $this->pi_getLL("icon_welcome_nonewmail")));
        }
        else{
            //New mails icon
            $mail_icon = $this->cObj->cImage($this->conf['icon_welcome_newmail'], array("alttext" => $this->pi_getLL("icon_welcome_newmail")));
        }

        // Create Marker Array
        $markerArray = array();
        $markerArray["###USERNAME###"] = $cObj -> stdWrap($user_info['username'], "");
        $markerArray["###NAME###"] = $cObj -> stdWrap($user_info['name'], "");
        $markerArray["###LASTLOGIN_DATE###"] = $cObj -> stdWrap(date($this->pi_getLL("CWT_DATE_FORMAT"), $user_info['lastlogin']),"");
        $markerArray["###LASTLOGIN_TIME###"] = $cObj -> stdWrap(date($this->pi_getLL("CWT_TIME_FORMAT"), $user_info['lastlogin']),"");
        $markerArray["###LASTLOGIN###"] = $cObj -> stdWrap(htmlspecialchars($this->pi_getLL("CWT_WELCOME_LASTLOGIN")),"");
        $markerArray["###LINK_TO_MESSAGES###"] = $cObj -> stdWrap($linkToMessages, "");
        $markerArray["###LINK_TO_MESSAGES_LABEL###"] = $cObj -> stdWrap(htmlspecialchars($this->pi_getLL("CWT_WELCOME_LINK_TO_MESSAGES_LABEL")), "");
        $markerArray["###MAIL_ICON###"] = $cObj -> stdWrap($mail_icon,"");
        $markerArray["###MAIL_COUNT_PRE###"] = $cObj -> stdWrap(htmlspecialchars($this->pi_getLL("CWT_WELCOME_MAIL_COUNT_PRE")),"");
        $markerArray["###MAIL_COUNT###"] = $cObj -> stdWrap($count,"");
        $markerArray["###MAIL_COUNT_POST###"] = $cObj -> stdWrap(htmlspecialchars($this->pi_getLL("CWT_WELCOME_MAIL_COUNT_POST")),"");
        $markerArray["###GREETING###"] = $cObj -> stdWrap(htmlspecialchars($this->pi_getLL("CWT_WELCOME_GREETING")),"");

        // Substitute the markers in the given sub sub part.
        $subpartContent .= $cObj -> substituteMarkerArray($cObj -> getSubpart($templateCode, "###" . $subSub_1 . "###"), $markerArray);

        // Substitute the template code with the given subpartcontent.
        $templateCode = $this -> cObj -> substituteSubpart($templateCode, $subSub_1, $subpartContent);

        // Return the generated content
        $content = $templateCode;
        return $content;
    }

    /* doGetUserlist($letter)
	*
	*  Gets information about frontend users.
	*
	*  @param $letter Only displays usernames with this as first letter.
	*  @return Array of tx_cwtcommunity_pi1_user records.
	*/
    function doGetUserlist($letter)
    {
        // fetch all by default
        if ($letter == null) {
            // Fetch users
            $users = $this -> doDatabaseQuery("SELECT uid, username, name, city, country, www FROM fe_users WHERE pid = ".$this->sysfolderList." AND NOT deleted=1 AND NOT disable=1 ORDER BY username ASC");
        }
        else{
            // Fetch users
            $users = $this -> doDatabaseQuery("SELECT uid, username, name, city, country, www FROM fe_users WHERE pid = ".$this->sysfolderList." AND (username LIKE '" . $letter . "%' OR username LIKE '" . strtoupper($letter) . "%') AND NOT deleted=1 AND NOT disable=1 ORDER BY username ASC");
        }
        // Return
        return $users;
    }

    /* doGetOnlineUids()
	*
	*  Fetches all online front end user ids.
	*
	*  @return Array fe_users uids;
	*/
    function doGetOnlineUids()
    {
        // Fetch uids
        $temp = array();
        $uids_online = $this -> doDatabaseQuery("SELECT fe_sessions.ses_userid FROM fe_users, fe_sessions WHERE fe_users.uid = fe_sessions.ses_userid AND (fe_users.username LIKE '" . $letter . "%' OR fe_users.username LIKE '" . strtoupper($letter) . "%')");
        for ($i = 0; $i < sizeof($uids_online);$i++) {
            $temp[] = $uids_online[$i]['ses_userid'];
        }
        // return
        return $temp;
    }

    /* doGetUser($fe_users_uid=null)
	*
	*  Fetches information about an fe user with a specified uid.
	*
	*  @param $fe_users_uid Valid uid from 'fe_users'
	*  @return Array
	*/
    function doGetUser($fe_users_uid = null)	{
        // Fetch user
        $user = $this -> doDatabaseQuery("SELECT * FROM fe_users WHERE uid = $fe_users_uid");
        $temp = array();
        $keys = array_keys($user[0]);
        // Create return array
        for ($i = 0;$i < sizeof($user[0]);$i++) {
            $temp[$keys[$i]] = $user[0][$keys[$i]];
        }
        // Return
        return $temp;
    }

    /* doGetUserInfo($fe_users_uid=null)
    *
    *  Fetches information about an fe user with a specified uid (for the welcome page).
    *
    *  @param $fe_users_uid Valid uid from 'fe_users'
    *  @return Array
    */
    function doGetUserInfo($fe_users_uid = null)	{
        // Fetch user
        $user_info = $this -> doDatabaseQuery("SELECT * FROM fe_users WHERE uid = $fe_users_uid");
        $temp = array();
        $keys = array_keys($user_info[0]);
        // Create return array
        for ($i = 0;$i < sizeof($user_info[0]);$i++) {
            $temp[$keys[$i]] = $user_info[0][$keys[$i]];
        }
        // Return
        return $temp;
    }

    /* doGetGuestbook($fe_users_uid=null)
	* 
	*  Fetches the guestbook of a user
	*  
	*  @param $fe_users_uid Valid uid from 'fe_users'
	*  @return Array
	*/
    function doGetGuestbook($fe_users_uid = null)
    {
        // Fetch user
        $records = $this -> doDatabaseQuery("SELECT tx_cwtcommunity_guestbook_data.cruser_id, tx_cwtcommunity_guestbook_data.crdate, tx_cwtcommunity_guestbook_data.text, tx_cwtcommunity_guestbook_data.uid, fe_users.username FROM tx_cwtcommunity_guestbook, tx_cwtcommunity_guestbook_data, fe_users WHERE tx_cwtcommunity_guestbook.fe_users_uid = $fe_users_uid AND tx_cwtcommunity_guestbook_data.guestbook_uid = tx_cwtcommunity_guestbook.uid AND tx_cwtcommunity_guestbook_data.cruser_id = fe_users.uid ORDER BY tx_cwtcommunity_guestbook_data.crdate DESC");

        // Return
        return $records;
    }

    /* doInsertGuestbookData($cruser_id,$text,$uid)
	* 
	*  Gets information about frontend users.
	*  
	*  @param 
	*  @return 
	*/
    function doInsertGuestbookData($cruser_id, $text, $uid)
    { 
        // Get guestbook uid for uid
        $guestbookUID = $this -> doDatabaseQuery("SELECT tx_cwtcommunity_guestbook.uid FROM tx_cwtcommunity_guestbook, fe_users WHERE fe_users.uid = $uid AND fe_users.uid = tx_cwtcommunity_guestbook.fe_users_uid");
        $guestbookUID = $guestbookUID[0]['uid']; 
        // Get timestamp
        $crdate = time(); 
        // Insert entry into db
        $res = $this -> doDatabaseUpdateQuery("INSERT INTO tx_cwtcommunity_guestbook_data (pid, guestbook_uid, text,cruser_id, crdate) VALUES (" . $this -> sysfolderList . ", $guestbookUID, '$text', $cruser_id, $crdate)");
        return $null;
    } 

    /* doGetLoggedInUserUID()
	* 
	*  Gets the uid of the user who is logged in.
	*  
	*  @return String uid of the session user
	*/
    function doGetLoggedInUserUID()
    {
        // Do the stuff
        $temp = get_object_vars($GLOBALS["TSFE"] -> fe_user);
        $temp = $temp['user']['uid'];
        // Return
        return $temp;
    }

    /* doGetGuestbookStatus($uid)
	* 
	*  Gets the status of a users Guestbook.
	*  
	*  @param $uid fe user uid
	*  @return 1 -> Closed // 0 -> Open
	*/
    function doGetGuestbookStatus($uid)
    { 
        // Check if an entry for a guestbook exists
		$res = $this->doDatabaseQuery("SELECT gb.status FROM tx_cwtcommunity_guestbook AS gb WHERE gb.fe_users_uid = $uid");
		//Okay...let us check
		//Guestbook OPEN
		if ($res[0]['status'] == "0") {
			return "0";		    
		}
		//Guestbook CLOSED
		elseif ($res[0]['status'] == "1"){
			return "1";
		}
		//Guestbook CLOSED		
		else{
			return "1";		
		}
    } 

    /* doOpenGuestbook($uid)
	* 
	*  Activates a user's guestbook
	*  
	*  @param $uid fe user uid
	*  @return null
	*/
    function doOpenGuestbook($uid)
    { 
		//Check first if an entry in tx_cwtcommunity_guestbook for the user uid exists
		$res = $this->doDatabaseQuery("SELECT tx_cwtcommunity_guestbook.status FROM tx_cwtcommunity_guestbook, fe_users WHERE fe_users.uid = $uid AND fe_users.uid = tx_cwtcommunity_guestbook.fe_users_uid");
		//NO ENTRY --> create one
		if ($res[0]['status'] == null) {
			//Execute the query
			$res1 = $this->doDatabaseUpdateQuery("INSERT INTO tx_cwtcommunity_guestbook (pid, crdate, fe_users_uid, status) VALUES (".$this->sysfolderList.", ".time().", $uid, 0)");		    
		}
		//ENTRY EXISTS --> only doing an update
		elseif ($res[0]['status'] == "1"){
			//Execute the query
			$res1 = $this->doDatabaseUpdateQuery("UPDATE tx_cwtcommunity_guestbook SET status = 0 WHERE fe_users_uid = $uid");
		}
		//Return
    	return null;
    }

    /* doLockGuestbook($uid)
	* 
	*  Deactivates a user's guestbook
	*  
	*  @param $uid fe user uid
	*  @return null
	*/
    function doLockGuestbook($uid)
    { 
		//Check first if an entry in tx_cwtcommunity_guestbook for the user uid exists
		$res = $this->doDatabaseQuery("SELECT tx_cwtcommunity_guestbook.status FROM tx_cwtcommunity_guestbook, fe_users WHERE fe_users.uid = $uid AND fe_users.uid = tx_cwtcommunity_guestbook.fe_users_uid");
		//NO ENTRY --> create one
		if ($res[0]['status'] == null) {
			//Execute the query
			$res1 = $this->doDatabaseUpdateQuery("INSERT INTO tx_cwtcommunity_guestbook (pid, crdate, fe_users_uid, status) VALUES (".$this->sysfolderList.", ".time().", $uid, 1)");		    
		}
		//ENTRY EXISTS --> only doing an update
		elseif ($res[0]['status'] == "0"){
			//Execute the query
			$res1 = $this->doDatabaseUpdateQuery("UPDATE tx_cwtcommunity_guestbook SET status = 1 WHERE fe_users_uid = $uid");
		}
		//Return
    	return null;
    }

    /* doDeleteGuestbookItem($item_uid)
	* 
	*  Deletes an item from a users' guestbook.
	*  
	*  @param $item_uid uid, of a tx_cwtcommunity_guestbook_data_item
	*  @return null
	*/
    function doDeleteGuestbookItem($item_uid){ 
		//Delete it
		$res = $this->doDatabaseUpdateQuery("DELETE FROM tx_cwtcommunity_guestbook_data WHERE uid = $item_uid");
		//Return
    	return null;
    }

    /* doDeleteGuestbook(uid)
	*
	*  Deletes ALL item from a users' guestbook.
	*
	*  @param $uid fe user uid
	*  @return null
	*/
    function doDeleteGuestbook($uid){
		//Get the users guestbook uid
		$gb_uid = $this->doDatabaseQuery("SELECT uid FROM tx_cwtcommunity_guestbook WHERE fe_users_uid = $uid");
		$gb_uid = $gb_uid[0]["uid"];
		//Delete it
		$res = $this->doDatabaseUpdateQuery("DELETE FROM tx_cwtcommunity_guestbook_data WHERE guestbook_uid = $gb_uid");
		//Return
    	return null;
    }

    /* doGetBuddylist($uid){
    *
    *  Fetches buddylist information for a fe user and return it.
    *
    *  @param $uid fe user uid
    *  @return $buddylist
    */
    function doGetBuddylist($uid){
        //Init some vars
        $buddylist = null;
        //Get buddylist
        $buddylist = $this->doDatabaseQuery("SELECT tx_cwtcommunity_buddylist.uid, tx_cwtcommunity_buddylist.buddy_uid, fe_users.username FROM tx_cwtcommunity_buddylist, fe_users WHERE tx_cwtcommunity_buddylist.fe_users_uid = $uid AND fe_users.uid = tx_cwtcommunity_buddylist.buddy_uid ORDER BY fe_users.username ASC");
        return $buddylist;
    }

	function doGetSexIcon($user_uid){
			//Get icon according to user's sex
			if ($this->isMale($user_uid)) {
			    return $this-> cObj -> cImage($this -> conf["icon_userlist_male"], "", array());
			}
			else{
			    return $this -> cObj -> cImage($this -> conf["icon_userlist_female"], "", array());			
			}	
	}
	/*
	* Gets the gender of a fe_user.
	* 
	* @param	int 	Fe_users uid.
	* @return	boolean	TRUE in case of male, FALSE in case of female.
	*/
	function isMale($user_uid){
		//do the query
		$res = $this->doDatabaseQuery("SELECT tx_cwtcommunityuser_sex FROM fe_users WHERE uid=$user_uid");
		if ($res[0]['tx_cwtcommunityuser_sex'] == "0") {
		    return TRUE;
		}
		else{
			return FALSE;
		}
	}
	
    function isUserActive($feuser_uid){
        //Get online status
        $last_action = $this->doDatabaseQuery("SELECT ses.ses_tstamp FROM fe_users AS usr, fe_sessions AS ses WHERE usr.uid = ".$feuser_uid." AND usr.uid = ses.ses_userid");
		$last_action= $last_action[0]['ses_tstamp'];
        $max_idle_time = $this->conf['maxIdleTime'];
        $time = time();

        $diff = $time - intval($last_action);
        if ($diff < 0){
            return true;
        }
        if (($diff / 60) < $max_idle_time){
            return true;
        }
        else{
            return false;
        }
    }

    /**
    * This function kills duplicate session entries in 'fe_sessions' for the logged in fe_user.
    *
    * @return   void
    */
    function killDuplicateUserSessions(){
        //Get user
        $uid = $this->doGetLoggedInUserUID();
        $ses_id = $GLOBALS["TSFE"]->fe_user->id;
        //Look for duplicate sessions
        $ses = $this->doDatabaseQuery("SELECT ses_id FROM fe_sessions WHERE ses_userid = $uid");
        if (sizeof($ses) > 1){
           //Keep the most actual session, delete the rest
           $this->doDatabaseUpdateQuery("DELETE FROM fe_sessions WHERE ses_userid = $uid AND ses_id != '$ses_id'");
        }
        return;
    }
			
    /* doAddBuddy($uid, $buddy_uid){
    *
    *  Adds the user with $buddy_uid to $uid's buddylist. Furthermore it check for double
    *  additions, (in case a user wants to add a buddy, that exists on his/her list).
    *
    *  @param $uid fe user uid
    *  @param $buddy_uid buddy to add to list
    *  @return $buddylist
    */
    function doAddBuddy($uid, $buddy_uid){
        //Check if the buddy already exists
        $buddy = $this->doDatabaseQuery("SELECT uid FROM tx_cwtcommunity_buddylist WHERE buddy_uid = $buddy_uid AND fe_users_uid = $uid");
        //EXISTING
        if ($buddy[0]['uid'] != null){
            //Buddy exists, so do nothing
        }
        //NOT EXISTING
        else{
            //Do the query
            $res = $this->doDatabaseUpdateQuery("INSERT INTO tx_cwtcommunity_buddylist (pid, crdate, cruser_id, fe_users_uid, buddy_uid) VALUES (".$this->sysfolderList.", ".time().", ".$uid.", ".$uid.", ".$buddy_uid.")");
        }
        return null;
    }

    /* doDeleteBuddy($uid, $buddy_uid)
    *
    *  Deletes a buddy from a list.
    *
    *  @param $uid fe user uid
    *  @param $buddy_uid buddy to delete from list
    *  @return $buddylist
    */
    function doDeleteBuddy($uid, $buddy_uid){
        //Check for vars, in case anybody wants to mess everythin up
        if ($uid == null || $buddy_uid == null){
            die("You are not allowed to do that!");
        }
        //Do the query
        $res = $this->doDatabaseUpdateQuery("DELETE FROM tx_cwtcommunity_buddylist WHERE fe_users_uid = $uid AND buddy_uid = $buddy_uid");
        return null;
    }

    /* doGetMessages($uid)
    *
    *  Gets all the messages for a user.
    *
    *  @param $uid fe user uid
    *  @return $messages
    */
    function doGetMessages($uid){
        //Init some vars
        $messages =  null;
        //Do the query
        $messages = $this->doDatabaseQuery("SELECT tx_cwtcommunity_message.cruser_id, tx_cwtcommunity_message.crdate, tx_cwtcommunity_message.subject, tx_cwtcommunity_message.uid, tx_cwtcommunity_message.status, fe_users.username FROM tx_cwtcommunity_message, fe_users WHERE tx_cwtcommunity_message.cruser_id = fe_users.uid AND tx_cwtcommunity_message.fe_users_uid = $uid ORDER BY crdate DESC");
        //return
        return $messages;
    }

    /* doGetNewMessages($uid)
    *
    *  Gets all the new messages for a user.
    *
    *  @param $uid fe user uid
    *  @return $messages
    */
    function doGetNewMessages($uid){
        //Init some vars
        $messages =  null;
        //Do the query
        $messages = $this->doDatabaseQuery("SELECT tx_cwtcommunity_message.cruser_id, tx_cwtcommunity_message.crdate, tx_cwtcommunity_message.subject, tx_cwtcommunity_message.uid, tx_cwtcommunity_message.status, fe_users.username FROM tx_cwtcommunity_message, fe_users WHERE tx_cwtcommunity_message.cruser_id = fe_users.uid AND tx_cwtcommunity_message.fe_users_uid = $uid AND tx_cwtcommunity_message.status = 0 ORDER BY crdate DESC");
        //return
        return $messages;
    }

    /* doGetMessagesSingle($uid, $msg_uid)
    *
    *  Gets one message for a user and sets the status to 'read' = 1
    *
    *  @param $uid session users uid
    *  @param $msg_uid uid of a message from database.
    *  @return $message
    */
    function doGetMessagesSingle($uid, $msg_uid){
        //Init some vars
        $message =  null;
        //Do the query
        $message = $this->doDatabaseQuery("SELECT tx_cwtcommunity_message.cruser_id, tx_cwtcommunity_message.crdate, tx_cwtcommunity_message.subject, tx_cwtcommunity_message.body, tx_cwtcommunity_message.status, tx_cwtcommunity_message.uid, fe_users.username FROM tx_cwtcommunity_message, fe_users WHERE tx_cwtcommunity_message.uid = $msg_uid AND tx_cwtcommunity_message.fe_users_uid = $uid AND tx_cwtcommunity_message.cruser_id = fe_users.uid ORDER BY crdate DESC");
        $message = $message[0];
        //Now update the status
        $res = $this->doDatabaseUpdateQuery("UPDATE tx_cwtcommunity_message SET status = 1 WHERE uid = $msg_uid");
        //return
        return $message;
    }

    /* doDeleteMessage($msg_uid)
    *
    *  Delete a single message.
    *
    *  @param $msg_uid uid of a message from database.
    *  @return null
    */
    function doDeleteMessage($msg_uid){
        //Do the query
        $res = $this->doDatabaseUpdateQuery("DELETE FROM tx_cwtcommunity_message WHERE uid = $msg_uid");
        //return
        return $null;
    }

    /* doSendMessage($uid, $recipient_uid, $subject, $body)
    *
    *  Sends a single message.
    *
    *  @param $recipient_uid Recipient user id
    *  @param $uid Session user id
    *  @param $subject The subject of the mail
    *  @param $body The mail body.
    *  @return null
    */
    function doSendMessage($uid, $recipient_uid, $subject, $body){
        //Do the query
        $res = $this->doDatabaseUpdateQuery("INSERT INTO tx_cwtcommunity_message (pid, crdate, fe_users_uid, cruser_id, subject, body, status) VALUES (".$this->sysfolderList.",".time().", ".$recipient_uid.", ".$uid.", '".$subject."', '".$body."', 0)");
        //return
        return $null;
    }

    /* parseIcons($textToParse)
    *
    *  Parses for strings in textToParse and replaces them with icons. The mapping between
    *   string and icon can be made with icon records in backend.
    *
    *  @return String The parsed text.
    */
    function parseIcons($textToParse){
      if ($this->iconReplacement){
           $pid_icons = $this->conf["pid_icons"];
           //Get strings to parse from db
           $res = $this->doDatabaseQuery("SELECT * FROM tx_cwtcommunity_icons WHERE pid =".$pid_icons." AND NOT deleted=1 AND NOT hidden=1");
           //Parse text
           for ($i=0; $i < sizeof($res); $i++){
              $textToParse = str_replace($res[$i]["string"],$this->cObj->cImage($this->uploadDir."icons/".$res[$i]["icon"],array("alttext" => $res[$i]["string"])),$textToParse);
           }
       }
       //Return the text
       return $textToParse;
    }

    /**
     * NEW FUNCTION TEMPLATE
     */

    /* getViewCategory($category_uid=null)
	*
	*  [Description]
	*
	*  @return String The generated HTML source for this view.
	*/
    function getViewCategory($category_uid = null)
    {
        // Init Vars
        $action = "getviewcategory";
        $content = "";
        $cObj = $this -> cObj; //Holds a typo content object
        $templateCode = null; //Hold the template source code.
        $conf["subpartMarker"] = "VIEW_CATEGORY"; //Holds a subpart marker.
        $subSub_1 = "CATEGORIES";
        $subSub_2 = "LINKS";
        $subSub_3 = "PATHMENU";
        $subSub_4 = "ADDITIONAL";
        // Get the html source between subpart markers from template file
        $templateCode = $cObj -> getSubpart($this -> orig_templateCode, "###" . $conf["subpartMarker"] . "###");

        /*
		* HANDLE CATEGORY BREADCUM
		*/
        // Init subpart content
        $subpartContent = null;
        // Create Marker Array
        $markerArray = array();
        $markerArray["###CATEGORY_BREADCUM###"] = $cObj -> stdWrap($this -> getCatPathMenu($category_uid), "");
        // Substitute the markers in the given sub sub part.
        $subpartContent .= $cObj -> substituteMarkerArray($cObj -> getSubpart($templateCode, "###" . $subSub_3 . "###"), $markerArray);
        // Substitute the template code with the given subpartcontent.
        $templateCode = $this -> cObj -> substituteSubpart($templateCode, $subSub_3, $subpartContent);
        // Return the generated content
        $content = $templateCode;
        return $content;
    }

    /* doDatabaseQuery($query)
	*
	*  This function runs queries on the typo3 database and returns the
	*  result set in an associative array e.g. $return[0]['myAttribute'].
	*  Please notice, that this function is only suitable for 'SELECT'
	*  queries.
	*
	*  @param	string	Database query, which will be executed. e.g. 'SELECT * FROM myTable'
	*  @return	array	Associative array with query results
	*/
    function doDatabaseQuery($query)
    {
        // Do the query
        $res = mysql(TYPO3_db, $query);
        // Preparing result set
        $rows = array();
        while ($row = mysql_fetch_assoc($res)) {
            $rows[] = $row;
        }
        // Debugging
        if ($this -> debug) {
            echo "Query: $query<br>";
            t3lib_div::print_array($rows);
            echo "<br>";
        }
        // Return the array
        return $rows;
    }

    /* doDatabaseUpdateQuery($query)
	*
	*  [Description]
	*  @param	string	Database query, which will be executed. e.g. 'UPDATE myTable SET myAttribute=myValue WHERE...'
	*  @return null
	*/
    function doDatabaseUpdateQuery($query)
    {
        // Do the query
        $res = mysql(TYPO3_db, $query);
        // Debugging
        if ($this -> debug) {
            echo "Query: $query<br>";
            echo "<br>";
        }
        // Return the array
        return null;
    }
}

if (defined("TYPO3_MODE") && $TYPO3_CONF_VARS[TYPO3_MODE]["XCLASS"]["ext/cwt_community/pi1/class.tx_cwtcommunity_pi1.php"]) {
    include_once($TYPO3_CONF_VARS[TYPO3_MODE]["XCLASS"]["ext/cwt_community/pi1/class.tx_cwtcommunity_pi1.php"]);
} 

?>
