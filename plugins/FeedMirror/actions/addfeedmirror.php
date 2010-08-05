<?php
/**
 * StatusNet - the distributed open-source microblogging tool
 * Copyright (C) 2010, StatusNet, Inc.
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
 *
 * PHP version 5
 *
 * @category  Action
 * @package   StatusNet
 * @author    Brion Vibber <brion@status.net>
 * @copyright 2010 StatusNet, Inc.
 * @license   http://www.fsf.org/licensing/licenses/agpl-3.0.html AGPLv3
 * @link      http://status.net/
 */

if (!defined('STATUSNET')) {
    exit(1);
}

/**
 * Takes parameters:
 *
 *    - feed: a profile ID
 *    - token: session token to prevent CSRF attacks
 *    - ajax: boolean; whether to return Ajax or full-browser results
 *
 * Only works if the current user is logged in.
 *
 * @category  Action
 * @package   StatusNet
 * @copyright 2010 StatusNet, Inc.
 * @license   http://www.fsf.org/licensing/licenses/agpl-3.0.html AGPLv3
 * @link      http://status.net/
 */

class AddFeedMirrorAction extends Action
{
    var $user;
    var $feedurl;

    /**
     * Check pre-requisites and instantiate attributes
     *
     * @param Array $args array of arguments (URL, GET, POST)
     *
     * @return boolean success flag
     */

    function prepare($args)
    {
        parent::prepare($args);
        $ok = $this->sharedBoilerplate();
        if ($ok) {
            // and now do something useful!
            $this->feedurl = $this->validateFeedUrl($this->trimmed('url'));
            return true;
        } else {
            return $ok;
        }
    }

    function validateFeedUrl($url)
    {
        common_log(LOG_DEBUG, __METHOD__ . ': ' . $url);
        if (!common_valid_http_url($url)) {
            // TRANS: Error when user has given an invalid feed URL
            // attempting to set up feed mirroring.
            $this->clientError(_m("Invalid URL."));
        }

        try {
            $discover = new FeedDiscovery();
            return $discover->discoverFromURL($url);
        } catch (FeedSubNoFeedException $e) {
            // TRANS: Error message returned to user when setting up feed mirroring, but we've been given an HTML page without autodiscoverable links.
            $this->clientError(_m("Could not find an RSS or Atom feed on the page. If there's a feed link, try copy-pasting it directly here."));
        } catch (Exception $e) {
            common_log(LOG_ERROR, __METHOD__ . ": " .
                "error in feed discovery for " .
                $url .
                " - " .
                get_class($e) .
                ": " .
                $e->getMessage());
            // TRANS: Error message returned to user when setting up feed mirroring, but we were unable to resolve the given URL to a working feed.
            $this->clientError(_m("Could not reach the feed; the server may be down or the URL may be misspelled."));
        }
    }

    /**
     * @fixme none of this belongs in end classes
     * this stuff belongs in shared code!
     */
    function sharedBoilerplate()
    {
        // Only allow POST requests

        if ($_SERVER['REQUEST_METHOD'] != 'POST') {
            $this->clientError(_('This action only accepts POST requests.'));
            return false;
        }

        // CSRF protection

        $token = $this->trimmed('token');

        if (!$token || $token != common_session_token()) {
            $this->clientError(_('There was a problem with your session token.'.
                                 ' Try again, please.'));
            return false;
        }

        // Only for logged-in users

        $this->user = common_current_user();

        if (empty($this->user)) {
            $this->clientError(_('Not logged in.'));
            return false;
        }
        return true;
    }

    /**
     * Handle request
     *
     * Does the subscription and returns results.
     *
     * @param Array $args unused.
     *
     * @return void
     */

    function handle($args)
    {
        // Throws exception on error
        $this->saveMirror();

        if ($this->boolean('ajax')) {
            $this->startHTML('text/xml;charset=utf-8');
            $this->elementStart('head');
            $this->element('title', null, _('Subscribed'));
            $this->elementEnd('head');
            $this->elementStart('body');
            $unsubscribe = new EditFeedMirrorForm($this, $this->feedurl);
            $unsubscribe->show();
            $this->elementEnd('body');
            $this->elementEnd('html');
        } else {
            $url = common_local_url('feedsettings');
            common_redirect($url, 303);
        }
    }

    function saveMirror()
    {
        // Will throw exception if couldn't set up a feedsub record
        $sub = FeedSub::ensureFeed($this->feedurl);
        
        // Make sure the feed is subscribed...
        if (!$sub->subscribe()) {
            $this->serverError(_m("Could not subscribe to feed."));
        }

        $mirror = new FeedMirror();
        $mirror->profile_id = $this->user->id;
        $mirror->url = $this->feedurl;
        $mirror->insert();
    }
}
