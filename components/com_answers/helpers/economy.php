<?php
/**
 * @package		HUBzero CMS
 * @author		Shawn Rice <zooley@purdue.edu>
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

//----------------------------------------------------------
// Answers Economy class:
// Stores economy funtions for com_answers
//----------------------------------------------------------

ximport('Hubzero_Bank');

class AnswersEconomy extends JObject
{
	var $_db = NULL;  // Database
	
	//-----------
	
	public function __construct( &$db)
	{
		$this->_db = $db;
	}
	
	//-----------
	
	public function getQuestions() 
	{
		// get all closed questions
		$sql = "SELECT q.id, q.created_by AS q_owner, a.created_by AS a_owner
				FROM #__answers_questions AS q LEFT JOIN #__answers_responses AS a ON q.id=a.qid AND a.state=1
				WHERE q.state=1";
		$this->_db->setQuery( $sql );
		return $this->_db->loadObjectList();
	}
	
	//-----------
	
	public function calculate_marketvalue($id, $type='regular')
	{
		if ($id === NULL) {
			$id = $this->qid;
		}
		if ($id === NULL) {
			return false;
		}
		
		require_once( JPATH_ROOT.DS.'administrator'.DS.'components'.DS.'com_answers'.DS.'tables'.DS.'question.php' );
		require_once( JPATH_ROOT.DS.'administrator'.DS.'components'.DS.'com_answers'.DS.'tables'.DS.'response.php' );
		require_once( JPATH_ROOT.DS.'administrator'.DS.'components'.DS.'com_answers'.DS.'tables'.DS.'log.php' );
		require_once( JPATH_ROOT.DS.'administrator'.DS.'components'.DS.'com_answers'.DS.'tables'.DS.'questionslog.php' );
		
		// Get point values for actions
		$BC = new Hubzero_Bank_Config( $this->_db );
		$p_Q  = $BC->get('ask');
		$p_A  = $BC->get('answer');
		$p_R  = $BC->get('answervote');
		$p_RQ = $BC->get('questionvote');
		$p_A_accepted = $BC->get('accepted');
		
		$calc = 0;
		
		// Get actons and sum up
		$ar = new AnswersResponse( $this->_db );
		$result = $ar->getActions( $id );
		
		if ($type != 'royalty') {
			$calc += $p_Q;  // ! this is different from version before code migration !			
			$calc += (count($result))*$p_A;
		}
		
		// Calculate as if there is at leat one answer
		if ($type == 'maxaward' && count($result)==0) {
			$calc += $p_A;
		}
		
		for ($i=0, $n=count($result); $i < $n; $i++) 
		{
			$calc += ($result[$i]->helpful)*$p_R;
			$calc += ($result[$i]->nothelpful)*$p_R;
			if ($result[$i]->state == 1 && $type != 'royalty') {
				$accepted = 1;
			}
		}
		
		if (isset($accepted) or $type=='maxaward') {
			$calc += $p_A_accepted;
		}
		
		// Add question votes
		$aq = new AnswersQuestion( $this->_db );
		$aq->load( $id );
		if ($aq->state != 2) {
			$calc += $aq->helpful * $p_RQ;
		}
		
		($calc) ? $calc = $calc : $calc ='0';
		
		return $calc;
	}

	//-----------
	
	public function distribute_points($qid, $Q_owner, $BA_owner, $type)
	{
		$juser =& JFactory::getUser();
		
		if ($qid === NULL) {
			$qid = $this->qid;
		}
		$cat = 'answers';
		require_once( JPATH_ROOT.DS.'administrator'.DS.'components'.DS.'com_answers'.DS.'tables'.DS.'question.php' );
		require_once( JPATH_ROOT.DS.'administrator'.DS.'components'.DS.'com_answers'.DS.'tables'.DS.'response.php' );
		require_once( JPATH_ROOT.DS.'administrator'.DS.'components'.DS.'com_answers'.DS.'tables'.DS.'log.php' );
		require_once( JPATH_ROOT.DS.'administrator'.DS.'components'.DS.'com_answers'.DS.'tables'.DS.'questionslog.php' );	
		
		$points = $this->calculate_marketvalue($qid, $type);
		
		$BT = new Hubzero_Bank_Transaction( $this->_db );
		$reward = $BT->getAmount( $cat, 'hold', $qid );
		$reward = ($reward) ? $reward : '0';		
		$share = $points/3;
		
		$BA_owner_share = $share + $reward;
		$A_owner_share  = 0;
		
		// Get qualifying users
		$juser =& JUser::getInstance( $Q_owner );
		$ba_user =& JUser::getInstance( $BA_owner );	
			
		// Calculate commissions for other answers
		$ar = new AnswersResponse( $this->_db );
		$result = $ar->getActions( $qid );
		
		$n = count($result);
		$eligible = array();
		
		if ($n > 1 ) {
			// More than one answer found
			for ($i=0; $i < $n; $i++) 
			{
				// Check if a regular answer has a good rating (at least 50% of positive votes)
				if (($result[$i]->helpful + $result[$i]->nothelpful) >= 3 
				 && ($result[$i]->helpful >= $result[$i]->nothelpful) 
				 && $result[$i]->state=='0' ) {
					$eligible[] = $result[$i]->created_by;
				}
			}
			if (count($eligible) > 0) {
				// We have eligible answers
				$A_owner_share = $share/$n;
			} else {
				// Best A owner gets remaining thrid
				$BA_owner_share += $share;
			}
		} else {
			// Best A owner gets remaining 3rd
			$BA_owner_share += $share;
		}
			
		// Reward asker
		if (is_object($juser)) {
			$BTL_Q = new Hubzero_Bank_Teller( $this->_db , $juser->get('id') );
			//$BTL_Q->deposit($Q_owner_share, 'Commission for posting a question', $cat, $qid);
			// Separate comission and reward payment
			// Remove credit
			$credit = $BTL_Q->credit_summary();
			$adjusted = $credit - $reward;
			$BTL_Q->credit_adjustment($adjusted);
			
			if (intval($share) > 0) {
				$share_msg = ($type=='royalty') ? 'Royalty payment for posting question #'.$qid : 'Commission for posting question #'.$qid;	
				$BTL_Q->deposit($share, $share_msg, $cat, $qid);
			}
			// withdraw reward amount
			if ($reward) {
				$BTL_Q->withdraw($reward, 'Reward payment for your question #'.$qid, $cat, $qid);
			}
		}
		
		// Reward other responders
		if (count($eligible) > 0) {
			foreach ($eligible as $e) 
			{
				$auser =& JUser::getInstance( $e );
				if (is_object($auser) && is_object($ba_user) && $ba_user->get('id') != $auser->get('id')) {
					$BTL_A = new Hubzero_Bank_Teller( $this->_db , $auser->get('id') );
					if (intval($A_owner_share) > 0) {
						$A_owner_share_msg = ($type=='royalty') ? 'Royalty payment for answering question #'.$qid : 'Answered question #'.$qid.' that was recently closed';
						$BTL_A->deposit($A_owner_share, $A_owner_share_msg , $cat, $qid);
					}	
				}
				// is best answer eligible for extra points?
				if (is_object($auser) && is_object($ba_user) && ($ba_user->get('id') == $auser->get('id'))) {
					$ba_extra = 1;
				}
			}
		}
		
		// Reward best answer
		if (is_object($ba_user)) {
			$BTL_BA = new Hubzero_Bank_Teller( $this->_db , $ba_user->get('id') );
			
			if (isset($ba_extra)) { 
				$BA_owner_share += $A_owner_share; 
			}
			
			if (intval($BA_owner_share) > 0) {
				$BA_owner_share_msg = ($type=='royalty') ? 'Royalty payment for answering question #'.$qid : 'Answer for question #'.$qid.' was accepted';
				$BTL_BA->deposit($BA_owner_share, $BA_owner_share_msg, $cat, $qid);
			}
		}
	
		// Remove hold if exists
		if ($reward) {
			$BT = new Hubzero_Bank_Transaction( $this->_db  );
			$BT->deleteRecords( 'answers', 'hold', $qid );
		}
	}
}
