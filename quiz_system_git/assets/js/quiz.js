/**
 * quiz.js - countdown/auto-submit machinery, syntax highlighter bootstrap
 * and right-click guard, extracted from quiz.php (T4.5). The legacy inline
 * timer(...) invocation embedded a PHP value; it now reads #quiz-boot.
 */
		    SyntaxHighlighter.all()

	     //function that submits the quiz
			function quiz_submit(){
				window.onbeforeunload = null;
				stop_timer();
				document.getElementById('quiz_form').submit();
	        }

	     //function that stops the countdown so it does not keep counting (negative) after submit
			function stop_timer(){
				if(time_again !== null){
					clearInterval(time_again);
					time_again = null;
				}
			}

			var time_again = null;
			var secs_left = 0;

	     //function that keeps the counter going
			function timer(secs){
				secs_left = secs;
				render_time();
			 //to animate the timer otherwise it'd just stay at the number entered
			 //calling render_time() every 1 sec through an interval (no string eval)
				time_again = setInterval(render_time, 1000);
			}

	     //renders one tick of the countdown
			function render_time(){
				var ele = document.getElementById("countdown");
				ele.innerHTML = "Your Time Starts Now";
				var mins_rem = parseInt(secs_left/60);
				var secs_rem = secs_left%60;

				if(mins_rem<10 && secs_rem>=10)
					ele.innerHTML = "Time Remaining: "+"0"+mins_rem+":"+secs_rem;
				else if(secs_rem<10 && mins_rem>=10)
					ele.innerHTML = "Time Remaining: "+mins_rem+":0"+secs_rem;
				else if(secs_rem<10 && mins_rem<10)
					ele.innerHTML = "Time Remaining: "+"0"+mins_rem+":0"+secs_rem;
				else
					ele.innerHTML = "Time Remaining: "+mins_rem+":"+secs_rem;

				if(mins_rem=="00" && secs_rem < 1){
					stop_timer();
					quiz_submit();
					return;
				}
				secs_left--;
			}

	 //wwarning confirmation that appears on closing/refreshing the quiz window/tab
			function closeEditorWarning(){
    				return "really wanna quit!? You can't take the test again you know!";
			}
			window.onbeforeunload = closeEditorWarning;

			document.addEventListener("contextmenu", function(e){
			    e.preventDefault();
			}, false);

//Quiz runtime values emitted by quiz.php as JSON (CSP-safe)
var QUIZ_BOOT = {};
try {
    QUIZ_BOOT = JSON.parse(document.getElementById("quiz-boot").textContent || "{}") || {};
} catch (e) {
    QUIZ_BOOT = {};
}
if (!QUIZ_BOOT.total_time) {
    QUIZ_BOOT.total_time = 0;
}

timer(QUIZ_BOOT.total_time);
