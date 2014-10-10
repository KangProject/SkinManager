<?php namespace EphysCMS; ?>
<section>
	<div class="phpdoc">
		<h2>Skin Manager API
			<small>documentation</small>
		</h2>
		<h3>How to use the api ?</h3>

		<p>You need to send an http request (GET or POST) to <b>http://skin.outadoc.fr/json/</b> with the name of the
			method that you wish to call (param "method") and the params of that function (the name of the param) as
			args.</p>

		<p>Exemple: to call the method "loadUserList", I'll make one of the following request:
			<br>GET http://skin.outadoc.fr/json/?method=loadUserList&amp;match=Ephys
			<br>POST http://skin.outadoc.fr/json/?method=loadUserList&amp;match=Ephys</p>

		<?php
			require_once ROOT . 'assets/php/API.class.php';

			$class = new \ReflectionClass('EphysCMS\API');

			echo '<p>' . parsePHPDoc($class->getDocComment()) . '</p>';
			echo '<h3>Available Methods</h3>';

			foreach ($class->getMethods(\ReflectionMethod::IS_PUBLIC) as $method) {
				if (($phpdoc = $method->getDocComment()) !== false) {
					echo '<p class="method">' . (($method->isDeprecated()) ? '<b>DEPRECATED</b> ' : '') . 'method: <i>' . $method->name . '</i>';
					$optParams = '';
					foreach ($method->getParameters() as $param) {
						if ($param->isOptional())
							$optParams .= '<br>&nbsp;- ' . $param->getName() . ' <small>(' . var_export($param->getDefaultValue(), true) . ')</small>';
					}
					if ($optParams !== '')
						echo '<br>Optional parameters: ' . $optParams;

					echo '<br>' . parsePHPDoc($phpdoc) . '</p>';
				}
			}

			function parsePHPDoc($doc)
			{
				$doc = str_replace(' ', '&nbsp;', $doc);
				$doc = preg_replace("#@(\w*)#iSu", '<span class="tag">@$1</span>$2', $doc);
				$doc = preg_replace("#\\\$(\w*)#iSu", '<span class="var">\$$1</span>$2', $doc);
				$doc = preg_replace("#'(\w*)'#iSu", '\'<span class="var">$1</span>\'$2', $doc);
				$doc = nl2br($doc);

				return $doc;
			}

		?>
	</div>
</section>