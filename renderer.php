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
 * Renderers to align Moodle's HTML with that expected by Bootstrap, output folder renderers are not used. We can delete them.
 *
 * @package    local_leeloolxptrivias
 * @copyright  2020 Leeloo LXP (https://leeloolxp.com)
 * @author     Leeloo LXP <info@leeloolxp.com>
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die;
/**
 * Extending the mod_quiz_renderer interface.
 *
 * @copyright 2017 Kathrin Osswald, Ulm University kathrin.osswald@uni-ulm.de
 * @license http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 * @package local_leeloolxptrivias
 * @category output
 */

class local_leeloolxptrivias_renderer_factory extends theme_overridden_renderer_factory {
    public function __construct(theme_config $theme) {
        parent::__construct($theme);
        array_unshift($this->prefixes, 'local_leeloolxptrivias');
    }
}

use moodle_url;
use single_button;
use curl;
use quiz_attempt;
use confirm_action;
use mod_quiz_preflight_check_form;
use quiz_nav_panel_base;
use html_writer;
use cm_info;
use core_text;

include_once($CFG->dirroot . "/mod/quiz/renderer.php");
include_once($CFG->dirroot . "/course/renderer.php");
/**
 * Extending the mod_quiz_renderer interface.
 *
 * @copyright 2017 Kathrin Osswald, Ulm University kathrin.osswald@uni-ulm.de
 * @license http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 * @package local_leeloolxptrivias
 * @category output
 */
class local_leeloolxptrivias_mod_quiz_renderer extends mod_quiz_renderer {
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

        global $USER;

        if( isset($quiz->quiztype) && !is_siteadmin() ){
            
            if( $quiz->quiztype == 'trivias' ){

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
                        $PAGE->requires->js(new moodle_url('/local/leeloolxptrivias/js/roulette.js'));
        
                        echo '<div class="roulette_m" style="left: 0;visibility: hidden;position: fixed;padding: 10%;background: rgba(0,0,0,.5);width: 100%;height: 100%;top: 0;z-index: 999999;"><div class="roulette" style="display:none;">';

                        foreach( $opponents as $key=>$opponent ){
                            if( $opponent->email == $opponentemail ){
                                $opponentname = $opponent->name;
                                $opponentstopnum = $key;
                            }
                            
                            echo '<img height="200" width="200" src="'.$opponent->image.'"/>';
                        }

                        echo '</div>
                        <div class="opponent_div" style="display:none"></div>
                        </div>
                        ';

                        $PAGE->requires->js_init_code('require(["jquery"], function ($) {
                            $(document).ready(function () {

                                function setCookie(cname, cvalue, exdays) {
                                    const d = new Date();
                                    d.setTime(d.getTime() + (exdays*24*60*60*1000));
                                    let expires = "expires="+ d.toUTCString();
                                    document.cookie = cname + "=" + cvalue + ";" + expires + ";path=/";
                                }

                                setCookie("l_quiz_isopp", 0, 1);
                                setCookie("l_quiz_time", 0, 1);
                                setCookie("l_quiz_id", 0, 1);
        
                                var option = {
                                    speed : 50,
                                    duration : 1,
                                    stopImageNumber : '.$opponentstopnum.',
                                    startCallback : function() {
                                        console.log("start");
                                    },
                                    slowDownCallback : function() {
                                        console.log("slowDown");
                                    },
                                    stopCallback : function($stopElm) {
                                        console.log("stop");
                                        $(".opponent_div").show();
                                        $(".quizstartbuttondivthinkblue form").unbind("submit").submit();
                                    }
                                }
                                
                                $("div.roulette").roulette(option);
        
                                $(".quizstartbuttondivthinkblue form").submit(function(e){

                                    $(".opponent_div").html("'.$opponentname.' is your opponent.");

                                    console.log("submitclicked");
                                    e.preventDefault();
                                    $(".roulette_m").css("visibility", "visible");
                                    setTimeout(function(){
                                        $("div.roulette").roulette("start");
                                    }, 500);    	
                                    //return false;
                                    console.log("submitstopped");
                                    //ajax code to insert data

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


                                    /*setTimeout(function(){
                                        
                                        e.currentTarget.submit();
                                        
                                    }, 7500);*/
                                    
                                });
                            });
        
                        });');
        
                        echo 'The trivia dynamics';

                    }
                    

                }
                
            }

        }

        $output = '';
        $output .= $this->view_information($quiz, $cm, $context, $viewobj->infomessages);
        $output .= $this->view_table($quiz, $context, $viewobj);
        $output .= $this->view_result_info($quiz, $context, $cm, $viewobj);
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
            $button->class .= ' quizstartbuttondiv quizstartbuttondivthinkblue';
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

/**
 * The core course renderer
 *
 * Can be retrieved with the following:
 * $renderer = $PAGE->get_renderer('core','course');
 */
class local_leeloolxptrivias_course_renderer extends core_course_renderer {

    /**
     * Renders html to display a name with the link to the course module on a course page
     *
     * If module is unavailable for user but still needs to be displayed
     * in the list, just the name is returned without a link
     *
     * Note, that for course modules that never have separate pages (i.e. labels)
     * this function return an empty string
     *
     * @param cm_info $mod
     * @param array $displayoptions
     * @return string
     */
    public function course_section_cm_name_title(cm_info $mod, $displayoptions = array()) {
        $output = '';
        $url = $mod->url;
        if (!$mod->is_visible_on_course_page() || !$url) {
            // Nothing to be displayed to the user.
            return $output;
        }

        //Accessibility: for files get description via icon, this is very ugly hack!
        $instancename = $mod->get_formatted_name();
        $altname = $mod->modfullname;
        // Avoid unnecessary duplication: if e.g. a forum name already
        // includes the word forum (or Forum, etc) then it is unhelpful
        // to include that in the accessible description that is added.
        if (false !== strpos(core_text::strtolower($instancename),
                core_text::strtolower($altname))) {
            $altname = '';
        }
        // File type after name, for alphabetic lists (screen reader).
        if ($altname) {
            $altname = get_accesshide(' '.$altname);
        }

        list($linkclasses, $textclasses) = $this->course_section_cm_classes($mod);

        // Get on-click attribute value if specified and decode the onclick - it
        // has already been encoded for display (puke).
        $onclick = htmlspecialchars_decode($mod->onclick, ENT_QUOTES);

        if( $mod->modname == 'quiz' ){
            global $DB;
            $quizid = $mod->get_course_module_record()->instance;
            $quizdata = $DB->get_record('quiz', array('id' => $quizid), '*', MUST_EXIST);

            if( isset($quizdata->quiztype) ){
                if( $quizdata->quiztype == 'discover' ){
                    $iconsrc = $mod->get_icon_url().'?discover';
                }else if( $quizdata->quiztype == 'exercises' ){
                    $iconsrc = $mod->get_icon_url().'?exercises';
                }else if( $quizdata->quiztype == 'trivias' ){
                    $iconsrc = $mod->get_icon_url().'?trivias';
                }else if( $quizdata->quiztype == 'assessments' ){
                    $iconsrc = $mod->get_icon_url().'?assessments';
                } else {
                    $iconsrc = $mod->get_icon_url().'?default';
                }
            } else {
                $iconsrc = $mod->get_icon_url().'?default';
            }

        }else{
            $iconsrc = $mod->get_icon_url();
        }

        // Display link itself.
        $activitylink = html_writer::empty_tag('img', array('src' => $iconsrc,
                'class' => 'iconlarge activityicon', 'alt' => '', 'role' => 'presentation', 'aria-hidden' => 'true')) .
                html_writer::tag('span', $instancename . $altname, array('class' => 'instancename'));
        if ($mod->uservisible) {
            $output .= html_writer::link($url, $activitylink, array('class' => 'aalink' . $linkclasses, 'onclick' => $onclick));
        } else {
            // We may be displaying this just in order to show information
            // about visibility, without the actual link ($mod->is_visible_on_course_page()).
            $output .= html_writer::tag('div', $activitylink, array('class' => $textclasses));
        }
        return $output;
    }
}
