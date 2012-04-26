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
 * Help me now gotomeeting user2plugin class.
 *
 * @package     block_helpmenow
 * @copyright   2012 VLACS
 * @author      David Zaharee <dzaharee@vlacs.org>
 * @license     http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

require_once(dirname(dirname(dirname(__FILE__))) . '/user2plugin.php');

class helpmenow_user2plugin_gotomeeting extends helpmenow_user2plugin {
    /**
     * Extra fields
     * @var array $extra_fields
     */
    protected $extra_fields = array(
        'access_token',
        'token_expiration',
        'refresh_token',
    );

    /**
     * Access token
     * @var string $access_token
     */
    public $access_token;

    /**
     * Token expiratation
     * @var int $token_expiration
     */
    public $token_expiration;

    /**
     * Refresh token
     * @var string $refresh_token
     */
    public $refresh_token;

    /**
     * plugin queue's meetings use
     * @var string $plugin
     */
    public $plugin = 'gotomeeting';
}

?>
