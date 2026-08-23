<?php

declare(strict_types=1);

namespace App;

/**
 * Shared AJAX renderers for admin.php (T4.2).
 *
 * All markup emitted here is byte-compatible with the legacy inline loops
 * these functions replaced, modulo the whitespace normalization documented
 * by StructureParityTest.
 */

/**
 * Fetch answers for many questions in one chunked IN() query.
 *
 * @param PDO $pdo
 * @param array<int|string, mixed> $ids question_id values (nulls/dupes ok)
 * @return array<int, array<int, array<string, mixed>>> question_id => answer rows (in table order)
 */
function fetch_answers_by_question_ids(\PDO $pdo, array $ids): array
{
    $ids = array_values(array_unique(array_filter(array_map('strval', $ids), static fn ($v) => $v !== '' && $v !== null)));
    if ($ids === []) {
        return [];
    }

    $answers = [];
    foreach (array_chunk($ids, 500) as $chunk) {
        $placeholders = implode(',', array_fill(0, count($chunk), '?'));
        $stmt = $pdo->prepare("SELECT * FROM answers WHERE question_id IN ($placeholders)");
        $stmt->execute($chunk);
        while ($row = $stmt->fetch(\PDO::FETCH_ASSOC)) {
            $answers[(int) $row['question_id']][] = $row;
        }
    }

    return $answers;
}

/**
 * Render a question listing for the admin AJAX endpoints.
 *
 * $mode controls the per-question control and the trailing submit row:
 *   - 'radio':    name="editAQ" radio + editQ_submit() row (editaquestion)
 *   - 'checkbox': name="qu{N}" checkbox + quiz_submit() row (deleteSomeQuestions)
 *   - 'none':     no control, no submit row (questionsQuiz)
 *
 * Rows come straight from the handler query; when the listing spans all
 * quizzes the caller marks each row with ['_all' => true] so the quiz-name
 * annotation renders.
 *
 * @param array<int, array<string, mixed>> $rows question rows (+ optional _all flag)
 * @param array<int, array<int, array<string, mixed>>> $answers_by_qid from fetch_answers_by_question_ids()
 */
function render_questions_table(array $rows, array $answers_by_qid, string $mode): string
{
    $inputName = '';
    $submitFn = '';
    if ($mode === 'radio') {
        $inputName = 'editAQ';
        $submitFn = 'editQ_submit';
    } elseif ($mode === 'checkbox') {
        $inputName = 'qu';
        $submitFn = 'quiz_submit';
    } elseif ($mode !== 'none') {
        throw new \InvalidArgumentException("Unknown mode '$mode'");
    }

    $output = '';
    $displayId = 1;

    foreach ($rows as $row) {
        $isAll = !empty($row['_all']);
        $qid = $row['question_id'];

        $control = '';
        if ($mode === 'radio') {
            $control = '<input type="radio" name="editAQ" value="' . $qid . '">';
        } elseif ($mode === 'checkbox') {
            $control = '<input type="checkbox" name="qu' . $displayId . '" value="' . $qid . '">';
        }

        $q = '<tr>
						<td width="40px" rowspan="1" align="center">';

        if ($isAll) {
            $q .= '<br>
							<strong>' . $displayId . '.</strong>
						</td>
						<td>
							' . $control . '
							<small><i>(' . $row['quiz_name'] . ')</i></small><br>';
        } elseif ($mode === 'none') {
            $q .= '	<strong>' . $displayId . '.</strong>
					</td>
					<td>';
        } else {
            $q .= '	<strong>' . $displayId . '.</strong>
					</td>
					<td>
						' . $control . '
					';
        }
        $q .= '<pre class="question_style"><strong><div style="width: 730px; word-wrap: break-word;">' . $row['question'] . '</div></strong></pre>
					</td>
				</tr>';

        if ($row['code'] != "" && $row['code_type'] != "") {
            // legacy questionsQuiz emitted the tighter <td></td> spacer; the
            // edit/delete loops kept a newline inside it.
            $spacer = $mode === 'none' ? '<td></td>' : "<td>\n\t\t\t\t\t\t</td>";
            $q .= '<tr>
						' . $spacer . '
						<td>
							<pre class="brush: ' . $row['code_type'] . ';">' . $row['code'] . '</pre>
						</td>
					</tr>
					';
        }

        $answers = '<tr>
							<td></td>
							<td>
								<ol type="a">
						';

        foreach ($answers_by_qid[(int) $qid] ?? [] as $answerRow) {
            if ($answerRow['correct'] == 1) {
                $answers .= '<u><i>';
            }
            $answers .= '<div style="width: 730px; word-wrap: break-word;"><li>' . $answerRow['answer'] . '</li></div>';
            if ($answerRow['correct'] == 1) {
                $answers .= '</i></u>';
            }
        }

        $answers .= '				</ol>
								</td>
							</tr>
							<tr height="20px"></tr>
							';

        $output .= $q . $answers;
        $displayId++;
    }

    if ($mode !== 'none') {
        $output .= '  <tr>
						<td colspan="2" align="center">

							<input type="hidden" name="total_ques" value="' . ($displayId - 1) . '">

							<span id="m_btnSpan">
								<a href="javascript:{}" onclick="' . $submitFn . '()" class="myButton">Submit</a>
							</span>
						</td>
					</tr>';
    }

    return $output;
}

/**
 * Render the usersQuiz/usersAll results table. $limit slices the ordered
 * rows (20 for the Top-20 view, null for all).
 *
 * @param array<int, array<string, mixed>> $rows quiz_takers rows, already ordered
 */
function render_results_table(array $rows, ?int $limit): string
{
    if ($limit !== null) {
        $rows = array_slice($rows, 0, $limit);
    }

    $output = ' <tr align="center">
					<th>Rank</th>
					<th>Roll No.</th>
					<th>Marks</th>
					<th>Percentage</th>
					<th>Time Taken</th>
					<th>TimeStamp</th>
				</tr>
			 ';

    $rank = 1;
    foreach ($rows as $row) {
        $output .= '<tr align="center">
						  <td>' . $rank . '</td>
						  <td>' . $row['username'] . '</td>
						  <td>' . $row['marks'] . '</td>
						  <td>' . $row['percentage'] . '</td>
						  <td>' . $row['duration'] . '</td>
						  <td>' . $row['date_time'] . '</td>
					  </tr>
					 ';
        $rank++;
    }

    return $output;
}
