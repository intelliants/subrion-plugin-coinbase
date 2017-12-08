<?php
/******************************************************************************
 *
 * Subrion - open source content management system
 * Copyright (C) 2017 Intelliants, LLC <https://intelliants.com>
 *
 * This file is part of Subrion.
 *
 * Subrion is free software: you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation, either version 3 of the License, or
 * (at your option) any later version.
 *
 * Subrion is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with Subrion. If not, see <http://www.gnu.org/licenses/>.
 *
 *
 * @link https://subrion.org/
 *
 ******************************************************************************/

if (iaView::REQUEST_HTML == $iaView->getRequestType()) {
    $action = isset($iaCore->requestPath[0]) ? $iaCore->requestPath[0] : null;

    $iaCoinbase = $iaCore->factoryModule('coinbase', 'coinbase', 'common');

    switch ($action) {
        case 'code':
            if (isset($_GET['code'])) {
                $iaCoinbase->obtainToken($_GET['code']);
            } elseif (isset($_GET['error'])) {
                $iaView->setMessages('Coinbase: ' . $_GET['error_description'], iaView::ERROR);
            }

            iaUtil::go_to(IA_SELF);

        default:
            $token = $iaCoinbase->getToken(true);

            if ($token) {
                $iaView->setMessages('Already successfully authorized.', iaView::SUCCESS);

                $expires = (int)$token['expires_in'];
                $expires = time() - ($iaCoinbase->getTokenTimestamp() + ($expires * 1000));
                $expires = round($expires / 1000 / 60);

                $iaView->assign('expires', $expires);
            } else {
                $authorizeUrl = $iaCoinbase->getAuthorizeUrl();
                $iaView->assign('authorize_url', $authorizeUrl);
            }

            $iaView->assign('token', $token);

            $iaView->display('index');
    }
}