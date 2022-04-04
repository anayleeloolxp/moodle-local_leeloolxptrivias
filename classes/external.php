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
 * Leeloo LXP Trivias external API
 *
 * @package    local_leeloolxptrivias
 * @category   external
 * @copyright  2020 Leeloo LXP (https://leeloolxp.com)
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 * @since      Moodle 3.0
 */

defined('MOODLE_INTERNAL') || die;

require_once($CFG->libdir . '/filelib.php');
require_once("$CFG->libdir/externallib.php");

/**
 * Leeloo LXP Trivias external functions
 *
 * @package    local_leeloolxptrivias
 * @category   external
 * @copyright  2020 Leeloo LXP (https://leeloolxp.com)
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 * @since      Moodle 3.0
 */
class local_leeloolxptrivias_external extends external_api {

    /**
     * Returns description of method parameters
     *
     * @return external_function_parameters
     * @since Moodle 3.0
     */
    public static function getquiz_parameters() {
        return new external_function_parameters(
            array(
                'cmId' => new external_value(PARAM_INT, 'module instance id'),
                'id' => new external_value(PARAM_INT, 'quiz id'),
            )
        );
    }

    /**
     * Get quiz data...
     *
     * @param int $cmId the quiz instance id
     * @return array of text and status result
     * @since Moodle 3.0
     * @throws moodle_exception
     */
    public static function getquiz($cmId,$id) {
        global $DB, $CFG;

        $params = self::validate_parameters(self::getquiz_parameters(),
            array(
                'cmId' => $cmId,
                'id' => $id,
            )
        );

        $cmid = $params['cmId'];
        // Request and permission validation.
        $quiz = $DB->get_record('quiz', array('id' => $params['id']), '*', MUST_EXIST);

        $reward = 0;
        $oppslises = array();
        $opponentname = '';
        $opponentstopnum = '';
        $opponentemail = '';
        $sentopponents = '';
        $baseemail = '';
        $leeloolxpurl = '';

        $l_quiz_isopp = 0;
        $l_quiz_time = 0;
        $l_quiz_id = 0;
        $savequizdata = array(
            'moodlequizid' => '',
            'course_id' => '',
            'quiztype' => '',
            'user_email' => '',
            'opponent_email' => '',
            'dateuser' => date('Y-m-d H:i:s'),
            'dateopponent' => date('Y-m-d H:i:s'),
            'completed' => 0,
            'accepted' => 0,
            'winner' => 0,
            'scoreuser' => 0,
            'scoreopponent' => 0,
            'date' => date('Y-m-d H:i:s'),
            'level' => '',
            'activity_id' => ''
        );
        $urlsave = '';
        $saveattemptidurl = '';
        $isopponent = 0;
        $isinprogress = 0;

        global $USER, $PAGE;

        $issiteadmin = is_siteadmin();

        if( isset($quiz->quiztype) && !is_siteadmin() ){

            $reqrematch = optional_param('rematch', 0, PARAM_INTEGER);
            
            if( $quiz->quiztype == 'duels' ){

                $spinnerhtml = '';

                global $CFG;
                require_once($CFG->dirroot . '/lib/filelib.php');
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
                    $false = 1;
                }

                $infoleeloolxp = json_decode($output);

                if ($infoleeloolxp->status != 'false') {
                    $leeloolxpurl = $infoleeloolxp->data->install_url;
                    $false = 0;
                } else {
                    $false = 1;
                }
                
                if( $false == 0 ){

                    $baseemail = base64_encode($USER->email);
                    $postdata = array(
                        'useremail' => $baseemail,
                        'quiztype' => $quiz->quiztype,
                        'course_id' => $quiz->course,
                        'moodlequizid' => $quiz->id,
                        'activityid' => $cmid,
                        'rematch' => $reqrematch,
                    );

                    $url = $leeloolxpurl.'/admin/sync_moodle_course/quiz_opponents_response';

                    $urlsave = $leeloolxpurl.'/admin/sync_moodle_course/quiz_attempt_start';

                    $saveattemptidurl = $leeloolxpurl.'/admin/sync_moodle_course/saveattemptid';

                    $curl = new curl;

                    $options = array(
                        'CURLOPT_RETURNTRANSFER' => true,
                        'CURLOPT_HEADER' => false,
                        'CURLOPT_POST' => count($postdata),
                    );

                    $outputopp = $curl->post($url, $postdata, $options);
    
                    $infoopp = json_decode($outputopp);

                    //print_r($postdata);
                    //print_r($infoopp);

                    if( $infoopp ){

                        $infooppdata = $infoopp->data;
                        $opponents = $infoopp->data->opponents;
                        $level = $infoopp->data->level;
                        $opponentemail = $infoopp->data->opponentemail;
                        $isopponent = $infoopp->data->is_opponent;
                        $lquizid = $infoopp->data->quizid_autoincrement;

                        $reward = $infoopp->reward;
                        $timelastatempt = $infoopp->timelastatempt*10000;
                        $l_quiz_isopp = $infoopp->l_quiz_isopp;
                        $l_quiz_id = $infoopp->l_quiz_id;

                        $savequizdata = array(
                            'moodlequizid' => $quiz->id,
                            'course_id' => $quiz->course,
                            'quiztype' => $quiz->quiztype,
                            'user_email' => $USER->email,
                            'opponent_email' => $opponentemail,
                            'dateuser' => date('Y-m-d H:i:s'),
                            'dateopponent' => date('Y-m-d H:i:s'),
                            'completed' => 0,
                            'accepted' => 0,
                            'winner' => 0,
                            'scoreuser' => 0,
                            'scoreopponent' => 0,
                            'date' => date('Y-m-d H:i:s'),
                            'level' => $level,
                            'activity_id' => $cmid
                        );

                        $attemptlast = $DB->get_record_sql('SELECT state FROM {quiz_attempts} WHERE quiz = ? and userid = ? ORDER BY id DESC', [$quiz->id, $USER->id]);

                        $hidespinner = '';

                        $l_quiz_isopp = 0;
                        $l_quiz_time = 0;
                        $l_quiz_id = 0;

                        if( isset( $attemptlast->state ) ){
                            if( $attemptlast->state == 'inprogress' ){
                                $hidespinner = 'style="display:none"';

                                $l_quiz_isopp = $l_quiz_isopp;
                                $l_quiz_time = $timelastatempt;
                                $l_quiz_id = $l_quiz_id;

                                $isinprogress = 1;

                            }
                        }
                        
                        $inc = 0;
                        foreach( $opponents as $opponent ){
                            if( $opponent->email == $opponentemail ){
                                $opponentname = $opponent->name;
                                $opponentstopnum = $inc+1;
                            }

                            $oppslises[$inc]['image'] = $opponent->image;
                            $oppslises[$inc]['value'] = $inc;
                            $oppslises[$inc]['name'] = $opponent->name;
                            $oppslises[$inc]['backgroundtext'] = '#a7b2da';
                            
                            $inc++;
                            $sentopponents = $inc;
                        }
                    }
                }

            }
        }

        

        $data = array();
        $data['is_siteadmin'] = $issiteadmin;
        $data['type'] = $quiz->quiztype;
        $data['reward'] = $reward;
        $data['oppslises'] = $oppslises;
        $data['baseemail'] = $baseemail;
        $data['saveattemptidurl'] = $saveattemptidurl;
        
        $data['opponentname'] = $opponentname;
        $data['opponentstopnum'] = $opponentstopnum;
        $data['opponentemail'] = $opponentemail;
        $data['sentopponents'] = $sentopponents;
        $data['isopponent'] = $isopponent;

        $data['isinprogress'] = $isinprogress;

        $data['l_quiz_isopp'] = $l_quiz_isopp;
        $data['l_quiz_time'] = $l_quiz_time;
        $data['l_quiz_id'] = $l_quiz_id;
        $data['savequizdata'] = $savequizdata;
        $data['urlsave'] = $urlsave;
        $data['leeloolxpurl'] = $leeloolxpurl;
        

        $result = array();
        $result['status'] = true;
        $result['data'] = $data;
        return $result;
    }

    /**
     * Returns description of method result value
     *
     * @return external_description
     * @since Moodle 3.0
     */
    public static function getquiz_returns() {
        return new external_single_structure(
            array(
                'status' => new external_value(PARAM_BOOL, 'status: true if success'),
                'data' => new external_single_structure(
                    array(
                        'is_siteadmin' => new external_value(PARAM_TEXT, 'is_siteadmin for quiz'),
                        'type' => new external_value(PARAM_TEXT, 'type for quiz'),
                        'reward' => new external_value(PARAM_TEXT, 'reward for quiz'),
                        'opponentname' => new external_value(PARAM_TEXT, 'opponentname for quiz'),
                        'opponentstopnum' => new external_value(PARAM_TEXT, 'opponentstopnum for quiz'),
                        'sentopponents' => new external_value(PARAM_TEXT, 'sentopponents for quiz'),
                        'oppslises' => new external_multiple_structure(
                            new external_single_structure(
                                array(
                                    'image' => new external_value(PARAM_TEXT, 'image'),
                                    'value' => new external_value(PARAM_TEXT, 'value'),
                                    'backgroundtext' => new external_value(PARAM_TEXT, 'backgroundtext'),
                                    'name' => new external_value(PARAM_RAW, 'name'),
                                )
                            )
                        ),
                        'baseemail' => new external_value(PARAM_TEXT, 'baseemail for quiz'),
                        'isopponent' => new external_value(PARAM_TEXT, 'isopponent for quiz'),
                        'l_quiz_isopp' => new external_value(PARAM_TEXT, 'l_quiz_isopp for quiz'),
                        'l_quiz_time' => new external_value(PARAM_TEXT, 'l_quiz_time for quiz'),
                        'l_quiz_id' => new external_value(PARAM_TEXT, 'l_quiz_id for quiz'),
                        'savequizdata' => new external_single_structure(
                            array(
                                'moodlequizid' => new external_value(PARAM_RAW, 'moodlequizid'),
                                'course_id' => new external_value(PARAM_RAW, 'course_id'),
                                'quiztype' => new external_value(PARAM_RAW, 'quiztype'),
                                'user_email' => new external_value(PARAM_RAW, 'user_email'),
                                'opponent_email' => new external_value(PARAM_RAW, 'opponent_email'),
                                'dateuser' => new external_value(PARAM_RAW, 'dateuser'),
                                'dateopponent' => new external_value(PARAM_RAW, 'dateopponent'),
                                'completed' => new external_value(PARAM_RAW, 'completed'),
                                'accepted' => new external_value(PARAM_RAW, 'accepted'),
                                'winner' => new external_value(PARAM_RAW, 'winner'),
                                'scoreuser' => new external_value(PARAM_RAW, 'scoreuser'),
                                'scoreopponent' => new external_value(PARAM_RAW, 'scoreopponent'),
                                'date' => new external_value(PARAM_RAW, 'date'),
                                'level' => new external_value(PARAM_RAW, 'level'),
                                'activity_id' => new external_value(PARAM_RAW, 'activity_id'),
                            )
                        ),
                        'urlsave' => new external_value(PARAM_RAW, 'urlsave'),
                        'saveattemptidurl' => new external_value(PARAM_RAW, 'saveattemptidurl'),
                        'isinprogress' => new external_value(PARAM_TEXT, 'isinprogress'),
                        'leeloolxpurl' => new external_value(PARAM_TEXT, 'leeloolxpurl'),
                    )
                )
            )
        );
    }

}
