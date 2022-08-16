<?php

if (!defined("IN_MYBB")) die("Direct initialization of this file is not allowed.");

function contentwarnings_info() {
    global $lang;
    $lang->load('contentwarnings');

    return array(
        "name"          => "Content Warnings",
        "description"   => $lang->plugin_desc,
        "website"       => "https://github.com/venomnomous/content-warnings",
        "author"        => "Venomous",
        "authorsite"    => "https://github.com/venomnomous",
        "version"       => "1.0",
        "codename"      => "contentwarnings",
        "compatibility" => "18*"
    );
}

function contentwarnings_install() {
    global $db, $lang;
    $lang->load('contentwarnings');

    $db->add_column("posts", "contentwarnings", "varchar(255) NOT NULL DEFAULT ''");

    $setting_group = array(
        'name' => 'cw',
        'title' => $lang->setting_title,
        'description' => $lang->setting_desc,
        'disporder' => 30,
        'isdefault' => 0
    );

    $gid = $db->insert_query("settinggroups", $setting_group);

    $setting_array = array(
        'cw_fid' => array(
            'title' => $lang->setting_title1,
            'description' => $lang->setting_desc1,
            'optionscode' => 'text',
            'value' => '0',
            'disporder' => 1
        ),
        'cw_areas' => array(
            'title' => $lang->setting_title2,
            'description' => $lang->setting_desc2,
            'optionscode' => 'forumselect',
            'value' => '',
            'disporder' => 20
        )
    );

    $db->delete_query('settings', "name IN ('cw_fid','cw_areas')");

    foreach ($setting_array as $name => $setting) {
        $setting['name'] = $name;
        $setting['gid'] = $gid;
    
        $db->insert_query('settings', $setting);
    }

    rebuild_settings();

    $template_group1 = array(
        'prefix' => $db->escape_string("cw"),
        'title' => $db->escape_string($lang->template_title1),
        'isdefault' => 0
    );

    $db->insert_query('templategroups', $template_group1);

    cw_templates_add();
    cw_css_add();
}

function contentwarnings_is_installed() {
    global $db;

    if ($db->field_exists("contentwarnings", "posts")) return true;
    return false;
}

function contentwarnings_uninstall() {
    global $db;

    if ($db->field_exists("contentwarnings", "posts")) $db->drop_column("posts", "contentwarnings");

    $db->delete_query('settings', "name IN ('cw_fid','cw_areas')");
    $db->delete_query('settinggroups', "name = 'cw'");

    rebuild_settings();

    $db->delete_query("templategroups", "prefix = 'cw'");
    $db->delete_query("templates", "title LIKE 'cw%'");

    require_once MYBB_ADMIN_DIR . "inc/functions_themes.php";

    $db->delete_query("themestylesheets", "name = 'cw.css'");
    $query = $db->simple_select("themes", "tid");

    while($theme = $db->fetch_array($query)) {
        update_theme_stylesheet_list($theme['tid']);
    }
}

function contentwarnings_activate() {
    global $lang;
    $lang->load('contentwarnings');

    require_once MYBB_ROOT . "inc/adminfunctions_templates.php";

    find_replace_templatesets("newthread", "#".preg_quote('{$postoptions}').'#i', '{$cw_input}{$postoptions}');
    find_replace_templatesets("newreply", "#".preg_quote('{$postoptions}').'#i', '{$cw_input}{$postoptions} ');
    find_replace_templatesets("editpost", "#".preg_quote('{$editreason}').'#i', '{$editreason}{$cw_input}');
    find_replace_templatesets("postbit", "#".preg_quote('{$post[\'message\']}').'#i', '{$post[\'contentwarning\']}{$post[\'message\']}');
    find_replace_templatesets("postbit_classic", "#".preg_quote('{$post[\'message\']}').'#i', '{$post[\'contentwarning\']}{$post[\'message\']}');
    find_replace_templatesets("postbit", "#".preg_quote('{$post[\'attachments\']}').'#i', '{$post[\'cw_display\']}{$post[\'attachments\']}');
    find_replace_templatesets("postbit_classic", "#".preg_quote('{$post[\'attachments\']}').'#i', '{$post[\'cw_display\']}{$post[\'attachments\']}');
}

function contentwarnings_deactivate() {
    global $lang;
    $lang->load('contentwarnings');

    require_once MYBB_ROOT . "inc/adminfunctions_templates.php";

    find_replace_templatesets("newthread", "#".preg_quote('{$cw_input}')."#i", '', 0);
    find_replace_templatesets("newreply", "#".preg_quote('{$cw_input}')."#i", '', 0);
    find_replace_templatesets("editpost", "#".preg_quote('{$cw_input}')."#i", '', 0);
    find_replace_templatesets("postbit", "#".preg_quote('{$post[\'contentwarning\']}')."#i", '', 0);
    find_replace_templatesets("postbit_classic", "#".preg_quote('{$post[\'contentwarning\']}')."#i", '', 0);
    find_replace_templatesets("postbit", "#".preg_quote('{$post[\'cw_display\']}')."#i", '', 0);
    find_replace_templatesets("postbit_classic", "#".preg_quote('{$post[\'cw_display\']}')."#i", '', 0);
}

$plugins->add_hook("newthread_start", "add_cw_newthread");
function add_cw_newthread() {
    global $db, $mybb, $lang, $templates, $cw_input;
    $lang->load('contentwarnings');

    $fid = $_REQUEST['fid'];

    $contentwarning = $db->fetch_array($db->simple_select("profilefields", "*", "fid = '" . $mybb->settings['cw_fid'] . "'"));
    $contentwarnings = preg_split('/\r\n|\r|\n/', $contentwarning['type']);

    $contentwarnings_options = "";
    for($i = 1; $i < count($contentwarnings); $i++) {
        $contentwarnings_options .= "<option value=\"{$contentwarnings[$i]}\">{$contentwarnings[$i]}</option>";
    }

    if((my_strpos(','.$mybb->settings['cw_areas'].',', ','.(int)$fid.',') !== false || $mybb->settings['cw_areas'] == -1) && $mybb->settings['cw_fid'] != '0') {
        eval("\$cw_input = \"".$templates->get("cw_input")."\";");
    }
}

$plugins->add_hook("newreply_start", "add_cw_newreply");
function add_cw_newreply() {
    global $db, $mybb, $lang, $templates, $cw_input;
    $lang->load('contentwarnings');

    $tid = $_REQUEST['tid'];
    $fquery = $db->query("
        SELECT *
        FROM " . TABLE_PREFIX . "forums f
        JOIN " . TABLE_PREFIX . "threads t
        ON f.fid = t.fid
        WHERE t.tid = '" . $tid . "'
    ");
    $fid = $db->fetch_field($fquery, "fid");
    
    $contentwarning = $db->fetch_array($db->simple_select("profilefields", "*", "fid = '" . $mybb->settings['cw_fid'] . "'"));
    $contentwarnings = preg_split('/\r\n|\r|\n/', $contentwarning['type']);

    $contentwarnings_options = "";
    for($i = 1; $i < count($contentwarnings); $i++) {
        $contentwarnings_options .= "<option value=\"{$contentwarnings[$i]}\">{$contentwarnings[$i]}</option>";
    }

    if((my_strpos(','.$mybb->settings['cw_areas'].',', ','.(int)$fid.',') !== false || $mybb->settings['cw_areas'] == -1) && $mybb->settings['cw_fid'] != '0') {
        eval("\$cw_input = \"".$templates->get("cw_input")."\";");
    }
}

$plugins->add_hook("editpost_start", "add_cw_input");
function add_cw_input() {
    global $db, $mybb, $lang, $templates, $post, $cw_input;
    $lang->load('contentwarnings');

    $pid = $_REQUEST['pid'];
    $fquery = $db->query("
        SELECT *
        FROM " . TABLE_PREFIX . "threads t
        JOIN " . TABLE_PREFIX . "posts p
        ON t.tid = p.tid
        WHERE p.pid = '" . $pid . "'
    ");
    $fid = $db->fetch_field($fquery, "fid");

    $post = $db->fetch_array($db->simple_select("posts", "*", "pid = '" . $pid . "'"));

    $contentwarning = $db->fetch_array($db->simple_select("profilefields", "*", "fid = '" . $mybb->settings['cw_fid'] . "'"));
    $contentwarnings = preg_split('/\r\n|\r|\n/', $contentwarning['type']);
    $selected_contentwarnings = explode(',', $post['contentwarnings']);

    $contentwarnings_options = "";
    $selected_contentwarning = "";
    for($i = 1; $i < count($contentwarnings); $i++) {
        $selected_contentwarning = in_array($contentwarnings[$i], $selected_contentwarnings) ? "selected" : "";
        $contentwarnings_options .= "<option value=\"{$contentwarnings[$i]}\" ".$selected_contentwarning.">{$contentwarnings[$i]}</option>";
    }

    if((my_strpos(','.$mybb->settings['cw_areas'].',', ','.(int)$fid.',') !== false || $mybb->settings['cw_areas'] == -1) && $mybb->settings['cw_fid'] != '0') {
        eval("\$cw_input = \"".$templates->get("cw_input")."\";");
    }
}

$plugins->add_hook("datahandler_post_insert_post", "add_cw_postinsert");
$plugins->add_hook("datahandler_post_insert_thread_post", "add_cw_postinsert");
$plugins->add_hook("datahandler_post_update", "add_cw_postinsert");
function add_cw_postinsert(&$handler) {
    global $db, $mybb;

    $tid = $_REQUEST['tid'];
    $fidquery = $db->query("
        SELECT *
        FROM " . TABLE_PREFIX . "threads t
        JOIN " . TABLE_PREFIX . "forums f
        ON t.fid = f.fid
        WHERE t.tid = '" . $tid . "'
    ");
    $pid = $_REQUEST['pid'];
    $fidpquery = $db->query("
        SELECT *
        FROM " . TABLE_PREFIX . "threads t
        JOIN " . TABLE_PREFIX . "posts p
        ON t.tid = p.tid
        WHERE p.pid = '" . $pid . "'
    ");
    $fid = !empty($_REQUEST['fid']) ? $_REQUEST['fid'] : (!empty($_REQUEST['tid']) ? $db->fetch_field($fidquery, 'fid') : $db->fetch_field($fidpquery, 'fid'));

    if ((my_strpos(','.$mybb->settings['cw_areas'].',', ','.(int)$fid.',') !== false || $mybb->settings['cw_areas'] == -1) && $mybb->settings['cw_fid'] != '0') {
        $tid = $mybb->get_input('tid');

        if ($handler->action == 'post' && $handler->method == 'insert') {
            $handler->post_insert_data['contentwarnings'] = $db->escape_string(implode(',', $_POST['contentwarnings']));
        }

        if (($handler->action == 'post' || $handler->action == 'thread') && $handler->method == 'insert') {
            $handler->post_insert_data['contentwarnings'] = $db->escape_string(implode(',', $_POST['contentwarnings']));
        }

        if ($handler->action == 'post' && $handler->method == 'update') {
            $handler->post_update_data['contentwarnings'] = $db->escape_string(implode(',', $_POST['contentwarnings']));
        }
    }
}

$plugins->add_hook("postbit", "add_cw_postbit");
function add_cw_postbit(&$post) {
    global $db, $mybb, $plugins, $lang;
    $lang->load('contentwarnings');

    $user_contentwarning = $db->fetch_array($db->simple_select("profilefields", "*", "fid = '" . $mybb->settings['cw_fid'] . "'"));
    $user_contentwarnings = preg_split('/\r\n|\r|\n/', $user_contentwarning['type']);

    $post_contentwarnings = explode(',', $post['contentwarnings']);
    $cw_compare = array_intersect($user_contentwarnings, $post_contentwarnings);

    $cw_display = !empty($cw_compare) && !empty($user_contentwarnings) ? "cw_display" : "";
    $contentwarning =  ($mybb->settings['cw_fid'] != '0') && !empty($cw_compare) && !empty($user_contentwarnings) ? '<div class="contentwarning"><center><h2>' . $lang->cw_title . '</h2><br/>'. implode(', ', $cw_compare) .'</br><br/><a href="#pid_' . $post['pid'] . '">' . $lang->cw_show . '</a></center></div>' : '';

    $post['contentwarning'] = $contentwarning;
    $post['cw_display'] = $cw_display;
}

function cw_templates_add() {
    global $db;

    $templates[] = array(
        'title' => 'cw_input',
        'template' => $db->escape_string('
<tr>
    <td class="trow1">
        <strong>{$lang->cw_input_title}</strong>
    </td>
    <td class="trow1">
        <select name="contentwarnings[]" id="contentwarnings" multiple>{$contentwarnings_options}</select>
    </td>
</tr>'),
        'sid' => '-2',
        'version' => '',
        'dateline' => time()
    );

    $db->insert_query_multiple("templates", $templates);
}

function cw_css_add() {
    global $db;

    $css = array(
        'name' => 'cw.css',
        'tid' => 1,
        "stylesheet" =>	'
.post_body {
    position: relative;
}

.cw_display {
    filter: blur(2px);
    max-height: 0px;
    padding-top: 0 !important;
    padding-bottom: 0 !important;
    overflow: hidden;
}

.cw_display:target {
    filter: none;
    max-height: 100%;
    overflow: unset;
    padding-bottom: 50px !important;
}

.contentwarning {
    padding: 50px;
    background: #fff;
}',
        'cachefile' => 'cw.css',
        'lastmodified' => time()
    );

    require_once MYBB_ADMIN_DIR . "inc/functions_themes.php";

    $sid = $db->insert_query("themestylesheets", $css);
    $db->update_query("themestylesheets", array("cachefile" => "css.php?stylesheet=" . $sid), "sid = '" . $sid . "'", 1);
    $tids = $db->simple_select("themes", "tid");
    while ($theme = $db->fetch_array($tids)) {
        update_theme_stylesheet_list($theme['tid']);
    }
}
