<?php

	namespace EphysCMS;

	if (self::isLogged()) {
		if (isset($_URL['skin']) && is_numeric($_URL['skin'])) {
			$bdd   = Database::getInstance();
			$query = $bdd->prepare('SELECT * FROM `sm_skins` WHERE `owner` = :owner AND `id` = :id');
			$query->bindParam(':owner', $_SESSION['user_id'], \PDO::PARAM_INT);
			$query->bindParam(':id', $_URL['skin'], \PDO::PARAM_INT);
			$query->execute();
			$skin = $query->fetch(\PDO::FETCH_ASSOC);
			$query->closeCursor();

			if ($skin === false) {
				self::redirect(WEBSITE_ROOT . '404/');
			} else {
				?>
				<section>
					<h2><?= Language::translate('EDIT') ?> <span id="title"><?= $skin['title'] ?></span></h2>

					<form action="json/" method="post" class="shell" id="editForm">
						<label for="skinName"><?= Language::translate('SKIN_NAME') ?></label>
						<input id="skinName" name="title" type="text"
						       placeholder="<?= Language::translate('SKIN_NAME') ?>" value="<?= $skin['title'] ?>">
						<!-- <button class="btn-white" id="btn_editSkin"><?= Language::translate('SKIN_EDIT_GRAPHICAL') ?></button> -->

						<label for="skinDesc"><?= Language::translate('SKIN_DESCRIPTION') ?></label>
						<input id="skinDesc" name="description" type="text"
						       placeholder="<?= Language::translate('SKIN_DESCRIPTION') ?>"
						       value="<?= $skin['description'] ?>">

						<label for="skinModel"><?= Language::translate('SKIN_MODEL') ?></label>

						<div class="input-group">
							<input id="skinModelSteve" name="model" type="radio"
							       value="steve" <?= $skin['model'] == 'steve' ? 'checked' : '' ?>>
							<label for="skinModelSteve">Steve (normal)</label>
						</div>
						<div class="input-group">
							<input id="skinModelAlex" name="model" type="radio"
							       value="alex" <?= $skin['model'] == 'alex' ? 'checked' : '' ?>>
							<label for="skinModelAlex">Alex (slim arms)</label>
						</div>

						<input type="hidden" name="method" value="editSkin">
						<input type="hidden" name="id" value="<?= $_URL['skin'] ?>">
						<input type="submit" value="<?= Language::translate('SKIN_EDIT') ?>">
					</form>
				</section>
				<script type="text/javascript">

					var editForm = new FormHandler(document.getElementById('editForm'));
					editForm.displayer = function (message) {
						if (typeof(message) === 'array' || typeof(message) === 'object')
							notifier.notify(message.join('<br>'));
						else
							notifier.notify(message);
					};

					editForm.onSuccess = function (data) {
						notifier.notify(_LANGUAGE['SAVE_COMPLETED']);
						window.skinBar.updateSkin({
							id: <?=$_URL['skin']?>,
							title: $("#skinName").val(),
							description: $("#skinDesc").val()
						});
					};

					editForm.inputs.name.keyup = function (value) {
						$('#title').html(value);
					}

				</script>
			<?php
			}
		} else {
			self::redirect(WEBSITE_ROOT . '404/');
		}
	} else {
		self::redirect(WEBSITE_ROOT);
	}
