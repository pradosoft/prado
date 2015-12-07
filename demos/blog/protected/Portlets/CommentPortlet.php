<?php
/**
 * CommentPortlet class file
 *
 * @author Qiang Xue <qiang.xue@gmail.com>
 * @link https://github.com/pradosoft/prado
 * @copyright Copyright &copy; 2006-2015 The PRADO Group
 * @license https://github.com/pradosoft/prado/blob/master/COPYRIGHT
 */

Prado::using('Application.Portlets.Portlet');

/**
 * CommentPortlet class
 *
 * @author Qiang Xue <qiang.xue@gmail.com>
 * @link https://github.com/pradosoft/prado
 * @copyright Copyright &copy; 2006-2015 The PRADO Group
 * @license https://github.com/pradosoft/prado/blob/master/COPYRIGHT
 */
class CommentPortlet extends Portlet
{
	public function onLoad($param)
	{
		parent::onLoad($param);
		$commentLimit=TPropertyValue::ensureInteger($this->Application->Parameters['RecentComments']);
		$comments=$this->Application->getModule('data')->queryComments('','ORDER BY create_time DESC',"LIMIT $commentLimit");
		foreach($comments as $comment)
		{
			$comment->ID=$this->Service->constructUrl('Posts.ViewPost',array('id'=>$comment->PostID)).'#c'.$comment->ID;
			if(strlen($comment->Content)>40)
				$comment->Content=substr($comment->Content,0,40).' ...';
		}
		$this->CommentList->DataSource=$comments;
		$this->CommentList->dataBind();
	}
}

