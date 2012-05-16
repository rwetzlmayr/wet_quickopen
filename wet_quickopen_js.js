/**
 * wet_quickopen: Open recent (and not so recent) articles quickly.
 *
 * @author Robert Wetzlmayr
 * @link http://awasteofwords.com/software/wet_quickopen-textpattern-plugin
 * @version 0.5
 */

var wet_quickopen = {
 	rows: 10,
	sortdir: 'desc',
	crit: 'lastmod',
	search: '',

	// the worker function refreshes the list of matching articles and inserts the result into the "recent articles" list
	refresh: function() {
		var box = $('ul.recent');
		$.ajax( {
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
				success: function(xml){
		    		// paint the article list
		    		var list = "";
					// parse the XML response
					$("article", xml).each(function(i) {
                        // paint one article row
                        list += "<li class='recent-article'>" +
                                "<a href='?event=article&amp;step=edit&amp;ID="+wet_quickopen.htmlspecialchars($('id', this).text())+"'>"+
                                wet_quickopen.htmlspecialchars($('title', this).text())+
                                "</a>" +
                                "</li>";
					});
					// inject list into "Recent Articles"
		    		box.html(list);
		 		},
		 		error: function(XMLHttpRequest, textStatus, errorThrown){
                     box.html('<strong>wet_quickopen: ' + textStatus + '</strong>');
                 }
		} );
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
