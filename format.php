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
 * Spreadsheet (CSV) question import.
 *
 * @package    qformat_spreadsheet
 * @copyright  2026 DevLMS
 * @license    https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die();

require_once($CFG->dirroot . '/question/format.php');

/**
 * Imports multiple choice and true/false questions from a CSV file.
 *
 * The first row names the columns (any order, any case): type, question, A..E, correct, and optional feedback and name.
 * Comma, semicolon and tab separated files are accepted, quoted cells may span lines, a UTF-8 BOM is ignored.
 *
 * @package    qformat_spreadsheet
 * @copyright  2026 DevLMS
 * @license    https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class qformat_spreadsheet extends qformat_default {
    /** @var string[] Answer columns, in order. */
    const LETTERS = ['A', 'B', 'C', 'D', 'E'];

    #[\Override]
    public function provide_import() {
        return true;
    }

    #[\Override]
    public function mime_type() {
        return 'text/csv';
    }

    #[\Override]
    public function validate_file(stored_file $file): string {
        return $this->validate_is_utf8_file($file);
    }

    /**
     * Read the whole file instead of lines, because a cell may span lines.
     *
     * @param string $filename
     * @return array|false rows
     */
    #[\Override]
    protected function readdata($filename) {
        if (!is_readable($filename)) {
            return false;
        }
        return $this->read_csv(file_get_contents($filename));
    }

    /**
     * Rows of a CSV text, each keyed by lowercase column name, plus '_row' (the row number in the file).
     *
     * @param string $content
     * @return array
     */
    public function read_csv(string $content): array {
        $content = core_text::trim_utf8_bom($content);
        $firstline = strtok($content, "\r\n");
        $delimiter = ',';
        foreach ([';', "\t"] as $candidate) {
            if (substr_count((string)$firstline, $candidate) > substr_count((string)$firstline, $delimiter)) {
                $delimiter = $candidate;
            }
        }
        $handle = fopen('php://memory', 'r+');
        fwrite($handle, $content);
        rewind($handle);
        $header = null;
        $rows = [];
        $rownumber = 0;
        while (($cells = fgetcsv($handle, 0, $delimiter, '"', '')) !== false) {
            $rownumber++;
            if ($cells === [null]) {
                continue;
            }
            if ($header === null) {
                $header = array_map(fn($h) => core_text::strtolower(trim((string)$h)), $cells);
                continue;
            }
            $row = ['_row' => $rownumber];
            foreach ($header as $i => $name) {
                $row[$name] = trim((string)($cells[$i] ?? ''));
            }
            $rows[] = $row;
        }
        fclose($handle);
        if ($header !== null && (!in_array('question', $header, true) || !in_array('correct', $header, true))) {
            $this->error(get_string('err_header', 'qformat_spreadsheet'));
            return [];
        }
        return $rows;
    }

    /**
     * Turn rows into questions; rows with problems are reported and skipped.
     *
     * @param array $lines rows from read_csv()
     * @return array questions
     */
    #[\Override]
    public function readquestions($lines) {
        $questions = [];
        foreach ($lines as $row) {
            $type = core_text::strtolower($row['type'] ?? 'multichoice') ?: 'multichoice';
            if ($row['question'] === '') {
                $this->error(get_string('err_question', 'qformat_spreadsheet', $row['_row']));
                continue;
            }
            $question = match ($type) {
                'multichoice', 'mc' => $this->multichoice($row),
                'truefalse', 'tf' => $this->truefalse($row),
                default => null,
            };
            if ($question === null && !in_array($type, ['multichoice', 'mc', 'truefalse', 'tf'], true)) {
                $this->error(get_string('err_type', 'qformat_spreadsheet', (object)['row' => $row['_row'], 'type' => s($type)]));
            }
            if ($question) {
                $questions[] = $question;
            }
        }
        return $questions;
    }

    /**
     * A text field as the question API expects it. The text must already be escaped.
     *
     * @param string $html
     * @return array
     */
    protected function text_field(string $html): array {
        return ['text' => $html, 'format' => FORMAT_HTML, 'files' => []];
    }

    /**
     * Fields every question gets.
     *
     * @param array $row
     * @param string $qtype
     * @return stdClass
     */
    protected function base(array $row, string $qtype): stdClass {
        $question = $this->defaultquestion();
        $question->qtype = $qtype;
        $name = ($row['name'] ?? '') !== '' ? $row['name'] : $row['question'];
        $question->name = $this->create_default_question_name(s($name), get_string('questionname', 'question'));
        $question->questiontext = nl2br(s($row['question']), false);
        $question->questiontextformat = FORMAT_HTML;
        $question->generalfeedback = '';
        $question->generalfeedbackformat = FORMAT_HTML;
        if (($row['feedback'] ?? '') !== '') {
            $question->generalfeedback = s($row['feedback']);
        }
        return $question;
    }

    /**
     * A multiple choice question; several correct letters make it multiple answer.
     *
     * @param array $row
     * @return stdClass|null
     */
    protected function multichoice(array $row): ?stdClass {
        $answers = [];
        foreach (self::LETTERS as $letter) {
            $text = $row[core_text::strtolower($letter)] ?? '';
            if ($text !== '') {
                $answers[$letter] = $text;
            }
        }
        if (count($answers) < 2) {
            $this->error(get_string('err_twoanswers', 'qformat_spreadsheet', $row['_row']));
            return null;
        }
        $correct = array_unique(preg_split('/[\s,;]+/', strtoupper($row['correct']), -1, PREG_SPLIT_NO_EMPTY));
        if (!$correct || array_diff($correct, array_keys($answers))) {
            $this->error(get_string('err_correct', 'qformat_spreadsheet', $row['_row']));
            return null;
        }
        $question = $this->base($row, 'multichoice');
        $question->single = count($correct) === 1 ? 1 : 0;
        $share = 1.0 / count($correct);
        $question->answer = [];
        $question->fraction = [];
        $question->feedback = [];
        foreach ($answers as $letter => $text) {
            $question->answer[] = $this->text_field(s($text));
            $right = in_array($letter, $correct, true);
            $question->fraction[] = $right ? round($share, 7) : ($question->single ? 0.0 : -round($share, 7));
            $question->feedback[] = $this->text_field('');
        }
        $question->correctfeedback = $this->text_field('');
        $question->partiallycorrectfeedback = $this->text_field('');
        $question->incorrectfeedback = $this->text_field('');
        return $question;
    }

    /**
     * A true/false question.
     *
     * @param array $row
     * @return stdClass|null
     */
    protected function truefalse(array $row): ?stdClass {
        $value = strtoupper($row['correct']);
        if (!in_array($value, ['TRUE', 'FALSE', 'T', 'F'], true)) {
            $this->error(get_string('err_tf', 'qformat_spreadsheet', $row['_row']));
            return null;
        }
        $question = $this->base($row, 'truefalse');
        $question->correctanswer = $value[0] === 'T';
        $question->answer = $question->correctanswer;
        $question->feedbacktrue = $this->text_field('');
        $question->feedbackfalse = $this->text_field('');
        return $question;
    }
}
