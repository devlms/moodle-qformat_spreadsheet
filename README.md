# Spreadsheet (CSV) question import (qformat_spreadsheet)

Write multiple choice and true/false questions in Excel, Google Sheets or LibreOffice, save as CSV, and import them into the Moodle question bank.

| type | question | A | B | C | D | E | correct | feedback | name |
|---|---|---|---|---|---|---|---|---|---|
| multichoice | What is 2 + 2? | 3 | 4 | 5 | | | B | Two pairs make four. | |
| multichoice | Pick the prime numbers | 2 | 4 | 5 | 9 | | A,C | | |
| truefalse | Moodle is written in PHP. | | | | | | TRUE | | |

- One correct letter makes a single answer question. Several (A,C) make a multiple answer question: each right answer gets an equal share and each wrong one the same share off.
- Comma, semicolon (European Excel) and tab separated files work. Cells may contain line breaks. Columns can be in any order; feedback and name are optional.
- Rows with a problem are skipped and named in the import report ("Row 3: ..."); the others are imported.
- Text is imported as plain text (HTML in a cell is shown, not run).
- Save as **CSV UTF-8** (Excel: File > Save As > CSV UTF-8; Google Sheets and LibreOffice use UTF-8 by default). Moodle refuses files in other encodings, which Excel's plain "CSV" uses for accented and Arabic text.

Question bank > Import > Spreadsheet (CSV). See example.csv. Moodle 4.5 to 5.3.

Built by DevLMS: https://devlms.com
