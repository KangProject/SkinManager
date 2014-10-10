<?php if (self::isLogged()) { ?>
	<section>
		<h2><?= Language::translate('PROFILES_BTN') ?></h2>

		<form action="javascript:void(0);" class="shell">
			<div class="fieldgroup">
				<label for="skinName"><?= Language::translate('SEARCH_USER') ?></label>
				<input id="skinName" type="text" placeholder="<?= Language::translate('FIND_USERNAME') ?>"
				       value="">
			</div>
			<div id="userList"></div>
		</form>
		<div id="userSkinList"></div>

		<script type="text/javascript">
			var searchTimeout = null;
			$("#skinName").keyup(function () {
				clearTimeout(searchTimeout);
				searchTimeout = setTimeout(searchUsers, 200);
			});

			var loadedUsers = {};
			function searchUsers() {
				$.ajax({
					url: 'json/',
					type: 'POST',
					data: {method: "loadUserList", match: $("#skinName").val()}
				}).fail(function () {
					notifier.notify(_LANGUAGE['ERROR_UNKNOW']);
				}).success(function (data) {
					var userList;
					try {
						userList = $.parseJSON(data);
					} catch (e) {
						console.log(e, data);
						userList = [];
					}

					var userListContainer = document.getElementById('userList');
					userListContainer.innerHTML = '';

					for (var i = 0; i < userList.length; i++) {
						var user_tag = document.createElement('p');
						user_tag.className = 'user_icon';

						var user_tag_link = document.createElement('a');
						user_tag_link.innerHTML = '<span>' + userList[i].username.trunc(13) + '</span>';
						user_tag_link.href = '';
						user_tag_link.user_id = userList[i].id;

						user_tag_link.addEventListener('click', function (e) {
							e.preventDefault();

							var userSkinList = document.getElementById('userSkinList');
							userSkinList.innerHTML = '';

							var user_skinmanager = new SkinManager(userSkinList, undefined, this.user_id, this.user_id == <?=$_SESSION['user_id']?>);
							user_skinmanager.switchMode = window.SkinManager.SWITCHMODE.<?=($_SESSION['minecraft_username'] === ''?'MCSKINSAPI':'SKINSWITCH')?>;
							user_skinmanager.display_3D = <?=($_SESSION['force2D']?'false':'true')?>;
						});

						user_tag.appendChild(user_tag_link);
						userListContainer.appendChild(user_tag);
					}
				});
			}
		</script>
	</section>
<?php
} else {
	self::redirect(WEBSITE_ROOT);
}
?>