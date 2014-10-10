<?php

	namespace EphysCMS;

	if (self::isLogged()) {
		?>
		<section>
			<h2><?= Language::translate('ACCOUNT') ?></h2>

			<form action="json/" id="settingsForm" method="post" class="form-vertical shell large" autocomplete="off">
				<label><?= Language::translate('USERNAME') ?></label>
				<input type="text" name="username" value="<?= $_SESSION['username'] ?>"
				       placeholder="<?= Language::translate('USERNAME_ALPHANUMERIC') ?>">
				<label><?= Language::translate('EMAIL') ?></label>
				<input type="text" name="email" value="<?= $_SESSION['email'] ?>" placeholder="exemple@mail.net">
				<label><?= Language::translate('GRAPHMODE_SELECT') ?></label>
				<select name="force2D">
					<option
						value="0" <?= ($_SESSION['force2D'] ? '' : 'selected') ?>><?= Language::translate('GRAPHMODE_SELECT_3D') ?></option>
					<option
						value="1" <?= ($_SESSION['force2D'] ? 'selected' : '') ?>><?= Language::translate('GRAPHMODE_SELECT_2D') ?></option>
				</select>
				<label><?= Language::translate('SWITCHSKINMODE_SELECT') ?></label>
				<select name="skinmode">
					<option
						value="0" <?= ($_SESSION['minecraft_username'] === '' ? 'selected' : '') ?>><?= Language::translate('SWITCHSKINMODE_SELECT_OFFICAL') ?></option>
					<option
						value="1" <?= ($_SESSION['minecraft_username'] === '' ? '' : 'selected') ?>><?= Language::translate('SWITCHSKINMODE_SELECT_SKINSWITCH') ?></option>
				</select>

				<div <?= ($_SESSION['minecraft_username'] === '' ? 'style="display: none;"' : '') ?> id="mc_login">
					<label><?= Language::translate('MINECRAFT_LOGIN') ?></label>
					<input type="text" name="minecraft_username" value="<?= $_SESSION['minecraft_username'] ?>"
					       placeholder="<?= Language::translate('MINECRAFT_LOGIN_TIP') ?>">
					<label><?= Language::translate('MINECRAFT_PASSWORD') ?></label>
					<input type="password" name="minecraft_password" value=""
					       placeholder="<?= Language::translate('TIP_NOCHANGE') ?>">
					<label><?= Language::translate('MINECRAFT_PASSWORD_KEY') ?></label>
					<input type="password" name="minecraft_password_key" value=""
					       placeholder="<?= Language::translate('TIP_NOCHANGE') ?>">
				</div>
				<input type="submit">
				<input type="hidden" name="method" value="changeSettings">

				<p></p>
			</form>
			<form action="json/" id="form_recover" method="post" class="form-vertical large">
				<input id="form_recover_username" type="hidden" name="login" value="<?= $_SESSION['username'] ?>">
				<input type="submit" value="<?= Language::translate('PASSWORD_CHANGE') ?>">
				<input type="hidden" name="method" value="resetPassword">

				<p></p>
			</form>
			<p><a href="unregister/" class="btn-red" style="float: right;"><?= Language::translate('UNREGISTER') ?></a>
			</p>
			<script>
				var recover_form = new FormHandler(document.getElementById('form_recover'));
				recover_form.displayer = $("#form_recover p")[0];
				var settingsForm = new FormHandler(document.getElementById('settingsForm'));
				settingsForm.displayer = $("#settingsForm p")[0];

				settingsForm.inputs.skinmode.change = function (value) {
					if (value === '1') {
						$("#mc_login").slideDown();
					} else {
						$("#mc_login").slideUp();
						$("#settingsForm input[name=minecraft_username]").val('');
					}
				};

				settingsForm.onSuccess = function (value) {
					settingsForm.displayMessage(_LANGUAGE['SAVE_COMPLETED']);
				};
			</script>
		</section>
	<?php
	} else {
		self::redirect(WEBSITE_ROOT);
	}