<?php
/* $LastChangedRevision: 147 $ */

$plugin['version'] = '1.1';
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

# Textpattern 4.5+ is required.
# Install both @wet_quickopen@ and @wet_peex@ as a Textpattern plugin.
# Done. If all went well, the "Content > Write":./?event=article screen will be adorned by a new input box atop of the "Recent Articles" list.

h4. Licence and Disclaimer

This plug-in is released under the Gnu General Public Licence.

# --- END PLUGIN HELP ---

<?php
}

# --- BEGIN PLUGIN CODE ---

// serve assorted resources
switch(gps('wet_rsrc')) {
	case 'quickopen_js':
		wet_quickopen_js();
		break;
	default:
		break;
}

global $app_mode;
if ($app_mode != 'async') {
	/**
	 * Insert a search box at the top of "Recent Articles".
	 */
	register_callback('wet_quickopen_form', 'article_ui', 'recent_articles');
	function wet_quickopen_form($event, $step, $default)
	{
		return n.fInput('text', 'wet_quickopen_search', '', 'edit', '', '', INPUT_REGULAR, 0, 'wet_quickopen_search')/*.'<input size="INPUT_REGULAR" class="edit" type="text" id="wet_quickopen_search" />'*/.n.$default;
	}

	/**
	 * Pull in the JS worker file near the end of the page
	 */
	register_callback('wet_quickopen_jslink', 'article');
	function wet_quickopen_jslink($event, $step)
	{
		echo '<script src="?wet_rsrc=quickopen_js" type="text/javascript"></script>'.n;
		require_plugin('wet_peex'); // won't help for loading wet_peex on time, but point out the lack of it to unwary users.
	}

	/**
	 * Pull in additional styles at the end of the HEAD
	 */
	register_callback('wet_quickopen_style', 'admin_side', 'head_end');
	function wet_quickopen_style($event, $step)
	{
		echo n.'<style type="text/css">div#recent{padding-top:1em;}</style>'.n;
	}
}

/**
 * Serve embedded JS resource
 */
function wet_quickopen_js()
{
	while(@ob_end_clean());
	header("Content-Type: text/javascript; charset=utf-8");
	header("Expires: ".date("r", time() + 3600));
	header("Cache-Control: public");
	$rows = (defined('WRITE_RECENT_ARTICLES_COUNT') ? WRITE_RECENT_ARTICLES_COUNT : 10);
	echo <<<JS
/**
 * wet_quickopen: Open recent (and not so recent) articles quickly
 *
 * @author Robert Wetzlmayr
 * @link http://awasteofwords.com/software/wet_quickopen-textpattern-plugin
 */
var wet_quickopen = {
	rows: {$rows},
	sortdir: 'desc',
	crit: 'lastmod',
	search: '',

	// the worker function refreshes the list of matching articles and inserts the result into the "recent articles" list
	refresh: function() {
		var box = $('ul.recent');
		$.ajax({
			url: '',
			data: {
				'wet_peex': 'article',
				'limit': wet_quickopen.rows.toString(),
				'offset': '0',
				'dir': wet_quickopen.sortdir,
				'sort': wet_quickopen.crit,
				'search': wet_quickopen.search
			},
			dataType: 'xml',
			success: function(xml) {
				// paint the article list
				var list = $('<ul/>');
				// parse the XML response
				$('article', xml).each(function(i) {
					var li = $('<li class="recent-article"/>');
					var href = '?event=article&step=edit&ID=' + $('id', this).text();
					var title = wet_quickopen.htmlspecialchars($('title', this).text());
					list.append(
						$('<dummy/>').append(  // jQuerish outerHTML()
							li.append(
								$('<a />').attr('href', href).html(title)
							)
						).html()
					);
				});
				// inject list into "Recent Articles"
				box.html(list.html());
			},
			error: function(XMLHttpRequest, textStatus, errorThrown) {
				 box.html('<strong>wet_quickopen: ' + textStatus + '</strong>');
			}
		});
		return;
	},

	// add behaviours
	behaviours: function() {
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

	htmlspecialchars: function (s) {
		return $('<p/>').text(s).html();
	}
};

$(document).ready( function(){wet_quickopen.behaviours();} );
JS;
	exit();
}

# --- END PLUGIN CODE ---

?>