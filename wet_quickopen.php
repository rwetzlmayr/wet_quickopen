<?php
/* $LastChangedRevision: 147 $ */

$plugin['version'] = '0.4';
$plugin['author'] = 'Robert Wetzlmayr';
$plugin['author_uri'] = 'http://awasteofwords.com/software/wet_quickopen-textpattern-plugin';
$plugin['description'] = 'Open recent (and not so recent) articles quickly';
$plugin['type'] = 3;

if (!defined('txpinterface'))
	@include_once('zem_tpl.php');

if (0) {
?>
# --- BEGIN PLUGIN HELP ---

h3. Open recent (and not so recent) articles quickly

*wet_quickopen* is a plugin for Textpattern which prepends a search term input on top of the "Recent Articles" list at the article edit screen. The "Recent Articles" list is filtered by matching the current search term against all articles' titles and bodies.

h4. usage:

# Textpattern 4.3+ is required, wet_quickopen will _not_ work with any prior version.
# Install both @wet_quickopen@ and @wet_peex@ as a Textpattern plugin.
# Done. If all went well, the "content > write":./?event=article screen will be adorned by a new input box atop of the "Recent Articles" list.

h4. Licence and Disclaimer

This plug-in is released under the "Gnu General Public Licence":http://www.gnu.org/licenses/gpl.txt.

# --- END PLUGIN HELP ---

<?php
}

# --- BEGIN PLUGIN CODE ---

global $event;
if ($event == 'article') register_callback('wet_quickopen_form', 'article_ui', 'recent_articles');
register_callback('wet_quickopen_clutch', 'article');

// serve assorted resources
switch(gps('wet_rsrc')) {
	case 'quickopen_js':
		wet_quickopen_js();
		break;
	default:
		break;
}

/**
 * Insert a search box at the top of "Recent Articles".
 */
function wet_quickopen_form($event, $step, $default)
{
	return n.'<input class="edit" type="text" id="wet_quickopen_search" />'.n.$default;
}

/**
 * Pull in the JS worker file near the end of the page
 */
function wet_quickopen_clutch($event, $step)
{
	echo '<script src="./index.php?wet_rsrc=quickopen_js&amp;v=0.4" type="text/javascript"></script>'.n;
	require_plugin('wet_peex'); // won't help for loading wet_peex on time, but point out the lack of it to unwary users.
}

/**
 * Serve JS resource, as either an embedded resource or from a file while in development
 */
function wet_quickopen_js()
{
	$debug = false;
	while(@ob_end_clean());
	header("Content-Type: text/javascript; charset=utf-8");
	header("Expires: ".date("r", time() + ($debug ? -3600 : 3600)));
	header("Cache-Control: public");
	if ($debug) {
		readfile( dirname(__FILE__). '/wet_quickopen_js.js');
	} else {
		echo <<<JS
/*=*=*=* script goes here */
/**
 * wet_quickopen: Open recent (and not so recent) articles quickly.
 *
 * @author Robert Wetzlmayr
 * @link http://awasteofwords.com/software/wet_quickopen-textpattern-plugin
 */

var wet_quickopen = {
 	rows : 10,
	sortdir : "desc",
	crit : "lastmod",
	search : "",
	timeout : 2000,

	// the worker function refreshes the list of matching articles and inserts the result into the "recent articles" list
	refresh : function () {
		var box = $("ul.recent");
		$.ajax( {
		 		url : "",
		 		data : {wet_peex : "article",
						limit : wet_quickopen.rows.toString(),
						offset : "0",
						dir : wet_quickopen.sortdir,
						sort : wet_quickopen.crit,
						search : wet_quickopen.search
					},
				success : function(xml){
		    		// paint the article list
		    		var list = "";
					// parse the XML response
					$("article", xml).each (
						function(i) {
			    			// paint one article row
			    			list += "<li class='recent-article'>" +
			    					"<a href='?event=article&amp;step=edit&amp;ID="+wet_quickopen.htmlspecialchars($("id", this).text())+"'>"+
			    					wet_quickopen.htmlspecialchars($("title", this).text())+
			    					"</a>" +
			    					"</li>";
						}
					);
					// inject list into "Recent Articles"
		    		box.html(list);
		 		},
		 		timeout : wet_quickopen.timeout,
		 		error : function(XMLHttpRequest, textStatus, errorThrown){box.html("<strong>wet_quickopen: "+textStatus+"</strong>");}
		} );
		return; // dead end
		$.get(
	 		"",
	 		{ wet_peex: "article", limit: wet_quickopen.rows.toString(), offset: '0',
				dir: wet_quickopen.sortdir, sort: wet_quickopen.crit, search: wet_quickopen.search },
			function(xml){
	    		// paint the article list
	    		var list = "<ul class='plain-list'>";
				// parse the XML response
				$("article", xml).each (
					function(i) {
		    			// paint one article row
		    			list += "<li>" +
		    					"<a href='?event=article&amp;step=edit&amp;ID="+wet_quickopen.htmlspecialchars($("id", this).text())+"'>"+
		    					wet_quickopen.htmlspecialchars($("title", this).text())+
		    					"</a>" +
		    					"</li>";
					}
				);
				list += "</ul>";
				// inject list into "Recent Articles"
	    		box.html(list);
	 		}
		);
	},

	// add behaviours
	behaviours : function() {
		var i = $('input#wet_quickopen_search');
		// User hit <enter>: submit query immediately
		i.keypress(
			function(event) {
				if (event.keyCode == '13') {
					event.preventDefault();
					wet_quickopen.refresh();
				}
			}
		);
		// User hit any other key: submit query after a little timeout to reduce network traffic
		i.keyup(
			function() {
				if (this.value != wet_quickopen.search) {
					wet_quickopen.search = this.value;
					try {
						window.clearTimeout(this.lazy);
					} catch(e) {}
					this.lazy = window.setTimeout(wet_quickopen.refresh, 750);
				}
			}
		);
	},

	htmlspecialchars : function (s) {
		s = s.replace(/&/g,"&amp;");
		s = s.replace(/</g,"&lt;");
		s = s.replace(/>/g,"&gt;");
		s = s.replace(/"/g,"&quot;");
		return s;
	}
};

$(document).ready( function(){wet_quickopen.behaviours();} );
/*=*=*=* script ends here */
JS;
	}
	exit();
}

# --- END PLUGIN CODE ---

?>