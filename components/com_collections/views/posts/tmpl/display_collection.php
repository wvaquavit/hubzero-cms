<?php
/**
 * HUBzero CMS
 *
 * Copyright 2005-2011 Purdue University. All rights reserved.
 *
 * This file is part of: The HUBzero(R) Platform for Scientific Collaboration
 *
 * The HUBzero(R) Platform for Scientific Collaboration (HUBzero) is free
 * software: you can redistribute it and/or modify it under the terms of
 * the GNU Lesser General Public License as published by the Free Software
 * Foundation, either version 3 of the License, or (at your option) any
 * later version.
 *
 * HUBzero is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU Lesser General Public License for more details.
 *
 * You should have received a copy of the GNU Lesser General Public License
 * along with this program.  If not, see <http://www.gnu.org/licenses/>.
 *
 * HUBzero is a registered trademark of Purdue University.
 *
 * @package   hubzero-cms
 * @author    Shawn Rice <zooley@purdue.edu>
 * @copyright Copyright 2005-2011 Purdue University. All rights reserved.
 * @license   http://www.gnu.org/licenses/lgpl-3.0.html LGPLv3
 */

// Check to ensure this file is included in Joomla!
defined('_JEXEC') or die('Restricted access');

if (is_a($this->row, 'CollectionsModelCollection'))
{
	$collection = $this->row;
	$content = $collection->get('description'); 
}
else
{
	$collection = CollectionsModelCollection::getInstance($this->row->item()->get('object_id'));
	$content = ($this->row->get('description')) ? $this->row->get('description') : $collection->get('description'); 
}

switch ($collection->get('object_type'))
{
	case 'member':
		$url = 'index.php?option=com_members&id=' . $collection->get('object_id') . '&active=collections&task=' . $collection->get('alias');
	break;

	case 'group':
		ximport('Hubzero_Group');
		$group = new Hubzero_Group();
		$group->read($collection->get('object_id'));
		$url = 'index.php?option=com_groups&cn=' . $group->get('cn') . '&active=collections&scope=' . $collection->get('alias');
	break;
	
	default:
		$url = 'index.php?option=com_collections&task=all&id=' . $collection->get('id');
	break;
}
?>
		<h4<?php if ($collection->get('access', 0) == 4) { echo ' class="private"'; } ?>>
			<a href="<?php echo JRoute::_($url); ?>">
				<?php echo $this->escape(stripslashes($collection->get('title'))); ?>
			</a>
		</h4>
		<div class="description">
			<?php echo $this->parser->parse(stripslashes($content), $this->wikiconfig, false); ?>
		</div>
		<?php /* <table summary="Board content counts">
			<tbody>
				<tr>
					<td>
						<strong><?php echo $collection->count('file'); ?></strong> <span class="post-type file"><?php echo JText::_('COM_COLLECTIONS_POST_TYPE_FILES'); ?></span>
					</td>
					<td>
						<strong><?php echo $collection->count('collection'); ?></strong> <span class="post-type collection"><?php echo JText::_('COM_COLLECTIONS_POST_TYPE_COLLECTIONS'); ?></span>
					</td>
					<td>
						<strong><?php echo $collection->count('link'); ?></strong> <span class="post-type link"><?php echo JText::_('COM_COLLECTIONS_POST_TYPE_LINKS'); ?></span>
					</td>
				</tr>
			</tbody>
		</table> */ ?>