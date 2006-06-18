<?php

$props = <<<EOD
_prepend
_property
_compareProperty
_compareValue
EOD;

print_vars($props);
echo "\n";
print_funcs($props);

function print_vars($props)
{
	foreach(explode("\n", $props) as $prop)
	{
		echo "\tprivate \${$prop};\n";
	}
}

function print_funcs($props)
{
	foreach(explode("\n", $props) as $prop)
	{
		$name = ucfirst(str_replace('_', '', $prop));
		$getter = "\tpublic function get{$name}(){ return \$this->{$prop}; }\n";
		$setter = "\tpublic function set{$name}(\$value){ \$this->{$prop} = \$value; }\n";
		echo $getter.$setter."\n";
	}
}

?>