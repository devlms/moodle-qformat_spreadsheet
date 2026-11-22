@qformat @qformat_spreadsheet
Feature: Import questions from a spreadsheet
  In order to write questions where I already work
  As a teacher
  I need to import a CSV saved from Excel or Google Sheets

  Background:
    Given the following "courses" exist:
      | fullname | shortname | format |
      | Course 1 | C1        | topics |
    And the following "users" exist:
      | username | firstname |
      | teacher  | Teacher   |
    And the following "course enrolments" exist:
      | user    | course | role           |
      | teacher | C1     | editingteacher |
    And the following "activities" exist:
      | activity | name    | course | idnumber |
      | qbank    | Qbank 1 | C1     | qbank1   |

  @javascript @_file_upload
  Scenario: Import a semicolon separated file with multiple choice and true/false questions
    # The qbank activity exists from Moodle 5.0; 4.5 skips this scenario.
    Given the site is running Moodle version 5.0 or higher
    And I am on the "Qbank 1" "core_question > question import" page logged in as "teacher"
    And I set the field "id_format_spreadsheet" to "1"
    And I upload "question/format/spreadsheet/tests/fixtures/semicolon.csv" file to "Import" filemanager
    When I press "id_submitbutton"
    Then I should see "Importing 4 questions from file"
    And I press "Continue"
    And I should see "What is 2 + 2?"
    And I should see "Moodle is written in PHP."
