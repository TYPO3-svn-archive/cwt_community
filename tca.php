<?php
if (!defined ("TYPO3_MODE")) 	die ("Access denied.");

$TCA["tx_cwtcommunity_message"] = Array (
	"ctrl" => $TCA["tx_cwtcommunity_message"]["ctrl"],
	"interface" => Array (
		"showRecordFieldList" => "hidden,fe_group,fe_users_uid,subject,body,status"
	),
	"feInterface" => $TCA["tx_cwtcommunity_message"]["feInterface"],
	"columns" => Array (
		"hidden" => Array (		
			"exclude" => 1,	
			"label" => "LLL:EXT:lang/locallang_general.php:LGL.hidden",
			"config" => Array (
				"type" => "check",
				"default" => "0"
			)
		),
		"fe_group" => Array (		
			"exclude" => 1,	
			"label" => "LLL:EXT:lang/locallang_general.php:LGL.fe_group",
			"config" => Array (
				"type" => "select",
				"items" => Array (
					Array("", 0),
					Array("LLL:EXT:lang/locallang_general.php:LGL.hide_at_login", -1),
					Array("LLL:EXT:lang/locallang_general.php:LGL.any_login", -2),
					Array("LLL:EXT:lang/locallang_general.php:LGL.usergroups", "--div--")
				),
				"foreign_table" => "fe_groups"
			)
		),
		"fe_users_uid" => Array (		
			"exclude" => 1,		
			"label" => "LLL:EXT:cwt_community/locallang_db.php:tx_cwtcommunity_message.fe_users_uid",		
			"config" => Array (
				"type" => "group",	
				"internal_type" => "db",	
				"allowed" => "fe_users",
				"size" => 1,	
				"minitems" => 0,
				"maxitems" => 1,
			)
		),
		"subject" => Array (		
			"exclude" => 1,		
			"label" => "LLL:EXT:cwt_community/locallang_db.php:tx_cwtcommunity_message.subject",		
			"config" => Array (
				"type" => "input",	
				"size" => "30",	
				"max" => "30",
			)
		),
		"body" => Array (		
			"exclude" => 1,		
			"label" => "LLL:EXT:cwt_community/locallang_db.php:tx_cwtcommunity_message.body",		
			"config" => Array (
				"type" => "text",
				"cols" => "30",	
				"rows" => "5",
			)
		),
		"status" => Array (
			"exclude" => 1,
			"label" => "LLL:EXT:cwt_community/locallang_db.php:tx_cwtcommunity_message.status",
			"config" => Array (
				"type" => "select",
				"items" => Array (
					Array("LLL:EXT:cwt_community/locallang_db.php:tx_cwtcommunity_message.status.I.0", "0"),
					Array("LLL:EXT:cwt_community/locallang_db.php:tx_cwtcommunity_message.status.I.1", "1"),
				),
			)
		),
	),
	"types" => Array (
		"0" => Array("showitem" => "hidden;;1;;1-1-1, fe_users_uid, subject, body, status")
	),
	"palettes" => Array (
		"1" => Array("showitem" => "fe_group")
	)
);



$TCA["tx_cwtcommunity_buddylist"] = Array (
	"ctrl" => $TCA["tx_cwtcommunity_buddylist"]["ctrl"],
	"interface" => Array (
		"showRecordFieldList" => "hidden,fe_group,fe_users_uid,buddy_uid"
	),
	"feInterface" => $TCA["tx_cwtcommunity_buddylist"]["feInterface"],
	"columns" => Array (
		"hidden" => Array (		
			"exclude" => 1,	
			"label" => "LLL:EXT:lang/locallang_general.php:LGL.hidden",
			"config" => Array (
				"type" => "check",
				"default" => "0"
			)
		),
		"fe_group" => Array (		
			"exclude" => 1,	
			"label" => "LLL:EXT:lang/locallang_general.php:LGL.fe_group",
			"config" => Array (
				"type" => "select",	
				"items" => Array (
					Array("", 0),
					Array("LLL:EXT:lang/locallang_general.php:LGL.hide_at_login", -1),
					Array("LLL:EXT:lang/locallang_general.php:LGL.any_login", -2),
					Array("LLL:EXT:lang/locallang_general.php:LGL.usergroups", "--div--")
				),
				"foreign_table" => "fe_groups"
			)
		),
		"fe_users_uid" => Array (		
			"exclude" => 1,
			"label" => "LLL:EXT:cwt_community/locallang_db.php:tx_cwtcommunity_buddylist.fe_users_uid",		
			"config" => Array (
				"type" => "group",	
				"internal_type" => "db",	
				"allowed" => "fe_users",	
				"size" => 1,	
				"minitems" => 0,
				"maxitems" => 1,
			)
		),
		"buddy_uid" => Array (		
			"exclude" => 1,		
			"label" => "LLL:EXT:cwt_community/locallang_db.php:tx_cwtcommunity_buddylist.buddy_uid",		
			"config" => Array (
				"type" => "group",	
				"internal_type" => "db",	
				"allowed" => "fe_users",	
				"size" => 1,	
				"minitems" => 0,
				"maxitems" => 1,
			)
		),
	),
	"types" => Array (
		"0" => Array("showitem" => "hidden;;1;;1-1-1, fe_users_uid, buddy_uid")
	),
	"palettes" => Array (
		"1" => Array("showitem" => "fe_group")
	)
);



$TCA["tx_cwtcommunity_buddylist_approval"] = Array (
	"ctrl" => $TCA["tx_cwtcommunity_buddylist_approval"]["ctrl"],
	"interface" => Array (
		"showRecordFieldList" => "hidden,fe_group,requestor_uid,target_uid"
	),
	"feInterface" => $TCA["tx_cwtcommunity_buddylist_approval"]["feInterface"],
	"columns" => Array (
		"hidden" => Array (		
			"exclude" => 1,	
			"label" => "LLL:EXT:lang/locallang_general.php:LGL.hidden",
			"config" => Array (
				"type" => "check",
				"default" => "0"
			)
		),
		"fe_group" => Array (		
			"exclude" => 1,	
			"label" => "LLL:EXT:lang/locallang_general.php:LGL.fe_group",
			"config" => Array (
				"type" => "select",	
				"items" => Array (
					Array("", 0),
					Array("LLL:EXT:lang/locallang_general.php:LGL.hide_at_login", -1),
					Array("LLL:EXT:lang/locallang_general.php:LGL.any_login", -2),
					Array("LLL:EXT:lang/locallang_general.php:LGL.usergroups", "--div--")
				),
				"foreign_table" => "fe_groups"
			)
		),
		"requestor_uid" => Array (		
			"exclude" => 1,		
			"label" => "LLL:EXT:cwt_community/locallang_db.php:tx_cwtcommunity_buddylist_approval.requestor_uid",		
			"config" => Array (
				"type" => "group",	
				"internal_type" => "db",	
				"allowed" => "fe_users",	
				"size" => 1,	
				"minitems" => 0,
				"maxitems" => 1,
			)
		),
		"target_uid" => Array (		
			"exclude" => 1,
			"label" => "LLL:EXT:cwt_community/locallang_db.php:tx_cwtcommunity_buddylist_approval.target_uid",		
			"config" => Array (
				"type" => "group",	
				"internal_type" => "db",	
				"allowed" => "fe_users",	
				"size" => 1,	
				"minitems" => 0,
				"maxitems" => 1,
			)
		),
	),
	"types" => Array (
		"0" => Array("showitem" => "hidden;;1;;1-1-1, requestor_uid, target_uid")
	),
	"palettes" => Array (
		"1" => Array("showitem" => "fe_group")
	)
);



$TCA["tx_cwtcommunity_guestbook"] = Array (
	"ctrl" => $TCA["tx_cwtcommunity_guestbook"]["ctrl"],
	"interface" => Array (
		"showRecordFieldList" => "hidden,fe_group,fe_users_uid,status"
	),
	"feInterface" => $TCA["tx_cwtcommunity_guestbook"]["feInterface"],
	"columns" => Array (
		"hidden" => Array (		
			"exclude" => 1,	
			"label" => "LLL:EXT:lang/locallang_general.php:LGL.hidden",
			"config" => Array (
				"type" => "check",
				"default" => "0"
			)
		),
		"fe_group" => Array (		
			"exclude" => 1,
			"label" => "LLL:EXT:lang/locallang_general.php:LGL.fe_group",
			"config" => Array (
				"type" => "select",	
				"items" => Array (
					Array("", 0),
					Array("LLL:EXT:lang/locallang_general.php:LGL.hide_at_login", -1),
					Array("LLL:EXT:lang/locallang_general.php:LGL.any_login", -2),
					Array("LLL:EXT:lang/locallang_general.php:LGL.usergroups", "--div--")
				),
				"foreign_table" => "fe_groups"
			)
		),
		"fe_users_uid" => Array (		
			"exclude" => 1,		
			"label" => "LLL:EXT:cwt_community/locallang_db.php:tx_cwtcommunity_guestbook.fe_users_uid",		
			"config" => Array (
				"type" => "group",	
				"internal_type" => "db",	
				"allowed" => "fe_users",	
				"size" => 1,	
				"minitems" => 0,
				"maxitems" => 1,
			)
		),
		"status" => Array (		
			"exclude" => 1,		
			"label" => "LLL:EXT:cwt_community/locallang_db.php:tx_cwtcommunity_guestbook.status",		
			"config" => Array (
				"type" => "select",
				"items" => Array (
					Array("LLL:EXT:cwt_community/locallang_db.php:tx_cwtcommunity_guestbook.status.I.0", "0"),
					Array("LLL:EXT:cwt_community/locallang_db.php:tx_cwtcommunity_guestbook.status.I.1", "1"),
				),
			)
		),
	),
	"types" => Array (
		"0" => Array("showitem" => "hidden;;1;;1-1-1, fe_users_uid, status")
	),
	"palettes" => Array (
		"1" => Array("showitem" => "fe_group")
	)
);



$TCA["tx_cwtcommunity_guestbook_data"] = Array (
	"ctrl" => $TCA["tx_cwtcommunity_guestbook_data"]["ctrl"],
	"interface" => Array (
		"showRecordFieldList" => "hidden,fe_group,guestbook_uid,text"
	),
	"feInterface" => $TCA["tx_cwtcommunity_guestbook_data"]["feInterface"],
	"columns" => Array (
		"hidden" => Array (		
			"exclude" => 1,	
			"label" => "LLL:EXT:lang/locallang_general.php:LGL.hidden",
			"config" => Array (
				"type" => "check",
				"default" => "0"
			)
		),
		"fe_group" => Array (		
			"exclude" => 1,	
			"label" => "LLL:EXT:lang/locallang_general.php:LGL.fe_group",
			"config" => Array (
				"type" => "select",	
				"items" => Array (
					Array("", 0),
					Array("LLL:EXT:lang/locallang_general.php:LGL.hide_at_login", -1),
					Array("LLL:EXT:lang/locallang_general.php:LGL.any_login", -2),
					Array("LLL:EXT:lang/locallang_general.php:LGL.usergroups", "--div--")
				),
				"foreign_table" => "fe_groups"
			)
		),
		"guestbook_uid" => Array (
			"exclude" => 1,		
			"label" => "LLL:EXT:cwt_community/locallang_db.php:tx_cwtcommunity_guestbook_data.guestbook_uid",		
			"config" => Array (
				"type" => "group",	
				"internal_type" => "db",	
				"allowed" => "tx_cwtcommunity_guestbook",	
				"size" => 1,	
				"minitems" => 0,
				"maxitems" => 1,
			)
		),
		"text" => Array (		
			"exclude" => 1,		
			"label" => "LLL:EXT:cwt_community/locallang_db.php:tx_cwtcommunity_guestbook_data.text",		
			"config" => Array (
				"type" => "text",
				"cols" => "30",	
				"rows" => "5",
			)
		),
	),
	"types" => Array (
		"0" => Array("showitem" => "hidden;;1;;1-1-1, guestbook_uid, text")
	),
	"palettes" => Array (
		"1" => Array("showitem" => "fe_group")
	)
);

$TCA["tx_cwtcommunity_icons"] = Array (
    "ctrl" => $TCA["tx_cwtcommunity_icons"]["ctrl"],
    "interface" => Array (
        "showRecordFieldList" => "hidden,string,icon"
    ),
    "feInterface" => $TCA["tx_cwtcommunity_icons"]["feInterface"],
    "columns" => Array (
        "hidden" => Array (
            "exclude" => 1,
            "label" => "LLL:EXT:lang/locallang_general.php:LGL.hidden",
            "config" => Array (
                "type" => "check",
                "default" => "0"
            )
        ),
        "string" => Array (
            "exclude" => 1,
            "label" => "LLL:EXT:cwt_community/locallang_db.php:tx_cwtcommunity_icons.string",
            "config" => Array (
                "type" => "input",
                "size" => "30",
                "eval" => "required",
            )
        ),
        "icon" => Array (
            "exclude" => 1,
            "label" => "LLL:EXT:cwt_community/locallang_db.php:tx_cwtcommunity_icons.icon",
            "config" => Array (
                "type" => "group",
                "internal_type" => "file",
                "allowed" => $GLOBALS["TYPO3_CONF_VARS"]["GFX"]["imagefile_ext"],
                "max_size" => 500,
                "uploadfolder" => "uploads/tx_cwtcommunity/icons",
                "show_thumbs" => 1,
                "size" => 1,
                "minitems" => 0,
                "maxitems" => 1,
            )
        ),
    ),
    "types" => Array (
        "0" => Array("showitem" => "hidden;;1;;1-1-1, string, icon")
    ),
    "palettes" => Array (
        "1" => Array("showitem" => "")
    )
);
?>
