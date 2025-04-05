<?php
@include "vars.inc.php";
include "function.inc.php";

isVarFile();

if(!isAllow()) {
	eval(DISPLAY_CAPTCHA_FORM_EXIT);
}
?>