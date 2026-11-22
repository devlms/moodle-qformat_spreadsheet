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

namespace qformat_spreadsheet;

/**
 * Tests for the spreadsheet (CSV) question import.
 *
 * @package    qformat_spreadsheet
 * @copyright  2026 DevLMS
 * @license    https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 * @covers     \qformat_spreadsheet
 */
final class format_test extends \advanced_testcase {
    public static function setUpBeforeClass(): void {
        global $CFG;
        parent::setUpBeforeClass();
        require_once($CFG->libdir . '/questionlib.php');
        require_once($CFG->dirroot . '/question/format.php');
        require_once($CFG->dirroot . '/question/format/spreadsheet/format.php');
    }

    /**
     * Parse a fixture file.
     *
     * @param string $name
     * @return array [questions, importer]
     */
    private function parse(string $name): array {
        $importer = new \qformat_spreadsheet();
        $importer->set_display_progress(false);
        ob_start();
        $questions = $importer->readquestions($importer->read_csv(file_get_contents(__DIR__ . '/fixtures/' . $name)));
        $output = ob_get_clean();
        return [$questions, $output];
    }

    public function test_semicolon_file_with_all_kinds(): void {
        [$qs] = $this->parse('semicolon.csv');
        $this->assertCount(4, $qs);

        $this->assertSame('multichoice', $qs[0]->qtype);
        $this->assertSame(1, $qs[0]->single);
        $this->assertCount(3, $qs[0]->answer, 'Empty columns are not answers');
        $this->assertSame([0.0, 1.0, 0.0], $qs[0]->fraction);
        $this->assertSame('Two pairs make four.', $qs[0]->generalfeedback);

        $this->assertSame(0, $qs[1]->single, 'Two correct letters make a multiple answer question');
        $this->assertSame([0.5, -0.5, 0.5, -0.5], $qs[1]->fraction);

        $this->assertSame('truefalse', $qs[2]->qtype);
        $this->assertTrue($qs[2]->correctanswer);

        $this->assertStringContainsString("line two", $qs[3]->questiontext, 'Cells may span lines');
    }

    public function test_comma_file_skips_bad_rows_with_a_reason(): void {
        [$qs, $out] = $this->parse('comma.csv');
        $this->assertCount(1, $qs);
        $this->assertSame('Capital of France?', $qs[0]->name);
        $this->assertStringContainsString('Row 3', $out);
        $this->assertStringContainsString('Row 4', $out);
    }

    public function test_text_is_escaped(): void {
        $importer = new \qformat_spreadsheet();
        $qs = $importer->readquestions($importer->read_csv("type,question,A,B,correct\nmultichoice,<script>x</script>,a,b,A\n"));
        $this->assertStringNotContainsString('<script>', $qs[0]->questiontext);
    }
}
