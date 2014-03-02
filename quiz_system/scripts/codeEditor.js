

		 //JS for changing the textarea
			function lang_chosen(selectObj){
			 // get the index of the selected option 
				var idx = selectObj.selectedIndex;
			 // get the value of the selected option 
				var which = selectObj.options[idx].value;

				if(which=="cpp"){
									var editor = CodeMirror.fromTextArea(document.getElementById("tfcodeDesc"), {
							        lineNumbers: true,
							        matchBrackets: true,
							        mode: "text/x-c++src",
							        indentUnit: 4,
							        indentWithTabs: true,
							        smartIndent: true,
							        styleActiveLine: true,
							        autoCloseBrackets: true,
							        autoCloseTags: true,
							        viewportMargin: Infinity
							      });
					var editor = CodeMirror.fromTextArea(document.getElementById("mccodeDesc"), {
							        lineNumbers: true,
							        matchBrackets: true,
							        mode: "text/x-c++src",
							        indentUnit: 4,
							        indentWithTabs: true,
							        smartIndent: true,
							        styleActiveLine: true,
							        autoCloseBrackets: true,
							        autoCloseTags: true,
							        viewportMargin: Infinity
							      });
				}
				if(which=="css"){
					var editor = CodeMirror.fromTextArea(document.getElementById("tfcodeDesc"), {
							        lineNumbers: true,
							        matchBrackets: true,
							        mode: "text/css",
							        indentUnit: 4,
							        indentWithTabs: true,
							        smartIndent: true,
							        styleActiveLine: true,
							        autoCloseBrackets: true,
							        autoCloseTags: true,
							        viewportMargin: Infinity
							      });
					var editor = CodeMirror.fromTextArea(document.getElementById("mccodeDesc"), {
							        lineNumbers: true,
							        matchBrackets: true,
							        mode: "text/css",
							        indentUnit: 4,
							        indentWithTabs: true,
							        smartIndent: true,
							        styleActiveLine: true,
							        autoCloseBrackets: true,
							        autoCloseTags: true,
							        viewportMargin: Infinity
							      });
				}
				if(which=="diff"){
					var editor = CodeMirror.fromTextArea(document.getElementById("tfcodeDesc"), {
							        lineNumbers: true,
							        matchBrackets: true,
							        mode: "text/x-diff",
							        indentUnit: 4,
							        indentWithTabs: true,
							        smartIndent: true,
							        styleActiveLine: true,
							        autoCloseBrackets: true,
							        autoCloseTags: true,
							        viewportMargin: Infinity
							      });
					var editor = CodeMirror.fromTextArea(document.getElementById("mccodeDesc"), {
							        lineNumbers: true,
							        matchBrackets: true,
							        mode: "text/x-diff",
							        indentUnit: 4,
							        indentWithTabs: true,
							        smartIndent: true,
							        styleActiveLine: true,
							        autoCloseBrackets: true,
							        autoCloseTags: true,
							        viewportMargin: Infinity
							      });
				}
				if(which=="erlang"){
					var editor = CodeMirror.fromTextArea(document.getElementById("tfcodeDesc"), {
							        lineNumbers: true,
							        matchBrackets: true,
							        mode: "text/x-erlang",
							        indentUnit: 4,
							        indentWithTabs: true,
							        smartIndent: true,
							        styleActiveLine: true,
							        autoCloseBrackets: true,
							        autoCloseTags: true,
							        viewportMargin: Infinity
							      });
					var editor = CodeMirror.fromTextArea(document.getElementById("mccodeDesc"), {
							        lineNumbers: true,
							        matchBrackets: true,
							        mode: "text/x-erlang",
							        indentUnit: 4,
							        indentWithTabs: true,
							        smartIndent: true,
							        styleActiveLine: true,
							        autoCloseBrackets: true,
							        autoCloseTags: true,
							        viewportMargin: Infinity
							      });
				}
				if(which=="groovy"){
					var editor = CodeMirror.fromTextArea(document.getElementById("tfcodeDesc"), {
							        lineNumbers: true,
							        matchBrackets: true,
							        mode: "text/x-groovy",
							        indentUnit: 4,
							        indentWithTabs: true,
							        smartIndent: true,
							        styleActiveLine: true,
							        autoCloseBrackets: true,
							        autoCloseTags: true,
							        viewportMargin: Infinity
							      });
					var editor = CodeMirror.fromTextArea(document.getElementById("mccodeDesc"), {
							        lineNumbers: true,
							        matchBrackets: true,
							        mode: "text/x-groovy",
							        indentUnit: 4,
							        indentWithTabs: true,
							        smartIndent: true,
							        styleActiveLine: true,
							        autoCloseBrackets: true,
							        autoCloseTags: true,
							        viewportMargin: Infinity
							      });
				}
				if(which=="java"){
					var editor = CodeMirror.fromTextArea(document.getElementById("tfcodeDesc"), {
							        lineNumbers: true,
							        matchBrackets: true,
							        mode: "text/x-java",
							        indentUnit: 4,
							        indentWithTabs: true,
							        smartIndent: true,
							        styleActiveLine: true,
							        autoCloseBrackets: true,
							        autoCloseTags: true,
							        viewportMargin: Infinity
							      });
					var editor = CodeMirror.fromTextArea(document.getElementById("mccodeDesc"), {
							        lineNumbers: true,
							        matchBrackets: true,
							        mode: "text/x-java",
							        indentUnit: 4,
							        indentWithTabs: true,
							        smartIndent: true,
							        styleActiveLine: true,
							        autoCloseBrackets: true,
							        autoCloseTags: true,
							        viewportMargin: Infinity
							      });
				}
				if(which=="js"){
					var editor = CodeMirror.fromTextArea(document.getElementById("tfcodeDesc"), {
							        lineNumbers: true,
							        matchBrackets: true,
							        mode: "text/x-javascript",
							        indentUnit: 4,
							        indentWithTabs: true,
							        smartIndent: true,
							        styleActiveLine: true,
							        autoCloseBrackets: true,
							        autoCloseTags: true,
							        viewportMargin: Infinity
							      });
					var editor = CodeMirror.fromTextArea(document.getElementById("mccodeDesc"), {
							        lineNumbers: true,
							        matchBrackets: true,
							        mode: "text/x-javascript",
							        indentUnit: 4,
							        indentWithTabs: true,
							        smartIndent: true,
							        styleActiveLine: true,
							        autoCloseBrackets: true,
							        autoCloseTags: true,
							        viewportMargin: Infinity
							      });
				}
				if(which=="perl"){
					var editor = CodeMirror.fromTextArea(document.getElementById("tfcodeDesc"), {
							        lineNumbers: true,
							        matchBrackets: true,
							        mode: "text/x-perl",
							        indentUnit: 4,
							        indentWithTabs: true,
							        smartIndent: true,
							        styleActiveLine: true,
							        autoCloseBrackets: true,
							        autoCloseTags: true,
							        viewportMargin: Infinity
							      });
					var editor = CodeMirror.fromTextArea(document.getElementById("mccodeDesc"), {
							        lineNumbers: true,
							        matchBrackets: true,
							        mode: "text/x-perl",
							        indentUnit: 4,
							        indentWithTabs: true,
							        smartIndent: true,
							        styleActiveLine: true,
							        autoCloseBrackets: true,
							        autoCloseTags: true,
							        viewportMargin: Infinity
							      });
				}
				if(which=="php"){
					var editor = CodeMirror.fromTextArea(document.getElementById("tfcodeDesc"), {
							        lineNumbers: true,
							        matchBrackets: true,
							        mode: "application/x-httpd-php",
							        indentUnit: 4,
							        indentWithTabs: true,
							        smartIndent: true,
							        styleActiveLine: true,
							        autoCloseBrackets: true,
							        autoCloseTags: true,
							        viewportMargin: Infinity
							      });
					var editor = CodeMirror.fromTextArea(document.getElementById("mccodeDesc"), {
							        lineNumbers: true,
							        matchBrackets: true,
							        mode: "application/x-httpd-php",
							        indentUnit: 4,
							        indentWithTabs: true,
							        smartIndent: true,
							        styleActiveLine: true,
							        autoCloseBrackets: true,
							        autoCloseTags: true,
							        viewportMargin: Infinity
							      });
				}
				if(which=="python"){
					var editor = CodeMirror.fromTextArea(document.getElementById("tfcodeDesc"), {
							        lineNumbers: true,
							        matchBrackets: true,
							        mode: "text/x-python",
							        indentUnit: 4,
							        indentWithTabs: true,
							        smartIndent: true,
							        styleActiveLine: true,
							        autoCloseBrackets: true,
							        autoCloseTags: true,
							        viewportMargin: Infinity
							      });
					var editor = CodeMirror.fromTextArea(document.getElementById("mccodeDesc"), {
							        lineNumbers: true,
							        matchBrackets: true,
							        mode: "text/x-python",
							        indentUnit: 4,
							        indentWithTabs: true,
							        smartIndent: true,
							        styleActiveLine: true,
							        autoCloseBrackets: true,
							        autoCloseTags: true,
							        viewportMargin: Infinity
							      });
				}
				if(which=="ruby"){
					var editor = CodeMirror.fromTextArea(document.getElementById("tfcodeDesc"), {
							        lineNumbers: true,
							        matchBrackets: true,
							        mode: "text/x-ruby",
							        indentUnit: 4,
							        indentWithTabs: true,
							        smartIndent: true,
							        styleActiveLine: true,
							        autoCloseBrackets: true,
							        autoCloseTags: true,
							        viewportMargin: Infinity
							      });
					var editor = CodeMirror.fromTextArea(document.getElementById("mccodeDesc"), {
							        lineNumbers: true,
							        matchBrackets: true,
							        mode: "text/x-ruby",
							        indentUnit: 4,
							        indentWithTabs: true,
							        smartIndent: true,
							        styleActiveLine: true,
							        autoCloseBrackets: true,
							        autoCloseTags: true,
							        viewportMargin: Infinity
							      });
				}
				if(which=="sass"){
					var editor = CodeMirror.fromTextArea(document.getElementById("tfcodeDesc"), {
							        lineNumbers: true,
							        matchBrackets: true,
							        mode: "text/x-sass",
							        indentUnit: 4,
							        indentWithTabs: true,
							        smartIndent: true,
							        styleActiveLine: true,
							        autoCloseBrackets: true,
							        autoCloseTags: true,
							        viewportMargin: Infinity
							      });
					var editor = CodeMirror.fromTextArea(document.getElementById("mccodeDesc"), {
							        lineNumbers: true,
							        matchBrackets: true,
							        mode: "text/x-sass",
							        indentUnit: 4,
							        indentWithTabs: true,
							        smartIndent: true,
							        styleActiveLine: true,
							        autoCloseBrackets: true,
							        autoCloseTags: true,
							        viewportMargin: Infinity
							      });
				}
				if(which=="scala"){
					var editor = CodeMirror.fromTextArea(document.getElementById("tfcodeDesc"), {
							        lineNumbers: true,
							        matchBrackets: true,
							        mode: "text/x-scala",
							        indentUnit: 4,
							        indentWithTabs: true,
							        smartIndent: true,
							        styleActiveLine: true,
							        autoCloseBrackets: true,
							        autoCloseTags: true,
							        viewportMargin: Infinity
							      });
					var editor = CodeMirror.fromTextArea(document.getElementById("mccodeDesc"), {
							        lineNumbers: true,
							        matchBrackets: true,
							        mode: "text/x-scala",
							        indentUnit: 4,
							        indentWithTabs: true,
							        smartIndent: true,
							        styleActiveLine: true,
							        autoCloseBrackets: true,
							        autoCloseTags: true,
							        viewportMargin: Infinity
							      });
				}
				if(which=="shell"){
					var editor = CodeMirror.fromTextArea(document.getElementById("tfcodeDesc"), {
							        lineNumbers: true,
							        matchBrackets: true,
							        mode: "text/x-sh",
							        indentUnit: 4,
							        indentWithTabs: true,
							        smartIndent: true,
							        styleActiveLine: true,
							        autoCloseBrackets: true,
							        autoCloseTags: true,
							        viewportMargin: Infinity
							      });
					var editor = CodeMirror.fromTextArea(document.getElementById("mccodeDesc"), {
							        lineNumbers: true,
							        matchBrackets: true,
							        mode: "text/x-sh",
							        indentUnit: 4,
							        indentWithTabs: true,
							        smartIndent: true,
							        styleActiveLine: true,
							        autoCloseBrackets: true,
							        autoCloseTags: true,
							        viewportMargin: Infinity
							      });
				}
				if(which=="sql"){
					var editor = CodeMirror.fromTextArea(document.getElementById("tfcodeDesc"), {
							        lineNumbers: true,
							        matchBrackets: true,
							        mode: "text/x-sql",
							        indentUnit: 4,
							        indentWithTabs: true,
							        smartIndent: true,
							        styleActiveLine: true,
							        autoCloseBrackets: true,
							        autoCloseTags: true,
							        viewportMargin: Infinity
							      });
					var editor = CodeMirror.fromTextArea(document.getElementById("mccodeDesc"), {
							        lineNumbers: true,
							        matchBrackets: true,
							        mode: "text/x-sql",
							        indentUnit: 4,
							        indentWithTabs: true,
							        smartIndent: true,
							        styleActiveLine: true,
							        autoCloseBrackets: true,
							        autoCloseTags: true,
							        viewportMargin: Infinity
							      });
				}
				if(which=="vbnet"){
					var editor = CodeMirror.fromTextArea(document.getElementById("tfcodeDesc"), {
							        lineNumbers: true,
							        matchBrackets: true,
							        mode: "text/x-vb",
							        indentUnit: 4,
							        indentWithTabs: true,
							        smartIndent: true,
							        styleActiveLine: true,
							        autoCloseBrackets: true,
							        autoCloseTags: true,
							        viewportMargin: Infinity
							      });
					var editor = CodeMirror.fromTextArea(document.getElementById("mccodeDesc"), {
							        lineNumbers: true,
							        matchBrackets: true,
							        mode: "text/x-vb",
							        indentUnit: 4,
							        indentWithTabs: true,
							        smartIndent: true,
							        styleActiveLine: true,
							        autoCloseBrackets: true,
							        autoCloseTags: true,
							        viewportMargin: Infinity
							      });
				}
				if(which=="html"){
					var editor = CodeMirror.fromTextArea(document.getElementById("tfcodeDesc"), {
							        lineNumbers: true,
							        matchBrackets: true,
							        mode: "text/x-html",
							        indentUnit: 4,
							        indentWithTabs: true,
							        smartIndent: true,
							        styleActiveLine: true,
							        autoCloseBrackets: true,
							        autoCloseTags: true,
							        viewportMargin: Infinity
							      });
					var editor = CodeMirror.fromTextArea(document.getElementById("mccodeDesc"), {
							        lineNumbers: true,
							        matchBrackets: true,
							        mode: "text/x-html",
							        indentUnit: 4,
							        indentWithTabs: true,
							        smartIndent: true,
							        styleActiveLine: true,
							        autoCloseBrackets: true,
							        autoCloseTags: true,
							        viewportMargin: Infinity
							      });
				}
				


			}





