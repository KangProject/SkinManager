<?php

	namespace EphysCMS;

	if (self::isLogged()) {
		if (isset($_URL['skin']) && is_numeric($_URL['skin'])) {
			$bdd   = Database::getInstance();
			$query = $bdd->prepare('SELECT * FROM `skins` WHERE `owner` = :owner AND `id` = :id');

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
					<h2>Skin Editor</h2>

					<div id="skinEditor" class="shell">
						<div id="toolbar">
							<input type="text" id="colorpicker">

							<div class="group">
								<button id="pen" class="btn-blue"><?= Language::translate('TOOL_PEN') ?></button>
								<button id="pipette"><?= Language::translate('TOOL_PIPETTE') ?></button>
								<button id="eraser"><?= Language::translate('TOOL_ERASER') ?></button>
							</div>
							<div class="group">
								<button id="reset" class="btn-red"><?= Language::translate('TOOL_RESET') ?></button>
								<button id="undo"><?= Language::translate('TOOL_UNDO') ?></button>
								<button id="redo"><?= Language::translate('TOOL_REDO') ?></button>
							</div>
							<div class="group">
								<button id="rules"><?= Language::translate('TOOL_RULES') ?></button>
								<button id="save" class="btn-blue"><?= Language::translate('SAVE') ?></button>
							</div>
						</div>
						<canvas id="canvas"><?= Language::translate('ERROR_CANVAS') ?></canvas>
						<div id="toolbar">
							<div class="group">
								<button id="texture"
								        class="btn-blue"><?= Language::translate('TOOL_TEXTURE') ?></button>
								<input id="texture_range" type="range" min="0" max="30" value="10">
							</div>
							<div class="group">
								<button class="btn-blue disabled"><?= Language::translate('TOOL_CURSOR') ?></button>
								<input id="cursor_size" type="range" min="1" max="10" value="1">
							</div>
							<div class="group">
								<button id="copy"><?= Language::translate('TOOL_COPY') ?></button>
								<button id="paste"><?= Language::translate('TOOL_PASTE') ?></button>
							</div>
						</div>
					</div>
				</section>
				<script src="assets/js/plugins/spectrum/spectrum.js"></script>
				<script src="assets/js/SkinEditor.class.js"></script>
				<script>
					(function () {
						"use strict";
						var link = document.createElement('link');
						link.rel = 'stylesheet';
						link.type = 'text/css';
						link.href = 'assets/js/plugins/spectrum/spectrum.css';
						link.media = 'all';
						document.head.appendChild(link);

						var editor;

						var skin = new Image();
						skin.src = 'json/?method=getSkin&id=<?=$_URL['skin']?>';
						skin.onload = function () {
							var tools = {
								'colorpicker': document.getElementById('colorpicker'),
								'pen': document.getElementById('pen'),
								'pipette': document.getElementById('pipette'),
								'eraser': document.getElementById('eraser'),
								'copy': document.getElementById('copy'),
								'paste': document.getElementById('paste'),
								'rules': document.getElementById('rules'),
								'undo': document.getElementById('undo'),
								'redo': document.getElementById('redo'),
								'reset': document.getElementById('reset'),
								'texture_effect': document.getElementById('texture'),
								'texture_range': document.getElementById('texture_range'),
								'cusor_size': document.getElementById('cursor_size')
							};

							editor = new Editor(this, 10, document.getElementById('canvas'), tools);
							var saveBtn = document.getElementById('save');
							saveBtn.addEventListener('click', function (e) {
								e.preventDefault();
								$(this).button('loading');

								$.post('json/', {
									method: 'updateSkin',
									image_b64: editor.exportSkin(),
									id: <?=$_URL['skin']?>
								}, function (data) {
									try {
										var json = $.parseJSON(data);
										if (json.error === true)
											notifier.notify(json[0]);
										else
											notifier.notify(_LANGUAGE['SAVE_COMPLETED']);
									} catch (e) {
										console.log(e, data);
									}

									$(saveBtn).button('reset');
								});
							}, false);
						};
					})();
				</script>
			<?php
			}
		} else {
			self::redirect(WEBSITE_ROOT . '404/');
		}
	} else {
		self::redirect(WEBSITE_ROOT);
	}
