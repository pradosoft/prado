<?php

class ClassDoc extends TPage
{
	public $Class;
	private $_classes;

	public function onLoad($param)
	{
		parent::onLoad($param);
		$dataFile=Prado::getPathOfNamespace('Application.Data.classes','.data');
		$this->_classes=unserialize(file_get_contents($dataFile));

		if(($className=$this->Request['class'])!==null && isset($this->_classes[$className]))
		{
			$this->Class=$this->_classes[$className];
			$this->Class['Name']=$className;
			$this->Title='PRADO - Documentation of '.$className;
		}
		else
			$this->Response->redirect('/docs/classdoc/');
	}

	public function getAncestors()
	{
		$ancestors=array();
		$thisClass=$this->Class;
		while(true)
		{
			$parentClass=$thisClass['ParentClass'];
			if(isset($this->_classes[$parentClass]))
			{
				$ancestors[]=$parentClass;
				$thisClass=$this->_classes[$parentClass];
			}
			else
				break;
		}
		$ancestors=array_reverse($ancestors);
		$s='';
		foreach($ancestors as $ancestor)
			$s.="<a href=\"$ancestor.html\">$ancestor</a> &raquo;\n";
		if($s!=='')
			$s="<div class=\"doc-ancestors\">\nInheritance: $s</div>\n";
		return $s;
	}

	public function getProperties()
	{
		$class=$this->Class;
		$className=$this->Class['Name'];
		$s='';
		foreach($class['Properties'] as $name=>$property)
		{
			$inherited=strcasecmp($property['class'],$className)!==0;
			$rowclass=$inherited?'doc-inherited':'doc-native';
			$s.="<tr class=\"$rowclass\">\n";
			$access='';
			if($property['readonly'])
				$access.='R';
			if($property['protected'])
				$access.='P';
			if($access==='')
				$access='&nbsp;';
			$s.="<td width=\"1\" nowrap=\"nowrap\" align=\"center\">$access</td>\n";

			if($inherited)
			{
				$parentClass=$property['class'];
				if(isset($this->_classes[$parentClass]))
				{
					$url="../manual/CHMdefaultConverter/{$this->_classes[$parentClass]['Package']}/{$parentClass}.html#methodget{$name}";
					$s.="<td><a href=\"$url\">$name</a></td>\n";
				}
				else
					$s.="<td>$name</td>\n";
			}
			else
			{
				$url="../manual/CHMdefaultConverter/{$class['Package']}/{$className}.html#methodget{$name}";
				$s.="<td><a href=\"$url\">$name</a></td>\n";
			}

			$type=$property['type'];
			if(isset($this->_classes[$type]))
			{
				$url="$type.html";
				$s.="<td><a href=\"$url\">$type</a></td>\n";
			}
			else
				$s.="<td>$type</td>\n";

			$comments=rtrim($property['comments'],'.').'.';
			if($inherited)
			{
				$parentClass=$property['class'];
				if(isset($this->_classes[$parentClass]))
				{
					$url="$parentClass.html";
					$comments.=" (inherited from <a href=\"$url\">$parentClass</a>)";
				}
				else
					$comments.=" (inherited from {$parentClass})";
			}
			$s.="<td>$comments</td>\n";
			$s.="</tr>\n";
		}

		$header="<tr>\n<th>&nbsp;</th><th>Name</th><th>Type</th><th>Description</th>\n</tr>\n";
		return $s===''?'':"<div class=\"doc-properties\">\n<table>\n$header$s</table>\n</div>\n";
	}

	public function getEvents()
	{
		$class=$this->Class;
		$className=$this->Class['Name'];
		$s='';
		foreach($class['Events'] as $name=>$event)
		{
			$inherited=strcasecmp($event['class'],$className)!==0;
			$rowclass=$inherited?'doc-inherited':'doc-native';
			$s.="<tr class=\"$rowclass\">\n";

			$methodName=$name;
			$methodName[0]='o';
			if($inherited)
			{
				$parentClass=$event['class'];
				if(isset($this->_classes[$parentClass]))
				{
					$url="../manual/CHMdefaultConverter/{$this->_classes[$parentClass]['Package']}/{$parentClass}.html#method{$methodName}";
					$s.="<td><a href=\"$url\">$name</a></td>\n";
				}
				else
					$s.="<td>$name</td>\n";
			}
			else
			{
				$url="../manual/CHMdefaultConverter/{$class['Package']}/{$className}.html#method{$methodName}";
				$s.="<td><a href=\"$url\">$name</a></td>\n";
			}

			$comments=rtrim($event['comments'],'.').'.';
			if($inherited)
			{
				$parentClass=$event['class'];
				if(isset($this->_classes[$parentClass]))
				{
					$url="$parentClass.html";
					$comments.=" (inherited from <a href=\"$url\">$parentClass</a>)";
				}
				else
					$comments.=" (inherited from {$parentClass})";
			}
			$s.="<td>$comments</td>\n";
			$s.="</tr>\n";
		}
		$header="<tr>\n<th>Name</th><th>Description</th>\n</tr>\n";
		return $s===''?'':"<div class=\"doc-events\">\n<table>\n$header$s</table>\n</div>\n";
	}

	public function getMethods()
	{
		$class=$this->Class;
		$className=$this->Class['Name'];
		$s='';
		foreach($class['Methods'] as $name=>$method)
		{
			$inherited=strcasecmp($method['class'],$className)!==0;
			$rowclass=$inherited?'doc-inherited':'doc-native';
			$s.="<tr class=\"$rowclass\">\n";
			$access='';
			if($method['static'])
				$access.='S';
			if($method['protected'])
				$access.='P';
			if($access==='')
				$access='&nbsp;';
			$s.="<td nowrap=\"nowrap\" width=\"1\" align=\"center\">$access</td>\n";

			if($inherited)
			{
				$parentClass=$method['class'];
				if(isset($this->_classes[$parentClass]))
				{
					$url="../manual/CHMdefaultConverter/{$this->_classes[$parentClass]['Package']}/{$parentClass}.html#method{$name}";
					$s.="<td><a href=\"$url\">$name</a></td>\n";
				}
				else
					$s.="<td>$name</td>\n";
			}
			else
			{
				$url="../manual/CHMdefaultConverter/{$class['Package']}/{$className}.html#method{$name}";
				$s.="<td><a href=\"$url\">$name</a></td>\n";
			}

			$comments=rtrim($method['comments'],'.').'.';
			if($inherited)
			{
				$parentClass=$method['class'];
				if(isset($this->_classes[$parentClass]))
				{
					$url="$parentClass.html";
					$comments.=" (inherited from <a href=\"$url\">$parentClass</a>)";
				}
				else
					$comments.=" (inherited from {$parentClass})";
			}
			$s.="<td>$comments</td>\n";
			$s.="</tr>\n";
		}
		$header="<tr>\n<th>&nbsp;</th><th>Name</th><th>Description</th>\n</tr>\n";
		return $s===''?'':"<div class=\"doc-methods\">\n<table>\n$header$s</table>\n</div>\n";
	}

	public function getDerived()
	{
		$class=$this->Class;
		$s='';
		foreach($class['ChildClasses'] as $childName)
			$s.="<li><a href=\"$childName.html\">$childName</a></li>\n";
		return $s===''?'':"<div class=\"doc-derived\">\n<ul>\n$s</ul>\n</div>\n";
	}

}

?>
