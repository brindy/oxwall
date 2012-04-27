<?php
function smarty_function_blog_post_url($params, $smarty)
{
	$id = $params['id'];

	return OW::getRouter()->urlForRoute('user-post', array('id'=>$id ));
}
?>