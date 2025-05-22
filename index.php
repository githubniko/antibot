<?php
/*
 * @author EgorNiKO <niko_egor@mail.ru>
 * @repository https://github.com/githubniko/antibot
 *
 * @copyright Copyright (c) 2025, EgorNiKO. All rights reserved.
 * @license MIT License
 */

@include "vars.inc.php";
include "includes/function.inc.php";

isVarFile();
initSystem();
	
if(!isAllow()) {
	if($AB_IS_404)
		header("HTTP/1.0 404 Not Found");
	
	DISPLAY_CAPTCHA_FORM_EXIT();
}
?>