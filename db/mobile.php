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
 * Defines handlers for mobile support for myprograms block
 *
 * @copyright  2022 Open LMS (https://www.openlms.net/)
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

$addons = [
    'block_myprograms' => [
        'handlers' => [
            'myprogramoverview' => [
                'delegate' => 'CoreBlockDelegate',
                'method' => 'mobile_program_overview_view',
                'displaydata' => [
                    'title' => 'block_myprograms:pluginname',
                ],
                'styles' => [
                    'url' => '/blocks/myprograms/mobile/style.css',
                    'version' => 2
                ]
            ],
            'myprogramview' => [
                'displaydata' => [
                    'title' => 'block_myprograms:programdetail',
                    'icon' => '',
                    'class' => '',
                ],
                'delegate' => 'CoreCourseModuleDelegate',
                'method' => 'mobile_program_view',
                'styles' => [
                    'url' => '/blocks/myprograms/mobile/style.css',
                    'version' => 2
                ]
            ],
        ],
        'lang' => [
            ['pluginname', 'block_myprograms'],
            ['allocationdate', 'block_myprograms'],
            ['allocationend', 'block_myprograms'],
            ['allocationstart', 'block_myprograms'],
            ['completiondate', 'block_myprograms'],
            ['content', 'block_myprograms'],
            ['filterprograms', 'block_myprograms'],
            ['noprograms', 'block_myprograms'],
            ['programdetail', 'block_myprograms'],
            ['programend', 'block_myprograms'],
            ['programdue', 'block_myprograms'],
            ['programprogress', 'block_myprograms'],
            ['programstart', 'block_myprograms'],
            ['programstatus', 'block_myprograms'],
            ['sequencetype', 'block_myprograms'],
        ],
    ],
];
