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
 * Renderers to align Moodle's HTML with that expected by Bootstrap
 *
 * @package    local_leeloolxptrivias
 * @copyright  2020 Leeloo LXP (https://leeloolxp.com)
 * @author     Leeloo LXP <info@leeloolxp.com>
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
namespace local_leeloolxptrivias\output;

use moodle_url;
use single_button;
use curl;
use quiz_attempt;
use confirm_action;
use mod_quiz_preflight_check_form;
use quiz_nav_panel_base;
use html_writer;
use popup_action;
use mod_quiz_display_options;

defined('MOODLE_INTERNAL') || die;
/**
 * Extending the mod_quiz_renderer interface.
 *
 * @copyright 2017 Kathrin Osswald, Ulm University kathrin.osswald@uni-ulm.de
 * @license http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 * @package local_leeloolxptrivias
 * @category output
 */
class mod_quiz_renderer extends \mod_quiz_renderer {
    /**
     * Builds the review page
     *
     * @param quiz_attempt $attemptobj an instance of quiz_attempt.
     * @param array $slots an array of intgers relating to questions.
     * @param int $page the current page number
     * @param bool $showall whether to show entire attempt on one page.
     * @param bool $lastpage if true the current page is the last page.
     * @param mod_quiz_display_options $displayoptions instance of mod_quiz_display_options.
     * @param array $summarydata contains all table data
     * @return $output containing html data.
     */
    public function review_page(quiz_attempt $attemptobj, $slots, $page, $showall,
                                $lastpage, mod_quiz_display_options $displayoptions,
                                $summarydata) {

        
        if($attemptobj->get_quiz()->quiztype == 'duels' && !is_siteadmin()){
            global $PAGE;
            $PAGE->add_body_class('trivia_review');
        }

        global $USER;

            
        if( $attemptobj->get_quiz()->quiztype == 'duels' && !is_siteadmin()){
            $triva_review = '';
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

                $postdata = array(
                    'id' => base64_encode($_COOKIE['l_quiz_id']),
                    'activityid' => $attemptobj->get_quiz()->cmid
                );

                $url = $leeloolxpurl.'/admin/sync_moodle_course/gettriviadata';

                $curl = new curl;

                $options = array(
                    'CURLOPT_RETURNTRANSFER' => true,
                    'CURLOPT_HEADER' => false,
                    'CURLOPT_POST' => count($postdata),
                );

                $outputopp = $curl->post($url, $postdata, $options);

                $infoopp = json_decode($outputopp);

                if( $infoopp ){
                    $data = $infoopp->data;

                    //print_r($data);
                    $reward = $data->reward;

                    if( $reward != 0 ){
                        $rewardhtml = '<div class="reward_html">Reward: <span>'.$reward.'</span><small>coins</small></div>
                        <div class="reward_icns"> <div class="reward_icn"><img src="https://rockstardaddy.com/moodle-testing/theme/image.php/thinkblue/local_leeloolxptrivias/1634543391/reward_icn-Img" /></div> <div class="reward_icn"><img src="https://rockstardaddy.com/moodle-testing/theme/image.php/thinkblue/local_leeloolxptrivias/1634543391/reward_icn-Img" /></div> </div>';
                    }else{
                        $rewardhtml = '';
                    }

                    /*$usershtml = '<div class="trivia_users">
                    <div class="triviauser1">'.$data->user.' <b>Score</b>: '.$data->scoreuser.' <b>Time</b>: '.$data->usertime.' seconds</div>
                    <div class="triviauser2">'.$data->opponent.' <b>Score</b>: '.$data->scoreopponent.' <b>Time</b>: '.$data->opponenttime.' seconds</div>
                    </div>';*/

                    $usershtml = '
                    <div class="trivia_users">
                    <div class="trivia_user triviauser1"> <div class="user_img"><img src="'.$data->userimage.'" /></div> <div class="user_name"><span>1</span> <small>'.$data->user.'</small></div> <div class="user_scrore"><b>'.$data->scoreuser.'</b> <small>'.$data->usertime.' seconds</small></div> </div>
                    <div class="trivia_user triviauser2"> <div class="user_img"><img src="'.$data->oppimage.'" /></div> <div class="user_name"><span>2</span> <small>'.$data->opponent.'</small></div> <div class="user_scrore"><b>'.$data->scoreopponent.'</b> <small>'.$data->opponenttime.' seconds</small></div> </div>
                    </div>';

                    if( $data->winner == '0' ){
                        $triva_review .= '<div class="triva_review_inner waiting"><h2>Waiting!</h2>'.$rewardhtml.$usershtml.'</div>';
                    }elseif( $USER->email == $data->winner ){
                        $triva_review .= '<div class="triva_review_inner winner"><h2>You Won!</h2>'.$rewardhtml.$usershtml.'</div>';
                    }else{
                        $triva_review .= '<div class="triva_review_inner losser"><h2>You Lost!</h2>'.$rewardhtml.$usershtml.'</div>';
                    }
                }
            }

            $triva_review .= '<style>[data-region="blocks-column"]{display:none}</style>';

            /*$triva_review .= '<div class="playagaindiv"><div class="playagaininnerdiv"><a class="trivia_playagain" href="'.$CFG->wwwroot.'/mod/quiz/view.php?id='.$attemptobj->get_quiz()->cmid.'">Play Again!</a></div></div>';*/

            $triva_review .= '<div class="playagaindiv"><div class="playagaininnerdiv"><a class="trivia_playagain" href="'.$CFG->wwwroot.'/mod/quiz/view.php?id='.$attemptobj->get_quiz()->cmid.'">Play<br>Again!</a></div></div>';

            $output = '';
            $output .= $this->header();
            $output .= $triva_review;
            //$output .= $this->review_summary_table($summarydata, $page);
            /*$output .= $this->review_form($page, $showall, $displayoptions,
                    $this->questions($attemptobj, true, $slots, $page, $showall, $displayoptions),
                    $attemptobj);*/
    
            $output .= $this->review_next_navigation($attemptobj, $page, $lastpage, $showall);
            $output .= $this->footer();

        }else{
            $output = '';
            $output .= $this->header();
            $output .= $this->review_summary_table($summarydata, $page);
            $output .= $this->review_form($page, $showall, $displayoptions,
                    $this->questions($attemptobj, true, $slots, $page, $showall, $displayoptions),
                    $attemptobj);
    
            //$output .= $this->review_next_navigation($attemptobj, $page, $lastpage, $showall);
            $output .= $this->footer();
        }

        
        return $output;
    }

    /*
     * Summary Page
     */
    /**
     * Create the summary page
     *
     * @param quiz_attempt $attemptobj
     * @param mod_quiz_display_options $displayoptions
     */
    public function summary_page($attemptobj, $displayoptions) {

        global $PAGE;
        $PAGE->requires->js_init_code('require(["jquery"], function ($) {
            $(document).ready(function () {

                $("body").addClass("trivia_summarypage loaderonly");

                $(".submitbtns form").submit();
            });
        });');

        $output = '';
        $output .= $this->header();
        $output .= $this->heading(format_string($attemptobj->get_quiz_name()));
        $output .= $this->heading(get_string('summaryofattempt', 'quiz'), 3);
        $output .= $this->summary_table($attemptobj, $displayoptions);
        $output .= $this->summary_page_controls($attemptobj);
        $output .= $this->footer();
        return $output;
    }

    /*
     * View Page
     */
    /**
     * Generates the view page
     *
     * @param int $course The id of the course
     * @param array $quiz Array conting quiz data
     * @param int $cm Course Module ID
     * @param int $context The page context ID
     * @param array $infomessages information about this quiz
     * @param mod_quiz_view_object $viewobj
     * @param string $buttontext text for the start/continue attempt button, if
     *      it should be shown.
     * @param array $infomessages further information about why the student cannot
     *      attempt this quiz now, if appicable this quiz
     */
    public function view_page($course, $quiz, $cm, $context, $viewobj) {

        global $USER, $PAGE;

        if( isset($quiz->quiztype) && !is_siteadmin() ){

            $reqautostart = optional_param('autostart', 0, PARAM_RAW);

            $reqrematch = optional_param('rematch', 0, PARAM_INTEGER);
            
            if( $quiz->quiztype == 'duels' ){

                $spinnerhtml = '';
                $reward = 0;

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
                        'activityid' => $cm->id,
                        'rematch' => $reqrematch,
                    );

                    $url = $leeloolxpurl.'/admin/sync_moodle_course/quiz_opponents_response';

                    $urlsave = $leeloolxpurl.'/admin/sync_moodle_course/quiz_attempt_start';

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
                        $timelastatempt = $infoopp->timelastatempt*1000;
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
                            'activity_id' => $quiz->cmid
                        );

                        global $PAGE;
                        $PAGE->requires->js(new moodle_url('/local/leeloolxptrivias/js/jquery.superwheel.min.js'));

                        $attemptlast = end($viewobj->attempts);
                        $hidespinner = '';

                        $setcookies = 'setCookie("l_quiz_isopp", 0, 1);
                        setCookie("l_quiz_time", 0, 1);
                        setCookie("l_quiz_id", 0, 1);';

                        if( isset( $attemptlast->state ) ){
                            if( $attemptlast->state == 'inprogress' ){
                                $hidespinner = 'style="display:none"';

                                $setcookies = 'setCookie("l_quiz_isopp", '.$l_quiz_isopp.', 1);
                                setCookie("l_quiz_time", '.$timelastatempt.', 1);
                                setCookie("l_quiz_id", '.$l_quiz_id.', 1);';

                            }
                        }
                        $spinnerhtml .= '<div class="roulette_m" '.$hidespinner.'><main class="cd-main-content text-center">
                            <div class="wheel-with-image"></div>
                            <!--<button type="button" class="button button-primary wheel-with-image-spin-button">Spin</button>-->
                        </main> <!-- cd-main-content -->';

                        $spinnerhtml .= '<div class="opponent_div" style="display:none"></div></div>';
        
                        $oppslises = array();
                        foreach( $opponents as $key=>$opponent ){
                            if( $opponent->email == $opponentemail ){
                                $opponentname = $opponent->name;
                                $opponentstopnum = $key;
                            }

                            $oppslises[$key]['text'] = $opponent->image;
                            $oppslises[$key]['value'] = $key;
                            $oppslises[$key]['message'] = $opponent->name;
                            $oppslises[$key]['backgroundtext'] = '#a7b2da';

                        }

                        if( $reqrematch != 0 ){

                            $PAGE->requires->js_init_code('require(["jquery"], function ($) {
                                $(document).ready(function () {
    
                                    $("body").addClass("trivia_quiz_view");
    
                                    function setCookie(cname, cvalue, exdays) {
                                        const d = new Date();
                                        d.setTime(d.getTime() + (exdays*24*60*60*1000));
                                        let expires = "expires="+ d.toUTCString();
                                        document.cookie = cname + "=" + cvalue + ";" + expires + ";path=/";
                                    }
    
                                    $(document).on("click",".trivia_play",function(e){	
                                        $(".quizstartbuttondivthinkblue button").trigger("click");
                                    });
    
                                    '.$setcookies.'
            
                                    $(".opponent_div").html("Rematch with '.$opponentname.'<div class=\'rematch_playdiv\'><span class=\'trivia_play\'>PLAY!</span></div>");
                                    $(".opponent_div").show();

                                    $(".quizstartbuttondivthinkblue form").submit(function(e){
    
                                        var postForm = {
                                            "useremail" : "'.$baseemail.'",
                                            "data" : \''.json_encode($savequizdata).'\'
                                        };
                                          
                                        $.ajax({
                                            type : "POST",
                                            url : "'.$urlsave.'",
                                            data : postForm,
                                            dataType : "json",
                                            success : function(data) {
                                                setCookie("l_quiz_isopp", '.$isopponent.', 30);
                                                setCookie("l_quiz_id", data, 30);
                                                console.log(data);
                                            }
                                        });
                                        
                                    });
                                });
                            });');

                        }else{
                            $PAGE->requires->js_init_code('require(["jquery"], function ($) {
                                $(document).ready(function () {
    
                                    $("body").addClass("trivia_quiz_view");
    
                                    function setCookie(cname, cvalue, exdays) {
                                        const d = new Date();
                                        d.setTime(d.getTime() + (exdays*24*60*60*1000));
                                        let expires = "expires="+ d.toUTCString();
                                        document.cookie = cname + "=" + cvalue + ";" + expires + ";path=/";
                                    }
    
                                    $(document).on("click",".trivia_play",function(e){	
                                        $(".quizstartbuttondivthinkblue button").trigger("click");
                                        $(".sWheel-title").css("visibility", "visible");
                                    });
    
                                    '.$setcookies.'
            
                                    $(".wheel").superWheel("onFail",function(results,spinCount,now){
                                        console.log("Spin Failed, Something wrong in wheel settings");
                                        console.log(results);
                                    });
    
                                    $(".wheel-with-image").superWheel({
                                        slices: '.json_encode($oppslises).',
                                        text : {
                                            type: "image",
                                            color: "#d3d8ec",
                                            size: 25,
                                            offset : 10,
                                            orientation: "h"
                                            
                                        },
                                        slice: {
                                            background: "#d3d8ec",
                                            selected: {
                                                background: "#a7b2da"
                                            }
                                        },                                   
                                        line: {
                                            width: 1,
                                            color: "#a7b2da"
                                        },
                                        outer: {
                                            width: 1,
                                            color: "#a7b2da"
                                        },
                                        inner: {
                                            width: 15,
                                            color: "#a7b2da"
                                        },
                                        marker: {
                                            background: "#a7b2da",
                                            animate: 1
                                        },
                                        center: {
                                            rotate: "false",
                                            html: {
                                                template: "<span class=\'trivia_play\'>PLAY!</span>"
                                            }
                                        },
                                        selector: "value",
                                    });
                                
                                    var tick = new Audio("'.$CFG->wwwroot.'/local/leeloolxptrivias/media/tick.mp3");
                                    
                                    $(document).on("click",".wheel-with-image-spin-button",function(e){	
                                        $(".wheel-with-image").superWheel("start","value",'.$opponentstopnum.');
                                        $(this).prop("disabled",true);
                                    });
                                    
                                    $(".wheel-with-image").superWheel("onStart",function(results){	
                                        $(".wheel-with-image-spin-button").text("Spinning...");
                                    });
    
                                    $(".wheel-with-image").superWheel("onStep",function(results){
                                        if (typeof tick.currentTime !== "undefined")
                                            tick.currentTime = 0;   
                                        tick.play();
                                    });
    
                                    $(".wheel-with-image").superWheel("onComplete",function(results){
                                        $(".wheel-with-image-spin-button:disabled").prop("disabled",false).text("Spin");
    
                                        $(".opponent_div").show();
    
                                        var postForm = {
                                            "useremail" : "'.$baseemail.'",
                                            "data" : \''.json_encode($savequizdata).'\'
                                        };
                                          
                                        $.ajax({
                                            type : "POST",
                                            url : "'.$urlsave.'",
                                            data : postForm,
                                            dataType : "json",
                                            success : function(data) {
                                                setCookie("l_quiz_isopp", '.$isopponent.', 30);
                                                setCookie("l_quiz_id", data, 30);
                                                console.log(data);
                                            }
                                        });
    
                                        $(".quizstartbuttondivthinkblue form").unbind("submit").submit();
                                    });
    
                                    $(".quizstartbuttondivthinkblue form").submit(function(e){
    
                                        $(".opponent_div").html("'.$opponentname.' is your opponent.");
    
                                        e.preventDefault();
                                        
                                        $(".roulette_m").css("visibility", "visible");
                                        
                                        $(".wheel-with-image").superWheel("start","value",'.$opponentstopnum.');  	
                                        
                                    });
                                });
                            });');
                        }
                        
                    }
                }
            }elseif( ($quiz->quiztype == 'trivias' || $quiz->quiztype == 'discover') && $reqautostart == 1 ){

                if( $quiz->quiztype == 'trivias' ){
                    $PAGE->requires->js_init_code('require(["jquery"], function ($) {
                        function setCookie(cname, cvalue, exdays) {
                            const d = new Date();
                            d.setTime(d.getTime() + (exdays*24*60*60*1000));
                            let expires = "expires="+ d.toUTCString();
                            document.cookie = cname + "=" + cvalue + ";" + expires + ";path=/";
                        }
        
                        setCookie("l_quiz_isopp", 0, 1);
                        setCookie("l_quiz_time", 0, 1);
                        setCookie("l_quiz_id", 0, 1);
                    });'); 
                }

                $PAGE->requires->js_init_code('require(["jquery"], function ($) {
                    $(document).ready(function () {

                        $("body").addClass("loaderonly");

                        $(".quizstartbuttondiv button").trigger("click");
                        
                    });
                });');    
            }
        }

        $output = '';
        if( isset($quiz->quiztype) && !is_siteadmin() && $quiz->quiztype == 'duels' ){

            if( $reward != 0 ){
                $rewardhtml = '<div class="reward_html">Reward: <span>'.$reward.'</span>coins</div>';
            }else{
                $rewardhtml = '';
            }

            $output .= '<div class="trivia_info"><h2>Ready?</h2>'.$rewardhtml.'</div>';
            $output .= $spinnerhtml;
        }else{
            $output .= $this->view_information($quiz, $cm, $context, $viewobj->infomessages);
            $output .= $this->view_table($quiz, $context, $viewobj);
            $output .= $this->view_result_info($quiz, $context, $cm, $viewobj);
        }
        
        $output .= $this->box($this->view_page_buttons($viewobj), 'quizattempt');
        return $output;
    }

    /**
     * Generates the view attempt button
     *
     * @param string $buttontext the label to display on the button.
     * @param moodle_url $url The URL to POST to in order to start the attempt.
     * @param mod_quiz_preflight_check_form $preflightcheckform deprecated.
     * @param bool $popuprequired whether the attempt needs to be opened in a pop-up.
     * @param array $popupoptions the options to use if we are opening a popup.
     * @return string HTML fragment.
     */
    public function start_attempt_button($buttontext, moodle_url $url,
            mod_quiz_preflight_check_form $preflightcheckform = null,
            $popuprequired = false, $popupoptions = null, $stopentry = 1) {

        if (is_string($preflightcheckform)) {
            // Calling code was not updated since the API change.
            debugging('The third argument to start_attempt_button should now be the ' .
                    'mod_quiz_preflight_check_form from ' .
                    'quiz_access_manager::get_preflight_check_form, not a warning message string.');
        }

        $button = new single_button($url, $buttontext);
        if( $stopentry == 1 ){
            $button->class .= ' quizstartbuttondiv';
        }else{
            $button->class .= ' quizstartbuttondiv quizstartbuttondivthinkblue quizstartbuttondivthinkbluemodren';
        }

        if ($popuprequired) {
            $button->class .= ' quizsecuremoderequired';
        }

        $popupjsoptions = null;
        if ($popuprequired && $popupoptions) {
            $action = new popup_action('click', $url, 'popup', $popupoptions);
            $popupjsoptions = $action->get_js_options();
        }

        if ($preflightcheckform) {
            $checkform = $preflightcheckform->render();
        } else {
            $checkform = null;
        }

        $this->page->requires->js_call_amd('mod_quiz/preflightcheck', 'init',
                array('.quizstartbuttondiv [type=submit]', get_string('startattempt', 'quiz'),
                       '#mod_quiz_preflight_form', $popupjsoptions));

        return $this->render($button) . $checkform;
    }

    /**
     * Work out, and render, whatever buttons, and surrounding info, should appear
     * at the end of the review page.
     * @param mod_quiz_view_object $viewobj the information required to display
     * the view page.
     * @return string HTML to output.
     */
    public function view_page_buttons(\mod_quiz_view_object $viewobj) {
        global $CFG;
        $output = '';

        $attemptlast = end($viewobj->attempts);

        $stopentry = 0;
        if( isset( $attemptlast->state ) ){
            if( $attemptlast->state == 'inprogress' ){
                $stopentry = 1;
            }
        }
        
        if (!$viewobj->quizhasquestions) {
            $output .= $this->no_questions_message($viewobj->canedit, $viewobj->editurl);
        }

        $output .= $this->access_messages($viewobj->preventmessages);

        if ($viewobj->buttontext) {
            $output .= $this->start_attempt_button($viewobj->buttontext,
                    $viewobj->startattempturl, $viewobj->preflightcheckform,
                    $viewobj->popuprequired, $viewobj->popupoptions, $stopentry);
        }

        if ($viewobj->showbacktocourse) {
            $output .= $this->single_button($viewobj->backtocourseurl,
                    get_string('backtocourse', 'quiz'), 'get',
                    array('class' => 'continuebutton'));
        }

        return $output;
    }

    /**
     * Attempt Page
     *
     * @param quiz_attempt $attemptobj Instance of quiz_attempt
     * @param int $page Current page number
     * @param quiz_access_manager $accessmanager Instance of quiz_access_manager
     * @param array $messages An array of messages
     * @param array $slots Contains an array of integers that relate to questions
     * @param int $id The ID of an attempt
     * @param int $nextpage The number of the next page
     */
    public function attempt_page($attemptobj, $page, $accessmanager, $messages, $slots, $id,
            $nextpage) {
        
        
        
        global $DB;
        $quizid = $attemptobj->get_quiz()->id;
        $slotsarr = $DB->get_records('quiz_slots', array('quizid' => $quizid), 'slot');
        $questionsperpage = $attemptobj->get_quiz()->questionsperpage; 
        $total_questions = count($slotsarr); 
        $total_page = ceil($total_questions/$questionsperpage);
        $crr_page = $page + 1;

        if($attemptobj->get_quiz()->quiztype == 'duels' || $attemptobj->get_quiz()->quiztype == 'discover' || $attemptobj->get_quiz()->quiztype == 'trivias'){
            global $PAGE;
            $PAGE->add_body_class('trivia_question');

            $PAGE->add_body_class('leeloolxp_quiz_type_'.$attemptobj->get_quiz()->quiztype);

            $PAGE->requires->js_init_code('require(["jquery"], function ($) {
                $(document).ready(function () {

                    $(".endtestlink").text("Finish '.get_string($attemptobj->get_quiz()->quiztype.'_lang', 'local_leeloolxptrivias').'");
                    
                });
            });');  

            if( $attemptobj->get_quiz()->quiztype == 'trivias' ){
                $PAGE->requires->js_init_code('require(["jquery"], function ($) {
                    $(window).bind("beforeunload", function(){
                        $(".thinkblue_quiztimetaken").hide();
                    });
    
                    $(".thinkblue_quiztimetaken").show();
                });');  
            }

        }
        
        if( !is_siteadmin() && $attemptobj->get_quiz()->quiztype == 'duels' ){

            global $USER;
            $baseemail = base64_encode($USER->email);
            
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

                $url = $leeloolxpurl.'/admin/sync_moodle_course/savetriviadata';
                //$PAGE->requires->css('/local/leeloolxptrivias/css/trivia.css');

                $this->page->requires->js_init_code('require(["jquery"], function ($) {
                    $(document).ready(function () {

                        function getCookie(cname) {
                            let name = cname + "=";
                            let ca = document.cookie.split(";");
                            for(let i = 0; i < ca.length; i++) {
                            let c = ca[i];
                            while (c.charAt(0) == " ") {
                                c = c.substring(1);
                            }
                            if (c.indexOf(name) == 0) {
                                return c.substring(name.length, c.length);
                            }
                            }
                            return "";
                        }
            
                        var l_quiz_time = getCookie("l_quiz_time");
                        var l_quiz_isopp = getCookie("l_quiz_isopp");

                        function save_trivia_time(){
                            var postForm = {
                                "timetaken" : Math.round( (parseInt( getCookie("l_quiz_time") ) ) / 1000),
                                "useremail" : "'.$baseemail.'",
                                "course_id" : "'.$attemptobj->get_quiz()->course.'",
                                "moodlequizid" : "'.$attemptobj->get_quiz()->id.'",
                                "isopponent" : l_quiz_isopp,
                            };

                            var postdata = {
                                "data" : window.btoa(JSON.stringify(postForm))
                            }
                            
                            $.ajax({
                                type : "POST",
                                url : "'.$url.'",
                                data : postdata,
                            });
                        }

                        setInterval(function() {
                            save_trivia_time();
                        }, 1000 * 60 );

                        $(window).bind("beforeunload", function(){
                            save_trivia_time();
                            $(".thinkblue_quiztimetaken").hide();
                        });

                        $(".thinkblue_quiztimetaken").show();
                    }); 
                });');

            }
        }    

        $output = '';
        $output .= $this->header();

        if( $total_page == 1 ){
            $output .= '<style>#mod_quiz_navblock .qn_buttons { display: none!important; }</style>';
        }

        $output .= $this->quiz_notices($messages);
        $output .= html_writer::tag('div', '<span class="timerspan"></span><span class="pagespan">'.$crr_page.'/'.$total_page.'</span>',
                array('class' => 'thinkblue_quiztimetaken top_timer hidden'));
        $output .= $this->attempt_form($attemptobj, $page, $slots, $id, $nextpage);
        $output .= $this->footer();
        return $output;
    }

    /**
     * Outputs the navigation block panel
     *
     * @param quiz_nav_panel_base $panel instance of quiz_nav_panel_base
     */
    public function navigation_panel(quiz_nav_panel_base $panel) {

        $output = '';
        $userpicture = $panel->user_picture();
        if ($userpicture) {
            $fullname = fullname($userpicture->user);
            if ($userpicture->size === true) {
                $fullname = html_writer::div($fullname);
            }
            $output .= html_writer::tag('div', $this->render($userpicture) . $fullname,
                    array('id' => 'user-picture', 'class' => 'clearfix'));
        }
        $output .= $panel->render_before_button_bits($this);

        $bcc = $panel->get_button_container_class();
        $output .= html_writer::start_tag('div', array('class' => "qn_buttons clearfix $bcc"));
        foreach ($panel->get_question_buttons() as $button) {
            $output .= $this->render($button);
        }
        $output .= html_writer::end_tag('div');

        if( !is_siteadmin() ){
            $this->page->requires->js_init_code('require(["jquery"], function ($) {
                $(document).ready(function () {
                    function setCookie(cname, cvalue, exdays) {
                        const d = new Date();
                        d.setTime(d.getTime() + (exdays*24*60*60*1000));
                        let expires = "expires="+ d.toUTCString();
                        document.cookie = cname + "=" + cvalue + ";" + expires + ";path=/";
                    }

                    function getCookie(cname) {
                        let name = cname + "=";
                        let ca = document.cookie.split(";");
                        for(let i = 0; i < ca.length; i++) {
                        let c = ca[i];
                        while (c.charAt(0) == " ") {
                            c = c.substring(1);
                        }
                        if (c.indexOf(name) == 0) {
                            return c.substring(name.length, c.length);
                        }
                        }
                        return "";
                    }

                    var l_quiz_time = getCookie("l_quiz_time");

                    var start = new Date;

                    function myTimer() {

                        var end = new Date();
                        var timeSpent = end - start;
                        var cookiespenttime = parseInt(l_quiz_time) + parseInt(timeSpent);
                        setCookie("l_quiz_time", cookiespenttime, 1);

                        $(".thinkblue_quiztimetaken span.timerspan").text( Math.round( (parseInt(l_quiz_time) + (new Date - start)) / 1000) + " Seconds");
                    }

                    var myVar = setInterval( myTimer , 1000);
                    
                    $(window).bind("beforeunload", function(){
                        clearInterval(myVar);
                    });
                });
            });
            ');

            $output .= html_writer::tag('div', 'Time taken: <span class="timerspan"></span>',
                array('class' => 'thinkblue_quiztimetaken hidden'));
        }

        $output .= html_writer::tag('div', $panel->render_end_bits($this),
                array('class' => 'othernav'));

        $this->page->requires->js_init_call('M.mod_quiz.nav.init', null, false,
                quiz_get_js_module());

        return $output;
    }
}
