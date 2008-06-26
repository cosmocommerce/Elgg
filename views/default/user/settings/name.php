<?php
	/**
	 * Provide a way of setting your full name.
	 * 
	 * @package Elgg
	 * @subpackage Core
	 * @license http://www.gnu.org/licenses/old-licenses/gpl-2.0.html GNU Public License version 2
	 * @author Marcus Povey
	 * @copyright Curverider Ltd 2008
	 * @link http://elgg.org/
	 */

	$user = $_SESSION['user'];
	
	if ($user) {
?>
	<h2><?php echo elgg_echo('user:set:name'); ?></h2>
	<form action="<?php echo $vars['url']; ?>action/user/name" method="post">
	<p>
		<?php echo elgg_echo('user:name:label'); ?> : <input type="text" name="name" value="<?php echo $user->name; ?>" />
	</p>
	
	<p>
		<input type="submit" value="<?php

			echo elgg_echo('save');			
		
		?>" />
	</p>
	</form>

<?php } ?>