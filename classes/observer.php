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

defined('MOODLE_INTERNAL') || die();

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
        $other = $event->other;
        $quizid = $other['quizid'];
        $userid = $other['submitterid'];
        $courseid = $event->courseid;
        $lquizid = $_COOKIE['l_quiz_id'];
        $lquizisopp = $_COOKIE['l_quiz_isopp'];
        $lquiztime = round($_COOKIE['l_quiz_time']/1000);

        $attemptid = $event->objectid;
        $cmid = $event->contextinstanceid;

        $admins = get_admins(); 
        $isadmin = false; 
        foreach ($admins as $admin) {
            if ($userid == $admin->id) {
                $isadmin = true; 
                break; 
            } 
        }

        if( $isadmin ) {
            return true;
        }

        file_put_contents(dirname(__FILE__).'/event.txt', print_r($event, true));

        $quizdata = $DB->get_record('quiz', array('id' => $quizid));

        if( isset( $quizdata->quiztype ) ){
            if( $quizdata->quiztype == 'trivias' ){

                $userdata = $DB->get_record('user', array('id' => $userid));

                $attemptobj = quiz_create_attempt_handling_errors($attemptid, $cmid);

                $attempt = $attemptobj->get_attempt();

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

                //$score = rand(1,100);

                require_once($CFG->dirroot . '/lib/filelib.php');

                $postdata = array('email' => base64_encode($userdata->email), 'score' => $score, 'moodlequizid' => $quizid, 'courseid' => $courseid, 'lquizid' => $lquizid, 'lquizisopp' => $lquizisopp, 'lquiztime' => $lquiztime);

                file_put_contents(dirname(__FILE__).'/postdata.txt', print_r($postdata, true));

                //file_put_contents(dirname(__FILE__).'/leeloolxpurl.txt', print_r($leeloolxpurl, true));

                $url = $leeloolxpurl . '/admin/sync_moodle_course/trivia_submitted';

                $curl = new curl;

                $options = array(

                    'CURLOPT_RETURNTRANSFER' => true,

                    'CURLOPT_HEADER' => false,

                    'CURLOPT_POST' => count($postdata),

                );

                if (!$output = $curl->post($url, $postdata, $options)) {
                    //file_put_contents(dirname(__FILE__).'/output.txt', print_r($output, true));
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
        
        $questiondata = $DB->get_record('tb_question_diff', array('questionid' => $questionid));
        if( $questiondata ){
            $cookiename = 'question_difficulty_'.$questionid;
            $questiondifficulty = $_COOKIE[$cookiename];
            $DB->execute("update {tb_question_diff} set difficulty = ? where questionid = ?", [$questiondifficulty, $questionid]);
        }else{
            $cookiename = 'question_difficulty_'.$questionid;
            $questiondifficulty = $_COOKIE[$cookiename];
            $DB->execute("INSERT INTO {tb_question_diff} (questionid, difficulty) VALUES (?, ?)", [$questionid, $questiondifficulty]);
        }
        return true;
    }
       
}
