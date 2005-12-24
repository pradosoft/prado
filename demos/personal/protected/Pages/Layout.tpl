<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Transitional//EN" "http://www.w3.org/TR/xhtml1/DTD/xhtml1-transitional.dtd">

<html xmlns="http://www.w3.org/1999/xhtml" xml:lang="en" >
<head>
<title>My Personal Website</title>
</head>
<body>
<com:TForm>
<div class="header">
  <h1>Your Name Here</h1>
  <h2>My Personal Site</h2>
  <div class="nav">
    <a href=<%=$this->Service->constructUrl('Home') %> >HOME</a> |
    <a href=<%=$this->Service->constructUrl('Resume') %> >RESUME</a> |
    <a href=<%=$this->Service->constructUrl('Links') %> >LINKS</a> |
    <a href=<%=$this->Service->constructUrl('Albums') %> >ALBUMS</a> |
    <a href=<%=$this->Service->constructUrl('UserLogin') %> >LOGIN</a>
  </div>
</div>
<div class="main">
<com:TContentPlaceHolder ID="main" />
</div>
<div class="footer">
  Copyright &copy; 2005 Your Name here.<br/>
  Powered by <a href="http://www.pradosoft.com/">PRADO</a>.
</div>
</com:TForm>
</body>
</html>