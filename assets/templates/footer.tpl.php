<section id="footer"><p>&copy; <a href="http://dlp.fr.nf/">DLProduction</a> 2012 - 2014, &copy; <a
			href="http://dev.outadoc.fr">outa[dev]</a> <?php echo date('Y'); ?> - <a href="api/">API</a> - <a
			href="http://itunes.apple.com/fr/app/skinswitch-for-minecraft/id524911308">Skinswitch (iOS)</a></p>
</section>
<footer>
	<a href="<?= PAGE_RELATIVE ?>l/en_EN"><img src="assets/img/flags/en_EN.png"/></a>
	<a href="<?= PAGE_RELATIVE ?>l/fr_FR"><img src="assets/img/flags/fr_FR.png"/></a>
</footer>
</div>
<?php if (self::isLogged()) { ?>
	<script>
		(function () {
			"use strict";
			window.notificater = new Notificater();

			if (window.SkinManager !== undefined) {
				window.skinBar = new SkinManager(document.getElementById("skinList"), document.getElementById('showSkins'), <?=$_SESSION['user_id']?>, true);
				window.skinBar.switchMode = window.SkinManager.SWITCHMODE.<?=($_SESSION['minecraft_username'] === ''?'MCSKINSAPI':'SKINSWITCH')?>;
				window.skinBar.display_3D = <?=($_SESSION['force2D']?'false':'true')?>;
			} else
				console.error("Can't create skinManager instance, window.skinManager does not exist");
		})();
	</script>
<?php } ?>
<script id="_waus84">var _wau = _wau || [];
	_wau.push(["map", "0bsrkdljoc8i", "s84", "420", "210", "natural", "star-blue"]);
	(function () {
		var s = document.createElement("script");
		s.async = true;
		s.src = "http://widgets.amung.us/map.js";
		document.getElementsByTagName("head")[0].appendChild(s);
	})();</script>
</body>
</html>