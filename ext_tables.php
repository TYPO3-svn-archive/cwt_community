<?php
if (!defined ("TYPO3_MODE")) 	die ("Access denied.");

if (TYPO3_MODE=="BE")    {
    t3lib_extMgm::addModule("txcwtcommunityM2","","",t3lib_extMgm::extPath($_EXTKEY)."mod2/");
    t3lib_extMgm::addModule("txcwtcommunityM2","txcwtcommunityM1","",t3lib_extMgm::extPath($_EXTKEY)."mod1/");
}

$TCA["tx_cwtcommunity_message"] = Array (
	"ctrl" => Array (
		"title" => "LLL:EXT:cwt_community/locallang_db.php:tx_cwtcommunity_message",
		"label" => "uid",
		"tstamp" => "tstamp",
		"crdate" => "crdate",
		"cruser_id" => "cruser_id",
		"default_sortby" => "ORDER BY crdate",
		"delete" => "deleted",
		"enablecolumns" => Array (
			"disabled" => "hidden",
			"fe_group" => "fe_group",
		),
		"dynamicConfigFile" => t3lib_extMgm::extPath($_EXTKEY)."tca.php",
		"iconfile" => t3lib_extMgm::extRelPath($_EXTKEY)."icon_tx_cwtcommunity_message.gif",
	),
	"feInterface" => Array (
		"fe_admin_fieldList" => "hidden, fe_group, fe_users_uid, subject, body, status",
	)
);

$TCA["tx_cwtcommunity_buddylist"] = Array (
	"ctrl" => Array (
		"title" => "LLL:EXT:cwt_community/locallang_db.php:tx_cwtcommunity_buddylist",
		"label" => "uid",
		"tstamp" => "tstamp",
		"crdate" => "crdate",
		"cruser_id" => "cruser_id",
		"default_sortby" => "ORDER BY crdate",
		"delete" => "deleted",
		"enablecolumns" => Array (
			"disabled" => "hidden",
			"fe_group" => "fe_group",
		),
		"dynamicConfigFile" => t3lib_extMgm::extPath($_EXTKEY)."tca.php",
		"iconfile" => t3lib_extMgm::extRelPath($_EXTKEY)."icon_tx_cwtcommunity_buddylist.gif",
	),
	"feInterface" => Array (
		"fe_admin_fieldList" => "hidden, fe_group, fe_users_uid, buddy_uid",
	)
);

$TCA["tx_cwtcommunity_buddylist_approval"] = Array (
	"ctrl" => Array (
		"title" => "LLL:EXT:cwt_community/locallang_db.php:tx_cwtcommunity_buddylist_approval",
		"label" => "uid",
		"tstamp" => "tstamp",
		"crdate" => "crdate",
		"cruser_id" => "cruser_id",
		"default_sortby" => "ORDER BY crdate",
		"delete" => "deleted",
		"enablecolumns" => Array (
			"disabled" => "hidden",
			"fe_group" => "fe_group",
		),
		"dynamicConfigFile" => t3lib_extMgm::extPath($_EXTKEY)."tca.php",
		"iconfile" => t3lib_extMgm::extRelPath($_EXTKEY)."icon_tx_cwtcommunity_buddylist_approval.gif",
	),
	"feInterface" => Array (
		"fe_admin_fieldList" => "hidden, fe_group, requestor_uid, target_uid",
	)
);

$TCA["tx_cwtcommunity_guestbook"] = Array (
	"ctrl" => Array (
		"title" => "LLL:EXT:cwt_community/locallang_db.php:tx_cwtcommunity_guestbook",
		"label" => "uid",
		"tstamp" => "tstamp",
		"crdate" => "crdate",
		"cruser_id" => "cruser_id",
		"default_sortby" => "ORDER BY crdate",
		"delete" => "deleted",
		"enablecolumns" => Array (
			"disabled" => "hidden",
			"fe_group" => "fe_group",
		),
		"dynamicConfigFile" => t3lib_extMgm::extPath($_EXTKEY)."tca.php",
		"iconfile" => t3lib_extMgm::extRelPath($_EXTKEY)."icon_tx_cwtcommunity_guestbook.gif",
	),
	"feInterface" => Array (
		"fe_admin_fieldList" => "hidden, fe_group, fe_users_uid, status",
	)
);

$TCA["tx_cwtcommunity_guestbook_data"] = Array (
	"ctrl" => Array (
		"title" => "LLL:EXT:cwt_community/locallang_db.php:tx_cwtcommunity_guestbook_data",
		"label" => "uid",
		"tstamp" => "tstamp",
		"crdate" => "crdate",
		"cruser_id" => "cruser_id",
		"default_sortby" => "ORDER BY crdate",
		"delete" => "deleted",
		"enablecolumns" => Array (
			"disabled" => "hidden",
			"fe_group" => "fe_group",
		),
		"dynamicConfigFile" => t3lib_extMgm::extPath($_EXTKEY)."tca.php",
		"iconfile" => t3lib_extMgm::extRelPath($_EXTKEY)."icon_tx_cwtcommunity_guestbook_data.gif",
	),
	"feInterface" => Array (
		"fe_admin_fieldList" => "hidden, fe_group, guestbook_uid, text",
	)
);


$TCA["tx_cwtcommunity_icons"] = Array (
    "ctrl" => Array (
        "title" => "LLL:EXT:cwt_community/locallang_db.php:tx_cwtcommunity_icons",
        "label" => "string",
        "tstamp" => "tstamp",
        "crdate" => "crdate",
        "cruser_id" => "cruser_id",
        "default_sortby" => "ORDER BY crdate",
        "delete" => "deleted",
        "enablecolumns" => Array (
            "disabled" => "hidden",
        ),
        "dynamicConfigFile" => t3lib_extMgm::extPath($_EXTKEY)."tca.php",
        "iconfile" => t3lib_extMgm::extRelPath($_EXTKEY)."icon_tx_cwtcommunity_icons.gif",
    ),
    "feInterface" => Array (
        "fe_admin_fieldList" => "hidden, string, icon",
    )
);

t3lib_div::loadTCA("tt_content");
$TCA["tt_content"]["types"]["list"]["subtypes_excludelist"][$_EXTKEY."_pi1"]="layout,select_key";
$TCA['tt_content']['types']['list']['subtypes_addlist'][$_EXTKEY.'_pi1']='pi_flexform';

t3lib_extMgm::addPlugin(Array("LLL:EXT:cwt_community/locallang_db.php:tt_content.list_type", $_EXTKEY."_pi1"),"list_type");
t3lib_extMgm::addPiFlexFormValue($_EXTKEY.'_pi1', 'FILE:EXT:cwt_community/flexform_ds.xml');
?>
