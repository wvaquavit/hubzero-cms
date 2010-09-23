<?php
// No direct access
defined('_JEXEC') or die( 'Restricted access' );

JToolBarHelper::title( '<a href="index.php?option=com_services">'.JText::_( 'Services &amp; Subscriptions Manager' ).'</a>: <small><small>[ Subscriptions ]</small></small>', 'addedit.png' );
JToolBarHelper::preferences('com_services', '550');
JToolBarHelper::spacer();

$now = date( 'Y-m-d H:i:s', time() );
?>
<script type="text/javascript">
function submitbutton(pressbutton) 
{
	var form = document.adminForm;
	if (pressbutton == 'cancel') {
		submitform( pressbutton );
		return;
	}
	// do field validation
	submitform( pressbutton );
}
</script>

<h3><?php echo JText::_('Subscriptions'); ?></h3>
<form action="index.php" method="post" name="adminForm">
	<fieldset id="filter">
		<?php echo $this->total; ?> <?php echo JText::_('total subscriptions'); ?>.
		<label>
			<?php echo JText::_('Filter by'); ?>:
			<select name="filterby" onchange="document.adminForm.submit( );">
				<option value="pending"<?php if ($this->filters['filterby'] == 'pending') { echo ' selected="selected"'; } ?>><?php echo JText::_('Pending'); ?> <?php echo ucfirst(JText::_('Subscriptions')); ?></option>
				<option value="active"<?php if ($this->filters['filterby'] == 'processed') { echo ' selected="selected"'; } ?>><?php echo JText::_('Active'); ?> <?php echo ucfirst(JText::_('Subscriptions')); ?></option>
				<option value="cancelled"<?php if ($this->filters['filterby'] == 'cancelled') { echo ' selected="selected"'; } ?>><?php echo JText::_('Cancelled'); ?> <?php echo ucfirst(JText::_('Subscriptions')); ?></option>
				<option value="all"<?php if ($this->filters['filterby'] == 'all') { echo ' selected="selected"'; } ?>><?php echo JText::_('ALL'); ?> <?php echo ucfirst(JText::_('Subscriptions')); ?></option>
			</select>
		</label>
		
		<label>
			<?php echo JText::_('Sort by'); ?>:
			<select name="sortby" onchange="document.adminForm.submit( );">
				<option value="date"<?php if ($this->filters['sortby'] == 'date') { echo ' selected="selected"'; } ?>><?php echo JText::_('Date Added'); ?></option>
				<option value="date_updated"<?php if ($this->filters['sortby'] == 'date_updated') { echo ' selected="selected"'; } ?>><?php echo JText::_('Last Updated'); ?></option>
				<option value="date_expires"<?php if ($this->filters['sortby'] == 'date_expires') { echo ' selected="selected"'; } ?>><?php echo JText::_('Soon to Expire'); ?></option>
				<option value="pending"<?php if ($this->filters['sortby'] == 'pending') { echo ' selected="selected"'; } ?>><?php echo ucfirst(JText::_('Pending Admin Action')); ?></option>	
				<option value="status"<?php if ($this->filters['sortby'] == 'status') { echo ' selected="selected"'; } ?>><?php echo ucfirst(JText::_('Status')); ?></option>					
			</select>
		</label> 
	</fieldset>
	
	<table class="adminlist">
		<thead>
			<tr>
				<th><?php echo JText::_('ID -- Code'); ?></th>
				<th><?php echo JText::_('Status'); ?></th>
				<th><?php echo JText::_('Service'); ?></th>
				<th><?php echo JText::_('Pending Payment / Units'); ?></th>
				<th><?php echo JText::_('User'); ?></th>
				<th><?php echo JText::_('Added'); ?></th>
				<th><?php echo JText::_('Last Updated'); ?></th>
				<th><?php echo JText::_('Expires'); ?></th>
				<th></th>
			</tr>
		</thead>
		<tbody>
<?php
$k = 0;
for ($i=0, $n=count( $this->rows ); $i < $n; $i++) 
{
	$row = &$this->rows[$i];
		
	$name = JText::_('UNKNOWN');
	$login = JText::_('UNKNOWN');
	$ruser =& JUser::getInstance($row->uid);
	if (is_object($ruser)) {
		$name = $ruser->get('name');
		$login = $ruser->get('username');
	}
	
	$status='';
	$pending = $row->currency.' '.$row->pendingpayment.' - '.JText::_('for').' '.$row->pendingunits.' '.JText::_('units(s)');
	
	$expires = (intval($row->expires) <> 0) ? JHTML::_('date', $row->expires, '%d %b, %Y') : 'N/A';
		
	switch ($row->status) 
	{
		case '1':
			$status = ($row->expires > $now) ? '<span style="color:#197f11;">'.strtolower(JText::_('Active')).'</span>' : '<span style="color:#ef721e;">'.strtolower(JText::_('Expired')).'</span>';
			break;
		case '0':
			$status = '<span style="color:#ff0000;">'.strtolower(JText::_('Pending')).'</span>';
			break;
		case '2':
			$status = '<span style="color:#999;">'.strtolower(JText::_('Cancelled')).'</span>';
			$pending .= $row->pendingpayment ? ' ('.JText::_('refund').')' : '';
			break;
	}
?>
			<tr class="<?php echo "row$k"; ?>">
				<td><a href="index.php?option=<?php echo $this->option ?>&amp;task=subscription&amp;id=<?php echo $row->id; ?>" title="<?php echo JText::_('View Subscription Details'); ?>"><?php echo $row->id,' -- '.$row->code; ?></a></td>
				<td><?php echo $status;  ?></td>
				<td><?php echo $row->category.' -- '.$row->title; ?></td>
				<td><?php echo $row->pendingpayment &&  ($row->pendingpayment > 0 or $row->pendingunits > 0)  ? '<span style="color:#ff0000;">'.$pending.'</span>' : $pending;  ?></td>
				<td><?php echo $name.' ('.$login.')';  ?></td>
				<td><?php echo JHTML::_('date', $row->added, '%d %b, %Y'); ?></td>	   
				<td><?php echo JHTML::_('date', $row->updated, '%d %b, %Y'); ?></td>
				<td><?php echo $expires; ?></td>
				<td><a href="index.php?option=<?php echo $this->option ?>&amp;task=subscription&amp;id=<?php echo $row->id; ?>" title="<?php echo JText::_('View Subscription Details'); ?>"><?php echo JText::_('DETAILS'); ?></a></td>
			</tr>
<?php
	$k = 1 - $k;
}
?>
		</tbody>
	</table>

	<?php echo $this->pageNav->getListFooter(); ?>

	<input type="hidden" name="option" value="<?php echo $this->option ?>" />
	<input type="hidden" name="task" value="" />
	<input type="hidden" name="boxchecked" value="0" />
	<?php echo JHTML::_( 'form.token' ); ?>
</form>