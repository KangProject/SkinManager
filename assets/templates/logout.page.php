<?php
session_destroy();
unset($_SESSION);
self::redirect(WEBSITE_ROOT);
?>