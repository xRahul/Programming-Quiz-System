<?php

declare(strict_types=1);

/**
 * Shared add/edit-question form (T4.6). The includer sets $type to 'tf'
 * or 'mc'. All element names/ids mirror the legacy markup exactly so the
 * edit-prefill op list and assets/js/admin.js keep working unchanged.
 */

if (!function_exists('question_form_languages')) {
    /**
     * Single source of truth for the programming-language dropdown rendered
     * by both question forms.
     *
     * @return array<string, string> value => label
     */
    function question_form_languages(): array
    {
        return [
            'applescript' => 'AppleScript',
            'actionscript3' => 'ActionScript3',
            'shell' => 'Bash/Shell',
            'coldfusion' => 'ColdFusion',
            'csharp' => 'C#',
            'cpp' => 'C/C++',
            'css' => 'CSS',
            'delphi' => 'Delphi',
            'diff' => 'Diff',
            'erlang' => 'Erlang',
            'groovy' => 'Groovy',
            'js' => 'JavaScript',
            'java' => 'Java',
            'javafx' => 'JavaFX',
            'perl' => 'Perl',
            'php' => 'PHP',
            'plain' => 'Plain Text',
            'powershell' => 'PowerShell',
            'python' => 'Python',
            'ruby' => 'Ruby on Rails',
            'sass' => 'Sass',
            'scala' => 'Scala',
            'sql' => 'SQL',
            'vbnet' => 'VB.net',
            'html' => 'HTML/XML/xHTML/XSLT',
        ];
    }
}

$type = isset($type) ? $type : 'tf';
$is_tf = $type === 'tf';

if ($is_tf) {
    $qf_div_id = 'tf';
    $qf_heading = 'True or false';
    $qf_form_name = 'addQuestion';
    $qf_quiz_select_id = 'quizIDtf';
    $qf_desc_id = 'tfDesc';
    $qf_lang_id = 'prog-lang-tf';
    $qf_code_id = 'tfcodeDesc';
    $qf_submit_id = 'addToQuizTF';
    $qf_type_value = 'tf';
} else {
    $qf_div_id = 'mc';
    $qf_heading = 'Multiple Choice';
    $qf_form_name = 'addMcQuestion';
    $qf_quiz_select_id = 'quizIDmc';
    $qf_desc_id = 'mcdesc';
    $qf_lang_id = 'prog-lang-mc';
    $qf_code_id = 'mccodeDesc';
    $qf_submit_id = 'addToQuizMC';
    $qf_type_value = 'mc';
}
?>
		<div class="content" id="<?php echo $qf_div_id; ?>" style="margin-bottom: 100px;">
			<h2><?php echo $qf_heading; ?></h2>

		<form action="admin.php" name="<?php echo $qf_form_name; ?>" method="POST">
			<?php echo csrf_field(); ?>

			<strong>Select the quiz in which to enter the Question</strong>
			<select class="quizIDselect" name="quizID" id="<?php echo $qf_quiz_select_id; ?>">
				<?php echo $quizSelect; ?>
			</select>
			<br />
			<br />

			<strong>Please type your new question here:</strong>
			<br />

			<textarea class="txt_area" id="<?php echo $qf_desc_id; ?>" name="desc"></textarea>
			<br />
			<br />


			<strong>If there's a programming code, enter here</strong>
			<br />

			<strong style="font-family: Times;">Select Language of the code: </strong>
			<span class='css-select-moz'>
				<select class="lang_selector" name="prog-lang" onchange="lang_chosen(this);" id="<?php echo $qf_lang_id; ?>">
					<option value=""> ------ </option>
<?php foreach (question_form_languages() as $qf_lang_value => $qf_lang_label): ?>
						<option value="<?php echo $qf_lang_value; ?>"><?php echo $qf_lang_label; ?></option>
<?php endforeach; ?>
				</select>
			</span>
<?php if ($is_tf): ?>
			<br />
			<textarea class="txt_area" id="<?php echo $qf_code_id; ?>" name="code_desc" style="width:400px;height:95px;"></textarea>
			<br />

			<br />


			<strong>Select whether true or false is the Correct Answer</strong>
			<br />

		<input type="text" class="tf_txt_box" id="answer1" name="answer1" value="True" readonly>&nbsp;
		<label style="cursor:pointer; color:#555;">
			<input type="radio" id="tfans1" name="iscorrect" value="answer1">Correct Answer?
		</label>
			<br />
				<br />
		<input type="text" class="tf_txt_box" id="answer2" name="answer2" value="False" readonly>&nbsp;
		<label style="cursor:pointer; color:#555;">
			<input type="radio" id="tfans2" name="iscorrect" value="answer2">Correct Answer?
		</label>


			<br />
			<br />


			<input type="hidden" value="tf" name="type">
			<input type="hidden" value="<?php echo htmlspecialchars($gaq_question_id, ENT_QUOTES); ?>" name="questionID">
				<input type="submit" class="add_to_quiz" id="addToQuizTF" value="Add To Quiz">
<?php else: ?>
				<br />
			<pre><textarea class="txt_area" id="<?php echo $qf_code_id; ?>" name="code_desc" style="width:400px;height:95px;"></textarea>
			</pre>

			<br />
			<br />


			<strong>First Option</strong>
			<br />
			<input type="text" class="mc_txt_box" id="mcanswer1" name="answer1">&nbsp;
			<label style="cursor:pointer; color:#555;">
				<input type="radio" id="mcans1" name="iscorrect" value="answer1">Correct Answer?
			</label>
			<br />
			<br />
			<strong>Second Option</strong>
			<br />
			<input type="text" class="mc_txt_box" id="mcanswer2" name="answer2">&nbsp;
			<label style="cursor:pointer; color:#555;">
				<input type="radio" id="mcans2" name="iscorrect" value="answer2">Correct Answer?
			</label>
			<br />
			<br />
			<strong>Third Option</strong>
			<br />
			<input type="text" class="mc_txt_box" id="mcanswer3" name="answer3">&nbsp;
			<label style="cursor:pointer; color:#555;">
				<input type="radio" id="mcans3" name="iscorrect" value="answer3">Correct Answer?
			</label>
			<br />
			<br />
			<strong>Fourth Option</strong>
			<br />
			<input type="text" class="mc_txt_box" id="mcanswer4" name="answer4">&nbsp;
			<label style="cursor:pointer; color:#555;">
				<input type="radio" id="mcans4" name="iscorrect" value="answer4">Correct Answer?
			</label>
			<br />
			<br />
			<input type="hidden" value="mc" name="type">
			<input type="hidden" value="<?php echo htmlspecialchars($gaq_question_id, ENT_QUOTES); ?>" name="questionID">
			<input type="submit" class="add_to_quiz" id="addToQuizMC" value="Add To Quiz">
<?php endif; ?>
		</form>
		</div>
