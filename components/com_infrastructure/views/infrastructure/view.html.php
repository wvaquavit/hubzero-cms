<?php
/**
 * @package		HUBzero CMS
 * @author		Nicholas J. Kisseberth
 * @copyright	Copyright 2008-2009 by Purdue Research Foundation, West Lafayette, IN 47906
 * @license		http://www.gnu.org/licenses/gpl-2.0.html GPLv2
 *
 * Copyright 2008-2009 by Purdue Research Foundation, West Lafayette, IN 47906.
 * All rights reserved.
 *
 * This program is free software; you can redistribute it and/or
 * modify it under the terms of the GNU General Public License,
 * version 2 as published by the Free Software Foundation.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with this program; if not, write to the Free Software
 * Foundation, Inc., 51 Franklin Street, Fifth Floor, Boston, MA  02110-1301, USA.
 */

// Check to ensure this file is included in Joomla!
defined('_JEXEC') or die( 'Restricted access' );

jimport( 'joomla.application.component.view');

/**
 * HTML View class for the Tools Component
 */

class InfrastructureViewInfrastructure extends JView
{
    function display($tpl = null)
    {
    	ximport('Hubzero_Document');

    	$xhub  = & Hubzero_Factory::getHub();
    	$model = & $this->getModel();

	$forgeName = $xhub->getCfg('forgeName');
	$forgeURL = $xhub->getCfg('forgeURL');
	$hubShortName = $xhub->getCfg('hubShortName');
	$hubShortURL = $xhub->getCfg('hubShortURL');
	$hubLongURL = $xhub->getCfg('hubLongURL');
        $appTools = $model->getInfrastructureProjects();
	$image = Hubzero_Document::getComponentImage('com_projects', 'forge.png', 1);
        $this->assignRef( 'forgeName', $forgeName );
	$this->assignRef( 'forgeURL', $forgeURL);
	$this->assignRef( 'hubShortURL', $hubShortURL );
	$this->assignRef( 'hubLongURL', $hubLongURL );
	$this->assignRef( 'hubShortName', $hubShortName );
	$this->assignRef( 'appTools', $appTools);
	$this->assignRef( 'image', $image);
        parent::display($tpl);
    }
}
