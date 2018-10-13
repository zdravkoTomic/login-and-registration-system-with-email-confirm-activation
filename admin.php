<?php include("includes/header.tpl.php"); ?>
<?php include("includes/nav.tpl.php"); ?>


	<div class="jumbotron">
		<h1 class="text-center"><?php if(logged_in()) {
		    echo "logged in";
            } else {
		    redirect('login.php');
            }?></h1>
	</div>

<?php include("includes/footer.tpl.php"); ?>