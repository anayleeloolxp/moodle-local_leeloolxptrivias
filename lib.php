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

/**
 * Add Leeloo Icon by js
 */
function local_leeloolxptrivias_before_footer() {
    global $PAGE, $DB;

    if (strpos($PAGE->pagetype, 'question-type-') !== false) {
        $questionid = optional_param('id', 0, PARAM_INT);

        if ($questionid != 0) {

            $questiondata = $DB->get_record('local_leeloolxptrivias_qd', array('questionid' => $questionid));

            if ($questiondata) {
                $difficultyval = $questiondata->difficulty;
                $videoval = $questiondata->vimeoid;
            } else {
                $difficultyval = 1;
                $videoval = 0;
            }

            $selectedone = '';
            $selectedtwo = '';
            $selectedthree = '';

            if ($difficultyval == 1) {
                $selectedone = 'selected';
            }
            if ($difficultyval == 2) {
                $selectedtwo = 'selected';
            }
            if ($difficultyval == 3) {
                $selectedthree = 'selected';
            }

            $difficultyfield = '<div id="fitem_id_difficulty" class="form-group row  fitem">' .
                '<div class="col-md-3 col-form-label d-flex pb-0 pr-md-0">' .
                '<label class="d-inline word-break " for="id_difficulty">Difficulty</label>' .
                '<div class="ml-1 ml-md-auto d-flex align-items-center align-self-start"></div>' .
                '</div>' .
                '<div class="col-md-9 form-inline align-items-start felement" data-fieldtype="select">' .
                '<select class="custom-select" name="difficulty" id="id_difficulty">' .
                '<option value="1" ' . $selectedone . '>1</option>' .
                '<option value="2" ' . $selectedtwo . '>2</option>' .
                '<option value="3" ' . $selectedthree . '>3</option>' .
                '</select>' .
                '<div class="form-control-feedback invalid-feedback" id="id_error_difficulty"></div>' .
                '</div>' .
                '</div>' .
                '<div id="fitem_id_vimeoid" class="form-group row  fitem">' .
                '<div class="col-md-3 col-form-label d-flex pb-0 pr-md-0">' .
                '<label class="d-inline word-break " for="id_vimeoid">Vimeo video id</label>' .
                '<div class="ml-1 ml-md-auto d-flex align-items-center align-self-start"></div>' .
                '</div>' .
                '<div class="col-md-9 form-inline align-items-start felement" data-fieldtype="text">' .
                '<input type="text" class="form-control " name="vimeoid" id="id_vimeoid" value="' . $videoval . '" size="64">' .
                '<div class="form-control-feedback invalid-feedback" id="id_error_vimeoid"></div>' .
                '</div>' .
                '</div>';

            $PAGE->requires->js_init_code('require(["jquery"], function ($) {
                $(document).ready(function () {

                    function setCookie(cname, cvalue, exdays) {
                        const d = new Date();
                        d.setTime(d.getTime() + (exdays*24*60*60*1000));
                        let expires = "expires="+ d.toUTCString();
                        document.cookie = cname + "=" + cvalue + ";" + expires + ";path=/";
                    }

                    setCookie("question_difficulty_' . $questionid . '", ' . $difficultyval . ', 1);

                    var diff_field = \'' . $difficultyfield . '\';
                    $(diff_field).insertAfter("#fitem_id_name");

                    $("#id_difficulty").change(function () {
                        var selectedValue = $(this).val();
                        setCookie("question_difficulty_' . $questionid . '", selectedValue, 1);
                    });


                    setCookie("question_vimeo_' . $questionid . '", ' . $videoval . ', 1);

                    $("#id_vimeoid").change(function () {
                        var selectedValue = $(this).val();
                        setCookie("question_vimeo_' . $questionid . '", selectedValue, 1);
                    });

                });
            });');
        }
    }
}

/**
 * Adds field to Quiz Mod.
 *
 * @param formwrapper $formwrapper The formwrapper.
 * @param mform $mform The mform.
 */
function local_leeloolxptrivias_coursemodule_standard_elements($formwrapper, $mform) {
    $modulename = $formwrapper->get_current()->modulename;

    if ($modulename == 'quiz') {

        $mform->addElement('header', 'leeloo_fields', get_string('leeloo_fields_lang', 'local_leeloolxptrivias'));

        $options = array(
            'default' => get_string('default_lang', 'local_leeloolxptrivias'),
            'discover' => get_string('discover_lang', 'local_leeloolxptrivias'),
            'remember' => get_string('remember_lang', 'local_leeloolxptrivias'),
            'understand' => get_string('understand_lang', 'local_leeloolxptrivias'),
            'duels' => get_string('duels_lang', 'local_leeloolxptrivias'),
            'regularduel' => get_string('regularduel_lang', 'local_leeloolxptrivias'),
            'situation' => get_string('situation_lang', 'local_leeloolxptrivias'),
            'case' => get_string('case_lang', 'local_leeloolxptrivias'),
            'quest' => get_string('quest_lang', 'local_leeloolxptrivias'),
            'problem' => get_string('problem_lang', 'local_leeloolxptrivias'),
            'trivias' => get_string('trivias_lang', 'local_leeloolxptrivias'),
            'exercises' => get_string('exercises_lang', 'local_leeloolxptrivias'),
            'assessments' => get_string('assessments_lang', 'local_leeloolxptrivias'),
            's_assessments' => get_string('s_assessments_lang', 'local_leeloolxptrivias'),
            'certification' => get_string('certification_lang', 'local_leeloolxptrivias'),
            'exam' => get_string('exam_lang', 'local_leeloolxptrivias'),
            'diagnostic' => get_string('diagnostic_lang', 'local_leeloolxptrivias'),
            'placement' => get_string('placement_lang', 'local_leeloolxptrivias'),
            'proficiency' => get_string('proficiency_lang', 'local_leeloolxptrivias'),
            'achievements' => get_string('achievements_lang', 'local_leeloolxptrivias'),
            'internal' => get_string('internal_lang', 'local_leeloolxptrivias'),
            'external' => get_string('external_lang', 'local_leeloolxptrivias'),
            'objective' => get_string('objective_lang', 'local_leeloolxptrivias'),
            'subjective' => get_string('subjective_lang', 'local_leeloolxptrivias'),
            'custom1' => get_string('custom1_lang', 'local_leeloolxptrivias'),
            'custom2' => get_string('custom2_lang', 'local_leeloolxptrivias'),
            'custom3' => get_string('custom3_lang', 'local_leeloolxptrivias'),
            'custom4' => get_string('custom4_lang', 'local_leeloolxptrivias')
        );
        $mform->addElement('select', 'quiztype', get_string('quiz_type_lang', 'local_leeloolxptrivias'), $options);
    }
}
