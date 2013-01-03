<?php
// This file is part of Moodle - http://moodle.org/
//
// Moodle is free software: you can redistribute it and/or modify
// it under the terms of the GNU General Public License as published by
// the Free Software Foundation, either version 3 of the License, or
// (at your option) any later version.
//
// Moodle is distributed in the hope that it will be useful,
// but WITHOUT ANY WARRANTY; without even the implied warranty of
// MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
// GNU General Public License for more details.
//
// You should have received a copy of the GNU General Public License
// along with Moodle.  If not, see <http://www.gnu.org/licenses/>.

/**
 * ui for browsing through block_helpmenow_error_log
 *
 * @package     block_helpmenow
 * @copyright   2012 VLACS
 * @author      David Zaharee <dzaharee@vlacs.org>
 * @license     http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

require_once(dirname(dirname(dirname(dirname(__FILE__)))) . '/config.php');
require_once(dirname(dirname(__FILE__)) . '/lib.php');

# require login
require_login(0, false);

# contexts and cap check
$sitecontext = get_context_instance(CONTEXT_SYSTEM, SITEID);
if (!has_capability(HELPMENOW_CAP_MANAGE, $sitecontext)) {
    redirect();
}

# parameters
$page = optional_param('page', 0, PARAM_INT);
$search = optional_param('search', '', PARAM_TEXT);

# urls
$this_url = new moodle_url();

# title and nav
$title = 'Error Log';
$nav = array(array('name' => $title));

# paging
$per_page = 50;
$offset = $per_page * $page;

# get stuff from the db
$sql = "
    FROM {$CFG->prefix}block_helpmenow_error_log e
    LEFT JOIN {$CFG->prefix}user u ON e.userid = u.id
";
if ($search) {
    $ilike = sql_ilike();
    $sql .= "
        WHERE e.error $ilike '%$search%'
        OR u.firstname $ilike '%$search%'
        OR u.lastname $ilike '%$search%'
    ";
}
$count = count_records_sql('SELECT COUNT(*) '.$sql);
if ($count) {
    $sql = "
        SELECT e.*, u.firstname, u.lastname
        $sql
        LIMIT $per_page
        OFFSET $offset
    ";
    $errors = get_records_sql($sql);

    # start setting up the table
    $table = (object) array(
        'head' => array(
            get_string('user'),
            get_string('error'),
            'object',
            'raw',
        ),
        'data' => array(),
    );
    foreach ($errors as $e) {
        $object = json_decode($e->details);
        $table->data[] = array(
            "$e->lastname, $e->firstname",
            $e->error,
            $object ? "<pre>".htmlspecialchars(print_r($object, true))."</pre>" : "Error decoding JSON",
            "<div style='max-width:700px; overflow-x:scroll;'><pre>".htmlspecialchars($e->details)."</pre></div>",
        );
    }
}

/**
 * output
 */
print_header($title, $title, build_navigation($nav));
print_box_start('generalbox centerpara');

$submit = get_string('submit');
print_box("<form><input type='text' name='search' value='$search' /><input type='submit' value='$submit' /></form>");

if ($count) {
    print_paging_bar($count, $page, $per_page, $this_url);
    print_table($table);
    print_paging_bar($count, $page, $per_page, $this_url);
} else {
    print_box('No errors matched your search');
}

print_box_end();
print_footer();

?>
