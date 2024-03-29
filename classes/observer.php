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
 * Admin settings and defaults
 *
 * @package local_leeloolxptrivias
 * @copyright  2020 Leeloo LXP (https://leeloolxp.com)
 * @author Leeloo LXP <info@leeloolxp.com>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace local_leeloolxptrivias;

use core_user;
use curl;
use moodle_url;

/**
 * Plugin to sync users on new enroll, groups, trackign of activity view to LeelooLXP account of the Moodle Admin
 */
class observer {

    /**
     * Triggered when course completed.
     *
     * @param \mod_quiz\event\attempt_submitted $event
     */
    public static function attempt_submitted(\mod_quiz\event\attempt_submitted $event) {

        global $DB, $CFG;
        require_once($CFG->dirroot . '/lib/filelib.php');
        $other = $event->other;
        $quizid = $other['quizid'];
        $activityid = $event->contextinstanceid;
        $userid = $other['submitterid'];
        $courseid = $event->courseid;
        $lquizid = $_COOKIE['l_quiz_id'];
        $lquizisopp = $_COOKIE['l_quiz_isopp'];

        $attemptid = $event->objectid;
        $cmid = $event->contextinstanceid;

        $attemptobj = quiz_create_attempt_handling_errors($attemptid, $cmid);
        $attempt = $attemptobj->get_attempt();

        if (isset($_COOKIE['l_quiz_time']) && isset($_COOKIE['l_quiz_time']) != '') {
            $lquiztime = round($_COOKIE['l_quiz_time'] / 1000);
        } else {
            $lquiztime = 0;
        }

        $admins = get_admins();
        $isadmin = false;
        foreach ($admins as $admin) {
            if ($userid == $admin->id) {
                $isadmin = true;
                break;
            }
        }

        if ($isadmin) {
            return true;
        }

        $quizdata = $DB->get_record('quiz', array('id' => $quizid));

        if (isset($quizdata->quiztype)) {
            if ($quizdata->quiztype == 'duels' || $quizdata->quiztype == 'regularduel') {

                $userdata = $DB->get_record('user', array('id' => $userid));

                $score = $attempt->sumgrades + 0;

                $useremail = $userdata->email;

                $leeloolxplicense = get_config('local_leeloolxptrivias')->license;

                $url = 'https://leeloolxp.com/api_moodle.php/?action=page_info';
                $postdata = array('license_key' => $leeloolxplicense);

                $curl = new curl;

                $options = array(
                    'CURLOPT_RETURNTRANSFER' => true,
                    'CURLOPT_HEADER' => false,
                    'CURLOPT_POST' => count($postdata),
                );

                if (!$output = $curl->post($url, $postdata, $options)) {
                    return true;
                }

                $infoleeloolxp = json_decode($output);

                if ($infoleeloolxp->status != 'false') {
                    $leeloolxpurl = $infoleeloolxp->data->install_url;
                } else {
                    return true;
                }

                $postdata = array(
                    'email' => base64_encode($userdata->email),
                    'score' => $score,
                    'moodlequizid' => $quizid,
                    'courseid' => $courseid,
                    'lquizid' => $lquizid,
                    'lquizisopp' => $lquizisopp,
                    'lquiztime' => $lquiztime,
                    'activityid' => $activityid,
                    'attemptid' => $attemptid
                );

                $url = $leeloolxpurl . '/admin/sync_moodle_course/trivia_submitted';

                $curl = new curl;

                $options = array(

                    'CURLOPT_RETURNTRANSFER' => true,

                    'CURLOPT_HEADER' => false,

                    'CURLOPT_POST' => count($postdata),
                    'CURLOPT_HTTPHEADER' => array(
                        'Leeloolxptoken: ' . get_config('local_leeloolxpapi')->leelooapitoken . ''
                    )

                );

                if (!$output = $curl->post($url, $postdata, $options)) {
                    return true;
                }
            }
        }

        return true;
    }

    /**
     * Triggered when course completed.
     *
     * @param \core\event\question_updated $event
     */
    public static function question_updated(\core\event\question_updated $event) {

        global $DB;
        $questionid = $event->objectid;

        $questiondata = $DB->get_record('local_leeloolxptrivias_qd', array('questionid' => $questionid));
        if ($questiondata) {
            $cookiename = 'question_difficulty_' . $questionid;
            $questiondifficulty = $_COOKIE[$cookiename];

            $vcookiename = 'question_vimeo_' . $questionid;
            $questionvimeo = $_COOKIE[$vcookiename];
            $DB->execute(
                "update {local_leeloolxptrivias_qd} set difficulty = ? and vimeoid = ? where questionid = ?",
                [$questiondifficulty, $questionvimeo, $questionid]
            );
        } else {
            $cookiename = 'question_difficulty_' . $questionid;
            $questiondifficulty = $_COOKIE[$cookiename];

            $vcookiename = 'question_vimeo_' . $questionid;
            $questionvimeo = $_COOKIE[$vcookiename];
            $DB->execute(
                "INSERT INTO {local_leeloolxptrivias_qd} (questionid, difficulty, vimeoid) VALUES (?, ?, ?)",
                [$questionid, $questiondifficulty, $questionvimeo]
            );
        }
        return true;
    }
}
