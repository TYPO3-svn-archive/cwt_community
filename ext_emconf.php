<?php

########################################################################
# Extension Manager/Repository config file for ext: "cwt_community"
# 
# Auto generated 17-05-2006 20:22
# 
# Manual updates:
# Only the data in the array - anything else is removed by next write
########################################################################

$EM_CONF[$_EXTKEY] = Array (
	'title' => 'CWT Community',
	'description' => 'This extension provides a wide range of community features for frontend users. It mainly consists of the following parts:
Userlist, Profile, Profile Administration, Guestbook, Messages, Buddylist, Backend
User Administration.',
	'category' => 'plugin',
	'shy' => 0,
	'dependencies' => 'cms,cwt_feedit,cwt_community_user',
	'conflicts' => '',
	'priority' => '',
	'loadOrder' => '',
	'TYPO3_version' => '0.0.2-0.0.2',
	'PHP_version' => '0.0.2-0.0.2',
	'module' => 'mod1',
	'state' => 'beta',
	'internal' => '',
	'uploadfolder' => 0,
	'createDirs' => 'uploads/tx_cwtcommunity,uploads/tx_cwtcommunity/icons',
	'modify_tables' => '',
	'clearCacheOnLoad' => 1,
	'lockType' => '',
	'author' => 'Sebastian Faulhaber',
	'author_email' => 's.faulhaber@web-sol.de',
	'author_company' => 'websol - new media consulting & services',
	'CGLcompliance' => '',
	'CGLcompliance_note' => '',
	'version' => '0.8.2',	// Don't modify this! Managed automatically during upload to repository.
	'_md5_values_when_last_written' => 'a:74:{s:8:".project";s:4:"c880";s:12:"ext_icon.gif";s:4:"5bd4";s:17:"ext_localconf.php";s:4:"250d";s:14:"ext_tables.php";s:4:"4350";s:14:"ext_tables.sql";s:4:"8fc2";s:28:"ext_typoscript_constants.txt";s:4:"8450";s:28:"ext_typoscript_editorcfg.txt";s:4:"60aa";s:24:"ext_typoscript_setup.txt";s:4:"2415";s:15:"flexform_ds.xml";s:4:"b9eb";s:34:"icon_tx_cwtcommunity_buddylist.gif";s:4:"e0f2";s:43:"icon_tx_cwtcommunity_buddylist_approval.gif";s:4:"e0f2";s:34:"icon_tx_cwtcommunity_guestbook.gif";s:4:"320d";s:39:"icon_tx_cwtcommunity_guestbook_data.gif";s:4:"320d";s:30:"icon_tx_cwtcommunity_icons.gif";s:4:"a765";s:32:"icon_tx_cwtcommunity_message.gif";s:4:"774d";s:16:"locallang_db.php";s:4:"178c";s:17:"locallang_tca.php";s:4:"58e9";s:13:"project.index";s:4:"40ac";s:7:"tca.php";s:4:"8382";s:11:"CVS/Entries";s:4:"a980";s:14:"CVS/Repository";s:4:"2e5f";s:8:"CVS/Root";s:4:"b748";s:19:"doc/wizard_form.dat";s:4:"31f7";s:20:"doc/wizard_form.html";s:4:"78dd";s:15:"doc/CVS/Entries";s:4:"1bef";s:18:"doc/CVS/Repository";s:4:"803e";s:12:"doc/CVS/Root";s:4:"b748";s:22:"mod1/action_delete.gif";s:4:"6f1a";s:23:"mod1/action_disable.gif";s:4:"0860";s:22:"mod1/action_enable.gif";s:4:"5da3";s:14:"mod1/clear.gif";s:4:"cc11";s:13:"mod1/conf.php";s:4:"3c2c";s:14:"mod1/index.php";s:4:"5959";s:18:"mod1/locallang.php";s:4:"9864";s:22:"mod1/locallang_mod.php";s:4:"27e3";s:19:"mod1/moduleicon.gif";s:4:"8153";s:16:"mod1/CVS/Entries";s:4:"15e7";s:19:"mod1/CVS/Repository";s:4:"17d8";s:13:"mod1/CVS/Root";s:4:"b748";s:14:"mod2/clear.gif";s:4:"cc11";s:13:"mod2/conf.php";s:4:"1ab8";s:14:"mod2/index.php";s:4:"a473";s:18:"mod2/locallang.php";s:4:"dd99";s:22:"mod2/locallang_mod.php";s:4:"2f87";s:19:"mod2/moduleicon.gif";s:4:"8153";s:16:"mod2/CVS/Entries";s:4:"79dc";s:19:"mod2/CVS/Repository";s:4:"53c3";s:13:"mod2/CVS/Root";s:4:"b748";s:39:"pi1/.#class.tx_cwtcommunity_pi1.php.1.1";s:4:"e799";s:33:"pi1/class.tx_cwtcommunity_pi1.php";s:4:"ad27";s:24:"pi1/guestbook_delete.gif";s:4:"4048";s:13:"pi1/icons.png";s:4:"c5c0";s:17:"pi1/locallang.php";s:4:"60a9";s:23:"pi1/messages_answer.gif";s:4:"9a23";s:20:"pi1/messages_new.gif";s:4:"4ac7";s:21:"pi1/messages_read.gif";s:4:"fae4";s:23:"pi1/messages_unread.gif";s:4:"9081";s:38:"pi1/tx_cwtcommunity_pi1_buddylist.tmpl";s:4:"025b";s:38:"pi1/tx_cwtcommunity_pi1_guestbook.tmpl";s:4:"eb3e";s:37:"pi1/tx_cwtcommunity_pi1_messages.tmpl";s:4:"f926";s:36:"pi1/tx_cwtcommunity_pi1_profile.tmpl";s:4:"5a92";s:35:"pi1/tx_cwtcommunity_pi1_search.tmpl";s:4:"772c";s:37:"pi1/tx_cwtcommunity_pi1_userlist.tmpl";s:4:"3121";s:36:"pi1/tx_cwtcommunity_pi1_welcome.tmpl";s:4:"20d8";s:25:"pi1/userlist_addbuddy.gif";s:4:"6cf5";s:23:"pi1/userlist_female.gif";s:4:"a215";s:21:"pi1/userlist_male.gif";s:4:"b3b0";s:31:"pi1/userlist_status_offline.gif";s:4:"71e9";s:30:"pi1/userlist_status_online.gif";s:4:"381b";s:23:"pi1/welcome_newmail.gif";s:4:"01d6";s:25:"pi1/welcome_nonewmail.gif";s:4:"9a95";s:15:"pi1/CVS/Entries";s:4:"c80b";s:18:"pi1/CVS/Repository";s:4:"114a";s:12:"pi1/CVS/Root";s:4:"b748";}',
);

?>