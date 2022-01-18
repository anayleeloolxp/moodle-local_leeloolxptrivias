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
 * Leeloo LXP Vimeo external functions and service definitions.
 *
 * @package    local_leeloolxptrivias
 * @category   external
 * @copyright  2020 Leeloo LXP (https://leeloolxp.com)
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 * @since      Moodle 3.0
 */

defined('MOODLE_INTERNAL') || die;

$functions = array(

    'local_leeloolxptrivias_getquiz' => array(
        'classname' => 'local_leeloolxptrivias_external',
        'methodname' => 'getquiz',
        'description' => 'Get data of quiz',
        'type' => 'read',
        'capabilities' => 'local/leeloolxptrivias:view',
        'services' => array(MOODLE_OFFICIAL_MOBILE_SERVICE),
    ),

);
