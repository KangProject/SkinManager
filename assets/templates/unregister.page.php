<?php if(self::isLogged()) { ?>
<section>
	<form action="json/" id="deleteForm" method="post" class="form-vertical shell large" autocomplete="off">
		<p><?=Language::translate('UNREGISTER_WARNING')?></p>
		<input type="submit" class="btn-red" value="<?=Language::translate('UNREGISTER')?>">
		<input type="hidden" name="method" value="unregister">
		<p></p>
	</form>
	<script>
		var deleteForm = new FormHandler(document.getElementById('deleteForm'));
		deleteForm.displayer = $("#deleteForm p")[0];

		deleteForm.onSuccess = function(value) {
			location.reload();
		};
	</script>
</section>
<?php } else {
	self::redirect(WEBSITE_ROOT);
} ?>