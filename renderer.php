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
 * AMOS renderer class is defined here
 *
 * @package   local-onlinejudge2
 * @copyright 2011 Zhan Yu <yuzhanlaile@gmail.com>
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die();

/**
 * AMOS renderer class
 */
class local_onlinejudge2_renderer extends plugin_renderer_base {

    /**
     * Renders the filter form
     *
     * @todo this code was used as sort of prototype of the HTML produced by the future forms framework, to be replaced by proper forms library
     * @param local_onlinejudge2_filter $filter
     * @return string
     */
    protected function render_local_onlinejudge2_filter(local_onlinejudge2_filter $filter) {
        $output = '';

        // version checkboxes
        $output .= html_writer::start_tag('div', array('class' => 'item elementsgroup'));
        $output .= html_writer::start_tag('div', array('class' => 'label first'));
        $output .= html_writer::tag('label', get_string('filterver', 'local_onlinejudge2'), array('for' => 'amosfilter_fver'));
        $output .= html_writer::tag('div', get_string('filterver_desc', 'local_onlinejudge2'), array('class' => 'description'));
        $output .= html_writer::end_tag('div');
        $output .= html_writer::start_tag('div', array('class' => 'element'));
        $fver = '';
        foreach (mlang_version::list_all() as $version) {
            $checkbox = html_writer::checkbox('fver[]', $version->code, in_array($version->code, $filter->get_data()->version),
                    $version->label);
            $fver .= html_writer::tag('div', $checkbox, array('class' => 'labelled_checkbox'));
        }
        $output .= html_writer::tag('div', $fver, array('id' => 'amosfilter_fver', 'class' => 'checkboxgroup'));
        $output .= html_writer::end_tag('div');
        $output .= html_writer::end_tag('div');

        // language selector
        $output .= html_writer::start_tag('div', array('class' => 'item select'));
        $output .= html_writer::start_tag('div', array('class' => 'label'));
        $output .= html_writer::tag('label', get_string('filterlng', 'local_onlinejudge2'), array('for' => 'amosfilter_flng'));
        $output .= html_writer::tag('div', get_string('filterlng_desc', 'local_onlinejudge2'), array('class' => 'description'));
        $output .= html_writer::end_tag('div');
        $output .= html_writer::start_tag('div', array('class' => 'element'));
        $options = mlang_tools::list_languages();
        foreach ($options as $langcode => $langname) {
            $options[$langcode] = $langname;
        }
        unset($options['en']); // English is not translatable via AMOS
        $output .= html_writer::select($options, 'flng[]', $filter->get_data()->language, '',
                    array('id' => 'amosfilter_flng', 'multiple' => 'multiple', 'size' => 3));
        $output .= html_writer::tag('span', '', array('id' => 'amosfilter_flng_actions', 'class' => 'actions'));
        $output .= html_writer::end_tag('div');
        $output .= html_writer::end_tag('div');

        // component selector
        $output .= html_writer::start_tag('div', array('class' => 'item select'));
        $output .= html_writer::start_tag('div', array('class' => 'label'));
        $output .= html_writer::tag('label', get_string('filtercmp', 'local_onlinejudge2'), array('for' => 'amosfilter_fcmp'));
        $output .= html_writer::tag('div', get_string('filtercmp_desc', 'local_onlinejudge2'), array('class' => 'description'));
        $output .= html_writer::end_tag('div');
        $output .= html_writer::start_tag('div', array('class' => 'element'));
        $optionscore = array();
        $optionsstandard = array();
        $optionscontrib = array();
        $installed = local_onlinejudge2_installed_components();
        foreach (mlang_tools::list_components() as $componentname => $undefined) {
            list($ctype, $cname) = normalize_component($componentname);
            if ($ctype == 'core') {
                $optionscore[$componentname] = $installed[$componentname];
            } elseif (isset($installed[$componentname])) {
                $optionsstandard[$componentname] = $installed[$componentname];
            } else {
                $optionscontrib[$componentname] = $componentname;
            }
        }
        asort($optionscore);
        asort($optionsstandard);
        ksort($optionscontrib);
        $options = array(
            array(get_string('typecore', 'local_onlinejudge2') => $optionscore),
            array(get_string('typestandard', 'local_onlinejudge2') => $optionsstandard),
            array(get_string('typecontrib', 'local_onlinejudge2') => $optionscontrib));
        $output .= html_writer::select($options, 'fcmp[]', $filter->get_data()->component, '',
                    array('id' => 'amosfilter_fcmp', 'multiple' => 'multiple', 'size' => 5));
        $output .= html_writer::tag('span', '', array('id' => 'amosfilter_fcmp_actions', 'class' => 'actions'));
        $output .= html_writer::end_tag('div');
        $output .= html_writer::end_tag('div');

        // other filter settings
        $output .= html_writer::start_tag('div', array('class' => 'item elementsgroup'));
        $output .= html_writer::start_tag('div', array('class' => 'label'));
        $output .= html_writer::tag('label', get_string('filtermis', 'local_onlinejudge2'), array('for' => 'amosfilter_fmis'));
        $output .= html_writer::tag('div', get_string('filtermis_desc', 'local_onlinejudge2'), array('class' => 'description'));
        $output .= html_writer::end_tag('div');
        $output .= html_writer::start_tag('div', array('class' => 'element'));

        $fmis    = html_writer::checkbox('fmis', 1, $filter->get_data()->missing, get_string('filtermisfmis', 'local_onlinejudge2'));
        $fmis    = html_writer::tag('div', $fmis, array('class' => 'labelled_checkbox'));

        $fhlp    = html_writer::checkbox('fhlp', 1, $filter->get_data()->helps, get_string('filtermisfhlp', 'local_onlinejudge2'));
        $fhlp    = html_writer::tag('div', $fhlp, array('class' => 'labelled_checkbox'));

        $fstg    = html_writer::checkbox('fstg', 1, $filter->get_data()->stagedonly, get_string('filtermisfstg', 'local_onlinejudge2'));
        $fstg    = html_writer::tag('div', $fstg, array('class' => 'labelled_checkbox'));

        $fgrey   = html_writer::start_tag('div', array('id' => 'amosfilter_fgrey', 'class' => 'checkboxgroup'));
        $fgrey  .= html_writer::tag('div',
                        html_writer::checkbox('fglo', 1, $filter->get_data()->greylistedonly, get_string('filtermisfglo', 'local_onlinejudge2'),
                                                array('id' => 'amosfilter_fglo')),
                        array('class' => 'labelled_checkbox'));
        $fgrey  .= html_writer::tag('div',
                        html_writer::checkbox('fwog', 1, $filter->get_data()->withoutgreylisted, get_string('filtermisfwog', 'local_onlinejudge2'),
                                                array('id' => 'amosfilter_fwog')),
                        array('class' => 'labelled_checkbox'));
        $fgrey  .= html_writer::end_tag('div');

        $output .= html_writer::tag('div', $fmis.$fhlp.$fstg.$fgrey, array('id' => 'amosfilter_fmis', 'class' => 'checkboxgroup'));

        $output .= html_writer::end_tag('div');
        $output .= html_writer::end_tag('div');

        // must contain string
        $output .= html_writer::start_tag('div', array('class' => 'item text'));
        $output .= html_writer::start_tag('div', array('class' => 'label'));
        $output .= html_writer::tag('label', get_string('filtertxt', 'local_onlinejudge2'), array('for' => 'amosfilter_ftxt'));
        $output .= html_writer::tag('div', get_string('filtertxt_desc', 'local_onlinejudge2'), array('class' => 'description'));
        $output .= html_writer::end_tag('div');
        $output .= html_writer::start_tag('div', array('class' => 'element'));

        $output .= html_writer::empty_tag('input', array('name' => 'ftxt', 'type' => 'text', 'value' => $filter->get_data()->substring));
        $output .= html_writer::checkbox('ftxr', 1, $filter->get_data()->substringregex, get_string('filtertxtregex', 'local_onlinejudge2'),
                    array('class' => 'inputmodifier'));
        $output .= html_writer::checkbox('ftxs', 1, $filter->get_data()->substringcs, get_string('filtertxtcasesensitive', 'local_onlinejudge2'),
                    array('class' => 'inputmodifier'));

        $output .= html_writer::end_tag('div');
        $output .= html_writer::end_tag('div');

        // string identifier
        $output .= html_writer::start_tag('div', array('class' => 'item text'));
        $output .= html_writer::start_tag('div', array('class' => 'label'));
        $output .= html_writer::tag('label', get_string('filtersid', 'local_onlinejudge2'), array('for' => 'amosfilter_fsid'));
        $output .= html_writer::tag('div', get_string('filtersid_desc', 'local_onlinejudge2'), array('class' => 'description'));
        $output .= html_writer::end_tag('div');
        $output .= html_writer::start_tag('div', array('class' => 'element'));

        $output .= html_writer::empty_tag('input', array('name' => 'fsid', 'type' => 'text', 'value' => $filter->get_data()->stringid));

        $output .= html_writer::end_tag('div');
        $output .= html_writer::end_tag('div');

        // hidden fields
        $output .= html_writer::start_tag('div');
        $output .= html_writer::empty_tag('input', array('type' => 'hidden', 'name' => '__lazyform_' . $filter->lazyformname, 'value' => 1));
        $output .= html_writer::empty_tag('input', array('type' => 'hidden', 'name' => 'sesskey', 'value' => sesskey()));
        $output .= html_writer::end_tag('div');

        // submit
        $output .= html_writer::start_tag('div', array('class' => 'item submit'));
        $output .= html_writer::start_tag('div', array('class' => 'label'));
        $output .= html_writer::tag('label', '&nbsp;', array('for' => 'amosfilter_fsbm'));
        $output .= html_writer::end_tag('div');
        $output .= html_writer::start_tag('div', array('class' => 'element'));
        $output .= html_writer::empty_tag('input', array('type' => 'submit', 'value' => 'Save filter settings', 'class' => 'submit'));
        $output .= html_writer::tag('span', '', array('id' => 'amosfilter_submitted_icon'));
        $output .= html_writer::end_tag('div');
        $output .= html_writer::end_tag('div');

        // permalink
        $permalink = $filter->get_permalink();
        if (!is_null($permalink)) {
            $output .= html_writer::start_tag('div', array('class' => 'item static'));
            $output .= html_writer::tag('div', '', array('class' => 'label'));
            $output .= html_writer::start_tag('div', array('class' => 'element'));
            $output .= html_writer::link($permalink, get_string('permalink', 'local_onlinejudge2'));
            $output .= html_writer::end_tag('div');
            $output .= html_writer::end_tag('div');
        }

        // block wrapper for xhtml strictness
        $output = html_writer::tag('div', $output, array('id' => 'amosfilter'));

        // form
        $attributes = array('method' => 'post',
                            'action' => $filter->handler->out(),
                            'id'     => 'amosfilter_form',
                            'class'  => 'lazyform ' . $filter->lazyformname,
                        );
        $output = html_writer::tag('form', $output, $attributes);
        $output = html_writer::tag('div', $output, array('class' => 'filterwrapper'));

        return $output;
    }

    /**
     * Returns formatted commit date and time
     *
     * In our git repos, timestamps are stored in UTC always and that is what standard git log
     * displays.
     *
     * @param int $timestamp
     * @return string formatted date and time
     */
    public static function commit_datetime($timestamp) {
        $tz = date_default_timezone_get();
        date_default_timezone_set('UTC');
        $t = date('Y-m-d H:i e', $timestamp);
        date_default_timezone_set($tz);
        return $t;
    }


    /**
     * Render index page of http://download.moodle.org/langpack/x.x/
     *
     * Output of this renderer is expected to be saved into the file index.php and uploaded to the server
     *
     * @param local_onlinejudge2_index_page $data
     * @return string HTML
     */
    protected function render_local_onlinejudge2_index_page() {

        $output = '<?php
                   require(dirname(dirname(dirname(__FILE__)))."/config.php");
                   require(dirname(dirname(dirname(__FILE__)))."/menu.php");

                   print_header("Moodle: Download: Language Packs", "Moodle Downloads",
                   "<a href=\"$CFG->wwwroot/\">Download</a> -> Language Packs",
                   "", "", true, " ", $navmenu);
                   $current = "lang";
                   require(dirname(dirname(dirname(__FILE__)))."/tabs.php");

                   print_simple_box_start("center", "100%", "#FFFFFF", 20);
                   ?>';
        print_simple_box_end();
        print_footer();

        return $output;
    }

    /**
     * Makes sure there is a zero-width space after non-word characters in the given string
     *
     * This is used to wrap long strings like 'A,B,C,D,...,x,y,z' in the translator
     *
     * @link http://www.w3.org/TR/html4/struct/text.html#h-9.1
     * @link http://www.fileformat.info/info/unicode/char/200b/index.htm
     *
     * @param string $text plain text
     * @return string
     */
    public static function add_breaks($text) {
        return preg_replace('/([,])(\S)/', '$1'."\xe2\x80\x8b".'$2', $text);
    }
}
