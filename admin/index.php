<?php
//##copyright##

if (iaView::REQUEST_HTML == $iaView->getRequestType())
{
	$action = isset($iaCore->requestPath[0]) ? $iaCore->requestPath[0] : null;

	$iaBitcoin = $iaCore->factoryPlugin('bitcoin', 'common');

	switch ($action)
	{
		case 'code':
			if (isset($_GET['code']))
			{
				$iaBitcoin->obtainToken($_GET['code']);
			}
			elseif (isset($_GET['error']))
			{
				$iaView->setMessages('Bitcoin: ' . $_GET['error_description'], iaView::ERROR);
			}

			iaUtil::go_to(IA_SELF);

		default:
			$token = $iaBitcoin->getToken(true);

			if ($token)
			{
				$iaView->setMessages('Already successfully authorized.', iaView::SUCCESS);

				$expires = (int)$token['expires_in'];
				$expires = time() - ($iaBitcoin->getTokenTimestamp() + ($expires * 1000));
				$expires = round($expires / 1000 / 60);

				$iaView->assign('expires', $expires);
			}
			else
			{
				$authorizeUrl = $iaBitcoin->getAuthorizeUrl();

				$iaView->assign('authorize_url', $authorizeUrl);
			}

			$iaView->assign('token', $token);

			$iaView->display('index');
	}
}