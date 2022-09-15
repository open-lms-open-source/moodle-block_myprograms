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
 * Contains functions that support mobile functionality of myprograms block.
 *
 * @copyright  Copyright (c] 2022 Open LMS (https://www.openlms.net/]
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
namespace block_myprograms\output;

use enrol_programs\local\program;
use enrol_programs\local\content\item;
use enrol_programs\local\content\top;
use enrol_programs\local\content\set;
use enrol_programs\local\content\course;

require_once($CFG->libdir . '/filelib.php');

defined('MOODLE_INTERNAL') || die();

class mobile {

    /**
     * Returns the myprograms overview view for the mobile app.
     * @param  array $args Arguments from tool_mobile_get_content WS
     *
     * @return array HTML, javascript and otherdata
     */
    public static function mobile_program_overview_view($args) {
        global $DB, $CFG, $OUTPUT, $USER;

        $sql = "SELECT p.*, pa.id as allocationid, pa.timecompleted as timecompleted, pa.timestart as timestart,
            pa.timeend as timeend, pa.timedue as timedue, pa.timeallocated as timeallocated
            FROM {enrol_programs_programs} p
            JOIN {enrol_programs_allocations} pa ON pa.programid = p.id
            WHERE p.archived = 0 AND pa.archived = 0
                AND pa.userid = :userid
            ORDER BY fullname ASC";
        $params = ['userid' => $USER->id];
        $programs = $DB->get_records_sql($sql, $params);
        $strnotset = get_string('notset', 'enrol_programs');

        foreach ($programs as $program) {
            $context = \context::instance_by_id($program->contextid, MUST_EXIST);
            $program->categoryname = $context->get_context_name();
            $program->status = self::get_program_status_for_display($program, $program);
            $program->image = self::get_program_image($program);
            $program->timeallocated = userdate($program->timeallocated, get_string('strftimedatetimeshort'));
            $program->timeend = ($program->timeend) ?
                userdate($program->timeend, get_string('strftimedatetimeshort')) : $strnotset;
            $program->duedate = ($program->timedue) ?
                userdate($program->timedue, get_string('strftimedatetimeshort')) : $strnotset;

            $top = program::load_content($program->id);
            $completedcount = 0;
            $totalcompletions = 0;
            $allocationid = $program->allocationid;

            $getcompletion = function(item $item, $itemdepth) use (&$getcompletion,
                $allocationid, &$DB, &$completedcount, &$totalcompletions): void {
                if (!($item instanceof top)) {
                    $totalcompletions++;
                    $completion = $DB->get_record('enrol_programs_completions',
                        ['itemid' => $item->get_id(), 'allocationid' => $allocationid]);
                    if ($completion) {
                        if (!empty($completion->timecompleted)) {
                            $completedcount++;
                        }
                    }
                }

                foreach ($item->get_children() as $child) {
                    $getcompletion($child, $itemdepth + 1);
                }
            };

            $getcompletion($top, 0);

            $percent = ($totalcompletions == 0) ? 0 : ($completedcount / $totalcompletions) * 100;
            $program->progress = $percent;

            $program->tags = [];
            if ($CFG->usetags) {
                $tagsitems = \core_tag_tag::get_item_tags('enrol_programs', 'program', $program->id);
                $tags = [];
                foreach ($tagsitems as $tagid => $tag) {
                    $tags[] = ['id' => $tagid, 'displayname' => $tag->get_display_name()];
                }
                $program->tags = $tags;
            }
        }

        $data['hasprograms'] = (count($programs) > 0);
        $data['programid'] = 1;
        return [
            'templates' => [
                [
                    'id' => 'main',
                    'html' => $OUTPUT->render_from_template("block_myprograms/mobile_programs_overview_page", $data),
                ],
            ],
            'javascript' => file_get_contents($CFG->dirroot . "/blocks/myprograms/mobile/js/programsview.js"),
            'otherdata' => [
                'programs' => json_encode(array_values($programs)),
                'filteredprograms' => json_encode(array_values($programs)),
            ],
        ];
    }

    /**
     * Returns the program view for the mobile app.
     * @param  array $args Arguments from tool_mobile_get_content WS
     *
     * @return array HTML, javascript and otherdata
     */
    public static function mobile_program_view($args) {
        global $OUTPUT, $DB, $USER, $CFG;

        $programid = $args['pid'];

        $program = $DB->get_record('enrol_programs_programs', ['id' => $programid]);
        if (!$program || $program->archived) {
            if ($program) {
                $context = \context::instance_by_id($program->contextid);
            } else {
                $context = \context_system::instance();
            }
            if (!has_capability('enrol/programs:view', $context)) {
                throw new \moodle_exception('error:cannotaccessprogram', 'block_myprograms');
            }
        }

        $context = \context::instance_by_id($program->contextid);
        $description = \file_rewrite_pluginfile_urls($program->description, 'pluginfile.php',
            $context->id, 'enrol_programs', 'description', $program->id);

        $program->fullname = format_string($program->fullname);
        $program->descriptiondisplay = format_text($description, $program->descriptionformat, ['context' => $context]);
        $program->image = self::get_program_image($program);

        $allocation = $DB->get_record('enrol_programs_allocations', ['programid' => $program->id, 'userid' => $USER->id]);

        $program->status = self::get_program_status_for_display($program, $allocation);

        $program->tags = [];
        if ($CFG->usetags) {
            $tagsitems = \core_tag_tag::get_item_tags('enrol_programs', 'program', $program->id);
            $tags = [];
            foreach ($tagsitems as $tagid => $tag) {
                $tags[] = ['id' => $tagid, 'displayname' => $tag->get_display_name()];
            }
            $program->tags = $tags;
        }

        $strnotset = '';
        $allocation->timeallocateddate = userdate($allocation->timeallocated);
        $allocation->timestartdate = userdate($allocation->timestart);
        $allocation->timeduedatedate = (isset($allocation->timedue)) ? userdate($allocation->timedue) : $strnotset;
        $allocation->timeenddatedate = (isset($allocation->timeend)) ? userdate($allocation->timeend) : $strnotset;
        $allocation->timecompleteddate = (isset($allocation->timecompleted)) ? userdate($allocation->timecompleted) : $strnotset;

        $top = program::load_content($program->id);

        $programcontent = [];
        $getcontent = function(item $item, $itemdepth) use (&$getcontent, &$programcontent, $allocation, &$DB): void {
            $fullname = $item->get_fullname();
            $id = $item->get_id();
            $padding = str_repeat('&nbsp;', $itemdepth * 6);

            $completiontype = '';
            $content = [];
            if ($item instanceof set) {
                $completiontype = $item->get_sequencetype_info();
                $content['type'] = 'set';
            }

            if ($item instanceof course) {
                $courseid = $item->get_courseid();
                $coursecontext = \context_course::instance($courseid, IGNORE_MISSING);
                $content['type'] = 'course';
                $content['course'] = self::get_course_for_display($courseid);
            }

            $content['fullname'] = $fullname;
            $content['completiontype'] = $completiontype;
            $content['padding'] = $padding;

            $completioninfo = '';
            $completion = $DB->get_record('enrol_programs_completions', ['itemid' => $id, 'allocationid' => $allocation->id]);
            if ($completion) {
                $completioninfo = userdate($completion->timecompleted, get_string('strftimedatetimeshort'));
            }
            $content[] = $completioninfo;

            $programcontent[] = $content;

            foreach ($item->get_children() as $child) {
                $getcontent($child, $itemdepth + 1);
            }
        };
        $getcontent($top, 0);

        $data = [];
        $data['program'] = json_encode($program);

        return [
            'templates' => [
                [
                    'id' => 'main',
                    'html' => $OUTPUT->render_from_template("block_myprograms/mobile_program_page", $data),
                ],
            ],
            'javascript' => file_get_contents($CFG->dirroot . "/blocks/myprograms/mobile/js/programsview.js"),
            'otherdata' => [
                'program' => json_encode($program),
                'allocation' => json_encode($allocation),
                'programcontent' => json_encode($programcontent)
            ],
        ];
    }

    protected static function get_program_image($program) {
        global $CFG;

        $programimage = '';
        $presentation = (array)json_decode($program->presentationjson);
        if (!empty($presentation['image'])) {
            $imageurl = \moodle_url::make_file_url("$CFG->wwwroot/pluginfile.php",
                '/' . $context->id . '/enrol_programs/image/' . $program->id . '/'. $presentation['image'], false);
            $programimage = $imageurl->out();
        }
        return $programimage;
    }

    protected static function get_program_status_for_display($program, $allocation) {
        $result = [];
        $now = time();

        if ($program->archived || $allocation->archived) {
            if ($allocation->timecompleted) {
                $result = ['color' => 'success', 'text' => get_string('programstatus_archivedcompleted', 'enrol_programs')];
            } else {
                $result = ['color' => 'dark', 'text' => get_string('programstatus_archived', 'enrol_programs')];
            }
        } else if ($allocation->timecompleted) {
            $result = ['color' => 'success', 'text' => get_string('programstatus_completed', 'enrol_programs')];
        } else if ($allocation->timestart > $now) {
            $result = ['color' => 'light', 'text' => get_string('programstatus_future', 'enrol_programs')];
        } else if ($allocation->timeend && $allocation->timeend < $now) {
            $result = ['color' => 'danger', 'text' => get_string('programstatus_failed', 'enrol_programs')];
        } else if ($allocation->timedue && $allocation->timedue < $now) {
            $result = ['color' => 'warning', 'text' => get_string('programstatus_overdue', 'enrol_programs')];
        } else {
            $result = ['color' => 'primary', 'text' => get_string('programstatus_open', 'enrol_programs')];
        }

        return $result;
    }

    protected static function get_course_for_display($courseid) {
        global $DB, $CFG, $USER;
        if (!$course = $DB->get_record('course', array('id' => $courseid))) {
            throw new \moodle_exception('error:block_myprograms', 'facetoface');
        }
        $category = \core_course_category::get($course->category, IGNORE_MISSING);
        $lastaccess = $DB->get_record('user_lastaccess', array('userid' => $USER->id, 'courseid' => $courseid));

        $progress = \core_completion\progress::get_course_progress_percentage($course);
        $hasprogress = false;
        if ($progress === 0 || $progress > 0) {
            $hasprogress = true;
        }
        $progress = floor($progress);

        $ufservice = \core_favourites\service_factory::get_service_for_user_context(\context_user::instance($USER->id));
        $favourites = $ufservice->find_favourites_by_type('core_course', 'courses');
        $favouritecourseids = [];
        if ($favourites) {
            $favouritecourseids = array_map(
                function($favourite) {
                    return $favourite->itemid;
                }, $favourites);
        }
        $isfavourite = false;
        if (in_array($course->id, $favouritecourseids)) {
            $isfavourite = true;
        }

        $completion = new \completion_info($course);
        $completionusertracked = $completion->is_tracked_user($USER->id);

        return [
            'categoryid' => ($category) ? $category->id : null,
            'categoryname' => ($category) ? $category->name : null,
            'courseimage' => \core_course\external\course_summary_exporter::get_course_image($course),
            'enddate' => $course->enddate,
            'fullname' => $course->fullname,
            'fullnamedisplay' => "Course 2",
            'hasprogress' => $hasprogress,
            'hidden' => boolval(get_user_preferences('block_myoverview_hidden_course_' . $course->id, 0)),
            'id' => $course->id,
            'idnumber' => $course->idnumber,
            'isfavourite' => $isfavourite,
            'progress' => $progress,
            'shortname' => $course->shortname,
            'showactivitydates' => $course->showactivitydates,
            'showcompletionconditions' => $course->showcompletionconditions,
            'showshortname' => $CFG->courselistshortnames ? true : false,
            'startdate' => $course->startdate,
            'summary' => $course->summary,
            'summaryformat' => $course->summaryformat,
            'timeaccess' => empty($lastaccess) ? 0 : $lastaccess->timeaccess,
            'viewurl' => new \moodle_url('/course/view.php', ['id' => $course->id]),
            'visible' => $course->visible,
            'completionusertracked' => $completionusertracked,
        ];
    }
}
