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
 * Local Library file for additional Functions
 *
 * @package    local_leeloolxptrivias
 * @copyright  2020 Leeloo LXP (https://leeloolxp.com)
 * @author     Leeloo LXP <info@leeloolxp.com>
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die();

/**
 * Add Leeloo Icon by js
 */
function local_leeloolxptrivias_before_footer() {
    global $PAGE, $DB;

    if (strpos($PAGE->pagetype, 'question-type-') !== false) {
        $questionid = optional_param('id', 0, PARAM_INT);

        if( $questionid != 0 ){

            $questiondata = $DB->get_record('tb_question_diff', array('questionid' => $questionid));

            if( $questiondata ){
                $difficultyval = $questiondata->difficulty;
            }else{
                $difficultyval = 1;
            }

            $$selectedone = '';
            $selectedtwo = '';
            $selectedthree = '';

            if( $difficultyval == 1 ){
                $selectedone = 'selected';
            }
            if( $difficultyval == 2 ){
                $selectedtwo = 'selected';
            }
            if( $difficultyval == 3 ){
                $selectedthree = 'selected';
            }

            $difficulty_field = '<div id="fitem_id_difficulty" class="form-group row  fitem   "><div class="col-md-3 col-form-label d-flex pb-0 pr-md-0"><label class="d-inline word-break " for="id_difficulty">Difficulty</label><div class="ml-1 ml-md-auto d-flex align-items-center align-self-start"></div></div><div class="col-md-9 form-inline align-items-start felement" data-fieldtype="select"><select class="custom-select" name="difficulty" id="id_difficulty"><option value="1" '.$selectedone.'>1</option><option value="2" '.$selectedtwo.'>2</option><option value="3" '.$selectedthree.'>3</option></select><div class="form-control-feedback invalid-feedback" id="id_error_difficulty"></div></div></div>';

            $PAGE->requires->js_init_code('require(["jquery"], function ($) {
                $(document).ready(function () {

                    function setCookie(cname, cvalue, exdays) {
                        const d = new Date();
                        d.setTime(d.getTime() + (exdays*24*60*60*1000));
                        let expires = "expires="+ d.toUTCString();
                        document.cookie = cname + "=" + cvalue + ";" + expires + ";path=/";
                    }

                    setCookie("question_difficulty_'.$questionid.'", '.$difficultyval.', 1);

                    var diff_field = \''.$difficulty_field.'\';
                    $(diff_field).insertAfter("#fitem_id_name");

                    $("#id_difficulty").change(function () {
                        var selectedValue = $(this).val();
                        setCookie("question_difficulty_'.$questionid.'", selectedValue, 1);
                    });

                });
            });');

        }
    }
}

/**
 * Returns the main SCSS content.
 *
 * @param formwrapper $formwrapper The formwrapper.
 * @param mform $mform The mform.
 */
function local_leeloolxptrivias_coursemodule_standard_elements ($formwrapper, $mform){
    $modulename = $formwrapper->get_current()->modulename;
    
    if ($modulename == 'quiz') {

        // get_string ( 'leeloo_text' , 'local_leeloolxptrivias' );

        $mform->addElement('header', 'leeloo_fields', get_string ('leeloo_fields_lang', 'local_leeloolxptrivias'));
        
        $options = array(
            'discover' => get_string ('discover_lang', 'local_leeloolxptrivias'),
            'exercises' => get_string ('exercises_lang', 'local_leeloolxptrivias'),
            'trivias' => get_string ('trivias_lang', 'local_leeloolxptrivias'),
            'assessments' => get_string ('assessments_lang', 'local_leeloolxptrivias')
        );
        $mform->addElement('select', 'quiztype', get_string ('quiz_type_lang', 'local_leeloolxptrivias'), $options);
    }    
}
