<?
///////////////////////////////////////////////////////////////////////////
//                                                                       //
// NOTICE OF COPYRIGHT                                                   //
//                                                                       //
//                      Online Judge for Moodle                          //
//       https://github.com/hit-moodle/moodle-local_onlinejudge2         //
//                                                                       //
// Copyright (C) 2009 onwards  Sun Zhigang  http://sunner.cn             //
//                                                                       //
// This program is free software; you can redistribute it and/or modify  //
// it under the terms of the GNU General Public License as published by  //
// the Free Software Foundation; either version 3 of the License, or     //
// (at your option) any later version.                                   //
//                                                                       //
// This program is distributed in the hope that it will be useful,       //
// but WITHOUT ANY WARRANTY; without even the implied warranty of        //
// MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the         //
// GNU General Public License for more details:                          //
//                                                                       //
//          http://www.gnu.org/copyleft/gpl.html                         //
//                                                                       //
///////////////////////////////////////////////////////////////////////////

/**
 * Administration forms of the online judge
 * 
 * @package   local_onlinejudge2
 * @copyright 2011 Sun Zhigang (http://sunner.cn)
 * @author    Sun Zhigang
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
require_once($CFG->libdir . '/formslib.php');

/**
 * This form displays global settings
 */
class onlinejudge2_settings_form extends moodleform {

    public function definition() {

        $mform = & $this->_form;

        $mform->addElement('header', 'moodle', get_string('settingsform', 'local_onlinejudge2'));

        $mform->addElement('text', 'maxmemlimit', get_string('maxmemlimit', 'local_onlinejudge2'));
        $mform->addHelpButton('maxmemlimit', 'maxmemlimit', 'local_onlinejudge2');
        $mform->addRule('maxmemlimit', null, 'required', null, 'client');
        $mform->addRule('maxmemlimit', null, 'numeric', null, 'client');
        $mform->addRule('maxmemlimit', null, 'nonzero', null, 'client');
        $mform->setType('maxmemlimit', PARAM_NUMBER);
        $mform->setDefault('maxmemlimit', 64);

        $mform->addElement('text', 'maxcpulimit', get_string('maxcpulimit', 'local_onlinejudge2'));
        $mform->addHelpButton('maxcpulimit', 'maxcpulimit', 'local_onlinejudge2');
        $mform->addRule('maxcpulimit', null, 'required', null, 'client');
        $mform->addRule('maxcpulimit', null, 'numeric', null, 'client');
        $mform->addRule('maxcpulimit', null, 'nonzero', null, 'client');
        $mform->setType('maxcpulimit', PARAM_NUMBER);
        $mform->setDefault('maxcpulimit', 10);

        $mform->addElement('text', 'ideonedelay', get_string('ideonedelay', 'local_onlinejudge2'));
        $mform->addHelpButton('ideonedelay', 'ideonedelay', 'local_onlinejudge2');
        $mform->addRule('ideonedelay', null, 'required', null, 'client');
        $mform->addRule('ideonedelay', null, 'numeric', null, 'client');
        $mform->setType('ideonedelay', PARAM_NUMBER);
        $mform->setDefault('ideonedelay', 5);

        $this->add_action_buttons(false, get_string('update'));
    }

    function validation($data, $files) {
        $errors = parent::validation($data, $files);

        if ($data['maxmemlimit'] <= 0)
            $errors['maxmemlimit'] = get_string('badvalue', 'local_onlinejudge2');
        if ($data['maxcpulimit'] <= 0)
            $errors['maxcpulimit'] = get_string('badvalue', 'local_onlinejudge2');
        if ($data['ideonedelay'] < 0)
            $errors['ideonedelay'] = get_string('badvalue', 'local_onlinejudge2');

        return $errors;
    }

}

