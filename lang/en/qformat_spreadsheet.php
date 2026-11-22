<?php
// This file is part of Moodle - https://moodle.org/
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
// along with Moodle.  If not, see <https://www.gnu.org/licenses/>.

/**
 * Language strings.
 *
 * @package    qformat_spreadsheet
 * @copyright  2026 DevLMS
 * @license    https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die();

$string['err_correct'] = 'Row {$a}: the "correct" column must name existing answers, for example B or A,C.';
$string['err_header'] = 'The first row must name the columns: type, question, A, B, ... and correct.';
$string['err_question'] = 'Row {$a}: the question text is empty.';
$string['err_tf'] = 'Row {$a}: a true/false question needs TRUE or FALSE in the "correct" column.';
$string['err_twoanswers'] = 'Row {$a}: a multiple choice question needs at least two answers.';
$string['err_type'] = 'Row {$a->row}: "{$a->type}" is not a type this import knows. Use multichoice or truefalse.';
$string['pluginname'] = 'Spreadsheet (CSV)';
$string['pluginname_help'] = 'Import multiple choice and true/false questions from a CSV file saved from Excel, Google Sheets or LibreOffice. Columns: type, question, A to E (answers), correct (letters, for example B or A,C, or TRUE/FALSE), and optional feedback and name.';
$string['pluginname_link'] = 'qformat/spreadsheet';
$string['privacy:metadata'] = 'The spreadsheet import format stores no personal data.';
