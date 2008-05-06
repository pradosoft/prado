/**
 * Script that builds jGrouseDoc Index Page
 * Copyright (c) 2007 by Robert Kieffer and jGrouseDoc contributors 
 * $Id: jgindex.js 303 2007-12-24 22:52:30Z denis.riabtchik $
 */

var jgindex = {
  load: function() {
    // Sort data by localName
    jgindex.data.sort(function(a,b) {
      var c = (a.localName || a.fullName).toLowerCase();
      var d = (b.localName || b.fullName).toLowerCase();
      return c < d ? -1 : (c > d ? 1 : 0);
    });

    // Now render the index
    jgindex.renderEntries();
  },

  renderEntries: function() {
    var h = [];

    // Use a DL, since this is the most semantically correct structure
    h.push('<dl>');

    // Hash to track which letters have entries
    var letters = {};

    // Loop through each entry
    for (var i = 0; i < jgindex.data.length; i++) {
      var entry = jgindex.data[i];

      // Get name/url for the entry's namespace
      var srcName = entry.parent;
      var srcLink = entry.ref.replace(/#.*/, '');

      // Apply odd/even classname (makes styling even/odd rows easy)
      var cn = [(i % 2) ? 'odd' : 'even'];
      cn.push(/^(class|interface|struct|object)/.test(entry.summary) ? 'is_namespace' : 'is_not_namespace');

      // Get the entry's first letter
      var ln = entry.localName || entry.fullName || '_unnamed';
      var letter = ln.charAt(0).toUpperCase();

      //  ... and see if it's the first one for that letter
      if (!letters[letter]) {
        letters[letter] = true;
      } else {
        letter = null;
      }

      // ... and if it is, render the section header
      if (letter) {
        h.push('<h3 class="letter_section"><a name="' + letter + '">' + letter + '</a></h3>');
      }

      // Render the entry's HTML
      cn = cn.join(' ');
      h.push(
        '<dt title="' + entry.summary + '" class="' + cn + '">' +
          '<a href="' + entry.ref + '">' + ln + '</a>' +
        '</dt>' +
        '<dd class="' + cn + '">' +
          '<a href="' + srcLink + '">' + srcName + '</a>' +
        '</dd>'
      );
    }
    h.push('</dl>');

    // Stick it all into the element
    document.getElementById('index').innerHTML = h.join('\n');

    // Render the letters table-of-contents at the top
    h = [];
    var toc = '$_ABCDEFGHIJKLMNOPQRSTUVWXYZ';
    for (var i = 0; i < toc.length; i++) {
      var letter = toc.charAt(i);
      h.push(letters[letter] ?
        '<span class="has_entries"><a href="#' + letter + '">' + letter + '</a></span>' :
        '<span class="no_entries">' + letter + '</span>'
      );
    }
    document.getElementById('toc').innerHTML = h.join('\n');
  }
}


// Hack so we can get access to the index data
var jgdoc = {
  Searcher: {
    setData: function(data) {
      jgindex.data = data;
      jgindex.load();
    }
  }
}
