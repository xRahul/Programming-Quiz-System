/**
 * admin.js - all admin console behaviour extracted from admin.php (T4.5).
 * Function names and callsites are unchanged; page-specific values arrive
 * via the #admin-boot / #edit-state JSON islands instead of PHP string
 * interpolation so the page can ship without script-src unsafe-inline.
 */
//Shared page configuration emitted by admin.php as JSON (CSP-safe)
var ADMIN_BOOT = {};
try {
    ADMIN_BOOT = JSON.parse(document.getElementById("admin-boot").textContent || "{}") || {};
} catch (e) {
    ADMIN_BOOT = {};
}
if (!ADMIN_BOOT.login_session) {
    ADMIN_BOOT.login_session = "";
}

//CSRF token appended to every XHR body (mirrors the hidden input in each form)
			function csrfQS(){
				return '&csrf_token=' + encodeURIComponent(document.querySelector('input[name=csrf_token]').value);
			}

 		 //displaying different code-blocks on button click
 			function showDiv(el1,el2,el3){
 				document.getElementById(el1).style.display = 'block';
 				document.getElementById(el2).style.display = 'none';
 				document.getElementById(el3).style.display = 'none';
 			//editors initialize inside hidden divs and measure nothing;
 			//refresh once visible so lineNumbers/active-line render
 				if (typeof tfeditor !== "undefined") { tfeditor.refresh(); }
 				if (typeof mceditor !== "undefined") { mceditor.refresh(); }
 			}

		 //hide all divs
			function hideDivs(){
				document.getElementById('tf').style.display = 'none';
				document.getElementById('mc').style.display = 'none';
				document.getElementById('quesans').style.display = 'none';
			}



/*SETTING TIMELOCKS
			function set_timelock(quizz){

			}

			function remove_timelock(quizzz){

			}
*/


			function clear_result(qquizID){
				if(confirm("Really wanna clear the result of all users of this quiz?!")) {
					var x = new XMLHttpRequest();
					var url = "admin.php";
					var vars = 'clearResult='+qquizID;
					x.open("POST", url, true);
					x.setRequestHeader("Content-type", "application/x-www-form-urlencoded");
					x.onreadystatechange = function() {
						if(x.readyState == 4 && x.status == 200) {
							document.getElementById("msg").innerHTML = x.responseText;
						}
					}
					x.send(vars + csrfQS());
					document.getElementById("msg").innerHTML = "processing...";
				}
			}




			function reset_tables(){
				if(confirm("Really wanna reset all the tables?!")) {
					if(confirm("Your admin ID will be \'admin\' and password \'12345\'")){
						var x = new XMLHttpRequest();
						var url = "admin.php";
						var vars = 'resetTables='+'yes';
						x.open("POST", url, true);
						x.setRequestHeader("Content-type", "application/x-www-form-urlencoded");
						x.onreadystatechange = function() {
							if(x.readyState == 4 && x.status == 200) {
								window.open("login.php?user_msg="+x.responseText, "_self");
							}
						}
						x.send(vars + csrfQS());
						document.getElementById("msg").innerHTML = "processing...";
					}
				}
			}




			function delete_account(){
				if(confirm("Really wanna delete this admin Account?!")) {
					var x = new XMLHttpRequest();
					var url = "admin.php";
					var vars = 'deleteAdmin='+ADMIN_BOOT.login_session;
					x.open("POST", url, true);
					x.setRequestHeader("Content-type", "application/x-www-form-urlencoded");
					x.onreadystatechange = function() {
						if(x.readyState == 4 && x.status == 200) {
							document.getElementById("msg").innerHTML = x.responseText;
						}
					}
					x.send(vars + csrfQS());
					document.getElementById("msg").innerHTML = "processing...";
				}
			}


			function top_users(qiiizName){
				var x = new XMLHttpRequest();
				var url = "admin.php";
				var vars = 'usersQuiz='+qiiizName;
				x.open("POST", url, true);
				x.setRequestHeader("Content-type", "application/x-www-form-urlencoded");
				x.onreadystatechange = function() {
					if(x.readyState == 4 && x.status == 200) {
						showDiv('quesans', 'mc', 'tf');
						document.getElementById("quesans_table").innerHTML = x.responseText;
						document.getElementById("msg").innerHTML = "";

					}
				}
				x.send(vars + csrfQS());
				document.getElementById("msg").innerHTML = "processing...";
			}



			function all_users(qqizName){
				var x = new XMLHttpRequest();
				var url = "admin.php";
				var vars = 'usersAll='+qqizName;
				x.open("POST", url, true);
				x.setRequestHeader("Content-type", "application/x-www-form-urlencoded");
				x.onreadystatechange = function() {
					if(x.readyState == 4 && x.status == 200) {
						showDiv('quesans', 'mc', 'tf');
						document.getElementById("quesans_table").innerHTML = x.responseText;
						document.getElementById("msg").innerHTML = "";
					}
				}
				x.send(vars + csrfQS());
				document.getElementById("msg").innerHTML = "processing...";
			}


			function delete_some_questions(quizzName){
				var x = new XMLHttpRequest();
				var url = "admin.php";
				var vars = 'deleteSomeQuestions='+quizzName;
				x.open("POST", url, true);
				x.setRequestHeader("Content-type", "application/x-www-form-urlencoded");
				x.onreadystatechange = function() {
					if(x.readyState == 4 && x.status == 200) {
						showDiv('quesans', 'mc', 'tf');
						document.getElementById("quesans_table").innerHTML = x.responseText;
						highlightBrushBlocks();
						document.getElementById("msg").innerHTML = "";
					}
				}
				x.send(vars + csrfQS());
				document.getElementById("msg").innerHTML = "processing...";
			}



			function edit_question(qzname){
				var x = new XMLHttpRequest();
				var url = "admin.php";
				var vars = 'editaquestion='+qzname;
				x.open("POST", url, true);
				x.setRequestHeader("Content-type", "application/x-www-form-urlencoded");
				x.onreadystatechange = function() {
					if(x.readyState == 4 && x.status == 200) {
						showDiv('quesans', 'mc', 'tf');
						document.getElementById("quesans_table").innerHTML = x.responseText;
						highlightBrushBlocks();
						document.getElementById("msg").innerHTML = "";
					}
				}
				x.send(vars + csrfQS());
				document.getElementById("msg").innerHTML = "processing...";
			}


			function editQ_submit(){
				document.deleteedit.action = "admin.php";
	            document.getElementById('deleteedit').submit();
	        }


			function view_questions(qiizName){
				var x = new XMLHttpRequest();
				var url = "admin.php";
				var vars = 'questionsQuiz='+qiizName;
				x.open("POST", url, true);
				x.setRequestHeader("Content-type", "application/x-www-form-urlencoded");
				x.onreadystatechange = function() {
					if(x.readyState == 4 && x.status == 200) {
						showDiv('quesans', 'mc', 'tf');
						document.getElementById("quesans_table").innerHTML = x.responseText;
						highlightBrushBlocks();
						document.getElementById("msg").innerHTML = "";
					}
				}
				x.send(vars + csrfQS());
				document.getElementById("msg").innerHTML = "processing...";
			}

			function default_quiz(qizName){
				var x = new XMLHttpRequest();
				var url = "admin.php";
				var vars = 'defaultQuiz='+qizName;
				x.open("POST", url, true);
				x.setRequestHeader("Content-type", "application/x-www-form-urlencoded");
				x.onreadystatechange = function() {
					if(x.readyState == 4 && x.status == 200) {
						document.getElementById("resetBtnMsg").innerHTML = x.responseText;
					}
				}
				x.send(vars + csrfQS());
				document.getElementById("resetBtnMsg").innerHTML = "processing...";
			}


		//truncating the tables and resetting the quiz
			function delete_quiz(qzName){
				if(confirm("Really wanna delete this Quiz and all its Questions?!")) {
					var x = new XMLHttpRequest();
					var url = "admin.php";
					var vars = 'deleteQuiz='+qzName;
					x.open("POST", url, true);
					x.setRequestHeader("Content-type", "application/x-www-form-urlencoded");
					x.onreadystatechange = function() {
						if(x.readyState == 4 && x.status == 200) {
							document.getElementById("resetBtnMsg").innerHTML = x.responseText;
						}
					}
					x.send(vars + csrfQS());
					document.getElementById("resetBtnMsg").innerHTML = "processing...";
				}
			}


		 //truncating the tables and resetting the quiz
			function resetQuiz(){
				if(confirm("Really wanna delete all the Questions from all the quizes?!")) {
					var x = new XMLHttpRequest();
					var url = "admin.php";
					var vars = 'reset=yes';
					x.open("POST", url, true);
					x.setRequestHeader("Content-type", "application/x-www-form-urlencoded");
					x.onreadystatechange = function() {
						if(x.readyState == 4 && x.status == 200) {
							document.getElementById("resetBtnMsg").innerHTML = x.responseText;
						}
					}
					x.send(vars + csrfQS());
					document.getElementById("resetBtnMsg").innerHTML = "processing...";
				}
			}

//updating metadata for a quiz
		function update_quiz(qname){
			open_overlay('regNewQuiz', 'regNewAdmin');
			document.newQuiz_name.action = "updateExistingQuiz.php";
			document.getElementById('quizName').value = qname;
			document.getElementById('quizName').readOnly = true;
			document.getElementById('quizTime').focus();
		}


		function change_pass(){
			open_overlay('regNewAdmin', 'regNewQuiz');
			document.reg_name.action = "changePassword.php";
			document.getElementById('login').value = ADMIN_BOOT.login_session;
			document.getElementById('login').readOnly = true;
			document.getElementById("password").focus();
		}

//overlays
			//hiding the overlay
			    function close_overlay(){
			        document.getElementById('register').style.display='none';
			        document.getElementById('fade').style.display='none';
			    }

			//showing the overlay
			    function open_overlay(ele1, ele2){

				document.getElementById(ele1).style.display = 'block';
					document.getElementById(ele2).style.display = 'none';

			        document.getElementById('register').style.display='block';
			        document.getElementById('fade').style.display='block';

				if(ele1=='regNewAdmin'){
					document.getElementById("register").style.height = '200px';
					document.getElementById("login").focus();
				}
				else{
					document.getElementById("register").style.height = '300px';
					document.getElementById("quizName").focus();
				}
			    }

function submit_admin(){
			var x=document.forms["reg_name"]["login"].value;
			var y=document.forms["reg_name"]["password"].value;
            if (x==null || x=="" || y==null || y==""){
                document.getElementById("required").innerHTML = "Enter Both Values";
                exit();
            }
			close_overlay();
            document.getElementById('reg_name').submit();
            return false;
		}

function quiz_submit(){
				document.deleteedit.action = "deleteSomeQues.php";
	            document.getElementById('deleteedit').submit();
	        }

//audit panel (T5.3): collapsible recent-activity viewer
			var audit_loaded = false;
			function load_audit(){
				var x = new XMLHttpRequest();
				x.open("POST", "admin.php", true);
				x.setRequestHeader("Content-type", "application/x-www-form-urlencoded");
				x.onreadystatechange = function() {
					if(x.readyState == 4 && x.status == 200) {
						document.getElementById("audit_table").innerHTML = x.responseText;
					}
				};
				x.send("auditRecent=1" + csrfQS());
			}
			function toggle_audit_panel(){
				var wrap = document.getElementById("audit_wrap");
				var show = wrap.style.display === 'none';
				wrap.style.display = show ? 'block' : 'none';
				if(show && !audit_loaded){
					load_audit();
					audit_loaded = true;
				}
			}

CodeMirror.modeURL = "assets/vendor/codemirror-5.65.16/mode/%N/%N.js";

	        var codeMirrorConfig = {
						lineNumbers: true,
				        matchBrackets: true,
				        indentUnit: 4,
				        indentWithTabs: true,
				        smartIndent: true,
				        styleActiveLine: true,
				        autoCloseBrackets: true,
				        autoCloseTags: true,
				        viewportMargin: Infinity,
				        fixedGutter: true
				};

			 var tfeditor = CodeMirror.fromTextArea(document.getElementById("tfcodeDesc"), codeMirrorConfig);
			 var mceditor = CodeMirror.fromTextArea(document.getElementById("mccodeDesc"), codeMirrorConfig);



			 //JS for changing the textarea
				function lang_chosen(selectObj){
				 // get the index of the selected option
					var idx = selectObj.selectedIndex;
				 // get the value of the selected option
					var which = selectObj.options[idx].value;

					change_editor(which);
				}

				function change_editor(which){
                    var changedMode = "text/plain";
                    var modeName = "";

					if(which=="cpp") { changedMode = "text/x-c++src"; modeName = "clike"; }
					else if(which=="css") { changedMode = "text/css"; modeName = "css"; }
					else if(which=="diff") { changedMode = "text/x-diff"; modeName = "diff"; }
					else if(which=="erlang") { changedMode = "text/x-erlang"; modeName = "erlang"; }
					else if(which=="groovy") { changedMode = "text/x-groovy"; modeName = "groovy"; }
					else if(which=="java" || which=="javafx") { changedMode = "text/x-java"; modeName = "clike"; }
					else if(which=="js") { changedMode = "text/javascript"; modeName = "javascript"; }
					else if(which=="perl") { changedMode = "text/x-perl"; modeName = "perl"; }
					else if(which=="php") { changedMode = "text/x-httpd-php"; modeName = "php"; }
					else if(which=="python") { changedMode = "text/x-python"; modeName = "python"; }
					else if(which=="ruby") { changedMode = "text/x-ruby"; modeName = "ruby"; }
					else if(which=="sass") { changedMode = "text/x-sass"; modeName = "sass"; }
					else if(which=="scala") { changedMode = "text/x-scala"; modeName = "clike"; }
					else if(which=="shell") { changedMode = "text/x-sh"; modeName = "shell"; }
					else if(which=="sql") { changedMode = "text/x-sql"; modeName = "sql"; }
					else if(which=="vbnet") { changedMode = "text/x-vb"; modeName = "vb"; }
					else if(which=="html") { changedMode = "text/x-html"; modeName = "xml"; }
					else if(which=="csharp") { changedMode = "text/x-csharp"; modeName = "clike"; }
                    else if(which=="applescript") { modeName = "applescript"; }
                    else if(which=="actionscript3") { modeName = "clike"; }
                    else if(which=="coldfusion") { modeName = "coldfusion"; }
                    else if(which=="delphi") { changedMode = "text/x-pascal"; modeName = "pascal"; }
                    else if(which=="powershell") { modeName = "powershell"; }

                    if (modeName) {
					CodeMirror.autoLoadMode(tfeditor, modeName);
					CodeMirror.autoLoadMode(mceditor, modeName);
                    }

					tfeditor.setOption("mode", changedMode);
					mceditor.setOption("mode", changedMode);
				}

			//legacy SyntaxHighlighter.all() highlighted on window load; the
			//question <pre> blocks may not be parsed yet when admin.js runs
			if (document.readyState === "complete") {
				highlightBrushBlocks();
			} else {
				window.addEventListener("load", highlightBrushBlocks);
			}

//Applies the JSON operation list rendered by admin.php when a question was
//loaded for editing (replaces the legacy generated inline <script> blocks)
(function applyEditState() {
    var node = document.getElementById("edit-state");
    if (!node) {
        return;
    }
    var ops;
    try {
        ops = JSON.parse(node.textContent);
    } catch (e) {
        return;
    }
    if (!ops || !ops.forEach) {
        return;
    }
    ops.forEach(function (op) {
        var kind = op.shift();
        switch (kind) {
            case "showDiv":
                showDiv(op[0], op[1], op[2]);
                break;
            case "setValue":
                document.getElementById(op[0]).value = op[1];
                break;
            case "setChecked":
                document.getElementById(op[0]).checked = true;
                break;
            case "setAction":
                document[op[0]].action = op[1];
                break;
            case "setLabel":
                document.getElementById(op[0]).value = op[1];
                break;
            case "changeEditor":
                change_editor(op[0]);
                break;
            case "editorValue":
                var editor = window[op[0]];
                if (editor) {
                    editor.setValue(op[1]);
                }
                break;
        }
    });
})();

