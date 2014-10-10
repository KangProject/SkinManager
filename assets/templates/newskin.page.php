<?php if (self::isLogged()) { ?>
	<section>
		<h2><?= Language::translate('SKIN_ADD') ?></h2>

		<form action="json/" method="post" class="shell" id="newSkinForm">
			<canvas class="skin_preview right" id="preview"></canvas>
			<input type="hidden" name="method" value="addSkin">
			<label for="skinName"><?= Language::translate('SKIN_NAME') ?></label>
			<input id="skinName" name="name" type="text" placeholder="<?= Language::translate('SKIN_NAME') ?>"/>
			<label for="skinDesc"><?= Language::translate('SKIN_DESCRIPTION') ?></label>
			<input id="skinDesc" name="description" type="text"
			       placeholder="<?= Language::translate('SKIN_DESCRIPTION') ?>"/>

			<label for="skinModel"><?= Language::translate('SKIN_MODEL') ?></label>
			<div class="input-group">
				<input id="skinModelSteve" name="model" type="radio" value="steve" checked>
				<label for="skinModelSteve"><?= Language::translate('SKIN_MODEL_STEVE') ?></label>
			</div>
			<div class="input-group">
				<input id="skinModelAlex" name="model" type="radio" value="alex">
				<label for="skinModelAlex"><?= Language::translate('SKIN_MODEL_ALEX') ?></label>
			</div>

			<h3><?= Language::translate('SKIN_LOAD') ?></h3>
			<label for="skinURL"><?= Language::translate('SKIN_LOAD_URL') ?></label>
			<input id="skinURL" name="url" type="text" placeholder="http://example.com/skin.png">
			<button class="btn-blue" id="btn_submitSkin" type="submit"><?= Language::translate('SKIN_LOAD_SUBMIT') ?></button>

			<div id="uploadFile">
				<label class="control-label" for="skinFile"><?= Language::translate('SKIN_LOAD_UPLOAD') ?></label>
				<div id="skinFile"></div>
			</div>
		</form>
	</section>
	<script src="assets/js/plugins/fineuploader/fineuploader-3.6.4.min.js"></script>
	<script>

		(function () {
			"use strict";

			var skin_preview = new SkinRender('preview', "assets/img/char.png", 3, true, undefined, <?= ($_SESSION['force2D'] ? 'false' : 'true') ?>);
			var newSkinForm = new FormHandler(document.getElementById('newSkinForm'));

			newSkinForm.displayer = function (message) {
				if (typeof(message) === 'array' || typeof(message) === 'object') {
					notificater.notify(message.join('<br>'));
				} else {
					notificater.notify(message);
				}
			};

			newSkinForm.onSuccess = function (data) {
				window.skinBar.addSkin({id: data.id, title: $("#skinName").val(), description: $("#skinDesc").val()});
				window.skinBar.showSkinList();

				$("form").trigger('reset');
				$("#uploadFile").show();

				notificater.hide();
			};

			newSkinForm.inputs.url.keyup = function (value) {
				if (value !== '') {
					previewSkin();
					$("#uploadFile").hide();
				} else {
					$("#uploadFile").show();
				}
			};

			function previewSkin() {
				var url, skinUrlField = $("#skinURL");

				if (skinUrlField.val() !== "") {
					url = skinUrlField.val();
				} else {
					notificater.notify(_LANGUAGE['ERROR_NO_URL']);
					return false;
				}

				skin_preview.updateTexture("json/?method=getPreview&url=" + encodeURIComponent(url));
				return false;
			}

			// =================================
			//  Skin upload form
			// =================================
			window.onload = function () {
				var uploader = new qq.FineUploader({
					debug: false,
					element: document.getElementById('skinFile'),
					request: {
						endpoint: 'json/'
					},
					validation: {
						allowedExtensions: ['png'],
						sizeLimit: 102400
					},
					callbacks: {
						onSubmit: function (id, filename) {
							uploader.setParams({
								method: 'uploadSkin',
								name: $("#skinName").val(),
								description: $("#skinDesc").val(),
								model: $('input[name=model]:checked').val()
							});
						},
						onComplete: function (id, name, response) {
							if (response.error === true) {
								notificater.notify(response[0]);
							} else {
								window.skinBar.addSkin({
									id: response.id,
									title: $("#skinName").val(),
									description: $("#skinDesc").val()
								});

								$("form").trigger('reset');
								$("#uploadFile").show();

								window.skinBar.showSkinList();
								notificater.hide();
							}
						}
					}
				});
			};
		})();

	</script>
<?php
} else {
	self::redirect(WEBSITE_ROOT);
}
?>