<?php
@include "vars.inc.php";
include "includes/function.inc.php";

isVarFile();
initSystem();

if(!isAllow()) {
	DISPLAY_CAPTCHA_FORM_EXIT();
}
?>