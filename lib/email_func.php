<?php

/**********************************************************************
	FILENAME:	email_func.php
	PURPOSE:	contains generic functions used for sending an email
	MODIFIED:	06/22/2007 -> descriptions added to functions
**********************************************************************/


/**************************************************************
	FUNCTION:   assembleEmail
	PURPOSE:		legacy wrapper for calling SENDMAIL->assemble
***************************************************************/
function assembleEmail($to,$from,$subject,$message,$attachArray = null,$replyTo=null,$toFile=null,$cc = null, $bcc = null) 
{

	$se = new SENDEMAIL($to,$from,$subject,$message);

	if ($attachArray) $se->setAttach($attachArray);
	if ($replyTo) 		$se->setReplyTo($replyTo);
	if ($cc) 					$se->setCC($cc);
	if ($bcc) 				$se->setBCC($bcc);

	return $se->getContent();

}

/****************************************************************
	FUNCTION:	send_email
	PURPOSE:	legacy wrapper for calling SENDMAIL->send()
****************************************************************/
function send_email($to,$from,$subject,$message,$attachArray=null,$replyTo = null, $cc = null, $bcc = null) 
{

	$se = new SENDEMAIL($to,$from,$subject,$message);

	if ($attachArray) $se->setAttach($attachArray);
	if ($replyTo) 		$se->setReplyTo($replyTo);
	if ($cc) 					$se->setCC($cc);
	if ($bcc) 				$se->setBCC($bcc);

	$se->send();

}
