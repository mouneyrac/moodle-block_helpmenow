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
 * @package     block_helpmenow
 * @copyright   2012 VLACS
 * @author      David Zaharee <dzaharee@vlacs.org>
 * @license     http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') or die("Direct access to this location is not allowed.");

define('HELPMENOW_CAP_QUEUE_REQUEST', 'block/helpmenow:queue_request');
define('HELPMENOW_CAP_QUEUE_HELPER', 'block/helpmenow:queue_helper');
define('HELPMENOW_CAP_ADMIN', 'block/helpmenow:manage_queues');

$block_helpmenow_capabilities = array (
    HELPMENOW_CAP_QUEUE_REQUEST => array (
        'riskbitmask'   => RISK_SPAM,
        'captype'       => 'write',
        'contextlevel'  => CONTEXT_SYSTEM,
        'legecy'        => array (
            'guest'             => CAP_ALLOW,
            'student'           => CAP_ALLOW,
            'teacher'           => CAP_ALLOW,
            'editingteacher'    => CAP_ALLOW,
            'coursecreator'     => CAP_ALLOW,
            'admin'             => CAP_ALLOW
        )
    ),
    HELPMENOW_CAP_QUEUE_HELPER => array(
        'riskbitmask'   => RISK_SPAM,
        'captype'       => 'write',
        'contextlevel'  => CONTEXT_SYSTEM,
        'legecy'        => array (
            'teacher'           => CAP_ALLOW,
            'editingteacher'    => CAP_ALLOW,
            'admin'             => CAP_ALLOW
        )
    ),
    HELPMENOW_CAP_ADMIN => array(
        'riskbitmask'   => RISK_SPAM + RISK_CONFIG,
        'captype'       => 'write',
        'contextlevel'  => CONTEXT_SYSTEM,
        'legecy'        => array (
            'admin'             => CAP_ALLOW
        )
    ),
);

?>
