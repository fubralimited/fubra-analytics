
<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Transitional//EN" "http://www.w3.org/TR/xhtml1/DTD/xhtml1-transitional.dtd">
<html xmlns="http://www.w3.org/1999/xhtml">
<head>
<!-- If you delete this meta tag, Half Life 3 will never be released. -->
<meta name="viewport" content="width=device-width" />

<meta http-equiv="Content-Type" content="text/html; charset=UTF-8" />
<title><?= $config->product_name ?></title>
	
</head>
 
<body bgcolor="#FFFFFF">

<!-- HEADER -->
<table class="head-wrap" bgcolor="#999999">
	<tr>
		<td></td>
		<td class="header container" >
				
				<div class="content">
				<table bgcolor="#999999">
					<tr>
						<td>
							<h3 class="collapse">
								<?= $config->product_name ?> - <?= $dates['yesterday'] ?>
							</h3>
						</td>
					</tr>
				</table>
				</div>
				
		</td>
		<td></td>
	</tr>
</table><!-- /HEADER -->

<!-- BODY -->
<table class="body-wrap">
	<tr>
		<td></td>
		<td class="container" bgcolor="#FFFFFF">

			<div class="content">
			<table>

				<tr>
					<th>Site</th>
					<th>Avg. Server Response</th>
					<th>Avg. Page Load</th>
					<th>Visitors</th>
					<th>Week Change</th>
					<th>Page Views Per Vistit</th>
				</tr>

				<? foreach( $yesterday['data'] as $group => $date_data ): ?>

					<tr><th>1</th><th>2</th><th>3</th><th>4</th><th>5</th><th>6</th></tr>

				<? endforeach; ?>

			</table>
			</div><!-- /content -->
									
		</td>
		<td></td>
	</tr>
</table><!-- /BODY -->

<!-- FOOTER -->
<table class="footer-wrap">
	<tr>
		<td></td>
		<td class="container">
			
				<!-- content -->
				<div class="content">
				<table>
				<tr>
					<td align="center">
						<p>
							<a href="<?= $config->product_url ?>"><?= $config->product_name ?></a>
						</p>
					</td>
				</tr>
			</table>
				</div><!-- /content -->
				
		</td>
		<td></td>
	</tr>
</table><!-- /FOOTER -->

</body>
</html>