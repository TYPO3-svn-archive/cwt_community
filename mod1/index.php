<?php
/***************************************************************
*  Copyright notice
*
*  (c) 2003 sebastian (s.faulhaber@web-sol.de)
*  All rights reserved
*
*  This script is part of the Typo3 project. The Typo3 project is
*  free software; you can redistribute it and/or modify
*  it under the terms of the GNU General Public License as published by
*  the Free Software Foundation; either version 2 of the License, or
*  (at your option) any later version.
*
*  The GNU General Public License can be found at
*  http://www.gnu.org/copyleft/gpl.html.
*
*  This script is distributed in the hope that it will be useful,
*  but WITHOUT ANY WARRANTY; without even the implied warranty of
*  MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
*  GNU General Public License for more details.
*
*  This copyright notice MUST APPEAR in all copies of the script!
***************************************************************/
/**
 * Module 'CWT Community' for the 'cwt_community' extension.
 *
 * @author	sebastian <s.faulhaber@web-sol.de>
 */



	// DEFAULT initialization of a module [BEGIN]
unset($MCONF);
require ("conf.php");
require ($BACK_PATH."init.php");
require ($BACK_PATH."template.php");
include ("locallang.php");
require_once (PATH_t3lib."class.t3lib_scbase.php");
$BE_USER->modAccess($MCONF,1);	// This checks permissions and exits if the users has no permission for entry.
	// DEFAULT initialization of a module [END]

class tx_cwtcommunity_module1 extends t3lib_SCbase {
	var $pageinfo;
	var $debug = false;

	/**
	 *
	 */
	function init()	{
		global $AB,$BE_USER,$LANG,$BACK_PATH,$TCA_DESCR,$TCA,$HTTP_GET_VARS,$HTTP_POST_VARS,$CLIENT,$TYPO3_CONF_VARS;

		parent::init();

		/*
		if (t3lib_div::GPvar("clear_all_cache"))	{
			$this->include_once[]=PATH_t3lib."class.t3lib_tcemain.php";
		}
		*/
	}

	/**
	 * Adds items to the ->MOD_MENU array. Used for the function menu selector.
	 */
	function menuConfig()	{
		global $LANG;
		$this->MOD_MENU = Array (
			"function" => Array (
				"1" => $LANG->getLL("function1"),
				"2" => $LANG->getLL("function2"),
			)
		);
		parent::menuConfig();
	}

		// If you chose "web" as main module, you will need to consider the $this->id parameter which will contain the uid-number of the page clicked in the page tree
	/**
	 * Main function of the module. Write the content to $this->content
	 */
	function main()	{
		global $AB,$BE_USER,$LANG,$BACK_PATH,$TCA_DESCR,$TCA,$HTTP_GET_VARS,$HTTP_POST_VARS,$CLIENT,$TYPO3_CONF_VARS;

		// Access check!
		// The page will show only if there is a valid page and if this page may be viewed by the user
		$this->pageinfo = t3lib_BEfunc::readPageAccess($this->id,$this->perms_clause);
		$access = is_array($this->pageinfo) ? 1 : 0;

		if (($this->id && $access) || ($BE_USER->user["admin"] && !$this->id))	{

				// Draw the header.
			$this->doc = t3lib_div::makeInstance("mediumDoc");
			$this->doc->backPath = $BACK_PATH;
			$this->doc->form='<form action="" method="POST">';

				// JavaScript
			$this->doc->JScode = '
				<script language="javascript">
					script_ended = 0;
					function jumpToUrl(URL)	{
						document.location = URL;
					}
				</script>
			';
			$this->doc->postCode='
				<script language="javascript">
					script_ended = 1;
					if (top.theMenu) top.theMenu.recentuid = '.intval($this->id).';
				</script>
			';

			$headerSection = $this->doc->getHeader("pages",$this->pageinfo,$this->pageinfo["_thePath"])."<br>".$LANG->php3Lang["labels"]["path"].": ".t3lib_div::fixed_lgd_pre($this->pageinfo["_thePath"],50);

			$this->content.=$this->doc->startPage($LANG->getLL("title"));
			$this->content.=$this->doc->header($LANG->getLL("title"));
			$this->content.=$this->doc->spacer(5);
			$this->content.=$this->doc->section("",$this->doc->funcMenu($headerSection,t3lib_BEfunc::getFuncMenu($this->id,"SET[function]",$this->MOD_SETTINGS["function"],$this->MOD_MENU["function"])));
			$this->content.=$this->doc->divider(5);


			// Render content:
			$this->moduleContent();


			// ShortCut
			if ($BE_USER->mayMakeShortcut())	{
				$this->content.=$this->doc->spacer(20).$this->doc->section("",$this->doc->makeShortcutIcon("id",implode(",",array_keys($this->MOD_MENU)),$this->MCONF["name"]));
			}

			$this->content.=$this->doc->spacer(10);
		} else {
				// If no access or if ID == zero

			$this->doc = t3lib_div::makeInstance("mediumDoc");
			$this->doc->backPath = $BACK_PATH;

			$this->content.=$this->doc->startPage($LANG->getLL("title"));
			$this->content.=$this->doc->header($LANG->getLL("title"));
			$this->content.=$this->doc->spacer(5);
			$this->content.=$this->doc->spacer(10);
		}
	}

	/**
	 * Prints out the module HTML
	 */
	function printContent()	{
		global $SOBE;

		$this->content.=$this->doc->middle();
		$this->content.=$this->doc->endPage();
		echo $this->content;
	}

	/**
	 * Generates the module content
	 */
	function moduleContent()	{
                 global $LANG;
		switch((string)$this->MOD_SETTINGS["function"])	{
			/***********************************************
			* FUNCTION: User administration
			************************************************/
			case 1:
				//Get action value
				$action = null;
				$action = $GLOBALS["HTTP_GET_VARS"]['action'];
				if ($this->debug) {
				    echo "<br>Action: ".$action;
				}

				//Decide what to do
				if ($action == null || $action == "getviewuseradministration") {
					//Get letter
					$letter = $GLOBALS["HTTP_GET_VARS"]["letter"];
					//Get the model
					$users = $this->doGetUserlist($letter);
					//Generate the view
					$content.= $this->getViewUserAdministration($users);
				}
				elseif ($action == "getviewuseradministrationenabled"){
					//Get uid
					$uid = $GLOBALS["HTTP_GET_VARS"]["uid"];
					//Do the action
					$res = $this->doEnableUser($uid);
					//Generate the view
					$content.= $this->getViewUserAdministrationEnabled();
				}
				elseif ($action == "getviewuseradministrationdisabled"){
					//Get uid
					$uid = $GLOBALS["HTTP_GET_VARS"]["uid"];
					//Do the action
					$res = $this->doDisableUser($uid);
					//Generate the view
					$content.= $this->getViewUserAdministrationDisabled();
				}
				$this->content.=$this->doc->section($LANG->getLL("viewUserAdministration_title"),$content,1,1);
			break;
                        /***********************************************
                        * FUNCTION: Mailing
                        ************************************************/
			case 2:
                                //Get action value
                                $action = null;
                                $action = $GLOBALS["HTTP_POST_VARS"]['action'];
                                if ($this->debug) {
                                    echo "HTTP POST VARS: ";
                                    print_r($GLOBALS["HTTP_POST_VARS"]);
                                    echo "<br>Action: ".$action;
                                }

                                //Decide what to do
                                if ($action == null || $action == "") {
                                   //Get fe_groups
                                   $fe_groups = $this->doGetGroups();
                                   $fe_users = $this->doGetUsers();
                                   //Generate content
        			  				$content.= $this->getViewMailing($fe_groups, $fe_users);
                                   $this->content.=$this->doc->section($LANG->getLL("function2"),$content,1,1);
                                }
                                else if ($action == "message_preview"){
                                   //Get fe_groups
                                   $fe_groups = $this->doGetGroups();
                                   $fe_users = $this->doGetUsers();
                                   //Generate content
                                   $content.= $this->getViewMailingPreview($fe_groups,$fe_users);
                                   $this->content.=$this->doc->section($LANG->getLL("function2"),$content,1,1);
                                }
                                else if ($action == "message_send"){
                                     //Get button value
                                     $submit = $GLOBALS["HTTP_POST_VARS"]["submit"];
                                     if ($submit == $LANG->getLL("viewMailing_submitmessage")){
                                         //Get fe_group_uid
                                         $fe_group_uid = $GLOBALS["HTTP_POST_VARS"]["fe_group"];
                                         //Generate content
                                         $content.= $this->getViewMailingResult($fe_group_uid);
                                         $this->content.=$this->doc->section($LANG->getLL("function2"),$content,1,1);
                                     }
                                     else if ($submit == $LANG->getLL("viewMailing_modifymessage")){
                                       //Get fe_groups
                                       $fe_groups = $this->doGetGroups();
                                       $fe_users = $this->doGetUsers();
                                       //Generate content
                                       $content.= $this->getViewMailing($fe_groups, $fe_users);
                                       $this->content.=$this->doc->section($LANG->getLL("function2"),$content,1,1);
                                     }
                                     else if ($submit == $LANG->getLL("viewMailing_cancelmessage")){
                                       //Clear post vars
                                       $GLOBALS["HTTP_POST_VARS"] = null;
                                       //Get fe_groups
                                       $fe_groups = $this->doGetGroups();
                                       $fe_users = $this->doGetUsers();
                                       //Generate content
                                       $content.= $this->getViewMailing($fe_groups, $fe_users);
                                       $this->content.=$this->doc->section($LANG->getLL("function2"),$content,1,1);
                                     }
                                }
			break;
			case 3:
				$content="<div align=center><strong>Menu item #3...</strong></div>";
				$this->content.=$this->doc->section("Message #3:",$content,0,1);
			break;
		}
	}


        /* getViewMailing($fe_groups)
        *
        *  Display the mailing view. Administrators can send messages to all users
        *   and / or groups of users.
        *
        *  @param $fe_users Fe groups array from $this->doGetGroups()
        *  @return $content The generated content.
        */
        function getViewMailing($fe_groups, $fe_users){
           global $LANG;
           //Init some vars
           $content = null;
           $doc = get_object_vars($this->doc);

           //Output description
           $content.= $LANG->getLL("viewMailing_description")."<br><br>";

           //Starting formular
           $content.="<form action=\"\" method=\"POST\">";
           $content.="<input name=\"action\" type=\"hidden\" value=\""."message_preview"."\">";
           $content.="<table width=\"100%\">";
           $content.="<tr bgcolor=\"".$doc['bgColor5']."\">";
           $content.="<td colspan=\"2\"><b>".$LANG->getLL("viewMailing_newmessage_1")."</td>";
           $content.="</tr>";
           $content.="<tr>";
           $content.="<td valign=\"top\">".$LANG->getLL("viewMailing_cruser_id")."</td>";
           $content.="<td><select name=\"cruser_id\">";
           //Generate options from db result
           $cruser_id = $GLOBALS["HTTP_POST_VARS"]["cruser_id"];
           for ($i=0;$i < sizeof($fe_users) ; $i++){
                if ($cruser_id == $fe_users[$i]["uid"]){
                    $content.= "<option value=\"".$fe_users[$i]["uid"]."\" selected=\"selected\">".$fe_users[$i]["username"]."</option>";
                }
                else{
                    $content.= "<option value=\"".$fe_users[$i]["uid"]."\">".$fe_users[$i]["username"]."</option>";
                }
           }
           $content.="</select></td>";
           $content.="</tr>";
           $content.="<tr>";
           $content.="<td valign=\"top\">".$LANG->getLL("viewMailing_title")."</td>";
           $content.="<td><input name=\"title\" type=\"text\"/ size=\"51\" value=\"".$GLOBALS["HTTP_POST_VARS"]["title"]."\"></td>";
           $content.="</tr>";
           $content.="<tr>";
           $content.="<td valign=\"top\">".$LANG->getLL("viewMailing_text")."</td>";
           $content.="<td><textarea name=\"text\" cols=\"50\" rows=\"10\">".$GLOBALS["HTTP_POST_VARS"]["text"]."</textarea></td>";
           $content.="</tr>";
           $content.="<tr>";
           $content.="<td valign=\"top\">".$LANG->getLL("viewMailing_group")."</td>";
           $content.="<td><select name=\"fe_group\">";
           $content.="<option value=\"\" ></option>";
           //Generate options from db result
           $fe_group = $GLOBALS["HTTP_POST_VARS"]["fe_group"];
           for ($i=0;$i < sizeof($fe_groups) ; $i++){
                if ($fe_group == $fe_groups[$i]["uid"]){
                    $content.= "<option value=\"".$fe_groups[$i]["uid"]."\" selected=\"selected\">".$fe_groups[$i]["title"]."</option>";
                }
                else{
                    $content.= "<option value=\"".$fe_groups[$i]["uid"]."\">".$fe_groups[$i]["title"]."</option>";
                }
           }
           $content.="</select></td>";
           $content.="</tr>";
           $content.="<tr>";
           $content.="<td align=\"right\" colspan=\"2\"><input type=\"submit\" value=\"".$LANG->getLL("viewMailing_previewmessage")."\"></td>";
           $content.="</tr>";

           $content.="</table>";
           $content.="</form>";

           //Legend
           $content.= $this->doc->divider(5);

           //Return
           return $content;
        }

        /* getViewMailingPreview($fe_groups)
        *
        *  Display the mailing preview view.
        *
        *  @param $fe_users Fe groups array from $this->doGetGroups()
        *  @return $content The generated content.
        */
        function getViewMailingPreview($fe_groups, $fe_users){
           global $LANG;
           //Init some vars
           $content = null;
           $doc = get_object_vars($this->doc);
           $username = $this->doDatabaseQuery("SELECT username FROM fe_users WHERE uid = ".$GLOBALS["HTTP_POST_VARS"]['cruser_id']);
           $username = $username[0]["username"];
           if ($GLOBALS["HTTP_POST_VARS"]['fe_group'] != null && $GLOBALS["HTTP_POST_VARS"]['fe_group'] != ""){
               $fe_group = $this->doDatabaseQuery("SELECT title FROM fe_groups WHERE uid = ".$GLOBALS["HTTP_POST_VARS"]['fe_group']);
               $fe_group = $fe_group[0]["title"];
           }

           //Output description
           $content.= $LANG->getLL("viewMailing_description")."<br><br>";

           //Starting formular
           $content.="<form action=\"\" method=\"POST\">";
           //Hidden fields
           $content.="<input name=\"action\" type=\"hidden\" value=\""."message_send"."\">";
           $content.="<input name=\"cruser_id\" type=\"hidden\" value=\"".$GLOBALS["HTTP_POST_VARS"]['cruser_id']."\">";
           $content.="<input name=\"title\" type=\"hidden\" value=\"".$GLOBALS["HTTP_POST_VARS"]['title']."\">";
           $content.="<input name=\"text\" type=\"hidden\" value=\"".$GLOBALS["HTTP_POST_VARS"]['text']."\">";
           $content.="<input name=\"fe_group\" type=\"hidden\" value=\"".$GLOBALS["HTTP_POST_VARS"]['fe_group']."\">";
           $content.="<table width=\"100%\">";
           $content.="<tr bgcolor=\"".$doc['bgColor5']."\">";
           $content.="<td colspan=\"2\"><b>".$LANG->getLL("viewMailing_newmessage_2")."</td>";
           $content.="</tr>";
           $content.="<tr>";
           $content.="<td valign=\"top\" width=\"200\">".$LANG->getLL("viewMailing_cruser_id")."</td>";
           $content.="<td>".$username."</td>";
           $content.="</tr>";
           $content.="<tr>";
           $content.="<td valign=\"top\" width=\"200\">".$LANG->getLL("viewMailing_title")."</td>";
           $content.="<td>".$GLOBALS["HTTP_POST_VARS"]['title']."</td>";
           $content.="</tr>";
           $content.="<tr>";
           $content.="<td valign=\"top\">".$LANG->getLL("viewMailing_text")."</td>";
           $content.="<td>".$GLOBALS["HTTP_POST_VARS"]['text']."</td>";
           $content.="</tr>";
           $content.="<tr>";
           $content.="<td valign=\"top\">".$LANG->getLL("viewMailing_group")."</td>";
           $content.="<td>".$fe_group."</td>";
           $content.="</tr>";
           $content.="<tr>";
           $content.="<td align=\"right\" colspan=\"2\">";
           $content.="<input type=\"submit\" name=\"submit\" value=\"".$LANG->getLL("viewMailing_submitmessage")."\">";
           $content.="<input type=\"submit\" name=\"submit\" value=\"".$LANG->getLL("viewMailing_modifymessage")."\">";
           $content.="<input type=\"submit\" name=\"submit\" value=\"".$LANG->getLL("viewMailing_cancelmessage")."\">";
           $content.="</td>";
           $content.="</tr>";

           $content.="</table>";
           $content.="</form>";
           //Legend
           $content.= $this->doc->divider(5);

           //Return
           return $content;
        }

        /* getViewMailingResult($fe_group_uid)
        *
        *  Display the mailing result view.
        *
        *  @return $content The generated content.
        */
        function getViewMailingResult($fe_group_uid){
           global $LANG;
           //Init some vars
           $content = null;
           $doc = get_object_vars($this->doc);

           //Output description
           //$content.= $LANG->getLL("viewMailing_description")."<br><br>";
           $cruser_id = $GLOBALS["HTTP_POST_VARS"]["cruser_id"];
           $subject = $GLOBALS["HTTP_POST_VARS"]["title"];
           $text = $GLOBALS["HTTP_POST_VARS"]["text"];

           //Send messages
           $this->doSendMessages($fe_group_uid, $subject, $text, $cruser_id);

           //Starting content
           $content .= $LANG->getLL('viewMailing_result');
           $content .= "<br>";
           $content .= "<a href=\"index.php\">".$LANG->getLL('viewMailing_backtomailing')."</a>";
           $content .= "<br>";
           //Legend
           $content.= $this->doc->divider(5);

           //Return
           return $content;
        }

    /* doSendMessages($fe_group_uid=null, $subject, $text)
    *
    *  Sends messages either to all frontend users (if fe_group_uid is NULL) or to a specific
    *  usergroup.
    *
    *  @param $fe_group_uid Uid of an usergoup from 'fe_groups'
    *  @param $subject The subject of the mail to be sent.
    *  @param $text The message body.
    *  @param $cruser_id The fe_users uid of the user, who sends the mail.
    */
    function doSendMessages($fe_group_uid=null, $subject, $text, $cruser_id){
      //Fetch users from db
      $users = $this->doDatabaseQuery("SELECT uid, usergroup FROM fe_users WHERE NOT deleted = 1");
      $tstamp = time();

      //send to group
      if ($fe_group_uid != null){
         for ($i=0; $i < sizeof($users); $i++){
              //Explode usergroup
              $usergroups = explode(",", $users[$i]["usergroup"]);
              if ($usergroups != false){
                 if (in_array($fe_group_uid, $usergroups)){
                     $this->doDatabaseUpdateQuery("INSERT INTO tx_cwtcommunity_message (fe_users_uid, subject, body, status, cruser_id, tstamp, crdate) VALUES ('".$users[$i]["uid"]."', '$subject', '$text', 0, $cruser_id, $tstamp, $tstamp)");
                 }
              }
         }
      }
      //send to all
      else{
         for ($i=0; $i < sizeof($users); $i++){
            $this->doDatabaseUpdateQuery("INSERT INTO tx_cwtcommunity_message (fe_users_uid, subject, body, status, cruser_id, tstamp, crdate) VALUES ('".$users[$i]["uid"]."', '$subject', '$text', 0, $cruser_id, $tstamp, $tstamp)");
         }
      }

    }

	/* getViewUserAdministration($users)
	*
	*  Display the main user dministration view. BE users can disable and enable users here.
	*
	*  @param $users Users array from $this->doGetUserlist($letter)
	*  @return $content The generated content.
	*/
	function getViewUserAdministration($users){
		global $LANG;
		//Init some vars
		$content = null;
                $switch = true;
                $doc = get_object_vars($this->doc);

		//Output description
		$content.= $LANG->getLL("viewUserAdministration_description")."<br><br>";

		//Create the row
        $content .= "<div style=\"".$doc["defStyle"]."\">";
        $content .= "<a href=\"?action=getviewuseradministration\">".$LANG->getLL('all')."</a>|";
        $content .= "<a href=\"?action=getviewuseradministration&letter=a\">A</a>|";
        $content .= "<a href=\"?action=getviewuseradministration&letter=b\">B</a>|";
        $content .= "<a href=\"?action=getviewuseradministration&letter=c\">C</a>|";
        $content .= "<a href=\"?action=getviewuseradministration&letter=d\">D</a>|";
        $content .= "<a href=\"?action=getviewuseradministration&letter=e\">E</a>|";
        $content .= "<a href=\"?action=getviewuseradministration&letter=f\">F</a>|";
        $content .= "<a href=\"?action=getviewuseradministration&letter=g\">G</a>|";
        $content .= "<a href=\"?action=getviewuseradministration&letter=h\">H</a>|";
        $content .= "<a href=\"?action=getviewuseradministration&letter=i\">I</a>|";
        $content .= "<a href=\"?action=getviewuseradministration&letter=j\">J</a>|";
        $content .= "<a href=\"?action=getviewuseradministration&letter=k\">K</a>|";
        $content .= "<a href=\"?action=getviewuseradministration&letter=l\">L</a>|";
        $content .= "<a href=\"?action=getviewuseradministration&letter=m\">M</a>|";
        $content .= "<a href=\"?action=getviewuseradministration&letter=n\">N</a>|";
        $content .= "<a href=\"?action=getviewuseradministration&letter=o\">O</a>|";
        $content .= "<a href=\"?action=getviewuseradministration&letter=p\">P</a>|";
        $content .= "<a href=\"?action=getviewuseradministration&letter=q\">Q</a>|";
        $content .= "<a href=\"?action=getviewuseradministration&letter=r\">R</a>|";
        $content .= "<a href=\"?action=getviewuseradministration&letter=s\">S</a>|";
        $content .= "<a href=\"?action=getviewuseradministration&letter=t\">T</a>|";
        $content .= "<a href=\"?action=getviewuseradministration&letter=u\">U</a>|";
        $content .= "<a href=\"?action=getviewuseradministration&letter=v\">V</a>|";
        $content .= "<a href=\"?action=getviewuseradministration&letter=w\">W</a>|";
        $content .= "<a href=\"?action=getviewuseradministration&letter=x\">X</a>|";
        $content .= "<a href=\"?action=getviewuseradministration&letter=y\">Y</a>|";
        $content .= "<a href=\"?action=getviewuseradministration&letter=z\">Z</a>";
        $content .= "<br><br></div>";

		//Display the user records
		$content .= "<table width=\"100%\">";
		$content .= "<tr bgcolor=\"".$doc['bgColor5']."\">";
		$content .= "<td><b>".$LANG->getLL("username")."</small></b></td>";
        $content .= "<td><b>".$LANG->getLL("name")."</b></td>";
		$content .= "<td><b>".$LANG->getLL("crdate")."</b></td>";
		$content .= "<td><b>".$LANG->getLL("lastlogin")."</b></td>";
        $content .= "<td><b></b></td>";
        $content .= "<td><b></b></td>";
		$content .= "</tr>";

		for($i = 0; $i < sizeof($users); $i++){
                        //Alternating row colors
                        if ($switch == true){
                            $switch = false;
                            $content .= "<tr bgcolor=\"".$doc['bgColor4']."\">";
                        }
                        elseif($switch == false){
                            $switch = true;
                            $content .= "<tr>";
                        }
                        //Beginning row content
			$content .= "<td>".$users[$i]["username"]."</td>";
                        $content .= "<td>".$users[$i]["name"]."</td>";
                        $content .= "<td>".date($LANG->getLL("cwt_date_format"), $users[$i]["crdate"])."</td>";
                        $content .= "<td>".date($LANG->getLL("cwt_date_format"), $users[$i]["lastlogin"])."</td>";
			//User enabled
			if ($users[$i]["disable"] == 0) {
				$content .= "<td></td>";
				$content .= "<td><a href=\"?action=getviewuseradministrationdisabled&uid=".$users[$i]["uid"]."\"><img src=\"action_disable.gif\" alt=\"".$LANG->getLL("viewUserAdministration_disable")."\" border=\"0\"></a></td>";
			}
			//User disabled
			elseif ($users[$i]["disable"] == 1){
				$content .= "<td><a href=\"?action=getviewuseradministrationenabled&uid=".$users[$i]["uid"]."\"><img src=\"action_enable.gif\" alt=\"".$LANG->getLL("viewUserAdministration_enable")."\" border=\"0\"></a></td>";
				$content .= "<td></td>";									
			}
			$content .= "</tr>";			
		}
		$content .= "</table>";	

                //Legend
                $content.= $this->doc->divider(10);
                $content.= "<b>".$LANG->getLL("legend")."</b><br><br>";
                $content.= "<img src=\"action_enable.gif\" border=\"0\" alt=\"".$LANG->getLL("ViewCheckForBrokenLinks_enable")."\">&nbsp;".$LANG->getLL("viewUserAdministration_enable");
                $content.= "<br><img src=\"action_disable.gif\" border=\"0\" alt=\"".$LANG->getLL("ViewLinksToApprove_delete")."\">&nbsp;".$LANG->getLL("viewUserAdministration_disable")."<br>";

		//return
		return $content;	
	}	

	/* getViewUserAdministrationEnabled()
	* 
	*  Display the result view, when a user was enabled.
	*  
	*/
	function getViewUserAdministrationEnabled(){
		global $LANG;
		//Output header and description
		$content.= "<div align=\"left\"><strong>".$LANG->getLL("viewUserAdministrationenabled_title")."</strong></div><BR>";	
		$content.= $LANG->getLL("viewUserAdministrationenabled_description")."<br>";
		$content.= "<a href=\"index.php\">".$LANG->getLL("back")."...</a>";
		
		//Return
		return $content;		
	}

	/* getViewUserAdministrationDisabled()
	* 
	*  Display the result view, when a user was disabled.
	*
	*/
	function getViewUserAdministrationDisabled(){
		global $LANG;
		//Output header and description
		$content.= "<div align=\"left\"><strong>".$LANG->getLL("viewUserAdministrationdisabled_title")."</strong></div><BR>";
		$content.= $LANG->getLL("viewUserAdministrationdisabled_description")."<br>";		
		$content.= "<a href=\"index.php\">".$LANG->getLL("back")."...</a>";
		
		//Return
		return $content;		
	}

	/* doEnableUser($uid)
	* 
	*  Enables a fe_user.
	*  
	*  @param $uid fe_users uid
	*/
	function doEnableUser($uid){
		//Do the query
		$res = $this->doDatabaseUpdateQuery("UPDATE fe_users SET disable = 0 WHERE uid = $uid");
		return null;
	}	

	/* doDisableUser($uid)
	* 
	*  Disables a fe_user.
	*  
	*  @param $uid fe_users uid
	*/
	function doDisableUser($uid){
		//Do the query
		$res = $this->doDatabaseUpdateQuery("UPDATE fe_users SET disable = 1 WHERE uid = $uid");
		return null;
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
            $users = $this -> doDatabaseQuery("SELECT uid, username, name, crdate, lastlogin, disable FROM fe_users WHERE NOT deleted=1 ORDER BY username ASC");
        }
        else{
            // Fetch users
            $users = $this -> doDatabaseQuery("SELECT uid, username, name, crdate, lastlogin, disable FROM fe_users WHERE username LIKE '" . $letter . "%' OR username LIKE '" . strtoupper($letter) . "%' AND NOT deleted=1 ORDER BY username ASC");
        }
        // Return
        return $users;
    }

    /* doGetGroups()
    *
    *  Gets information about user groups.
    *
    *  @return Array of fe_groups records.
    */
    function doGetGroups(){
      $res = null;
      $res = $this->doDatabaseQuery("SELECT * FROM fe_groups WHERE NOT deleted = 1 AND NOT hidden = 1");
      return $res;
    }

    /* doGetUsers()
    *
    *  Gets information about fe users.
    *
    *  @return Array of fe_user records.
    */
    function doGetUsers(){
      $res = null;
      $res = $this->doDatabaseQuery("SELECT * FROM fe_users WHERE NOT deleted = 1 AND NOT disable = 1");
      return $res;
    }

    /* doDatabaseQuery($query)
    *
    *  This function runs queries on the typo3 database and returns the
    *  result set in an associative array e.g. $return[0]['myAttribute'].
    *  Please notice, that this function is only suitable for 'SELECT'
    *  queries.
    *
    *  @param $query Database query, which will be executed. e.g. 'SELECT * FROM myTable'
    *  @return array Associative array with query results
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
            print_r($rows);
            echo "<br>";
        }
        // Return the array
        return $rows;
    } 

    /* doDatabaseUpdateQuery($query)
	* 
	*  [Description]
	*  @param $query Database query, which will be executed. e.g. 'UPDATE myTable SET myAttribute=myValue WHERE...'
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



if (defined("TYPO3_MODE") && $TYPO3_CONF_VARS[TYPO3_MODE]["XCLASS"]["ext/cwt_community/mod1/index.php"])	{
	include_once($TYPO3_CONF_VARS[TYPO3_MODE]["XCLASS"]["ext/cwt_community/mod1/index.php"]);
}




// Make instance:
$SOBE = t3lib_div::makeInstance("tx_cwtcommunity_module1");
$SOBE->init();

// Include files?
reset($SOBE->include_once);	
while(list(,$INC_FILE)=each($SOBE->include_once))	{include_once($INC_FILE);}

$SOBE->main();
$SOBE->printContent();

?>
