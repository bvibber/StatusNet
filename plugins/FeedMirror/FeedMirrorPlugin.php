<?php
/*
 * StatusNet - the distributed open-source microblogging tool
 * Copyright (C) 2009-2010, StatusNet, Inc.
 *
 * This program is free software: you can redistribute it and/or modify
 * it under the terms of the GNU Affero General Public License as published by
 * the Free Software Foundation, either version 3 of the License, or
 * (at your option) any later version.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU Affero General Public License for more details.
 *
 * You should have received a copy of the GNU Affero General Public License
 * along with this program.  If not, see <http://www.gnu.org/licenses/>.
 */

/**
 * @package OStatusPlugin
 * @maintainer Brion Vibber <brion@status.net>
 */

if (!defined('STATUSNET') && !defined('LACONICA')) { exit(1); }


class FeedMirrorPlugin extends Plugin
{
    /**
     * Hook for RouterInitialized event.
     *
     * @param Net_URL_Mapper $m path-to-action mapper
     * @return boolean hook return
     */
    function onRouterInitialized($m)
    {
        $m->connect('settings/feeds',
                    array('action' => 'feedsettings'));
        $m->connect('settings/feeds/add',
                    array('action' => 'addfeedmirror'));
        $m->connect('settings/feeds/edit',
                    array('action' => 'editfeedmirror'));
        return true;
    }

    /**
     * Automatically load the actions and libraries used by the plugin
     *
     * @param Class $cls the class
     *
     * @return boolean hook return
     *
     */
    function onAutoload($cls)
    {
        $base = dirname(__FILE__);
        $lower = strtolower($cls);
        $files = array("$base/lib/$lower.php",
                       "$base/classes/$cls.php");
        if (substr($lower, -6) == 'action') {
            $files[] = "$base/actions/" . substr($lower, 0, -6) . ".php";
        }
        foreach ($files as $file) {
            if (file_exists($file)) {
                include_once $file;
                return false;
            }
        }
        return true;
    }

    /**
     * Send incoming PuSH feeds in for processing.
     *
     * @param FeedSub $feedsub
     * @param DOMDocument $feed
     * @return mixed hook return code
     */
    function onStartFeedSubReceive($feedsub, $feed)
    {
        $mirrors = new FeedMirror();
        $mirrors->feeduri = $feedsub->uri;
        if ($mirrors->find()) {
            while ($mirrors->fetch()) {
                $mirrors->processFeed($feed);
            }
        }
    }

    function onPluginVersion(&$versions)
    {
        $versions[] = array('name' => 'FeedMirror',
                            'version' => STATUSNET_VERSION,
                            'author' => 'Brion Vibber',
                            'homepage' => 'http://status.net/wiki/Plugin:FeedMirror',
                            'rawdescription' =>
                            _m('Pull feeds into your timeline!'));

        return true;
    }

    /**
     * Menu item for settings
     *
     * @param Action &$action Action being executed
     *
     * @return boolean hook return
     */

    function onEndAccountSettingsNav(&$action)
    {
        $action_name = $action->trimmed('action');

        $action->menuItem(common_local_url('feedsettings'),
                          // TRANS: FeedMirror plugin menu item on user settings page.
                          _m('MENU', 'Feeds'),
                          // TRANS: FeedMirror plugin tooltip for user settings menu item.
                          _m('Configure RSS and Atom feed mirroring'),
                          $action_name === 'feedsettings');

        return true;
    }

    function onCheckSchema()
    {
        $schema = Schema::get();
        $schema->ensureTable('feedmirror', FeedMirror::schemaDef());
        return true;
    }

}
