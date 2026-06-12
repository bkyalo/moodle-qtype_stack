<?php
// This file is part of Stack - http://stack.maths.ed.ac.uk/
//
// Stack is free software: you can redistribute it and/or modify
// it under the terms of the GNU General Public License as published by
// the Free Software Foundation, either version 3 of the License, or
// (at your option) any later version.
//
// Stack is distributed in the hope that it will be useful,
// but WITHOUT ANY WARRANTY; without even the implied warranty of
// MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
// GNU General Public License for more details.
//
// You should have received a copy of the GNU General Public License
// along with Stack.  If not, see <http://www.gnu.org/licenses/>.

/**
 * A flickmath keyboard input.
 *
 * @package    qtype_stack
 * @copyright  2015 Yasuyuki NAKAMURA and Takahiro NAKAHARA
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class stack_flickmath_input extends stack_input {
    // phpcs:ignore moodle.Commenting.VariableComment.Missing
    protected $extraoptions = [
        'hideanswer' => false,
        'allowempty' => false,
        'simp' => false,
        'rationalized' => false,
        'nounits' => false,
        'align' => 'left',
        'consolidatesubscripts' => false,
        'checkvars' => 0,
        'validator' => false,
        'feedback' => false,
        'monospace' => false,
        'basen' => false,
    ];

    // phpcs:ignore moodle.Commenting.MissingDocblock.Function
    public function render(stack_input_state $state, $fieldname, $readonly, $tavalue) {
        global $CFG;

        if ($this->errors) {
            return $this->render_error($this->errors);
        }

        $attributes = [
            'type' => 'text',
            'name' => $fieldname,
            'id'   => $fieldname,
            'size' => $this->parameters['boxWidth'],
        ];

        $cookieformula = '';
        if ($this->is_blank_response($state->contents)) {
            $attributes['value'] = $this->parameters['syntaxHint'];
        } else if (!empty($_COOKIE['flickmath-raw-' . $attributes['id']])) {
            $cookieformula = $_COOKIE['flickmath-raw-' . $attributes['id']];
            $attributes['value'] = $this->contents_to_maxima($state->contents);
            setcookie('flickmath-raw-' . $attributes['id'], 'del', time() - 18000);
        }

        if ($readonly) {
            $attributes['readonly'] = 'readonly';
        }

        $textarea = "<textarea id='" . $attributes['id'] . "' name='" . $attributes['name'] .
                "' class='mathdoxformula'>" . $cookieformula . "</textarea>
                <input type='hidden' name='" . $attributes['id'] . "-openmath' id='" .
                $attributes['id'] . "-openmath' value='' />";

        if (empty($CFG->mathdox)) {
            $jsscripts = "<script type='text/javascript' src='" . $CFG->wwwroot .
                    "/question/type/stack/stack/input/flickmath/scripts/jquery.min.js'></script>
            <script type='text/javascript' src='" . $CFG->wwwroot .
                    "/question/type/stack/stack/input/flickmath/org/mathdox/formulaeditor/main.js'></script>
            <script type='text/javascript' src='" . $CFG->wwwroot .
                    "/question/type/stack/stack/input/flickmath/maxima.js'></script>";

            $mathdoxoption = "
                    <script type='text/javascript'>
                        org = { mathdox:{formulaeditor:{options:{dragPalette:true, paletteShow: \"none\", useBar:true,onloadFocus:true}}}};
                        var form = document.getElementById('responseform');
                        form.onsubmit =function(){
                            var mathdoxs = document.getElementsByTagName('textarea');
                            for(var i = 0; i < mathdoxs.length; i++){
                                if(mathdoxs[i].className == 'mathdoxformula'){
                                    if(mathdoxs[i].value){
                                        document.cookie = 'flickmath-raw-' + mathdoxs[i].id + '=' + mathdoxs[i].value;
                                        mathdoxs[i].value = tomaxima(mathdoxs[i].id);
                                    }
                                }
                            }
                        }
                    </script>";

            $ua = $_SERVER['HTTP_USER_AGENT'] ?? '';
            if ((strpos($ua, 'iPhone') !== false) || (strpos($ua, 'iPod') !== false) ||
                    (strpos($ua, 'Android') !== false) || (strpos($ua, 'iPad') !== false)) {
                $mathdoxoption = "
                    <script type='text/javascript'>
                        org = { mathdox:{formulaeditor:{options:{dragPalette:false, paletteShow: \"none\", useBar:false,onloadFocus:false}}}};
                        var form = document.getElementById('responseform');
                        form.onsubmit =function(){
                            var mathdoxs = document.getElementsByTagName('textarea');
                            for(var i = 0; i < mathdoxs.length; i++){
                                if(mathdoxs[i].className == 'mathdoxformula'){
                                    if(mathdoxs[i].value){
                                        document.cookie = 'flickmath-raw-' + mathdoxs[i].id + '=' + mathdoxs[i].value;
                                        mathdoxs[i].value = tomaxima(mathdoxs[i].id);
                                    }
                                }
                            }
                        }
                    </script>";
                $flickscripts = '
                <script>
                    $(function(){
                        var w = $(window).width();
                    });
                </script>
                <link rel="stylesheet" href="' . $CFG->wwwroot .
                        '/question/type/stack/stack/input/flickmath/styles.css" type="text/css" />
                <link rel="stylesheet" href="' . $CFG->wwwroot .
                        '/question/type/stack/stack/input/flickmath/css/font.css" type="text/css" />';
                ob_start();
                include_once(dirname(__FILE__) . '/keyboard/keyboard.html');
                $keyboardhtml = ob_get_contents();
                ob_end_clean();
                $xhtml = $jsscripts . $flickscripts . $mathdoxoption . $textarea . $keyboardhtml;
            } else {
                $xhtml = $jsscripts . $mathdoxoption . $textarea;
            }
            $CFG->mathdox = true;
        } else {
            $xhtml = $textarea;
        }

        return $xhtml;
    }

    // phpcs:ignore moodle.Commenting.MissingDocblock.Function
    public function render_api_data($tavalue) {
        if ($this->errors) {
            throw new stack_exception('Error rendering input: ' . implode(',', $this->errors));
        }

        return [
            'type' => 'flickmath',
            'boxWidth' => $this->parameters['boxWidth'],
            'align' => $this->extraoptions['align'] === 'right' ? 'right' : 'left',
            'syntaxHint' => $this->parameters['syntaxHint'],
            'syntaxHintType' => $this->parameters['syntaxAttribute'] == '1' ? 'placeholder' : 'value',
        ];
    }

    // phpcs:ignore moodle.Commenting.MissingDocblock.Function
    public function add_to_moodleform_testinput(MoodleQuickForm $mform) {
        $mform->addElement('text', $this->name, $this->name, ['size' => $this->parameters['boxWidth']]);
        $mform->setDefault($this->name, $this->parameters['syntaxHint']);
        $mform->setType($this->name, PARAM_RAW);
    }

    /**
     * Return the default values for the parameters.
     * @return array parameters` => default value.
     */
    public static function get_parameters_defaults() {
        return [
            'mustVerify'         => true,
            'showValidation'     => 1,
            'boxWidth'           => 15,
            'insertStars'        => 0,
            'syntaxHint'         => '',
            'syntaxAttribute'    => 0,
            'forbidWords'        => '',
            'allowWords'         => '',
            'forbidFloats'       => true,
            'lowestTerms'        => true,
            'sameType'           => true,
            'options'            => '',
        ];
    }

    /**
     * Each actual extension of this base class must decide what parameter values are valid
     * @return array of parameters names.
     */
    public function internal_validate_parameter($parameter, $value) {
        $valid = true;
        switch ($parameter) {
            case 'boxWidth':
                $valid = is_int($value) && $value > 0;
                break;
        }
        return $valid;
    }

    /**
     * @return string the teacher's answer, displayed to the student in the general feedback.
     */
    public function get_teacher_answer_display($value, $display) {
        if ($this->extraoptions['hideanswer']) {
            return '';
        }
        if (trim($value) == 'EMPTYANSWER') {
            return stack_string('teacheranswerempty');
        }
        $cs = stack_ast_container::make_from_teacher_source($value, '', new stack_cas_security());
        $cs->set_nounify(0);
        $value = $cs->get_inputform(true, 0, true, $this->options->get_option('decimals'),
                $this->get_extra_option('basen', false));
        return stack_string('teacheranswershow', ['value' => '<code>' . $value . '</code>', 'display' => $display]);
    }
}
