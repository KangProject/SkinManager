<?php
	if (self::isLogged())
		self::redirect(WEBSITE_ROOT);
?>
<section>
	<h2><?= Language::translate('REGISTER') ?></h2>

	<form action="json/" id="form_register" method="post" class="shell form-vertical large">
		<label><?= Language::translate('USERNAME') ?></label>
		<input type="text" name="username" placeholder="<?= Language::translate('USERNAME') ?>" value=""
		       maxlength="255">
		<label><?= Language::translate('PASSWORD') ?></label>
		<input type="password" name="password" placeholder="<?= Language::translate('PASSWORD') ?>">
		<label><?= Language::translate('CONF') ?></label>
		<input type="password" name="password_conf" placeholder="<?= Language::translate('PASSWORD') ?>">
		<label><?= Language::translate('EMAIL') ?></label>
		<input type="text" name="email" placeholder="<?= Language::translate('EMAIL') ?>" maxlength="255">
		<label><?= Language::translate('CONF') ?></label>
		<input type="text" name="email_conf" placeholder="<?= Language::translate('EMAIL') ?>" maxlength="255">
		<input type="hidden" name="method" value="register">
		<input type="submit" value="<?= Language::translate('REGISTER') ?>">

		<p></p>
	</form>
</section>

<script>
	(function () {
		"use strict";
		var form_register = new FormHandler(document.getElementById('form_register'));
		form_register.displayer = $("#form_register p")[0];

		form_register.onSuccess = function () {
			location.reload();
		}
	})();
</script>