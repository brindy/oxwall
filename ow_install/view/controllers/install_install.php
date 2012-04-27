<h1>Finalizing install</h1>
<?php echo install_tpl_feedback(); ?>

<?php
if ( $_assign_vars['dirs'] )
{
?>
<div class="feedback_msg error">
	&bull; You need to make these folders writable: (<a target="_blank" href="http://docs.oxwall.org/install:index#writable-folders"><b>?</b></a>)
</div>
<ul class="directories">
    <?php foreach ($_assign_vars['dirs'] as $dir) { ?>
	    <li><?php echo $dir; ?></lu>
	<?php } ?>
</div>
<hr />
<?php
}
?>

<p>&bull; Please copy and paste this code replacing the existing one into <b>ow_includes/config.php</b> file.<br />Make sure you do not have any whitespace before and after the code.</p>

<textarea rows="5" class="config" onclick="this.select();"><?php echo $_assign_vars['configContent']; ?></textarea>

<p>&bull; Create a cron job that runs <b>ow_cron/run.php</b> once a minute. (<a target="_blank" href="http://docs.oxwall.org/install:index#cron"><b>?</b></a>)</p>

<form method="post">
    <p align="center"><input type="submit" value="Continue" name="continue" /></p>
</form>