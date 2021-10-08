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

        
        if($attemptobj->get_quiz()->quiztype == 'trivias'){
            global $PAGE;
            $PAGE->add_body_class('trivia_review');
        }

        $output = '';
        $output .= $this->header();
        $output .= $this->review_summary_table($summarydata, $page);
        $output .= $this->review_form($page, $showall, $displayoptions,
                $this->questions($attemptobj, true, $slots, $page, $showall, $displayoptions),
                $attemptobj);

        $output .= $this->review_next_navigation($attemptobj, $page, $lastpage, $showall);
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
            
            if( $quiz->quiztype == 'trivias' ){

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

                    //print_r($infoopp);

                    if( $infoopp ){

                        $infooppdata = $infoopp->data;
                        $opponents = $infoopp->data->opponents;
                        $level = $infoopp->data->level;
                        $opponentemail = $infoopp->data->opponentemail;
                        $isopponent = $infoopp->data->is_opponent;
                        $lquizid = $infoopp->data->quizid_autoincrement;

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
                        );

                        global $PAGE;
                        $PAGE->requires->js(new moodle_url('/local/leeloolxptrivias/js/jquery.superwheel.min.js'));

                        $attemptlast = end($viewobj->attempts);
                        $hidespinner = '';
                        if( isset( $attemptlast->state ) ){
                            if( $attemptlast->state == 'inprogress' ){
                                $hidespinner = 'style="display:none"';
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

                                setCookie("l_quiz_isopp", 0, 1);
                                setCookie("l_quiz_time", 0, 1);
                                setCookie("l_quiz_id", 0, 1);
        
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
                                    $(".quizstartbuttondivthinkblue form").unbind("submit").submit();
                                });

                                $(".quizstartbuttondivthinkblue form").submit(function(e){

                                    $(".opponent_div").html("'.$opponentname.' is your opponent.");

                                    e.preventDefault();
                                    
                                    $(".roulette_m").css("visibility", "visible");
                                    
                                    $(".wheel-with-image").superWheel("start","value",'.$opponentstopnum.');  	

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
                    }
                }
            }
        }

        $output = '';
        if( isset($quiz->quiztype) && !is_siteadmin() && $quiz->quiztype == 'trivias' ){
            $output .= '<div class="trivia_info"><h2>Ready?</h2></div>';
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
        
        
        if($attemptobj->get_quiz()->quiztype == 'trivias'){
            global $PAGE;
            $PAGE->add_body_class('trivia_question');
        }
        
        if( !is_siteadmin() && $attemptobj->get_quiz()->quiztype == 'trivias' ){

            //$PAGE->requires->css('/local/leeloolxptrivias/css/trivia.css');

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
                    console.log("l_quiz_time"+l_quiz_time);

                    var start = new Date();
                    console.log("start"+start);

                    $(window).bind("beforeunload", function(){
                        console.log("leavepage");
                        var end = new Date();
                        console.log("end"+end);
                        var timeSpent = end - start;
                        console.log("timeSpent"+timeSpent);
                        var cookiespenttime = parseInt(l_quiz_time) + parseInt(timeSpent);
                        console.log("cookiespenttime"+cookiespenttime);
                        setCookie("l_quiz_time", cookiespenttime, 1);
                        $(".thinkblue_quiztimetaken").hide();
                    });

                    $(".thinkblue_quiztimetaken").show();
                }); 
            });');
        }    

        $output = '';
        $output .= $this->header();
        $output .= $this->quiz_notices($messages);
        $output .= html_writer::tag('div', '<span></span>',
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
            $output .= '<script>
            
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
            console.log("l_quiz_time"+l_quiz_time);

            var start = new Date;

            setInterval(function() {
                $(".thinkblue_quiztimetaken span").text( Math.round( (parseInt(l_quiz_time) + (new Date - start)) / 1000) + " Seconds");
            }, 1000);</script>';

            $output .= html_writer::tag('div', 'Time taken: <span></span>',
                array('class' => 'thinkblue_quiztimetaken hidden'));
        }

        $output .= html_writer::tag('div', $panel->render_end_bits($this),
                array('class' => 'othernav'));

        $this->page->requires->js_init_call('M.mod_quiz.nav.init', null, false,
                quiz_get_js_module());

        return $output;
    }
}
