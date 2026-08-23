/**
 * code-highlight.js - display-side syntax highlighting bootstrap (T6.1).
 * Replaces SyntaxHighlighter 3's brush parsing: questions keep storing
 * legacy `brush:` classes server-side, so this maps them to Prism language
 * ids before handing off to Prism.highlightAll().
 */
function highlightBrushBlocks(){
	var BRUSH_TO_PRISM = {
		applescript: 'applescript',
		actionscript3: 'actionscript',
		shell: 'shell',
		coldfusion: 'markup',
		csharp: 'csharp',
		cpp: 'cpp',
		css: 'css',
		delphi: 'pascal',
		diff: 'diff',
		erlang: 'erlang',
		groovy: 'groovy',
		js: 'javascript',
		java: 'java',
		javafx: 'java',
		perl: 'perl',
		php: 'php',
		powershell: 'powershell',
		python: 'python',
		ruby: 'ruby',
		sass: 'sass',
		scala: 'scala',
		sql: 'sql',
		vbnet: 'vbnet',
		html: 'markup'
		// 'plain' stays unmapped on purpose: legacy Plain brush rendered
		// unstyled text and Prism has no plain component either.
	};

	var blocks = document.querySelectorAll('pre[class*="brush"]');
	for (var i = 0; i < blocks.length; i++) {
		var pre = blocks[i];
		var match = /brush:\s*([\w#+.-]+)/i.exec(pre.className);
		if (!match) {
			continue;
		}
		var language = BRUSH_TO_PRISM[match[1]] || 'none';
		pre.className = pre.className.replace(
			/brush:\s*[\w#+.-]+;?/i,
			'language-' + language
		);

		//questions render bare <pre>text</pre>; Prism only tokenizes
		//<code> children, so wrap the text node client-side
		var codeEl = pre.querySelector('code');
		if (!codeEl) {
			codeEl = document.createElement('code');
			while (pre.firstChild) {
				codeEl.appendChild(pre.firstChild);
			}
			pre.appendChild(codeEl);
		}
		codeEl.className = 'language-' + language;
	}

	if (window.Prism && typeof Prism.highlightAll === 'function') {
		Prism.highlightAll();
	}
}
