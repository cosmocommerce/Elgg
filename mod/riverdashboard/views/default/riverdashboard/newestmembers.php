<?php

	/**
	 * Elgg thewire view page
	 * 
	 * @package ElggTheWire
	 * @license http://www.gnu.org/licenses/old-licenses/gpl-2.0.html GNU Public License version 2
	 * @author Curverider <info@elgg.com>
	 * @copyright Curverider Ltd 2008-2009
	 * @link http://elgg.com/
	 * 
	 */

	$newest_members = get_entities_from_metadata('icontime', '', 'user', '', 0, 20);
	
?>

<div id="recent_members">
<h3>Recent members</h3>
<?php 
	foreach($newest_members as $mem){
		echo "<div style=\"float:left;\">" . elgg_view("profile/icon",array('entity' => $mem, 'size' => 'tiny')) . "</div>";
	}
?>
</div>