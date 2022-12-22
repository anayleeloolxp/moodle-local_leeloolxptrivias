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
use completion_info;

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
    public function review_page(
        quiz_attempt $attemptobj,
        $slots,
        $page,
        $showall,
        $lastpage,
        mod_quiz_display_options $displayoptions,
        $summarydata
    ) {

        if (
            ($attemptobj->get_quiz()->quiztype == 'duels' || $attemptobj->get_quiz()->quiztype == 'regularduel') && !is_siteadmin()
        ) {
            $this->page->add_body_class('trivia_review');
        }

        global $USER, $DB;

        $baseemail = base64_encode($USER->email);

        if (
            ($attemptobj->get_quiz()->quiztype == 'duels' || $attemptobj->get_quiz()->quiztype == 'regularduel') && !is_siteadmin()
        ) {

            if ($attemptobj->get_quiz()->quiztype == 'duels') {
                $spinbtntxt = 'Spin!';
            } else {
                $spinbtntxt = 'Check!';
            }

            $trivareview = '';
            $hidereviewclass = '';
            $trivaspinner = '';
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

            if ($false == 0) {

                $postdata = array(
                    'useremail' => base64_encode($USER->email),
                    'attemptId' => $attemptobj->get_attempt()->id,
                    'cmId' => $attemptobj->get_quiz()->cmid
                );

                $url = $leeloolxpurl . '/admin/sync_moodle_course/gettriviadata';

                $urlsavescore = $leeloolxpurl . '/admin/sync_moodle_course/attempt_save_score';

                $curl = new curl;

                $options = array(
                    'CURLOPT_RETURNTRANSFER' => true,
                    'CURLOPT_HEADER' => false,
                    'CURLOPT_POST' => count($postdata),
                    'CURLOPT_HTTPHEADER' => array(
                        'Leeloolxptoken: ' . get_config('local_leeloolxpapi')->leelooapitoken . ''
                    )
                );

                $outputopp = $curl->post($url, $postdata, $options);

                $infoopp = json_decode($outputopp);

                if ($infoopp) {
                    $data = $infoopp->data;

                    $userwinnerclass = '';
                    $oppwinnerclass = '';
                    if ($data->winner == $data->user_email) {
                        $userwinnerclass = 'iswinner';
                    } else if ($data->winner == $data->opponent_email) {
                        $oppwinnerclass = 'iswinner';
                    }

                    $usershtml = '<div class="trivia_users">
                    <div class="trivia_user triviauser1 ' .
                        $userwinnerclass . '"> <div class="user_img"><img src="' .
                        $data->userimage . '" /></div> <div class="user_name"><span>1</span>' .
                        '<div class="resultuserdetail"><small>' .
                        $data->user . '</small><span class="teamname">' .
                        $data->userteam . '</span></div></div> <div class="user_scrore"><b>' .
                        $data->userpoints . '</b> <small>points</small></div> </div> <div class="trivia_user triviauser2 ' .
                        $oppwinnerclass . '"> <div class="user_img"><img src="' .
                        $data->oppimage .
                        '" /></div> <div class="user_name"><span>2</span> <div class="resultuserdetail"><small>' .
                        $data->opponent . '</small><span class="teamname">' .
                        $data->oppteam . '</span></div></div> <div class="user_scrore"><b>' .
                        $data->opppoints . '</b> <small>points</small></div> </div></div>';

                    if ($data->winner == '0') {
                        $classwin = 'waiting_outer';
                        $trivareview .= '<div class="triva_review_inner waiting"><h2>Waiting!</h2>' .
                            $rewardhtml . $usershtml . '</div>';
                    } else if ($USER->email == $data->winner) {
                        $classwin = 'winner_outer';
                        $trivareview .= '<div class="triva_review_inner winner"><h2>You Won!</h2>' .
                            $rewardhtml . $usershtml . '</div>';
                    } else {
                        $classwin = 'losser_outer';
                        $trivareview .= '<div class="triva_review_inner losser"><h2>You Lost!</h2>' .
                            $rewardhtml . $usershtml . '</div>';
                    }

                    if ($USER->email == $data->user_email) {
                        $timetaken = $data->usertime;
                        $isopp = 0;
                        $attackdefencetext = 'Attack';
                        $attackdefencepoints = $data->attacks;
                    } else {
                        $timetaken = $data->opponenttime;
                        $isopp = 1;
                        $attackdefencetext = 'Defence';
                        $attackdefencepoints = $data->defences;
                    }

                    if ($data->userpoints == '' && $isopp == 0) {
                        $spinerfor = 'user';
                        $showpointsspinner = 1;
                    } else if ($data->opppoints == '' && $isopp == 1) {
                        $spinerfor = 'opp';
                        $showpointsspinner = 1;
                    } else {
                        $spinerfor = '';
                        $showpointsspinner = 0;
                    }

                    if ($showpointsspinner == 1) {
                        $attemptdifficultysum = $DB->get_records_sql(
                            "SELECT
                            qa.id,
                            qd.difficulty
                            FROM {question_attempts} qa
                            left join {local_leeloolxptrivias_qd} qd
                            on qa.questionid = qd.questionid
                            WHERE questionusageid = ?",
                            [$attemptobj->get_attempt()->id]
                        );

                        $countquestions = count($attemptdifficultysum);

                        $quizmaxtime = 0;

                        foreach ($attemptdifficultysum as $quizquestion) {

                            if (!$quizquestion->difficulty) {
                                $questiondiff = 1;
                            } else {
                                $questiondiff = $quizquestion->difficulty;
                            }

                            if ($questiondiff == 3) {
                                $questiontime = 15;
                            } else if ($questiondiff == 2) {
                                $questiontime = 10;
                            } else {
                                $questiontime = 5;
                            }

                            $quizmaxtime = $quizmaxtime + $questiontime;
                        }

                        $hidereviewclass = 'visibility: hidden;';
                        global $CFG;
                        $this->page->requires->js(new moodle_url('/local/leeloolxptrivias/js/jquery.superwheel.min.js'));

                        $luckmin = $data->luck - 9;
                        if ($luckmin < 1) {
                            $luckmin = 1;
                            $luckmax = 10;
                        } else {
                            $luckmax = $data->luck;
                        }

                        $slices = array();

                        $key = 0;
                        for ($x = $luckmin; $x <= $luckmax; $x++) {
                            $slices[$key]['text'] = $x;
                            $slices[$key]['value'] = $x;
                            $slices[$key]['message'] = $x;
                            $slices[$key]['backgroundtext'] = '#a7b2da';
                            $key++;
                        }

                        $this->page->requires->js_init_code('require(["jquery"], function ($) {
                            $(document).ready(function () {

                                $(".wheel-standard").superWheel({
                                    slices: ' . json_encode($slices) . ',
                                    text : {
                                        color: "#303030",
                                    },
                                    line: {
                                        width: 10,
                                        color: "#303030"
                                    },
                                    outer: {
                                        width: 14,
                                        color: "#303030"
                                    },
                                    inner: {
                                        width: 15,
                                        color: "#303030"
                                    },
                                    marker: {
                                        background: "#00BCD4",
                                        animate: 1
                                    },
                                    selector: "value",
                                });

                                var tick = new Audio("' . $CFG->wwwroot . '/local/leeloolxptrivias/media/tick.mp3");

                                $(document).on("click",".wheel-standard-spin-button",function(e){

                                    if( !$(this).hasClass("spinningDone") ){
                                        $(
                                            ".wheel-standard"
                                        ).superWheel(
                                            "start",
                                            "value",
                                            Math.floor(Math.random() * (' . $luckmax . ' - ' . $luckmin . ' + 1) + ' . $luckmin . ')
                                        );
                                        $(this).prop("disabled",true);
                                    }

                                });

                                $(document).on("click",".spinningDone",function(e){
                                    location.reload();
                                });


                                $(".wheel-standard").superWheel("onStart",function(results){

                                    $(".wheel-standard-spin-button").text("' . $spinbtntxt . '").addClass("spinning");

                                });
                                $(".wheel-standard").superWheel("onStep",function(results){

                                    if (typeof tick.currentTime !== "undefined")
                                        tick.currentTime = 0;
                                    tick.play();

                                });

                                $(".wheel-standard").superWheel("onComplete",function(results){

                                    var postForm = {
                                        "useremail" : "' . $baseemail . '",
                                        "attemptid" : "' . $attemptobj->get_attempt()->id . '",
                                        "isopp" : "' . $isopp . '",
                                        "spinnerval" : results.value,
                                        "course_id" : "' . $attemptobj->get_quiz()->course . '",
                                        "activityid" : "' . $attemptobj->get_quiz()->cmid . '",
                                        "quizmaxtime" : "' . $quizmaxtime . '",
                                        "countquestions" : "' . $countquestions . '",
                                        "attack" : "' . $data->attacks . '",
                                        "defence" : "' . $data->defences . '",
                                        "luck" : "' . $data->luck . '",
                                        "will" : "' . $data->will . '",
                                        "power" : "' . $data->power . '",
                                        "marks" : "' . $summarydata['marks']['content'] . '",
                                        "installlogintoken": "' . $_COOKIE['installlogintoken'] . '"
                                    };

                                    $.ajax({
                                        type : "POST",
                                        url : "' . $urlsavescore . '",
                                        data : postForm,
                                        dataType : "json",
                                        success : function(data) {

                                            $(".wheel-standard-spin-button").text("Ok!").addClass("spinningDone");
                                            $(".gam-points-right").addClass("addpoint");
                                            $(".gam-points-top").text(data);
                                            $(".wheel-standard-spin-button").prop("disabled",false);

                                        }
                                    });

                                });

                            });
                        });');

                        if (isset($summarydata['marks']) && 1 == 0) {
                            $titleformarks = $summarydata['marks']['title'];
                            $stringmarks = round(explode('/', $summarydata['marks']['content'])[0], 0) .
                                '<small>/' . round(explode('/', $summarydata['marks']['content'])[1], 0) . '</small>';
                        } else {
                            $titleformarks = '';
                            $stringmarks = '<small style="color: black;font-size: 30px;">' .
                                $summarydata['grade']['content'] .
                                '</small>';
                        }

                        $trivaspinner .= '
                        <div class="modal-backdrop fade show"></div>
                        <div class="modal fade show"
                        id="gam_popup_spinner" tabindex="-1"
                        role="dialog" aria-labelledby="gam_popup_spinner" aria-modal="true">
                            <div class="modal-dialog" role="document">
                                <div class="modal-content">
                                    <div class="gam-spinner">
                                        <div class="gam-spinner-left">
                                            <div class="gam-spinner-top">
                                                <!--<div class="gam-nm-top">08<small>/10</small></div>
                                                <div class="gam-txt-top">correct answers</div>-->
                                                <div class="gam-nm-top">
                                                ' . $stringmarks . '
                                                </div>
                                                <div class="gam-txt-top">' . $titleformarks . '</div>
                                            </div>
                                            <div class="gam-spinner-mdl">
                                                <main class="cd-main-content text-center">

                                                    <div class="wheel-standard"></div>
                                                    <button type="button" class="button button-primary wheel-standard-spin-button">
                                                    ' . $spinbtntxt . '
                                                    </button>

                                                </main> <!-- cd-main-content -->

                                            </div>
                                            <div class="gam-spinner-btm">' . gmdate("H:i:s", $timetaken) . '</div>
                                        </div>
                                        <div class="gam-spinner-right">
                                            <div class="gam-top-right">
                                                <div class="gam-points-right">
                                                    <div class="gam-points-top">______</div>
                                                    <div class="gam-points-txt">points</div>
                                                </div>
                                            </div>
                                            <div class="gam-btm-right">
                                                <ul>
                                                    <li class="active">
                                                        <div class="gam-btm-item">
                                                            <p>' . $attackdefencetext . '</p>
                                                            <h3>' . $attackdefencepoints . '</h3>
                                                        </div>
                                                    </li>
                                                    <li class="">
                                                        <div class="gam-btm-item">
                                                            <p>Luck</p>
                                                            <h3>' . $data->luck . '</h3>
                                                        </div>
                                                    </li>
                                                    <li class="">
                                                        <div class="gam-btm-item">
                                                            <p>Power</p>
                                                            <h3>' . $data->power . '</h3>
                                                        </div>
                                                    </li>
                                                    <li class="">
                                                        <div class="gam-btm-item">
                                                            <p>Will</p>
                                                            <h3>' . $data->will . '</h3>
                                                        </div>
                                                    </li>
                                                </ul>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                        ';

                        if ($attemptobj->get_quiz()->quiztype == 'regularduel') {
                            $trivaspinner .= '<style>.sWheel-title,.gam-btm-right {display: none;}</style>';
                        }
                    }

                    $letsgourl = '';
                    $thisar = 0;
                    $courseid = $this->page->course->id;


                    $modinfo = get_fast_modinfo($courseid);
                    foreach ($modinfo->cms as $ar) {

                        if ($letsgourl) {
                            break;
                        }

                        if ($ar->visible == 1 && $thisar == 1) {
                            $letsgourl = $ar->url;

                            if ($ar->modname == 'quiz') {
                                $quizid = $ar->get_course_module_record()->instance;
                                $quizdata = $DB->get_record('quiz', array('id' => $quizid), '*', MUST_EXIST);
                                if ($quizdata->quiztype == 'discover' || $quizdata->quiztype == 'trivias') {
                                    $letsgourl .= '&autostart=1';
                                }
                            }
                        }

                        if ($attemptobj->get_quiz()->cmid == $ar->id) {
                            $thisar = 1;
                        }
                    }

                    $trivareview .= '<div class="playagainDf playagaindiv ' . $classwin . '">
                        <div class="playagaininnerdiv">
                            <a class="small_playagain" href="' . $CFG->wwwroot . '/mod/quiz/view.php?id=' . $attemptobj->get_quiz()->cmid . '">
                                <span>New</span>
                            </a>
                        </div>
                        <div class="playagaininnerdiv">
                            <a class="trivia_playagain"
                            href="' . $CFG->wwwroot . '/mod/quiz/view.php?id=' . $attemptobj->get_quiz()->cmid . '&rematch=' . $data->id . '">
                            Play<br>Again!
                            </a>
                        </div>';

                    if ($letsgourl != '') {
                        $trivareview .= '<div class="playagaininnerdiv">
                            <a class="small_playagain" href="' . $letsgourl . '">
                                <span>Next</span>
                            </a>
                        </div>';
                    }

                    $trivareview .= '</div>';

                    if (!empty($data->current_rewards)) {

                        $allrewardshtml = '';

                        foreach ($data->current_rewards as $reward) {
                            $allrewardshtml .= '<div class="item">
                                <div class="arena-slider-item">
                                    <span data-toggle="tooltip" data-placement="top" data-original-title="' . $reward->name . '">
                                        <img src="' . $reward->image . '"/>
                                    </span>
                                    <small>' . $reward->count . '</small>
                                </div>
                            </div>';
                        }

                        $trivareview .= '<div class="arena-section-TopBanner-slider">
                            <div class="arena-slider owl-carousel">
                                ' . $allrewardshtml . '

                            </div>
                        </div>';

                        $this->page->requires->js(new moodle_url('/local/leeloolxptrivias/js/owl.carousel.js'));

                        $this->page->requires->js_init_code('require(["jquery"], function ($) {
                            $( document ).ready(function( $ ) {
                                var owl = $(".arena-slider");
                                owl.owlCarousel({
                                loop: false,
                                autoplay: false,
                                dots: false,
                                nav: true,
                                center:true,
                                responsive: {
                                    0: {
                                    items: 3
                                    },
                                    600: {
                                    items: 5
                                    },
                                    1000: {
                                    items: 11
                                    }
                                }
                                })
                            });
                        });');
                    }
                }
            }

            $trivareview .= '<style>[data-region="blocks-column"]{display:none}</style>';

            $output = '';
            $output .= $this->header();
            $output .= '<div class="triva_spinner_outer">' . $trivaspinner . '</div>';
            $output .= '<div class="triva_review_outer" style="' . $hidereviewclass . '">' . $trivareview . '</div>';

            $output .= $this->review_next_navigation($attemptobj, $page, $lastpage, $showall);
            $output .= $this->footer();
        } else {
            $output = '';
            $output .= $this->header();
            $output .= $this->review_summary_table($summarydata, $page);
            $output .= $this->review_form(
                $page,
                $showall,
                $displayoptions,
                $this->questions($attemptobj, true, $slots, $page, $showall, $displayoptions),
                $attemptobj
            );

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
        if (
            $attemptobj->get_quiz()->quiztype == 'duels' ||
            $attemptobj->get_quiz()->quiztype == 'discover' ||
            $attemptobj->get_quiz()->quiztype == 'trivias' ||
            $attemptobj->get_quiz()->quiztype == 'regularduel'
        ) {
            $this->page->requires->js_init_code('require(["jquery"], function ($) {
                $(document).ready(function () {

                    $("body").addClass("trivia_summarypage loaderonly");

                    $(".submitbtns form").submit();
                });
            });');
        }

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
     * @param mod_quiz_view_object $viewobj View object
     * @return void Return data
     */
    public function view_page($course, $quiz, $cm, $context, $viewobj) {

        global $USER;

        if (isset($quiz->quiztype) && !is_siteadmin()) {

            $reqautostart = optional_param('autostart', 0, PARAM_RAW);

            $reqrematch = optional_param('rematch', 0, PARAM_INTEGER);
            $reqaccept = optional_param('accept', 0, PARAM_INTEGER);

            if ($quiz->quiztype == 'duels' || $quiz->quiztype == 'regularduel') {

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

                if ($false == 0) {

                    $baseemail = base64_encode($USER->email);
                    $postdata = array(
                        'useremail' => $baseemail,
                        'quiztype' => $quiz->quiztype,
                        'course_id' => $quiz->course,
                        'moodlequizid' => $quiz->id,
                        'activityid' => $cm->id,
                        'rematch' => $reqrematch,
                        'accept' => $reqaccept,
                    );

                    $url = $leeloolxpurl . '/admin/sync_moodle_course/quiz_opponents_response';

                    $urlsave = $leeloolxpurl . '/admin/sync_moodle_course/quiz_attempt_start';

                    $curl = new curl;

                    $options = array(
                        'CURLOPT_RETURNTRANSFER' => true,
                        'CURLOPT_HEADER' => false,
                        'CURLOPT_POST' => count($postdata),
                        'CURLOPT_HTTPHEADER' => array(
                            'Leeloolxptoken: ' . get_config('local_leeloolxpapi')->leelooapitoken . ''
                        )
                    );

                    $outputopp = $curl->post($url, $postdata, $options);

                    $infoopp = json_decode($outputopp);
                    /* echo '<pre>';
                    print_r($infoopp);
                    echo '</pre>'; */
                    if ($infoopp) {

                        $infooppdata = $infoopp->data;
                        $opponents = $infoopp->data->opponents;
                        $level = $infoopp->data->level;
                        $opponentemail = $infoopp->data->opponentemail;
                        $isopponent = $infoopp->data->is_opponent;
                        $lquizid = $infoopp->data->quizid_autoincrement;

                        $reward = $infoopp->reward;
                        $timelastatempt = $infoopp->timelastatempt * 1000;
                        $lquizisoppunder = $infoopp->l_quiz_isopp;
                        $lquizidunder = $infoopp->l_quiz_id;

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
                            'activity_id' => $quiz->cmid,
                            'accept' => $reqaccept,
                        );

                        $this->page->requires->js(new moodle_url('/local/leeloolxptrivias/js/jquery.superwheel.min.js'));

                        $attemptlast = end($viewobj->attempts);
                        $hidespinner = '';

                        $setcookies = 'setCookie("l_quiz_isopp", 0, 1);
                        setCookie("l_quiz_time", 0, 1);
                        setCookie("l_quiz_id", 0, 1);
                        setCookie("m_attempt_id", 0, 30);';

                        if (isset($attemptlast->state)) {
                            if ($attemptlast->state == 'inprogress') {
                                $hidespinner = 'style="display:none"';

                                $setcookies = 'setCookie("l_quiz_isopp", ' . $lquizisoppunder . ', 1);
                                setCookie("l_quiz_time", ' . $timelastatempt . ', 1);
                                setCookie("l_quiz_id", ' . $lquizidunder . ', 1);
                                setCookie("m_attempt_id", 0, 30);';
                            }
                        }
                        $spinnerhtml .= '<div class="roulette_m" ' . $hidespinner . '><main class="cd-main-content text-center">
                            <div class="wheel-with-image"></div>
                            <!--<button type="button" class="button button-primary wheel-with-image-spin-button">Spin</button>-->
                        </main> <!-- cd-main-content -->';

                        $spinnerhtml .= '<div class="opponent_div" style="display:none"></div></div>';

                        $oppslises = array();
                        foreach ($opponents as $key => $opponent) {
                            if ($opponent->email == $opponentemail) {
                                $opponentname = $opponent->name;
                                $opponentstopnum = $key;
                            }

                            $oppslises[$key]['text'] = $opponent->image;
                            $oppslises[$key]['value'] = $key;
                            $oppslises[$key]['message'] = $opponent->name;
                            $oppslises[$key]['backgroundtext'] = '#a7b2da';
                        }

                        if ($reqrematch != 0) {

                            $oppnenntdiv = '<div class=\'rematch_playdiv\'>' .
                                '<div class=\'wheel-with-image superWheel _0\' ' .
                                'style=\'font-size: 25px;width: 500px;height: 500px;\'>' .
                                '<div class=\'sWheel-wrapper\' style=\'width: 500px; height: 500px; font-size: 100%;\'>' .
                                '<div class=\'sWheel-inner\'><div class=\'sWheel\'>' .
                                '<div class=\'sWheel-bg-layer\'></div></div><div class=\'sWheel-center\'>' .
                                '<div class=\'sw-center-html\' style=\'width: 30%; height: 30%;\'>' .
                                '<span class=\'trivia_play\'>PLAY!</span></div></div></div></div></div></div>';

                            $this->page->requires->js_init_code('require(["jquery"], function ($) {
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

                                    ' . $setcookies . '

                                    $(".opponent_div").html("Rematch with ' . $opponentname . $oppnenntdiv . '");
                                    $(".opponent_div").show();

                                    $(".quizstartbuttondivthinkblue form").submit(function(e){

                                        var postForm = {
                                            "useremail" : "' . $baseemail . '",
                                            "data" : \'' . json_encode($savequizdata) . '\',
                                            "installlogintoken": "' . $_COOKIE['installlogintoken'] . '"
                                        };

                                        $.ajax({
                                            type : "POST",
                                            url : "' . $urlsave . '",
                                            data : postForm,
                                            dataType : "json",
                                            success : function(data) {
                                                setCookie("l_quiz_isopp", ' . $isopponent . ', 30);
                                                setCookie("l_quiz_id", data, 30);
                                                console.log(data);
                                            }
                                        });

                                    });
                                });
                            });');
                        } else if ($isopponent == 1) {
                            $oppnenntdiv = '<div class=\'rematch_playdiv\'>' .
                                '<div class=\'wheel-with-image superWheel _0\' ' .
                                'style=\'font-size: 25px;width: 500px;height: 500px;\'>' .
                                '<div class=\'sWheel-wrapper\' style=\'width: 500px; height: 500px; font-size: 100%;\'>' .
                                '<div class=\'sWheel-inner\'><div class=\'sWheel\'>' .
                                '<div class=\'sWheel-bg-layer\'></div></div><div class=\'sWheel-center\'>' .
                                '<div class=\'sw-center-html\' style=\'width: 30%; height: 30%;\'>' .
                                '<span class=\'trivia_play\'>PLAY!</span></div></div></div></div></div></div>';

                            $this->page->requires->js_init_code('require(["jquery"], function ($) {
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

                                    ' . $setcookies . '

                                    $(".opponent_div").html("Accept Challenge of ' . $opponentname . $oppnenntdiv . '");
                                    $(".opponent_div").show();

                                    $(".quizstartbuttondivthinkblue form").submit(function(e){

                                        var postForm = {
                                            "useremail" : "' . $baseemail . '",
                                            "data" : \'' . json_encode($savequizdata) . '\',
                                            "installlogintoken": "' . $_COOKIE['installlogintoken'] . '"
                                        };

                                        $.ajax({
                                            type : "POST",
                                            url : "' . $urlsave . '",
                                            data : postForm,
                                            dataType : "json",
                                            success : function(data) {
                                                setCookie("l_quiz_isopp", ' . $isopponent . ', 30);
                                                setCookie("l_quiz_id", data, 30);
                                                console.log(data);
                                            }
                                        });

                                    });
                                });
                            });');
                        } else {
                            $this->page->requires->js_init_code('require(["jquery"], function ($) {
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

                                    ' . $setcookies . '

                                    $(".wheel").superWheel("onFail",function(results,spinCount,now){
                                        console.log("Spin Failed, Something wrong in wheel settings");
                                        console.log(results);
                                    });

                                    $(".wheel-with-image").superWheel({
                                        slices: ' . json_encode($oppslises) . ',
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

                                    var tick = new Audio("' . $CFG->wwwroot . '/local/leeloolxptrivias/media/tick.mp3");

                                    $(document).on("click",".wheel-with-image-spin-button",function(e){
                                        $(".wheel-with-image").superWheel("start","value",' . $opponentstopnum . ');
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
                                            "useremail" : "' . $baseemail . '",
                                            "data" : \'' . json_encode($savequizdata) . '\',
                                            "installlogintoken": "' . $_COOKIE['installlogintoken'] . '"
                                        };

                                        $.ajax({
                                            type : "POST",
                                            url : "' . $urlsave . '",
                                            data : postForm,
                                            dataType : "json",
                                            success : function(data) {
                                                setCookie("l_quiz_isopp", ' . $isopponent . ', 30);
                                                setCookie("l_quiz_id", data, 30);
                                                console.log(data);
                                            }
                                        });

                                        $(".quizstartbuttondivthinkblue form").unbind("submit").submit();
                                    });

                                    $(".quizstartbuttondivthinkblue form").submit(function(e){

                                        $(".opponent_div").html("' . $opponentname . ' is your opponent.");

                                        e.preventDefault();

                                        $(".roulette_m").css("visibility", "visible");

                                        $(".wheel-with-image").superWheel("start","value",' . $opponentstopnum . ');

                                    });
                                });
                            });');
                        }
                    }
                }
            } else if (($quiz->quiztype == 'trivias' || $quiz->quiztype == 'discover') && $reqautostart == 1) {

                if ($quiz->quiztype == 'trivias') {
                    $this->page->requires->js_init_code('require(["jquery"], function ($) {
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

                $this->page->requires->js_init_code('require(["jquery"], function ($) {
                    $(document).ready(function () {

                        if ($(".quizstartbuttondiv button").length) {
                            $("body").addClass("loaderonly");
                            $(".quizstartbuttondiv button").trigger("click");
                        }

                    });
                });');
            }
        }

        $output = '';
        if (isset($quiz->quiztype) && !is_siteadmin() && ($quiz->quiztype == 'duels' || $quiz->quiztype == 'regularduel')) {

            if ($reward != 0) {
                $rewardhtml = '<div class="reward_html">Reward: <span>' . $reward . '</span> ' . get_string('neurons', 'local_leeloolxptrivias') . '</div>';
            } else {
                $rewardhtml = '';
            }

            $output .= '<div class="trivia_info"><h2>Ready?</h2>' . $rewardhtml . '</div>';
            $output .= $spinnerhtml;
        } else {
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
     * @param int $stopentry stopentry flag.
     * @return string HTML fragment.
     */
    public function start_attempt_button(
        $buttontext,
        moodle_url $url,
        mod_quiz_preflight_check_form $preflightcheckform = null,
        $popuprequired = false,
        $popupoptions = null,
        $stopentry = 1
    ) {

        if (is_string($preflightcheckform)) {
            // Calling code was not updated since the API change.
            debugging('The third argument to start_attempt_button should now be the ' .
                'mod_quiz_preflight_check_form from ' .
                'quiz_access_manager::get_preflight_check_form, not a warning message string.');
        }

        $button = new single_button($url, $buttontext);
        if ($stopentry == 1) {
            $button->class .= ' quizstartbuttondiv';
        } else {
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

        $this->page->requires->js_call_amd(
            'mod_quiz/preflightcheck',
            'init',
            array(
                '.quizstartbuttondiv [type=submit]', get_string('startattempt', 'quiz'),
                '#mod_quiz_preflight_form', $popupjsoptions
            )
        );

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
        if (isset($attemptlast->state)) {
            if ($attemptlast->state == 'inprogress') {
                $stopentry = 1;
            }
        }

        if (!$viewobj->quizhasquestions) {
            $output .= $this->no_questions_message($viewobj->canedit, $viewobj->editurl);
        }

        $output .= $this->access_messages($viewobj->preventmessages);

        if ($viewobj->buttontext) {
            $output .= $this->start_attempt_button(
                $viewobj->buttontext,
                $viewobj->startattempturl,
                $viewobj->preflightcheckform,
                $viewobj->popuprequired,
                $viewobj->popupoptions,
                $stopentry
            );
        }

        if ($viewobj->showbacktocourse) {
            $output .= $this->single_button(
                $viewobj->backtocourseurl,
                get_string('backtocourse', 'quiz'),
                'get',
                array('class' => 'continuebutton')
            );
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
    public function attempt_page(
        $attemptobj,
        $page,
        $accessmanager,
        $messages,
        $slots,
        $id,
        $nextpage
    ) {
        global $DB;
        $quizid = $attemptobj->get_quiz()->id;
        $slotsarr = $DB->get_records('quiz_slots', array('quizid' => $quizid), 'slot');
        $questionsperpage = $attemptobj->get_quiz()->questionsperpage;
        $totalquestions = count($slotsarr);
        $totalpage = ceil($totalquestions / $questionsperpage);
        $crrpage = $page + 1;

        if (
            $attemptobj->get_quiz()->quiztype == 'duels' ||
            $attemptobj->get_quiz()->quiztype == 'discover' ||
            $attemptobj->get_quiz()->quiztype == 'trivias' ||
            $attemptobj->get_quiz()->quiztype == 'regularduel'
        ) {
            $this->page->add_body_class('trivia_question');

            $this->page->add_body_class('leeloolxp_quiz_type_' . $attemptobj->get_quiz()->quiztype);

            $this->page->requires->js_init_code('require(["jquery"], function ($) {
                $(document).ready(function () {

                    $(
                        ".endtestlink"
                    ).text(
                        "Finish ' . get_string($attemptobj->get_quiz()->quiztype . '_lang', 'local_leeloolxptrivias') . '"
                    );

                });
            });');

            if ($attemptobj->get_quiz()->quiztype == 'trivias') {
                $this->page->requires->js_init_code('require(["jquery"], function ($) {
                    $(window).bind("beforeunload", function(){
                        $(".thinkblue_quiztimetaken").hide();
                    });

                    $(".thinkblue_quiztimetaken").show();
                });');
            }
        }

        if (
            !is_siteadmin() && ($attemptobj->get_quiz()->quiztype == 'duels' || $attemptobj->get_quiz()->quiztype == 'regularduel')
        ) {

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

                $url = $leeloolxpurl . '/admin/sync_moodle_course/savetriviadata';
                $saveattemptidurl = $leeloolxpurl . '/admin/sync_moodle_course/saveattemptid';
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

                        function setCookie(cname, cvalue, exdays) {
                            const d = new Date();
                            d.setTime(d.getTime() + (exdays*24*60*60*1000));
                            let expires = "expires="+ d.toUTCString();
                            document.cookie = cname + "=" + cvalue + ";" + expires + ";path=/";
                        }

                        var l_quiz_time = getCookie("l_quiz_time");
                        var l_quiz_isopp = getCookie("l_quiz_isopp");
                        var l_quiz_id = getCookie("l_quiz_id");

                        var m_attempt_id = getCookie("m_attempt_id");

                        if( m_attempt_id == 0 ){
                            var postForm = {
                                "useremail" : "' . $baseemail . '",
                                "l_quiz_id" : l_quiz_id,
                                "attemptid" : "' . $attemptobj->get_attempt()->id . '",
                            };

                            var postdata = {
                                "data" : window.btoa(JSON.stringify(postForm)),
                                "installlogintoken": "' . $_COOKIE['installlogintoken'] . '"
                            }

                            $.ajax({
                                type : "POST",
                                url : "' . $saveattemptidurl . '",
                                data : postdata,
                                success : function(data) {
                                    setCookie("m_attempt_id", data, 30);
                                    console.log(data);
                                }
                            });
                        }



                        function save_trivia_time(){
                            var postForm = {
                                "timetaken" : Math.round( (parseInt( getCookie("l_quiz_time") ) ) / 1000),
                                "useremail" : "' . $baseemail . '",
                                "course_id" : "' . $attemptobj->get_quiz()->course . '",
                                "moodlequizid" : "' . $attemptobj->get_quiz()->id . '",
                                "isopponent" : l_quiz_isopp,
                            };

                            var postdata = {
                                "data" : window.btoa(JSON.stringify(postForm)),
                                "installlogintoken": "' . $_COOKIE['installlogintoken'] . '"
                            }

                            $.ajax({
                                type : "POST",
                                url : "' . $url . '",
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

        if ($totalpage == 1) {
            $output .= '<style>#mod_quiz_navblock .qn_buttons { display: none!important; }</style>';
        }

        $output .= $this->quiz_notices($messages);
        $output .= html_writer::tag(
            'div',
            '<span class="timerspan"></span><span class="pagespan">' . $crrpage . '/' . $totalpage . '</span>',
            array('class' => 'thinkblue_quiztimetaken top_timer hidden')
        );
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
            $output .= html_writer::tag(
                'div',
                $this->render($userpicture) . $fullname,
                array('id' => 'user-picture', 'class' => 'clearfix')
            );
        }
        $output .= $panel->render_before_button_bits($this);

        $bcc = $panel->get_button_container_class();
        $output .= html_writer::start_tag('div', array('class' => "qn_buttons clearfix $bcc"));
        foreach ($panel->get_question_buttons() as $button) {
            $output .= $this->render($button);
        }
        $output .= html_writer::end_tag('div');

        if (!is_siteadmin()) {
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

                        $(
                            ".thinkblue_quiztimetaken span.timerspan"
                        ).text( Math.round( (parseInt(l_quiz_time) + (new Date - start)) / 1000) + " Seconds");
                    }

                    var myVar = setInterval( myTimer , 1000);

                    $(window).bind("beforeunload", function(){
                        clearInterval(myVar);
                    });
                });
            });
            ');

            $output .= html_writer::tag(
                'div',
                'Time taken: <span class="timerspan"></span>',
                array('class' => 'thinkblue_quiztimetaken hidden')
            );
        }

        $output .= html_writer::tag(
            'div',
            $panel->render_end_bits($this),
            array('class' => 'othernav')
        );

        $this->page->requires->js_init_call(
            'M.mod_quiz.nav.init',
            null,
            false,
            quiz_get_js_module()
        );

        return $output;
    }

    /**
     * Ouputs the form for making an attempt
     *
     * @param quiz_attempt $attemptobj
     * @param int $page Current page number
     * @param array $slots Array of integers relating to questions
     * @param int $id ID of the attempt
     * @param int $nextpage Next page number
     */
    public function attempt_form($attemptobj, $page, $slots, $id, $nextpage) {
        $output = '';

        // Start the form.
        $output .= html_writer::start_tag(
            'form',
            array(
                'action' => new moodle_url(
                    $attemptobj->processattempt_url(),
                    array('cmid' => $attemptobj->get_cmid())
                ), 'method' => 'post',
                'enctype' => 'multipart/form-data', 'accept-charset' => 'utf-8',
                'id' => 'responseform'
            )
        );
        $output .= html_writer::start_tag('div');

        // Print all the questions.
        foreach ($slots as $slot) {
            $questiondata = $attemptobj->get_question_attempt($slot)->get_question(true);
            $questionid = $questiondata->id;

            global $DB;
            $qddata = $DB->get_record('local_leeloolxptrivias_qd', array('questionid' => $questionid));
            $videoval = 0;
            $videohtml = '';
            if ($qddata) {
                $videoval = $qddata->vimeoid;
            }

            if ($videoval) {
                $videohtml = '<div class="videoWrapper"><iframe
                id="vimeoiframe"
                src="https://player.vimeo.com/video/' . $videoval . '"
                width="640"
                height="320"
                allowfullscreen=""></iframe></div>
                <style>
                .videoWrapper {
                    position: relative;
                    padding-bottom: 56.25%; /* 16:9 */
                    height: 0;
                    margin-bottom: 20px;
                  }
                  .videoWrapper iframe {
                    position: absolute;
                    top: 0;
                    left: 0;
                    width: 100%;
                    height: 100%;
                  }
                </style>
                ';
            }

            $output .= $videohtml . $attemptobj->render_question(
                $slot,
                false,
                $this,
                $attemptobj->attempt_url($slot, $page),
                $this
            );
        }

        $navmethod = $attemptobj->get_quiz()->navmethod;
        $output .= $this->attempt_navigation_buttons($page, $attemptobj->is_last_page($page), $navmethod);

        // Some hidden fields to trach what is going on.
        $output .= html_writer::empty_tag('input', array(
            'type' => 'hidden', 'name' => 'attempt',
            'value' => $attemptobj->get_attemptid()
        ));
        $output .= html_writer::empty_tag('input', array(
            'type' => 'hidden', 'name' => 'thispage',
            'value' => $page, 'id' => 'followingpage'
        ));
        $output .= html_writer::empty_tag('input', array(
            'type' => 'hidden', 'name' => 'nextpage',
            'value' => $nextpage
        ));
        $output .= html_writer::empty_tag('input', array(
            'type' => 'hidden', 'name' => 'timeup',
            'value' => '0', 'id' => 'timeup'
        ));
        $output .= html_writer::empty_tag('input', array(
            'type' => 'hidden', 'name' => 'sesskey',
            'value' => sesskey()
        ));
        $output .= html_writer::empty_tag('input', array(
            'type' => 'hidden', 'name' => 'scrollpos',
            'value' => '', 'id' => 'scrollpos'
        ));

        // Add a hidden field with questionids. Do this at the end of the form, so
        // if you navigate before the form has finished loading, it does not wipe all
        // the student's answers.
        $output .= html_writer::empty_tag('input', array(
            'type' => 'hidden', 'name' => 'slots',
            'value' => implode(',', $attemptobj->get_active_slots($page))
        ));

        // Finish the form.
        $output .= html_writer::end_tag('div');
        $output .= html_writer::end_tag('form');

        $output .= $this->connection_warning();

        return $output;
    }
}
