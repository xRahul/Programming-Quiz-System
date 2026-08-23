<?php

declare(strict_types=1);

use PHPUnit\Framework\TestCase;

/**
 * T5.6 progress indicator: source-level contract only (no browser in CI).
 * quiz.php must render the target span plus a total_ques boot value;
 * quiz.js must wire radio changes to that span.
 */
final class ProgressIndicatorTest extends TestCase
{
    public function testQuizPageRendersProgressSpanAndBootTotal(): void
    {
        $quizPhp = (string) file_get_contents(dirname(__DIR__) . '/quiz.php');

        $this->assertStringContainsString('<span id="progress">', $quizPhp, 'quiz.php must render the progress span');

        // boot island must carry total_ques for the JS side
        $this->assertMatchesRegularExpression(
            "/<script id=\"quiz-boot\" type=\"application\/json\">.*'total_ques'.*<\/script>/s",
            $quizPhp,
            'quiz-boot island must emit total_ques'
        );
    }

    public function testQuizJsCountsAnsweredRadioGroupsIntoProgressSpan(): void
    {
        $quizJs = (string) file_get_contents(dirname(__DIR__) . '/assets/js/quiz.js');

        $this->assertStringContainsString('QUIZ_BOOT.total_ques', $quizJs, 'JS must read total from the boot island');
        $this->assertMatchesRegularExpression('/rads\\\\d\+/', $quizJs, 'JS must match the legacy rads<N> group names');
        $this->assertMatchesRegularExpression('/getElementById\("progress"\)/', $quizJs, 'JS must write into the progress span');
        $this->assertStringContainsString('answered', $quizJs, 'JS must render the X/Y answered text');
    }
}
