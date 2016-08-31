# MultiQuiz System
---

This is an upgrade of the application written in Laravel 5.1 and Reactjs.

It is using webpack and elixir 6 to build the react pages.

The general flow of the application will remain same, i.e

* For users- `User Dashboard => Quiz Page => Result Page`
* Admin dashboard
* Register/Login Pages

## Things to do-
* ~~Auth layer with login/register functionality with admin/user roles~~ (done)
* Admin Dashboard
    * Manage Quizzes
    * Manage Questions/Answers
    * Manage Results
    * Account Settings
* Landing Page
    * User Dashboard
* Quiz Page
* Result Pages


---
## Planned Admin dashboard

* Manage Quizzes
    + Create Quiz
        - QuizName
        - QuizType-TimeLimit/NotTimed
        - No of questions to show
        - active/Inactive quiz
        - User can take the quiz x number of times
    + View Quizzes
        - show list of all quizzes
        - filter= Active/Inactive/All
        - sort= 
            * byDateCreated,
            * byDateLastTaken,
            * byTimesTaken
    + Edit Quiz
        - Same as Create Quiz
        - show quiz ChangeLog
    + Quiz Details
        - Show all params from create quiz
        - Quiz Questions numbers
            * number of all questions linked to the quiz
            * active/Inactive links, active/Inactive questions
            * activeLink&activeQuestion, InactiveLink || InactiveQuestion
            * no of questions by Qtype, Marks
        - Quiz Result Overview
            * times quiz attempted
            * times quiz submitted
        - Quiz ChangeLog
        - Lint to Quiz Result Details- byUser, byQuestion
        - Link to Questions filtered by Quiz | All | All

* Manage Questions
    + Create Question
        - Active/Inactive Questions
        - Active/Inactive link to quiz(not delete)
        - Markdown Support in Questions/Options
        - Question Types= SingleChoice, MultipleChoice, TrueFalse
        - n number of options allowed in SCQ/MCQ
        - Marks?- default 5
    + View Questions
        - filters= 
            * byQuiz
            * byType[SCQ/MCQ/TF/All]
            * Active/Inactive/All
        - sort= 
            * byDateCreated, 
            * byDateLastUsed, 
            * byTimesUsed, 
            * byTimesAnswered, 
            * byTimesCorrect
        - Link To edit Question
    + Edit Question
        - Same as create Question
        - Show Question Edit History below
    + Question Details
        - Show all question parameters from create quiz
        - show all derived parameters for the question

* View Result of a Quiz
    + Result by Users
        - show list of users with their results(Users who have attempted)
            * sortBy- 
                + Rank(based on timeTaken&marks), 
                + Marks, 
                + Percentage
            * filters- 
                + Attempted/Submitted, 
                + Top10/Top20/Top50/All
    + Result by questions
        - Show list of questions linked to the quiz(Active and inactive link)
            * filterBy- 
                + Active/Inactive/All-Question, 
                + Active/Inactive/All-LinkToThisQuiz, 
                + QuestionType
            * sortBy- 
                + TimesShownWhenThisQuizIsAttempted, 
                + TimesQAttempted, 
                + %QcorrectlyAnswered, 
                + Marks
* Account Settings 
    + Change Name
    + Change password
    + Show Admin activity log
    + Delete Account(Active/Inactive User)
