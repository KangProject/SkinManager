<?php if(self::isLogged()) { ?>
	<section><?=Language::translate('ABOUT')?></section>
<?php } else { ?>
	<section>
			<?=Language::translate('INTRO')?>
			<form action="json/" class="form-vertical shell" id="loginForm">
				<input type="text" name="username" placeholder="<?=Language::translate('SKIN_LOAD_USERNAME')?>">
				<input type="password" name="password" placeholder="<?=Language::translate('LOGIN_PASSWORD')?>">
				<input type="submit" data-loading-text="<?=Language::translate('LOGIN_WAIT')?>" class="btn-blue" value="<?=Language::translate('LOGIN')?>">
				<input type="hidden" name="method" value="login">
				<p class="list_btn"><a href="register/" target"_blank" class="btn"><?=Language::translate('REGISTER')?></a></p>
			</form>
	</section>
	<script>
		(function() {
			"use strict";
			var loginForm = new FormHandler(document.getElementById('loginForm'));
			loginForm.displayer = $("#loginForm p")[0];

			loginForm.onSuccess = function() {
				location.reload();
			}
		})();
	</script>
<?php } ?>