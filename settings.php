<?php
/*
 +-------------------------------------------------------------------------+
 | Copyright (C) 2004-2016 The Cacti Group                                 |
 |                                                                         |
 | This program is free software; you can redistribute it and/or           |
 | modify it under the terms of the GNU General Public License             |
 | as published by the Free Software Foundation; either version 2          |
 | of the License, or (at your option) any later version.                  |
 |                                                                         |
 | This program is distributed in the hope that it will be useful,         |
 | but WITHOUT ANY WARRANTY; without even the implied warranty of          |
 | MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the           |
 | GNU General Public License for more details.                            |
 +-------------------------------------------------------------------------+
 | Cacti: The Complete RRDTool-based Graphing Solution                     |
 +-------------------------------------------------------------------------+
 | This code is designed, written, and maintained by the Cacti Group. See  |
 | about.php and/or the AUTHORS file for specific developer information.   |
 +-------------------------------------------------------------------------+
 | http://www.cacti.net/                                                   |
 +-------------------------------------------------------------------------+
*/

include("./include/auth.php");

/* set default action */
if (!isset($_REQUEST["action"])) { $_REQUEST["action"] = ""; }

switch ($_REQUEST["action"]) {
case 'save':
	while (list($field_name, $field_array) = each($settings{$_POST["tab"]})) {
		if (($field_array["method"] == "header") || ($field_array["method"] == "spacer" )){
			/* do nothing */
		}elseif ($field_array["method"] == "checkbox") {
			if (isset($_POST[$field_name])) {
				db_execute("replace into settings (name,value) values ('$field_name', 'on')");
			}else{
				db_execute("replace into settings (name,value) values ('$field_name', '')");
			}
		}elseif ($field_array["method"] == "checkbox_group") {
			while (list($sub_field_name, $sub_field_array) = each($field_array["items"])) {
				if (isset($_POST[$sub_field_name])) {
					db_execute("replace into settings (name,value) values ('$sub_field_name', 'on')");
				}else{
					db_execute("replace into settings (name,value) values ('$sub_field_name', '')");
				}
			}
		}elseif ($field_array["method"] == "textbox_password") {
			if ($_POST[$field_name] != $_POST[$field_name."_confirm"]) {
				raise_message(4);
				break;
			}elseif (isset($_POST[$field_name])) {
				$value = $cnn_id->qstr(get_request_var_post($field_name));
				db_execute("replace into settings (name,value) values ('$field_name', $value)");
			}
		}elseif ((isset($field_array["items"])) && (is_array($field_array["items"]))) {
			while (list($sub_field_name, $sub_field_array) = each($field_array["items"])) {
				if (isset($_POST[$sub_field_name])) {
					$value = $cnn_id->qstr(get_request_var_post($sub_field_name));

					db_execute("replace into settings (name,value) values ('$sub_field_name', $value)");
				}
			}
		}elseif (isset($_POST[$field_name])) {
			$value = $cnn_id->qstr(get_request_var_post($field_name));
			db_execute("replace into settings (name,value) values ('$field_name', $value)");
		}
	}
	api_plugin_hook_function('global_settings_update');
	raise_message(1);

	/* reset local settings cache so the user sees the new settings */
	kill_session_var("sess_config_array");

	header("Location: settings.php?tab=" . $_POST["tab"]);
	break;
default:
	include("./include/top_header.php");

	/* clean up tab string */
	if (isset($_REQUEST['tab'])) {
		$_REQUEST['tab'] = sanitize_search_string(get_request_var_request('tab'));
	}

	/* set the default settings category */
	if (!isset($_GET["tab"])) {
		/* there is no selected tab; select the first one */
		$current_tab = array_keys($tabs);
		$current_tab = $current_tab[0];
	}else{
		$current_tab = $_GET["tab"];
	}

	/* draw the categories tabs on the top of the page */
	print "<table cellpadding='0' cellspacing='0' border='0'><tr><td>\n";
	print "<table class='tabs' cellspacing='0' cellpadding='3' align='left'><tr class='tabsMarginLeft'>\n";

	if (sizeof($tabs) > 0) {
	foreach (array_keys($tabs) as $tab_short_name) {
		print "<td " . (($tab_short_name == $current_tab) ? "class='tabSelected tab'" : "class='tabNotSelected tab'") . " align='center'><span class='textHeader'><a href='" . htmlspecialchars("settings.php?tab=$tab_short_name") . "'>$tabs[$tab_short_name]</a></span></td><td class='tabSpacer'></td>\n";
	}
	}

	print "</tr></table></td>\n";
	print "</tr></table><table width='100%' cellpadding='0' cellspacing='0' border='0'><tr><td>\n";

	/* print form for validation */
	print "<form method='post' autocomplete='off' action='settings.php'>\n";

	html_start_box("<strong>Cacti Settings (" . $tabs[$current_tab] . ")</strong>", "100%", "", "3", "center", "");

	$form_array = array();

	while (list($field_name, $field_array) = each($settings[$current_tab])) {
		$form_array += array($field_name => $field_array);

		if ((isset($field_array["items"])) && (is_array($field_array["items"]))) {
			while (list($sub_field_name, $sub_field_array) = each($field_array["items"])) {
				if (config_value_exists($sub_field_name)) {
					$form_array[$field_name]["items"][$sub_field_name]["form_id"] = 1;
				}

				$form_array[$field_name]["items"][$sub_field_name]["value"] = db_fetch_cell("select value from settings where name='$sub_field_name'");
			}
		}else{
			if (config_value_exists($field_name)) {
				$form_array[$field_name]["form_id"] = 1;
			}

			$form_array[$field_name]["value"] = db_fetch_cell("select value from settings where name='$field_name'");
		}
	}

	draw_edit_form(
		array(
			"config" => array("no_form_tag" => true),
			"fields" => $form_array)
			);

	html_end_box();

	form_hidden_box("tab", $current_tab, "");

	form_save_button("", "save");

	print "</form></td></tr></table>\n";

	include("./include/bottom_footer.php");

	break;
}
?>
