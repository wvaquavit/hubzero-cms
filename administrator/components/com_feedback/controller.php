<?php
/**
 * @package		HUBzero CMS
 * @author		Alissa Nedossekina <alisa@purdue.edu>
 * @copyright	Copyright 2005-2009 by Purdue Research Foundation, West Lafayette, IN 47906
 * @license		http://www.gnu.org/licenses/gpl-2.0.html GPLv2
 *
 * Copyright 2005-2009 by Purdue Research Foundation, West Lafayette, IN 47906.
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

ximport('Hubzero_Controller');

class FeedbackController extends Hubzero_Controller
{
	public function execute()
	{
		// Load the component config
		$config =& JComponentHelper::getParams( $this->_option );
		$this->config = $config;
		
		$this->_task = strtolower(JRequest::getVar('task', '','request'));
		$this->type = JRequest::getVar( 'type', '', 'post' );
		if (!$this->type) {
			$this->type = JRequest::getVar( 'type', 'regular', 'get' );
		}
		
		switch ($this->_task) 
		{
			case 'new':       $this->edit();      break;
			case 'add':       $this->edit();      break;
			case 'edit':      $this->edit();      break;
			case 'save':      $this->save();      break;
			case 'remove':    $this->remove();    break;
			case 'cancel':    $this->cancel();    break;
			case 'upload':    $this->upload();    break;
			case 'img':       $this->img();       break;
			case 'deleteimg': $this->deleteimg(); break;
	
			default: $this->quotes(); break;
		}
	}

	//----------------------------------------------------------
	// Views
	//----------------------------------------------------------

	protected function quotes()
	{
		// Instantiate a new view
		$view = new JView( array('name'=>'quotes') );
		$view->option = $this->_option;
		$view->task = $this->_task;
		$view->type = $this->type;
		
		// Get site configuration
		$app =& JFactory::getApplication();
		$config = JFactory::getConfig();

		// Incoming
		$view->filters = array();
		$view->filters['search'] = urldecode(JRequest::getString('search'));
		$view->filters['sortby'] = JRequest::getVar( 'sortby', 'date' );
		$view->filters['start']  = JRequest::getInt('limitstart', 0);
		$view->filters['limit']  = $app->getUserStateFromRequest($this->_option.'.limit', 'limit', $config->getValue('config.list_limit'), 'int');

		if ($this->type == 'regular') {
			$obj = new FeedbackQuotes( $this->database );
		} else {
			$obj = new SelectedQuotes( $this->database );
		}
		
		// Get a record count
		$view->total = $obj->getCount( $view->filters );
		
		// Get records
		$view->rows = $obj->getResults( $view->filters );

		// Initiate paging class
		jimport('joomla.html.pagination');
		$view->pageNav = new JPagination( $view->total, $view->filters['start'], $view->filters['limit'] );

		// Set any errors
		if ($this->getError()) {
			$view->setError( $this->getError() );
		}
		
		// Output the HTML
		$view->display();
	}

	//-----------

	protected function create()
	{
		// Instantiate a new view
		$view = new JView( array('name'=>'create') );
		$view->option = $this->_option;
		$view->task = $this->_task;
		$view->type = $this->type;
		
		// Set any errors
		if ($this->getError()) {
			$view->setError( $this->getError() );
		}
		
		// Output the HTML
		$view->display();
	}

	//-----------

	protected function edit() 
	{
		// Instantiate a new view
		$view = new JView( array('name'=>'quote') );
		$view->option = $this->_option;
		$view->task = $this->_task;
		$view->type = $this->type;

		// Incoming ID
		$id = JRequest::getInt( 'id', 0 );

		// Initiate database class and load info
		if ($this->type == 'regular') {
			$view->row = new FeedbackQuotes( $this->database );
		} else {
			$view->row = new SelectedQuotes( $this->database );
		}
		$view->row->load( $id );

		$username = trim(JRequest::getVar( 'username', '' ));
		if ($username) {
			ximport('Hubzero_User_Profile');
			
			$profile = new Hubzero_User_Profile();
			$profile->load( $username );

			$view->row->fullname = $profile->get('name');
			$view->row->org      = $profile->get('organization');
			$view->row->userid   = $profile->get('uidNumber');
		}
		
		if (!$id) {
			$view->row->date = date( 'Y-m-d H:i:s');
		}
		
		if ($this->type == 'regular') {
			$view->row->notable_quotes = 0;
			$view->row->flash_rotation = 0;
		}

		// Set any errors
		if ($this->getError()) {
			$view->setError( $this->getError() );
		}
		
		// Output the HTML
		$view->display();
	}

	//----------------------------------------------------------
	// Processors
	//----------------------------------------------------------
	
	protected function save() 
	{
		// Check for request forgeries
		JRequest::checkToken() or jexit( 'Invalid Token' );
		
		// Incoming
		$replacequote   = JRequest::getInt( 'replacequote', 0 );
		$notable_quotes = JRequest::getInt( 'notable_quotes', 0 );
		$flash_rotation = JRequest::getInt( 'flash_rotation', 0 );
	
		if ($replacequote) {
			// Replace original quote

			// Initiate class and bind posted items to database fields
			$row = new FeedbackQuotes( $this->database );
			if (!$row->bind($_POST)) {
				JError::raiseError( 500, $row->getError() );
				return;
			}
	
			// Code cleaner for xhtml transitional compliance
			$row->quote = str_replace( '<br>', '<br />', $row->quote );
	
			$row->picture = basename($bits);
	
			// Check new content
			if (!$row->check()) {
				JError::raiseError( 500, $row->getError() );
				return;
			}
			
			// Store new content
			if (!$row->store()) {
				JError::raiseError( 500, $row->getError() );
				return;
			}
	
			$this->_message = JText::sprintf('FEEDBACK_QUOTE_SAVED',  $row->fullname);
		}

		if ($this->type == 'selected' || $notable_quotes || $flash_rotation) {
			// Initiate class and bind posted items to database fields
			$rowselected = new SelectedQuotes( $this->database );
			if (!$rowselected->bind($_POST)) {
				JError::raiseError( 500, $rowselected->getError() );
				return;
			}
			
			$rowselected->notable_quotes = $notable_quotes;
			$rowselected->flash_rotation = $flash_rotation;
			
			// Use new id if already exists under selected quotes
			if ($this->type == 'regular') {
				$rowselected->id = 0;
			}

			// Code cleaner for xhtml transitional compliance
			$rowselected->quote = str_replace( '<br>', '<br />', $rowselected->quote );
			
			$rowselected->picture = basename($rowselected->picture);
			
			// Trim the text to create a short quote
			$rowselected->short_quote = ($rowselected->short_quote) ? $rowselected->short_quote : substr($rowselected->quote, 0, 270);
			if (strlen($rowselected->short_quote)>=271) {
				$rowselected->short_quote .= '...';
			}
			
			// Trim the text to create a mini quote
			$rowselected->miniquote = ($rowselected->miniquote) ? $rowselected->miniquote : substr($rowselected->short_quote, 0, 150);
			if (strlen($rowselected->miniquote)>=147) {
				$rowselected->miniquote .= '...';
			}
			
			// Store new content
			if (!$rowselected->store()) {
				JError::raiseError( 500, $rowselected->getError() );
				return;
			}

			$this->_message = '';
		}
	
		if ($flash_rotation) {
			$this->_message .= JText::_('FEEDBACK_QUOTE_SELECTED_FOR_ROTATION');
		}
		if ($notable_quotes) {
			$this->_message .= JText::_('FEEDBACK_QUOTE_SELECTED_FOR_QUOTES');
		}
		
		$this->_redirect = 'index.php?option='.$this->_option.'&type='.$this->type;
	}
	
	//-----------

	protected function remove() 
	{
		// Check for request forgeries
		JRequest::checkToken() or jexit( 'Invalid Token' );
		
		// Incoming
		$id = JRequest::getInt( 'id', 0 );
	
		// Check for an ID
		if (!$id) {
			JError::raiseError( 500, JText::_('FEEDBACK_SELECT_QUOTE_TO_DELETE') );
			return;
		}

		// Load the quote
		if ($this->type == 'regular') {
			$row = new FeedbackQuotes( $this->database );
		} else {
			$row = new SelectedQuotes( $this->database );
		}
		$row->load( $id );
		
		// Delete associated files
		$row->deletePicture( $this->config );
		
		// Delete the quote
		$row->delete();
		
		// Output messsage and redirect
		$this->_redirect = 'index.php?option='.$this->_option.'&type='.$type;
		$this->_message = JText::_('FEEDBACK_REMOVED');
	}

	//-----------

	protected function cancel()
	{
		$this->_redirect = 'index.php?option='.$this->_option.'&type='.$this->type;
	}

	//----------------------------------------------------------
	//  Image handling
	//----------------------------------------------------------

	public function upload()
	{
		// Check for request forgeries
		JRequest::checkToken() or jexit( 'Invalid Token' );
		
		// Load the component config
		$config = $this->config;

		// Incoming
		$id = JRequest::getInt( 'id', 0 );
		if (!$id) {
			$this->setError( JText::_('FEEDBACK_NO_ID') );
			$this->img( '', $id );
			return;
		}
		
		// Incoming file
		$file = JRequest::getVar( 'upload', '', 'files', 'array' );
		if (!$file['name']) {
			$this->setError( JText::_('FEEDBACK_NO_FILE') );
			$this->img( '', $id );
			return;
		}
		
		// Build upload path
		ximport('Hubzero_View_Helper_Html');
		$dir  = Hubzero_View_Helper_Html::niceidformat( $id );
		$path = JPATH_ROOT;
		if (substr($config->get('uploadpath'), 0, 1) != DS) {
			$path .= DS;
		}
		if (substr($config->get('uploadpath'), -1, 1) == DS) {
			$path = substr($config->get('uploadpath'), 0, (strlen($config->get('uploadpath')) - 1));
		}
		$path .= $config->get('uploadpath').DS.$dir;
		
		if (!is_dir( $path )) {
			jimport('joomla.filesystem.folder');
			if (!JFolder::create( $path, 0777 )) {
				$this->setError( JText::_('UNABLE_TO_CREATE_UPLOAD_PATH') );
				$this->img( '', $id );
				return;
			}
		}

		// Make the filename safe
		jimport('joomla.filesystem.file');
		$file['name'] = JFile::makeSafe($file['name']);
		$file['name'] = str_replace(' ','_',$file['name']);
		
		$qid = JRequest::getInt( 'qid', 0 );
		
		// Perform the upload
		if (!JFile::upload($file['tmp_name'], $path.DS.$file['name'])) {
			$this->setError( JText::_('ERROR_UPLOADING') );
			$file = $curfile;
		} else {
			$row = new FeedbackQuotes( $this->database );
			$row->load( $qid );
			
			// Do we have an old file we're replacing?
			//$curfile = JRequest::getVar( 'currentfile', '' );
			$curfile = $row->picture;
			
			if ($curfile != '' && $curfile != $file['name']) {
				// Yes - remove it
				if (file_exists($path.DS.$curfile)) {
					if (!JFile::delete($path.DS.$curfile)) {
						$this->setError( JText::_('UNABLE_TO_DELETE_FILE') );
						$this->img( $file['name'], $id );
						return;
					}
				}
			}

			$file = $file['name'];
			
			$row->picture = $file;
			if (!$row->store()) {
				$this->setError( $row->getError() );
			}
		}

		// Push through to the image view
		$this->img( $file, $id, $qid );
	}

	//-----------

	protected function deleteimg()
	{
		// Check for request forgeries
		JRequest::checkToken('get') or jexit( 'Invalid Token' );
		
		// Load the component config
		$config = $this->config;
		
		// Incoming member ID
		$id = JRequest::getInt( 'id', 0 );
		if (!$id) {
			$this->setError( JText::_('FEEDBACK_NO_ID') );
			$this->img( '', $id );
		}
		
		$qid = JRequest::getInt( 'qid', 0 );

		$row = new FeedbackQuotes( $this->database );
		$row->load( $qid );

		// Incoming file
		//$file = JRequest::getVar( 'file', '' );
		if (!$row->picture) {
			$this->setError( JText::_('FEEDBACK_NO_FILE') );
			$this->img( '', $id );
			return;
		}
		
		// Build the file path
		ximport('Hubzero_View_Helper_Html');
		$dir  = Hubzero_View_Helper_Html::niceidformat( $id );
		$path = JPATH_ROOT;
		if (substr($config->get('uploadpath'), 0, 1) != DS) {
			$path .= DS;
		}
		if (substr($config->get('uploadpath'), -1, 1) == DS) {
			$path = substr($config->get('uploadpath'), 0, (strlen($config->get('uploadpath')) - 1));
		}
		$path .= $config->get('uploadpath').DS.$dir;

		if (!file_exists($path.DS.$row->picture) or !$row->picture) { 
			$this->setError( JText::_('FILE_NOT_FOUND') ); 
		} else {
			// Attempt to delete the file
			jimport('joomla.filesystem.file');
			if (!JFile::delete($path.DS.$row->picture)) {
				$this->setError( JText::_('UNABLE_TO_DELETE_FILE') );
				$this->img( $file, $id );
			}

			$row->picture = '';
			if (!$row->store()) {
				$this->setError( $row->getError() );
			}
		}
	
		// Push through to the image view
		$this->img( $row->picture, $id, $qid );
	}

	//-----------

	protected function img( $file='', $id=0, $qid=0 )
	{
		// Instantiate a new view
		$view = new JView( array('name'=>'quote', 'layout'=>'image') );
		$view->option = $this->_option;
		$view->task = $this->_task;
		$view->type = $this->type;
		
		// Load the component config
		$view->config = $this->config;
		
		// Do have an ID or do we need to get one?
		if (!$id) {
			$view->id = JRequest::getInt( 'id', 0 );
		} else {
			$view->id = $id;
		}
		
		ximport('Hubzero_View_Helper_Html');
		$view->dir = Hubzero_View_Helper_Html::niceidformat( $id );
		
		// Do we have a file or do we need to get one?
		if (!$file) {
			$view->file = JRequest::getVar( 'file', '' );
		} else {
			$view->file = $file;
		}
			  
		// Build the directory path
		$view->path = $this->config->get('uploadpath').DS.$view->dir;

		if (!$qid) {
			$view->qid = JRequest::getInt( 'qid', 0 );
		} else {
			$view->qid = $qid;
		}
		
		// Set any errors
		if ($this->getError()) {
			$view->setError( $this->getError() );
		}
		
		// Output the HTML
		$view->display();
	}
}
