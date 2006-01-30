TinyMCE Compressor 1.03
-------------------------

TinyMCE Compressor gzips all javascript files in TinyMCE to a single streamable file.
This makes the overall download sice 70% smaller and all requests are merged into a few requests.

To enable this compressor simply place the tiny_mce_gzip.php in the tiny_mce directory where tiny_mce.js is located and switch your scripts form:

<script language="javascript" type="text/javascript" src="tinymce/jscripts/tiny_mce/tiny_mce.js"></script>

to

<script language="javascript" type="text/javascript" src="tinymce/jscripts/tiny_mce/tiny_mce_gzip.php"></script>
