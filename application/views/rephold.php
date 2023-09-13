<?php
defined('BASEPATH') OR exit('No direct script access allowed');

$title = isset($title)? $title : 'Report';
$morehead = isset($morehead) ? $morehead : '';
?>
<!DOCTYPE html>
<html lang="en">
<head>
	<link rel="icon" href="<?= base_url('favicon.ico')?>" />
	<meta charset="utf-8">
	<meta name="viewport" content="width=device-width, initial-scale=1">
	<title><?= $title ?></title>
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/bulma/0.9.4/css/bulma.min.css" integrity="sha512-HqxHUkJM0SYcbvxUw5P60SzdOTy/QVwA1JJrvaXJv4q7lmbDZCmZaqz01UPOaQveoxfYRv1tHozWGPMcuTBuvQ==" crossorigin="anonymous" referrerpolicy="no-referrer" />
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.2/css/all.min.css" integrity="sha512-z3gLpd7yknf1YoNbCzqRKc4qyor8gaKU1qmn+CShxbuBusANI9QpRohGBreCFkKxLhei6S9CQXFEbbKuqLg0DA==" crossorigin="anonymous" referrerpolicy="no-referrer" />
<script src="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.2/js/all.min.js" integrity="sha512-uKQ39gEGiyUJl4AI6L+ekBdGKpGw4xJ55+xyJG7YFlJokPNYegn9KwQ3P8A7aFQAUtUsAQHep+d/lrGqrbPIDQ==" crossorigin="anonymous" referrerpolicy="no-referrer"></script>
<?= $morehead?>
<style>
	body { font-size: 10pt; }
</style>
</head>
<body>
<section class="section" style="padding-top: 10px;">

<div class="container">
	<h1 class="subtitle">Hold Report</h1>
	<p>&nbsp;</p>
<form id="frmSetup" action="<?= base_url('Reports/doHoldReport')?>" method="post">
	<div class="columns">
		<div class="column is-2">
			<div class="field">
				<label class="label" for="dtSta">Start Date</label>
				<div class="control">
					<input class="input" type="date" placeholder="mm/dd/yyyy" name="dtSta" value="<?=$dtSta?>" />
				</div>
			</div>
		</div>
		<div class="column is-2">
			<div class="field">
				<label class="label" for="dtEnd">End Date</label>
				<div class="control">
					<input class="input" type="date" placeholder="mm/dd/yyyy" name="dtEnd" value="<?=$dtEnd?>" />
				</div>
			</div>
		</div>
		<div class="column is-2 field ">
			<br />
			<button class="button is-dark " id="btnGo">
				<span>Generate</span>
				<span class="icon is-right">
					<i class="fa-regular fa-file-excel"></i>
				</span>
			</button>
		</div>
	</div>
</form>
</div>

</section>
	<footer class="footer">
		T/D: <span class="foot_ts"><?= date("Y-m-d H:i:s") ?></span>
		&nbsp;|&nbsp;
		Rendered in: <span class="foot_ts">{elapsed_time}seconds</span>
		&nbsp;|&nbsp;
		
	</footer>
</body>
</html>