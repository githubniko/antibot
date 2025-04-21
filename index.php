<?php
@include "vars.inc.php";
include "includes/function.inc.php";

isVarFile();

if(!isAllow()) {
	DISPLAY_CAPTCHA_FORM_EXIT();
}
?>